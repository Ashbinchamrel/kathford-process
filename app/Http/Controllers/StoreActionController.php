<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\RfqItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class StoreActionController extends Controller
{
    /**
     * "Available in Store" items skip vendor/PO sourcing entirely, so this is
     * their only home — not tied to any Purchase Order or Checklist.
     */
    public function index(): View
    {
        $items = RfqItem::where('available_in_store', true)
            ->with([
                'rfq.activityForm.creator',
                'rfq.activityForm.department',
                'rfq.activityForm.category',
                'storeIssuedBy',
            ])
            ->orderByDesc('id')
            ->paginate(25);

        return view('store-action.index', compact('items'));
    }

    public function markIssued(RfqItem $item): RedirectResponse
    {
        abort_unless($item->available_in_store, 404);

        if (! $item->store_issued_at) {
            $item->update(['store_issued_at' => now(), 'store_issued_by' => Auth::id()]);
            AuditLog::record(Auth::user(), 'store_action.issued', $item, $item->rfq?->rfq_number.' · '.$item->description);
        }

        return back()->with('success', "Marked \"{$item->description}\" as issued from Store.");
    }
}
