<?php

namespace App\Http\Controllers\Planning;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Planning\Document;
use App\Models\Planning\SupportRequest;
use App\Services\Planning\Access;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PlanningBulkDeleteController extends Controller
{
    /**
     * Super Admin only, mirroring the app-wide TransactionBulkDeleteController
     * pattern (soft delete, audit-logged, reversible). Planning documents are
     * additionally scoped by `type` so a module key can't be used to delete
     * records outside the tab it's shown on.
     */
    public function destroy(Request $request, string $module): RedirectResponse
    {
        abort_unless(Auth::user()?->isSuperAdmin(), 403);

        $request->validate([
            'ids' => ['required', 'array', 'max:100'],
            'ids.*' => ['string'],
            'confirmation' => ['required', 'in:DELETE'],
        ]);

        if (array_key_exists($module, Access::TYPES)) {
            $records = Document::where('type', $module)->whereIn('id', $request->input('ids'))->get();
            $label = fn ($r) => $r->title;
        } elseif ($module === 'planning_support') {
            $records = SupportRequest::with('item')->whereIn('id', $request->input('ids'))->get();
            $label = fn ($r) => $r->item?->title ?? $r->id;
        } else {
            abort(404);
        }

        DB::transaction(function () use ($records, $label) {
            foreach ($records as $record) {
                AuditLog::record(Auth::user(), 'planning.bulk_deleted', $record, $label($record));
                $record->delete();
            }
        });

        return back()->with('success', count($records).' record'.(count($records) === 1 ? '' : 's').' removed.');
    }
}
