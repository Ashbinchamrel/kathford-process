<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\GoodsReceivedItem;
use App\Models\GoodsReceivedNote;
use App\Models\PurchaseOrder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class GoodsReceivedController extends Controller
{
    public function index(): View
    {
        $grns = \App\Support\RecordVisibility::apply(GoodsReceivedNote::query(), Auth::user())->with(['purchaseOrder.vendor', 'receivedByUser'])
            ->latest()
            ->paginate(20);

        return view('grn.index', compact('grns'));
    }

    public function create(Request $request): RedirectResponse
    {
        return redirect()->route('checklists.index')->with('info', 'Goods receipt and invoice controls are now recorded through the Procurement Checklist for each vendor bill. Historical GRNs remain available for audit.');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'purchase_order_id'  => ['required', 'exists:purchase_orders,id'],
            'received_by_name'   => ['required', 'string', 'max:150'],
            'received_date'      => ['required', 'date'],
            'is_partial'         => ['boolean'],
            'notes'              => ['nullable', 'string'],
            'items'              => ['required', 'array'],
            'items.*.line_item_id'        => ['required'],
            'items.*.item_condition_note'  => ['nullable', 'string'],
            'items.*.ordered_quantity'    => ['required', 'numeric', 'min:0'],
            'items.*.received_quantity'   => ['required', 'numeric', 'min:0'],
        ]);

        $grn = DB::transaction(function () use ($request) {
            $po  = PurchaseOrder::findOrFail($request->purchase_order_id);
            $grn = GoodsReceivedNote::create([
                'grn_number'          => $this->nextGrnNumber(),
                'purchase_order_id'   => $po->id,
                'received_by_user_id' => Auth::id(),
                'received_by_name'    => $request->received_by_name,
                'received_date'       => $request->received_date,
                'is_partial'          => $request->boolean('is_partial'),
                'notes'               => $request->notes,
                'status'              => 'pending',
            ]);

            foreach ($request->items as $item) {
                GoodsReceivedItem::create([
                    'grn_id'            => $grn->id,
                    'line_item_id'      => $item['line_item_id'],
                    'item_condition_note' => $item['item_condition_note'] ?? null,
                    'ordered_quantity'  => $item['ordered_quantity'],
                    'received_quantity' => $item['received_quantity'],
                ]);
            }

            // Update PO status
            $status = $request->boolean('is_partial') ? 'partially_received' : 'fully_received';
            $po->update(['status' => $status]);

            AuditLog::record(Auth::user(), 'grn.created', $grn, $grn->grn_number);
            return $grn;
        });

        return redirect()->route('grn.show', $grn)
            ->with('success', "GRN {$grn->grn_number} recorded.");
    }

    public function show(GoodsReceivedNote $grn): View
    {
        $grn->load(['purchaseOrder.vendor', 'receivedByUser', 'confirmedBy', 'items']);
        return view('grn.show', compact('grn'));
    }

    public function confirm(GoodsReceivedNote $grn): RedirectResponse
    {
        abort_unless(Auth::user()->can('grn.confirm'), 403);
        // Older records used "draft" before confirmation was introduced. Keep
        // them actionable while all newly created GRNs use the pending status.
        abort_unless(in_array($grn->status, ['draft', 'pending'], true), 422);

        $grn->update([
            'status'       => 'confirmed',
            'confirmed_by' => Auth::id(),
            'confirmed_at' => now(),
        ]);

        AuditLog::record(Auth::user(), 'grn.confirmed', $grn, $grn->grn_number);

        return redirect()->route('grn.show', $grn)
            ->with('success', 'GRN confirmed. You can now create a payment schedule.');
    }

    private function nextGrnNumber(): string
    {
        $year  = now()->format('Y');
        $count = GoodsReceivedNote::withoutGlobalScope('fiscal_year')->withTrashed()->whereYear('created_at', $year)->count() + 1;
        return sprintf('GRN-%s-%04d', $year, $count);
    }
}
