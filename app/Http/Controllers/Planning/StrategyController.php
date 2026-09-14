<?php

namespace App\Http\Controllers\Planning;

use App\Http\Controllers\Controller;
use App\Models\ApprovalChain;
use App\Models\AuditLog;
use App\Models\Planning\Document;
use App\Services\Planning\Access;
use App\Services\Planning\Governance;
use App\Services\Planning\StrategyMatrix;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StrategyController extends Controller
{
    public function editor(Request $r, Document $document)
    {
        abort_unless(Governance::canWrite($r->user(), 'strategy', $document->exists ? 'edit' : 'create'), 403);
        if ($document->exists) {
            Access::document($document, $r->user());
            abort_unless(in_array($document->status, ['draft', 'returned']), 403);
        }
        $matrix = $document->strategy_data ?: ['starts_on' => '', 'ends_on' => '', 'semesters' => array_fill(0, 4, ['label' => '', 'starts_on' => '', 'ends_on' => ''])];
        $rows = $document->items->map(fn ($i) => $i->details['strategy'] ?? ['priority' => $i->details['objective'] ?? $i->title, 'goal' => $i->title, 'strategy' => $i->description, 'targets' => array_fill(0, count($matrix['semesters']), '')])->all();
        if (! $rows) {
            $rows = [['priority' => '', 'goal' => '', 'strategy' => '', 'targets' => array_fill(0, count($matrix['semesters']), '')]];
        }

        if (! $document->exists && $r->query('template') === 'reference') {
            $reference = json_decode(file_get_contents(resource_path('data/kathford-strategy-reference.json')), true, 512, JSON_THROW_ON_ERROR);
            $matrix = $reference['matrix'];
            $rows = $reference['rows'];
            $document->title = $reference['title'];
        }

        return view('planning.strategy-edit', compact('document', 'matrix', 'rows') + ['chains' => ApprovalChain::active()->get()]);
    }

    public function save(Request $r, Document $document)
    {
        abort_unless(Governance::canWrite($r->user(), 'strategy', $document->exists ? 'edit' : 'create'), 403);
        if ($document->exists) {
            Access::document($document, $r->user());
        }
        $data = $r->validate(['title' => 'required|string|max:200', 'approval_chain_id' => 'required|exists:approval_chains,id', 'description' => 'nullable|string|max:20000', 'version' => 'nullable|integer', 'matrix' => 'required|array:starts_on,ends_on,semesters', 'rows' => 'required|array']);
        StrategyMatrix::validate($data['matrix'], $data['rows']);
        $d = DB::transaction(function () use ($r, $document, $data) {
            $d = $document->exists ? Document::lockForUpdate()->findOrFail($document->id) : new Document(['type' => 'strategy', 'created_by' => $r->user()->id]);
            abort_unless($d->type === 'strategy' && in_array($d->status ?? 'draft', ['draft', 'returned']), 403);
            if ($d->exists) {
                abort_unless((int) $d->version === (int) ($data['version'] ?? 0), 409, 'This plan changed. Reload before saving.');
            }
            $review = array_intersect_key($d->strategy_data ?? [], array_flip(['review_semester', 'review_reason']));
            $d->fill(['title' => $data['title'], 'approval_chain_id' => $data['approval_chain_id'], 'description' => $data['description'] ?? null, 'strategy_data' => $data['matrix'] + $review, 'department_id' => null, 'parent_id' => null]);
            $d->version++;
            $d->save();
            $d->items()->delete();
            foreach ($data['rows'] as $position => $row) {
                $row = array_intersect_key($row, array_flip(['priority', 'goal', 'strategy', 'targets']));
                $d->items()->create(['title' => mb_substr(($row['goal'] ?? '') ?: $row['priority'], 0, 200), 'details' => ['strategy' => $row, 'position' => $position]]);
            }
            AuditLog::record($r->user(), 'planning.strategy.saved', $d, $d->title);

            return $d;
        });

        return redirect()->route('planning.show', $d)->with('success', 'Strategic Plan draft saved.');
    }

    public function review(Request $r, Document $document)
    {
        Access::document($document, $r->user());
        abort_unless($document->type === 'strategy' && Governance::canWrite($r->user(), 'strategy', 'review') && Governance::canWrite($r->user(), 'strategy', 'edit'), 403);
        $v = $r->validate(['review_semester' => 'required|integer|min:0|max:11', 'review_reason' => 'required|string|max:5000']);
        $copy = DB::transaction(function () use ($r, $document, $v) {
            $d = Document::lockForUpdate()->findOrFail($document->id);
            abort_unless($d->status === 'approved', 422, 'Only the current approved plan can be reviewed.');
            abort_unless(isset($d->strategy_data['semesters'][$v['review_semester']]), 422, 'Choose a semester in this plan.');
            $existing = Document::where('previous_id', $d->id)->whereIn('status', ['draft', 'returned', 'pending_verification', 'pending_approval'])->first();
            if ($existing) {
                return $existing;
            }
            $copy = $d->replicate();
            $copy->fill(['previous_id' => $d->id, 'revision' => $d->revision + 1, 'status' => 'draft', 'version' => 1, 'steps' => null, 'step_index' => 0, 'approved_at' => null, 'created_by' => $r->user()->id, 'strategy_data' => array_merge($d->strategy_data, $v)]);
            $copy->save();
            foreach ($d->items as $item) {
                $new = $item->replicate();
                $new->document_id = $copy->id;
                $new->source_id = $item->id;
                $new->save();
            }
            AuditLog::record($r->user(), 'planning.strategy.review', $copy, $v['review_reason']);

            return $copy;
        });

        return redirect()->route('planning.edit', $copy);
    }
}
