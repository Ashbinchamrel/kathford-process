<?php

namespace App\Http\Controllers\Planning;

use App\Http\Controllers\Controller;
use App\Models\ApprovalChain;
use App\Models\AuditLog;
use App\Models\Department;
use App\Models\Planning\Document;
use App\Models\Planning\Item;
use App\Models\Planning\Period;
use App\Models\Planning\SupportRequest;
use App\Models\Planning\Task;
use App\Models\User;
use App\Services\Planning\Access;
use App\Services\Planning\CmtGoals;
use App\Services\Planning\Governance;
use App\Services\Planning\Workflow;
use App\Support\FiscalYearContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PlanningController extends Controller
{
    public function overview(Request $r)
    {
        $levels = Governance::dashboardLevels($r->user());
        if ($levels) {
            $level = $r->input('dashboard', array_key_first($levels));
            abort_unless(is_string($level) && isset($levels[$level]), 403);

            return app(GovernanceController::class)->dashboard($r, $level);
        }
        abort_if($r->filled('dashboard'), 403);
        $cards = [];
        foreach (Access::TYPES as $type => $label) {
            if (! $r->user()->can('planning.'.$type.'_view')) {
                continue;
            }$q = Access::documents(Document::query(), $r->user())->where('type', $type);
            $cards[] = ['label' => $label, 'count' => (clone $q)->count(), 'approved' => (clone $q)->where('status', 'approved')->count(), 'url' => route('planning.index', ['type' => $type])];
        }
        $tasks = Access::tasks(Task::query(), $r->user())->where('assignee_id', $r->user()->id)->where('status', '!=', 'done');

        return view('planning.overview', ['cards' => $cards, 'taskCount' => (clone $tasks)->count(), 'overdue' => (clone $tasks)->where('due_on', '<', today())->count(), 'upcoming' => $tasks->orderBy('due_on')->limit(3)->get()]);
    }

    public function index(Request $r)
    {
        $type = $r->input('type');
        if (! $type) {
            foreach (array_keys(Access::TYPES) as $candidate) {
                if ($r->user()->can('planning.'.$candidate.'_view')) {
                    $type = $candidate;
                    break;
                }
            }if (! $type) {
                return redirect()->route('planning.tasks');
            }
        }abort_unless(isset(Access::TYPES[$type]) && $r->user()->can('planning.'.$type.'_view'), 403);
        $q = Access::documents(Document::with('department', 'period'), $r->user())->where('type', $type);
        if ($r->filled('search')) {
            $q->where('title', 'like', '%'.$r->string('search')->trim().'%');
        }
        if ($r->filled('status')) {
            $q->where('status', $r->status);
        }

        return view('planning.index', ['documents' => $q->latest()->paginate(20)->withQueryString(), 'type' => $type]);
    }

    private function lookups(Request $r): array
    {
        $departments = Department::active()->get()->filter(fn ($d) => Access::department($r->user(), $d->id));

        return ['departments' => $departments, 'supportDepartments' => Department::active()->get(), 'users' => User::active()->orderBy('name')->get(['id', 'name', 'department_id']), 'periods' => Period::orderByDesc('starts_on')->get(), 'chains' => ApprovalChain::active()->get(), 'parents' => Access::documents(Document::with('items'), $r->user())->whereIn('type', array_keys(array_filter(Access::TYPES, fn ($label, $type) => $r->user()->can('planning.'.$type.'_view'), ARRAY_FILTER_USE_BOTH)))->whereIn('status', ['approved', 'superseded'])->get()];
    }

    public function create(Request $r)
    {
        $type = $r->input('type', 'strategy');
        abort_unless(isset(Access::TYPES[$type]) && Governance::canWrite($r->user(), $type), 403);

        if ($type === 'strategy') {
            return app(StrategyController::class)->editor($r, new Document(['type' => 'strategy', 'approval_chain_id' => Governance::defaultChain('strategy')]));
        }

        return view($type === 'cmt_goals' ? 'planning.cmt-edit' : 'planning.edit', $this->lookups($r) + ['document' => new Document(['type' => $type, 'approval_chain_id' => Governance::defaultChain($type)]), 'items' => collect()]);
    }

    public function store(Request $r)
    {
        if ($r->input('type') === 'strategy') {
            return app(StrategyController::class)->save($r, new Document);
        }
        $d = new Document(['created_by' => $r->user()->id]);

        return $r->input('type') === 'cmt_goals' ? DB::transaction(fn () => $this->save($r, $d)) : $this->save($r, $d);
    }

    public function edit(Request $r, Document $document)
    {
        Access::document($document, $r->user());
        if ($document->type === 'strategy') {
            return app(StrategyController::class)->editor($r, $document);
        }
        abort_unless(in_array($document->status, ['draft', 'returned']) && Governance::canWrite($r->user(), $document->type), 403);

        return view($document->type === 'cmt_goals' ? 'planning.cmt-edit' : 'planning.edit', $this->lookups($r) + ['document' => $document, 'items' => $document->items]);
    }

    public function update(Request $r, Document $document)
    {
        Access::document($document, $r->user());

        if ($document->type === 'strategy') {
            return app(StrategyController::class)->save($r, $document);
        }

        return $document->type === 'cmt_goals' ? DB::transaction(fn () => $this->save($r, $document)) : $this->save($r, $document);
    }

    private function save(Request $r, Document $document)
    {
        if ($r->input('type') === 'cmt_goals') {
            CmtGoals::prepare($r);
            CmtGoals::normalize($r);
        }
        $data = $r->validate(['type' => ['required', Rule::in(array_keys(Access::TYPES))], 'title' => 'required|string|max:200', 'department_id' => 'nullable|exists:departments,id', 'period_id' => 'nullable|exists:planning_periods,id', 'parent_id' => 'nullable|exists:planning_documents,id', 'approval_chain_id' => 'required|exists:approval_chains,id', 'description' => 'nullable|string|max:20000', 'version' => 'nullable|integer', 'items' => 'required|array|min:1|max:200', 'items.*.title' => 'required|string|max:200', 'items.*.description' => 'nullable|string|max:5000', 'items.*.owner_id' => 'nullable|exists:users,id', 'items.*.due_on' => 'nullable|date_format:Y-m-d', 'items.*.definition_of_done' => 'nullable|string|max:5000', 'items.*.metric' => 'nullable|string|max:100', 'items.*.baseline' => 'nullable|numeric|min:0|max:999999999999', 'items.*.target' => 'nullable|numeric|min:0|max:999999999999', 'items.*.amount' => 'nullable|numeric|min:0|max:999999999999', 'items.*.requires_budget' => 'nullable|boolean', 'items.*.support_department_id' => 'nullable|exists:departments,id', 'items.*.source_id' => 'nullable|exists:planning_items,id', 'items.*.programme' => 'nullable|string|max:100', 'items.*.batch' => 'nullable|string|max:100', 'items.*.starts_on' => 'nullable|date_format:Y-m-d', 'items.*.frequency' => 'nullable|in:once,weekly,monthly', 'items.*.details' => 'nullable|array', 'items.*.details.participants' => 'nullable|string|max:100', 'items.*.details.duration' => 'nullable|string|max:100', 'items.*.details.tentative_date' => 'nullable|string|max:200', 'items.*.details.support_department_ids' => 'nullable|array|max:20', 'items.*.details.support_department_ids.*' => 'exists:departments,id', 'items.*.details.objective' => 'nullable|string|max:200', 'items.*.details.course_code' => 'nullable|string|max:100', 'items.*.details.credits' => 'nullable|string|max:100', 'items.*.details.teaching_load' => 'nullable|string|max:200', 'items.*.details.faculty' => 'nullable|string|max:200']);
        $goalDetails = $r->validate([
            'items.*.details.goal_key' => 'nullable|uuid',
            'items.*.details.deliverables' => 'nullable|string|max:5000',
            'items.*.details.milestones' => 'nullable|string|max:5000',
            'items.*.details.proposed_tasks' => 'nullable|string|max:5000',
            'items.*.details.target_note' => 'nullable|string|max:2000',
            'items.*.details.supporting_owner_ids' => 'nullable|array|max:30',
            'items.*.details.supporting_owner_ids.*' => 'exists:users,id',
            'items.*.details.additional_source_ids' => 'nullable|array|max:30',
            'items.*.details.additional_source_ids.*' => 'exists:planning_items,id',
        ]);
        foreach ($data['items'] as $index => &$line) {
            $line['details'] = array_merge($line['details'] ?? [], $goalDetails['items'][$index]['details'] ?? []);
        }
        unset($line);
        abort_unless(Governance::canWrite($r->user(), $data['type']), 403);
        if (! in_array($data['type'], ['strategy', 'cmt_goals'])) {
            abort_unless(Access::department($r->user(), $data['department_id'] ?? null), 403);
        }
        if (! empty($data['parent_id'])) {
            $parent = Document::findOrFail($data['parent_id']);
            Access::document($parent, $r->user());
            $expected = Governance::parentType($data['type']);
            abort_unless($expected === $parent->type && in_array($parent->status, $document->previous_id ? ['approved', 'superseded'] : ['approved']) && (in_array($parent->type, ['strategy', 'cmt_goals']) || $parent->department_id === ($data['department_id'] ?? null)), 422, 'Choose an approved parent of the matching type and department.');
        }
        if ($data['type'] === 'budget') {
            $ctx = app(FiscalYearContext::class);
            $ctx->assertWritable();
            abort_unless(! $r->filled('_fiscal_year_id') || (int) $r->_fiscal_year_id === $ctx->year?->id, 422, 'Fiscal year changed; reload the draft.');
            if ($document->exists) {
                abort_unless((int) $document->fiscal_year_id === $ctx->year?->id, 422, 'Select the proposal fiscal year.');
            }
        }
        foreach ($data['items'] as $item) {
            if ($data['type'] === 'plan' && ! empty($item['source_id'])) {
                abort_unless($document->previous_id && Item::whereKey($item['source_id'])->where('document_id', $document->previous_id)->exists(), 422, 'Invalid amendment source.');
            }if (! empty($item['owner_id'])) {
                $owner = User::active()->findOrFail($item['owner_id']);
                abort_unless(in_array($data['type'], ['strategy', 'cmt_goals']) || $owner->department_id === ($data['department_id'] ?? null), 422, 'Choose an activity owner in the plan department.');
            }
        }
        DB::transaction(function () use ($data, $document, $r) {
            $d = $document->exists ? Document::lockForUpdate()->findOrFail($document->id) : $document;
            if ($d->exists) {
                abort_unless(in_array($d->status, ['draft', 'returned']) && $d->type === $data['type'] && (int) $d->version === (int) ($data['version'] ?? 0), 409, 'This document changed. Reload before saving.');
            }
            $items = $data['items'];
            unset($data['items'],$data['version']);
            $d->fill($data);
            if ($d->type === 'cmt_goals') {
                $d->department_id = null;
            }
            if ($d->type === 'budget') {
                $d->fiscal_year_id = app(FiscalYearContext::class)->year?->id;
            }$d->version++;
            $d->save();
            $d->items()->delete();
            foreach ($items as $item) {
                $item['requires_budget'] = ! empty($item['requires_budget']);
                $item['amount'] = $item['amount'] ?? 0;
                $d->items()->create($item);
            }
            $d->unsetRelation('items');
            if (in_array($d->type, ['cmt_goals', 'goals'])) {
                abort_unless($d->parent_id && $d->parent?->type === Governance::parentType($d->type), 422, 'Select the correct approved parent.');
                foreach ($d->items as $line) {
                    if ($line->source_id) {
                        abort_unless($d->parent->items()->whereKey($line->source_id)->exists(), 422, 'The goal source must belong to the selected parent.');
                    }
                }
            }
            if ($d->type === 'cmt_goals') {
                CmtGoals::validateLinks($d);
            }
            $document->id = $d->id;
            AuditLog::record($r->user(), 'planning.saved', $d, $d->title);
        });

        return redirect()->route('planning.show', $document)->with('success', 'Planning draft saved.');
    }

    public function show(Request $r, Document $document)
    {
        Access::document($document, $r->user());

        return view('planning.show', ['document' => $document->load('items.owner', 'decisions.actor', 'department', 'parent', 'period'), 'publications' => DB::table('planning_budget_publications')->whereIn('item_id', $document->items->pluck('id'))->pluck('budget_id', 'item_id')]);
    }

    public function action(Request $r, Document $document, Workflow $workflow)
    {
        $v = $r->validate(['action' => 'required|in:submit,accept,return,reject', 'version' => 'required|integer', 'note' => 'nullable|string|max:5000']);
        $workflow->action($document, $r->user(), $v['action'], $v['version'], $v['note'] ?? null);

        return back()->with('success', 'Workflow updated.');
    }

    public function amend(Request $r, Document $document)
    {
        Access::document($document, $r->user());
        if ($document->type === 'strategy') {
            return app(StrategyController::class)->review($r, $document);
        }
        abort_unless(Governance::canWrite($r->user(), $document->type), 403);
        $copy = DB::transaction(function () use ($document, $r) {
            $d = Document::lockForUpdate()->findOrFail($document->id);
            abort_unless($d->status === 'approved', 422, 'Only approved documents can be amended.');
            $existing = Document::where('previous_id', $d->id)->whereIn('status', ['draft', 'returned', 'pending_verification', 'pending_approval'])->first();
            if ($existing) {
                return $existing;
            }
            $copy = $d->replicate();
            $copy->previous_id = $d->id;
            $copy->revision = $d->revision + 1;
            $copy->status = 'draft';
            $copy->version = 1;
            $copy->steps = null;
            $copy->step_index = 0;
            $copy->approved_at = null;
            $copy->created_by = $r->user()->id;
            $copy->save();
            foreach ($d->items as $item) {
                $new = $item->replicate();
                $new->document_id = $copy->id;
                if ($d->type === 'plan') {
                    $new->source_id = $item->id;
                }$new->save();
            }AuditLog::record($r->user(), 'planning.amendment.created', $copy, $copy->title);

            return $copy;
        });

        return redirect()->route('planning.edit', $copy);
    }

    public function checkin(Request $r, Document $document, Item $item)
    {
        Access::document($document, $r->user());
        abort_unless($item->document_id === $document->id && in_array($document->type, ['cmt_goals', 'goals']) && $document->status === 'approved' && Governance::canWrite($r->user(), $document->type, 'report') && ($item->owner_id === $r->user()->id || $document->created_by === $r->user()->id || $r->user()->isSuperAdmin()), 403);
        $data = $r->validate(['actual' => 'required|numeric|min:0|max:999999999999', 'evidence' => 'required|string|max:5000']);
        DB::transaction(function () use ($item, $data, $r) {
            $i = Item::lockForUpdate()->findOrFail($item->id);
            $i->update(['actual' => $data['actual']]);
            DB::table('planning_checkins')->insert($data + ['item_id' => $i->id, 'actor_id' => $r->user()->id, 'created_at' => now(), 'updated_at' => now()]);
        });

        return back()->with('success', 'Progress check-in recorded.');
    }

    public function setup(Request $r)
    {
        abort_unless($r->user()->can('planning.setup'), 403);

        return view('planning.setup', ['periods' => Period::orderByDesc('starts_on')->get(), 'governance' => Governance::settings(), 'chains' => ApprovalChain::active()->get()]);
    }

    public function period(Request $r)
    {
        abort_unless($r->user()->can('planning.setup'), 403);
        $data = $r->validate(['name' => 'required|string|max:150', 'academic_year' => 'required|string|max:30', 'programme' => 'nullable|string|max:100', 'batch' => 'nullable|string|max:100', 'starts_on' => 'required|date_format:Y-m-d', 'ends_on' => 'required|date_format:Y-m-d|after_or_equal:starts_on']);
        $p = Period::create($data);
        AuditLog::record($r->user(), 'planning.period.created', $p, $p->name);

        return back()->with('success', 'Planning period added.');
    }

    public function support(Request $r)
    {
        abort_unless($r->user()->can('planning.support'), 403);
        $q = SupportRequest::with('item.document');
        if (! $r->user()->isSuperAdmin() && ! $r->user()->can('planning.all_departments')) {
            $q->where('department_id', $r->user()->department_id ?? 'none');
        }

        return view('planning.support', ['requests' => $q->latest()->paginate(20)]);
    }

    public function respond(Request $r, SupportRequest $support)
    {
        abort_unless($r->user()->can('planning.support') && Access::department($r->user(), $support->department_id), 403);
        $support->update($r->validate(['status' => 'required|in:requested,accepted,completed,declined', 'response' => 'required|string|max:5000']));
        AuditLog::record($r->user(), 'planning.support.responded', $support);

        return back()->with('success', 'Support request updated.');
    }
}
