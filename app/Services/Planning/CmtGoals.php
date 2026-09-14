<?php

namespace App\Services\Planning;

use App\Models\ApprovalChain;
use App\Models\Planning\Document;
use App\Models\Planning\Period;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CmtGoals
{
    public static function groups($items): array
    {
        return $items->groupBy(fn ($i) => $i->details['goal_key'] ?? $i->id)->map(function ($rows) {
            $first = $rows->first();

            return [
                'key' => $first->details['goal_key'] ?? (string) Str::uuid(),
                'title' => $first->details['objective'] ?? $first->title,
                'source_id' => $first->source_id, 'owner_id' => $first->owner_id,
                'due_on' => $first->due_on, 'definition_of_done' => $first->definition_of_done,
                'deliverables' => $first->details['deliverables'] ?? $first->description,
                'milestones' => $first->details['milestones'] ?? '',
                'proposed_tasks' => $first->details['proposed_tasks'] ?? '',
                'target_note' => $first->details['target_note'] ?? '',
                'supporting_owner_ids' => $first->details['supporting_owner_ids'] ?? [],
                'additional_source_ids' => $first->details['additional_source_ids'] ?? [],
                'results' => $rows->map(fn ($i) => ['title' => $i->title, 'metric' => $i->metric, 'baseline' => $i->baseline, 'target' => $i->target])->values()->all(),
            ];
        })->values()->all();
    }

    public static function prepare(Request $r): void
    {
        $chain = Governance::defaultChain('cmt_goals');
        if (! $chain || ! ApprovalChain::active()->whereKey($chain)->exists()) {
            throw ValidationException::withMessages(['approval_chain_id' => 'Set the CMT Goals approval chain in Planning Setup → Form settings first.']);
        }
        $r->merge(['approval_chain_id' => $chain]);
        if ($r->has('semester_index')) {
            $r->validate(['parent_id' => 'required|exists:planning_documents,id', 'semester_index' => 'required|integer|min:0|max:11']);
            $parent = Document::findOrFail($r->parent_id);
            Access::document($parent, $r->user());
            $semester = $parent->strategy_data['semesters'][(int) $r->semester_index] ?? null;
            abort_unless($parent->type === 'strategy' && $semester, 422, 'Choose a semester from the selected Strategic Plan.');
            $period = Period::firstOrCreate([
                'starts_on' => $semester['starts_on'], 'ends_on' => $semester['ends_on'], 'programme' => null, 'batch' => null,
            ], ['name' => $semester['label'] ?? 'Semester '.((int) $r->semester_index + 1), 'academic_year' => substr($semester['starts_on'], 0, 4).'/'.substr($semester['ends_on'], 0, 4)]);
            $r->merge(['period_id' => $period->id]);
        }
    }

    public static function normalize(Request $r): void
    {
        if (! $r->has('cmt_groups')) {
            return;
        } // Existing imports/API drafts remain compatible.
        $v = $r->validate([
            'cmt_groups' => 'required|array|min:1|max:50',
            'cmt_groups.*.key' => 'required|uuid|distinct',
            'cmt_groups.*.title' => 'required|string|max:200',
            'cmt_groups.*.source_id' => 'required|exists:planning_items,id',
            'cmt_groups.*.owner_id' => 'nullable|exists:users,id',
            'cmt_groups.*.due_on' => 'nullable|date_format:Y-m-d',
            'cmt_groups.*.definition_of_done' => 'nullable|string|max:5000',
            'cmt_groups.*.deliverables' => 'nullable|string|max:5000',
            'cmt_groups.*.milestones' => 'nullable|string|max:5000',
            'cmt_groups.*.proposed_tasks' => 'nullable|string|max:5000',
            'cmt_groups.*.target_note' => 'nullable|string|max:2000',
            'cmt_groups.*.supporting_owner_ids' => 'nullable|array|max:30',
            'cmt_groups.*.supporting_owner_ids.*' => 'exists:users,id',
            'cmt_groups.*.additional_source_ids' => 'nullable|array|max:30',
            'cmt_groups.*.additional_source_ids.*' => 'exists:planning_items,id',
            'cmt_groups.*.results' => 'required|array|min:1|max:20',
            'cmt_groups.*.results.*.title' => 'required|string|max:200',
            'cmt_groups.*.results.*.metric' => 'nullable|string|max:100',
            'cmt_groups.*.results.*.baseline' => 'nullable|numeric|min:0|max:999999999999',
            'cmt_groups.*.results.*.target' => 'nullable|numeric|min:0|max:999999999999',
        ]);
        $items = [];
        foreach ($v['cmt_groups'] as $g) {
            foreach ($g['results'] as $kr) {
                $items[] = $kr + [
                    'source_id' => $g['source_id'], 'owner_id' => $g['owner_id'] ?? null,
                    'due_on' => $g['due_on'] ?? null, 'definition_of_done' => $g['definition_of_done'] ?? null,
                    'details' => array_merge(array_intersect_key($g, array_flip(['deliverables', 'milestones', 'proposed_tasks', 'target_note', 'supporting_owner_ids', 'additional_source_ids'])), ['goal_key' => $g['key'], 'objective' => $g['title']]),
                ];
            }
        }
        $r->merge(['items' => $items]);
    }

    public static function validateLinks(Document $d): void
    {
        $sourceIds = $d->parent->items()->pluck('id');
        foreach ($d->items as $item) {
            foreach ($item->details['additional_source_ids'] ?? [] as $id) {
                abort_unless($sourceIds->contains($id), 422, 'Additional strategic links must belong to the selected Strategic Plan.');
            }
            foreach ($item->details['supporting_owner_ids'] ?? [] as $id) {
                abort_unless(User::active()->whereKey($id)->exists(), 422, 'Choose active supporting owners.');
            }
        }
    }
}
