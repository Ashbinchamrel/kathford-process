<?php

namespace App\Services\Planning;

use App\Models\ApprovalChain;
use App\Models\AuditLog;
use App\Models\DepartmentBudget;
use App\Models\FiscalYear;
use App\Models\Planning\Document;
use App\Models\Planning\Item;
use App\Models\Planning\SupportRequest;
use App\Models\Planning\Task;
use App\Models\Setting;
use App\Models\User;
use App\Services\NotificationService;
use App\Support\FiscalYearContext;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class Workflow
{
    public function action(Document $document, User $actor, string $action, int $version, ?string $note = null): Document
    {
        return DB::transaction(function () use ($document, $actor, $action, $version, $note) {
            $d = Document::lockForUpdate()->findOrFail($document->id);
            Access::document($d, $actor);
            abort_unless($actor->is_active, 403);
            $override = [];
            if ((int) $d->version !== $version) {
                $this->fail('This record changed. Reload it before continuing.');
            }
            if ($action === 'submit') {
                abort_unless(Governance::canWrite($actor, $d->type, 'submit'), 403);
                Governance::validate($d);
                if (! in_array($d->status, ['draft', 'returned'])) {
                    $this->fail('Only a draft or returned document can be submitted.');
                }
                if (! $d->items()->exists()) {
                    $this->fail('Add at least one item before submitting.');
                }
                if ($d->parent_id && ! in_array($d->parent?->status, $d->previous_id ? ['approved', 'superseded'] : ['approved'])) {
                    $this->fail('The linked parent must be approved first.');
                }
                if ($d->type === 'cmt_goals') {
                    $d->approval_chain_id = Governance::defaultChain('cmt_goals');
                }
                $chain = ApprovalChain::active()->with(['verifiers', 'approvers'])->find($d->approval_chain_id);
                if (! $chain || $chain->approvers->isEmpty()) {
                    $this->fail('Choose an approval chain with at least one approver.');
                }
                $steps = [];
                foreach (['verifiers' => 'verify', 'approvers' => 'approve'] as $relation => $role) {
                    foreach ($chain->$relation as $member) {
                        if (! $member->is_active) {
                            if (! $actor->isSuperAdmin()) {
                                $this->fail($member->name.' must be active before this chain can be used.');
                            }
                            $override['chain_permission_exceptions'][] = ['user_id' => $member->id, 'action' => $role];
                        }
                        $steps[] = ['user_id' => $member->id, 'name' => $member->name, 'action' => $role];
                    }
                }
                $d->steps = $steps;
                $d->step_index = 0;
                $d->status = $steps[0]['action'] === 'verify' ? 'pending_verification' : 'pending_approval';
            } else {
                $step = $d->step();
                if (! in_array($d->status, ['pending_verification', 'pending_approval']) || ! $step) {
                    $this->fail('This document is not awaiting a decision.');
                }
                abort_unless($actor->isSuperAdmin() || ($step['user_id'] === $actor->id), 403);
                if ($actor->isSuperAdmin() && $step['user_id'] !== $actor->id) {
                    $override = ['assigned_user_id' => $step['user_id'], 'assigned_name' => $step['name'], 'step_action' => $step['action']];
                }
                if (in_array($action, ['return', 'reject'])) {
                    if (blank($note)) {
                        $this->fail('Enter a reason.');
                    }$d->status = $action === 'return' ? 'returned' : 'rejected';
                } elseif ($action === 'accept') {
                    $d->step_index++;
                    $next = $d->step();
                    $d->status = $next ? ($next['action'] === 'verify' ? 'pending_verification' : 'pending_approval') : 'approved';
                    if (! $next) {
                        $d->approved_at = now();
                        $this->publish($d, $actor);
                        if ($d->previous_id) {
                            $previous = Document::lockForUpdate()->findOrFail($d->previous_id);
                            if ($previous->status !== 'approved') {
                                $this->fail('The previous revision is no longer current.');
                            }$previous->update(['status' => 'superseded']);
                        }
                    }
                } else {
                    $this->fail('Invalid decision.');
                }
            }
            $d->version++;
            $d->save();
            if ($override) {
                $override['super_admin_override'] = true;
                $context = isset($override['step_action'])
                    ? 'Super Admin performed the '.$override['step_action'].' step assigned to '.$override['assigned_name'].'.'
                    : 'Super Admin submitted with chain-member permission exceptions for testing.';
                $note = $context.(filled($note) ? "\n".$note : '');
            }
            $d->decisions()->create(['actor_id' => $actor->id, 'decision' => $action, 'note' => $note]);
            AuditLog::record($actor, 'planning.'.$action, $d, $d->title, [], $override);
            $recipient = User::find(in_array($d->status, ['pending_verification', 'pending_approval']) ? ($d->step()['user_id'] ?? null) : $d->created_by);
            if ($recipient) {
                app(NotificationService::class)->send($recipient, 'planning_workflow', 'Planning update', $d->title.' · '.str_replace('_', ' ', $d->status), route('planning.show', $d), false);
            }

            return $d;
        });
    }

    private function publish(Document $d, User $actor): void
    {
        if ($d->type === 'cmt_goals') {
            $ids = $d->items->flatMap(fn ($item) => $item->details['supporting_owner_ids'] ?? [])->unique();
            foreach (User::active()->whereIn('id', $ids)->get() as $supporter) {
                app(NotificationService::class)->send($supporter, 'cmt_support_assignment', 'CMT goals approved', 'You are a supporting owner for '.$d->title, route('planning.show', $d), false);
            }
        }
        if ($d->type === 'plan') {
            if ($d->previous_id) {
                foreach (Document::findOrFail($d->previous_id)->items as $oldItem) {
                    if (! $d->items->contains('source_id', $oldItem->id)) {
                        $this->fail('Keep previously approved activities in amendments to preserve their task and budget links.');
                    }
                }
            }
            foreach ($d->items as $item) {
                if ($d->previous_id && $item->source_id) {
                    $old = Task::where('item_id', $item->source_id)->first();
                    if ($old) {
                        $old->item_id = $item->id;
                        $old->save();
                    }
                }
                $task = Task::firstOrCreate(['item_id' => $item->id], ['department_id' => $d->department_id, 'created_by' => $d->created_by, 'assignee_id' => $item->owner_id ?: $d->created_by, 'title' => $item->title, 'description' => $item->description, 'definition_of_done' => $item->definition_of_done, 'due_on' => $item->due_on]);
                if (in_array($item->frequency, ['weekly', 'monthly'])) {
                    if (! $item->starts_on || ! $item->due_on || $item->due_on < $item->starts_on) {
                        $this->fail('Recurring activities need a start and end date.');
                    }
                    $date = Carbon::parse($item->starts_on);
                    $end = Carbon::parse($item->due_on);
                    $count = 0;
                    while ($date->lte($end)) {
                        if (++$count > 104) {
                            $this->fail('Limit recurring activities to 104 occurrences per plan.');
                        }Task::firstOrCreate(['parent_id' => $task->id, 'due_on' => $date->format('Y-m-d')], ['department_id' => $d->department_id, 'created_by' => $d->created_by, 'assignee_id' => $task->assignee_id, 'title' => $item->title.' · '.$date->format('d M Y'), 'definition_of_done' => $item->definition_of_done]);
                        $item->frequency === 'weekly' ? $date->addWeek() : $date->addMonthNoOverflow();
                    }
                }
                $recipients = array_filter(array_unique(array_merge([$item->support_department_id], $item->details['support_department_ids'] ?? [])));
                foreach ($recipients as $recipient) {
                    SupportRequest::firstOrCreate(['item_id' => $item->id, 'department_id' => $recipient]);
                }
            }
        }
        if ($d->type === 'budget') {
            if ($d->previous_id) {
                $previous = Document::findOrFail($d->previous_id);
                foreach ($previous->items as $oldLine) {
                    if (! $d->items->contains('source_id', $oldLine->source_id)) {
                        $this->fail('Keep existing allocations in an amendment; revise their amounts rather than removing them.');
                    }
                }
            }

            $context = app(FiscalYearContext::class);
            $context->assertWritable();
            $year = FiscalYear::findOrFail($d->fiscal_year_id);
            if ((int) Setting::get('active_fiscal_year_id') !== $year->id) {
                $this->fail('Only the active fiscal year can receive allocations.');
            }
            foreach ($d->items as $item) {
                if (DB::table('planning_budget_publications')->where('item_id', $item->id)->exists()) {
                    continue;
                }
                $source = Item::with('document')->find($item->source_id);
                if (! $source || $source->document->type !== 'plan' || ! in_array($source->document->status, $d->previous_id ? ['approved', 'superseded'] : ['approved']) || $source->document->department_id !== $d->department_id || ! $source->requires_budget) {
                    $this->fail('Every budget line must link to an approved funded activity in this department.');
                }
                // Serialize publications by activity lineage, including amended plans.
                $sourceIds = [$source->id];
                $ancestor = $source;
                while ($ancestor->source_id) {
                    $ancestor = Item::find($ancestor->source_id);
                    if (! $ancestor || in_array($ancestor->id, $sourceIds)) {
                        break;
                    }$sourceIds[] = $ancestor->id;
                }
                DB::table('planning_items')->where('id', end($sourceIds))->lockForUpdate()->first();
                $existing = DB::table('planning_budget_publications as p')->join('planning_items as i', 'i.id', '=', 'p.item_id')->join('planning_documents as d', 'd.id', '=', 'i.document_id')->whereIn('i.source_id', $sourceIds)->where('d.fiscal_year_id', $year->id)->exists();
                if ($existing) {
                    $mapping = DB::table('planning_budget_publications as p')->join('planning_items as i', 'i.id', '=', 'p.item_id')->where('i.document_id', $d->previous_id)->where('i.source_id', $source->id)->select('p.*')->first();
                    if (! $d->previous_id || ! $mapping) {
                        $this->fail('This activity already has an allocation for this fiscal year.');
                    }
                    $budget = DepartmentBudget::lockForUpdate()->findOrFail($mapping->budget_id);
                    if ((float) $item->amount < $budget->reservedAmount()) {
                        $this->fail('The revised allocation cannot be below existing commitments.');
                    }
                    $budget->allocated_amount = $item->amount;
                    $budget->save();
                    DB::table('planning_budget_publications')->where('id', $mapping->id)->update(['item_id' => $item->id, 'updated_at' => now()]);

                    continue;
                }
                if ((float) $item->amount <= 0) {
                    $this->fail('Budget allocations must be greater than zero.');
                }
                $budget = new DepartmentBudget(['department_id' => $d->department_id, 'fiscal_year' => $year->name, 'activity_title' => mb_substr($source->title, 0, 160).' · '.substr($source->id, -8), 'allocated_amount' => $item->amount, 'notes' => 'Approved through Planning: '.$d->title, 'is_active' => true, 'created_by' => $actor->id, 'fiscal_year_id' => $year->id]);
                $budget->fiscal_year_id = $year->id;
                $budget->save();
                DB::table('planning_budget_publications')->insert(['item_id' => $item->id, 'budget_id' => $budget->id, 'created_at' => now(), 'updated_at' => now()]);
            }
        }
    }

    private function fail(string $message): never
    {
        throw ValidationException::withMessages(['planning' => $message]);
    }
}
