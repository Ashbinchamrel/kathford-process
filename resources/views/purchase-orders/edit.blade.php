@extends('layouts.app')
@section('title', 'Edit Purchase Order')
@section('page-title', 'Edit Purchase Order')

@section('content')
<div class="max-w-4xl space-y-4">

    @if ($errors->any())
    <div class="rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
        @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
    </div>
    @endif

    <div class="bg-blue-50 border border-blue-200 rounded-lg px-4 py-3 text-sm text-blue-800">
        Editing <span class="font-mono font-semibold">{{ $purchaseOrder->po_number }}</span>
        — Vendor: <strong>{{ $purchaseOrder->vendor->name }}</strong>
    </div>

    @can('purchase_orders.edit')
<form method="POST" action="{{ route('purchase-orders.update', $purchaseOrder) }}">
        @csrf
<input type="hidden" name="_fiscal_year_id" value="{{ $workingFiscalYear?->id }}">
        @method('PUT')

        {{-- Line items — only editable on standalone POs --}}
        @if(! $purchaseOrder->rfq_quote_id)
        <div class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="text-base font-semibold text-gray-800">Line Items</h2>
                <button type="button" onclick="addItem()"
                        class="text-sm text-teal-600 hover:text-teal-700 font-medium flex items-center gap-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Add Row
                </button>
            </div>
            <div class="text-xs text-gray-400 grid grid-cols-12 gap-2 font-semibold uppercase tracking-wider px-1">
                <div class="col-span-3">Description</div>
                <div class="col-span-2">Qty</div>
                <div class="col-span-2">Unit</div>
                <div class="col-span-2">Rate (Rs)</div>
                <div class="col-span-2 text-right">Amount (Rs)</div>
                <div class="col-span-1"></div>
            </div>
            <div id="items-container" class="space-y-2">
                @foreach($purchaseOrder->items as $item)
                <div class="grid grid-cols-12 gap-2 items-start item-row">
                    <div class="col-span-3">
                        <input type="text" name="items[{{ $loop->index }}][description]" value="{{ $item->description }}" required
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                    </div>
                    <div class="col-span-2">
                        <input type="number" name="items[{{ $loop->index }}][quantity]" value="{{ $item->quantity }}" step="0.01" min="0.01" required
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none qty-field">
                    </div>
                    <div class="col-span-2">
                        <input type="text" name="items[{{ $loop->index }}][unit]" value="{{ $item->unit }}"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                    </div>
                    <div class="col-span-2">
                        <input type="number" name="items[{{ $loop->index }}][unit_rate]" value="{{ $item->unit_rate }}" step="0.01" min="0" required
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none rate-field">
                    </div>
                    <div class="col-span-2 flex items-center justify-end px-2 font-mono text-sm font-semibold text-gray-700 line-amount">Rs {{ number_format($item->quantity * $item->unit_rate, 2) }}</div>
                    <div class="col-span-1">
                        <button type="button" onclick="this.closest('.item-row').remove(); recalc();"
                                class="text-red-400 hover:text-red-600 py-2 px-1 text-lg">✕</button>
                    </div>
                </div>
                @endforeach
            </div>
            <div class="flex flex-wrap justify-end gap-x-5 gap-y-2 pt-3 border-t border-gray-100 text-sm">
                <span class="text-gray-500">Total Amount: <span id="subtotal-display" class="font-mono font-semibold text-gray-800">Rs {{ number_format($purchaseOrder->subtotal, 2) }}</span></span>
                <span class="text-gray-500">Tax Amount: <span id="tax-display" class="font-mono font-semibold text-gray-800">Rs {{ number_format($purchaseOrder->tax_amount, 2) }}</span></span>
                <span class="font-semibold text-teal-800">Grand Total: <span id="grand-total-display" class="font-mono">Rs {{ number_format($purchaseOrder->total_amount, 2) }}</span></span>
            </div>
        </div>
        @else
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <p class="text-sm text-gray-500">This PO was generated from an RFQ quote — line items are fixed and cannot be edited here.</p>
        </div>
        @endif

        {{-- Delivery & Terms --}}
        <div class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
            <h2 class="text-base font-semibold text-gray-800">Delivery &amp; Terms</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Delivery Address <span class="text-red-500">*</span></label>
                    <textarea name="delivery_address" rows="2" required
                              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none resize-none">{{ old('delivery_address', $purchaseOrder->delivery_address) }}</textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Expected Delivery Date <span class="text-red-500">*</span></label>
                    <input type="date" name="expected_delivery_date"
                           value="{{ old('expected_delivery_date', $purchaseOrder->expected_delivery_date?->format('Y-m-d')) }}" required
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                </div>
                <div class="sm:col-span-2 flex flex-wrap items-center gap-4 rounded-lg bg-teal-50 p-3"><label class="flex items-center gap-2 text-sm font-medium text-teal-900"><input type="hidden" name="tax_applied" value="0"><input type="checkbox" name="tax_applied" value="1" @checked(old('tax_applied', $purchaseOrder->tax_applied)) class="rounded border-teal-300 text-teal-600"> Apply tax / VAT</label><label class="text-sm text-teal-900">Tax rate (%) <input type="number" name="tax_rate" value="{{ old('tax_rate', $purchaseOrder->tax_rate ?: 13) }}" min="0" max="100" step="0.01" class="ml-1 w-24 rounded border border-teal-200 bg-white px-2 py-1 text-right text-sm"></label><p class="text-xs text-teal-700">Tax is calculated from the PO gross amount when saved.</p></div>
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Terms &amp; Conditions <span class="text-red-500">*</span></label>
                    <textarea name="terms_and_conditions" rows="3" required
                              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none resize-none">{{ old('terms_and_conditions', $purchaseOrder->terms_and_conditions) }}</textarea>
                </div>
            </div>
        </div>

        <div class="flex gap-3 mt-2">
            <button type="submit" class="px-6 py-2.5 rounded-lg text-sm font-semibold text-white" style="background:#0B1E3D;">
                Save Changes
            </button>
            @can('purchase_orders.view')
<a href="{{ route('purchase-orders.show', $purchaseOrder) }}" class="text-gray-500 hover:text-gray-700 text-sm py-2.5">Cancel</a>
@endcan
        </div>
    </form>
@endcan
</div>

<script>
let itemIndex = {{ $purchaseOrder->items->count() }};

function addItem() {
    const container = document.getElementById('items-container');
    const div = document.createElement('div');
    div.className = 'grid grid-cols-12 gap-2 items-start item-row';
    div.innerHTML = `
        <div class="col-span-3"><input type="text" name="items[${itemIndex}][description]" placeholder="Description" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none"></div>
        <div class="col-span-2"><input type="number" name="items[${itemIndex}][quantity]" value="1" step="0.01" min="0.01" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none qty-field"></div>
        <div class="col-span-2"><input type="text" name="items[${itemIndex}][unit]" placeholder="pcs" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none"></div>
        <div class="col-span-2"><input type="number" name="items[${itemIndex}][unit_rate]" value="0" step="0.01" min="0" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none rate-field"></div>
        <div class="col-span-2 flex items-center justify-end px-2 font-mono text-sm font-semibold text-gray-700 line-amount">Rs 0.00</div>
        <div class="col-span-1"><button type="button" onclick="this.closest('.item-row').remove();recalc();" class="text-red-400 hover:text-red-600 py-2 px-1 text-lg">✕</button></div>
    `;
    container.appendChild(div);
    div.querySelectorAll('.qty-field,.rate-field').forEach(el => el.addEventListener('input', recalc));
    itemIndex++;
}

function recalc() {
    let s = 0;
    document.querySelectorAll('.item-row').forEach(r => {
        const lineAmount = (parseFloat(r.querySelector('.qty-field')?.value)||0) * (parseFloat(r.querySelector('.rate-field')?.value)||0);
        s += lineAmount;
        const display = r.querySelector('.line-amount');
        if (display) display.textContent = 'Rs ' + lineAmount.toFixed(2);
    });
    const tax = parseFloat(document.getElementById('tax-amount')?.value) || 0;
    document.getElementById('subtotal-display').textContent = 'Rs ' + s.toFixed(2);
    document.getElementById('tax-display').textContent = 'Rs ' + tax.toFixed(2);
    document.getElementById('grand-total-display').textContent = 'Rs ' + (s + tax).toFixed(2);
}

document.querySelectorAll('.qty-field,.rate-field').forEach(el => el.addEventListener('input', recalc));
document.getElementById('tax-amount')?.addEventListener('input', recalc);
recalc();
</script>
@endsection
