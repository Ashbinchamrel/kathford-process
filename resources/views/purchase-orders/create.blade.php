@extends('layouts.app')
@section('title', 'Create Purchase Order')
@section('page-title', 'New Purchase Order')

@section('content')
@php($rfqMode = (bool) ($selectedQuote || $awardedQuotes->isNotEmpty()))
<div class="max-w-5xl space-y-5" x-data="purchaseOrderBuilder()">
    @if ($errors->any())
    <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"><p class="font-semibold">Please fix the following errors:</p><ul class="mt-1 list-inside list-disc">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif

    @can('purchase_orders.create')
<form method="POST" action="{{ route('purchase-orders.store') }}">
        @csrf
<input type="hidden" name="_fiscal_year_id" value="{{ $workingFiscalYear?->id }}">
        <section class="rounded-xl border border-gray-200 bg-white p-6">
            <h2 class="text-base font-semibold text-gray-800">1. Purchase Order source</h2>
            <p class="mt-1 text-sm text-gray-500">Create one PO per vendor. Awarded RFQ items are locked to their awarded vendor and quoted price.</p>
            <div class="mt-4 flex flex-wrap gap-5">
                <label class="flex cursor-pointer items-center gap-2"><input type="radio" value="rfq" x-model="source" class="text-teal-500"><span class="text-sm font-medium">From awarded RFQ items</span></label>
                <label class="flex cursor-pointer items-center gap-2"><input type="radio" value="manual" x-model="source" class="text-teal-500"><span class="text-sm font-medium">Standalone manual PO</span></label>
            </div>
            <div x-show="source === 'rfq'" x-cloak class="mt-4">
                <label class="mb-1 block text-sm font-medium text-gray-700">Awarded vendor items</label>
                <select name="rfq_quote_id" :disabled="source !== 'rfq'" onchange="window.location.href='{{ route('purchase-orders.create') }}?rfq_quote_id='+encodeURIComponent(this.value)" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500">
                    <option value="">— Select awarded RFQ items —</option>
                    @foreach($awardedQuotes as $quote)<option value="{{ $quote->id }}" {{ $selectedQuote?->id === $quote->id ? 'selected' : '' }}>{{ $quote->rfq->rfq_number }} — {{ $quote->vendor->name }} · {{ $quote->items->count() }} awarded item{{ $quote->items->count() === 1 ? '' : 's' }} · Rs {{ number_format($quote->items->sum('total'), 2) }}</option>@endforeach
                </select>
                @if($awardedQuotes->isEmpty())<p class="mt-2 text-xs text-gray-400">No awarded RFQ items are waiting for a Purchase Order.</p>@endif
            </div>
        </section>

        @if($selectedQuote)
        <section x-show="source === 'rfq'" x-cloak class="rounded-xl border border-teal-200 bg-teal-50 p-6">
            <div class="flex flex-wrap items-start justify-between gap-3"><div><p class="text-xs font-semibold uppercase text-teal-700">Selected supplier</p><h2 class="mt-1 text-lg font-bold text-gray-900">{{ $selectedQuote->vendor->name }}</h2><p class="mt-1 text-sm text-gray-600">RFQ {{ $selectedQuote->rfq->rfq_number }} · only the awarded items below will be ordered.</p></div><p class="font-mono text-lg font-bold text-teal-800">Rs {{ number_format($selectedQuote->items->sum('total'), 2) }}</p></div>
            <div class="mt-5 overflow-x-auto rounded-lg border border-teal-100 bg-white"><table class="min-w-full text-sm"><thead class="bg-teal-50 text-left text-xs uppercase text-teal-800"><tr><th class="px-4 py-3">Item</th><th class="px-4 py-3">Request details</th><th class="px-4 py-3 text-right">Qty</th><th class="px-4 py-3 text-right">Rate</th><th class="px-4 py-3 text-right">Total</th></tr></thead><tbody class="divide-y divide-gray-100">@foreach($selectedQuote->items as $item)<tr><td class="px-4 py-3 font-medium text-gray-800">{{ $item->description }}</td><td class="px-4 py-3 text-xs text-gray-500">{{ $item->request_remarks ?? '—' }}</td><td class="px-4 py-3 text-right font-mono">{{ $item->quantity }} {{ $item->unit }}</td><td class="px-4 py-3 text-right font-mono">Rs {{ number_format($item->unit_rate, 2) }}</td><td class="px-4 py-3 text-right font-mono">Rs {{ number_format($item->total, 2) }}</td></tr>@endforeach</tbody></table></div>
        </section>
        @endif

        <section x-show="source === 'manual'" x-cloak class="rounded-xl border border-gray-200 bg-white p-6">
            <div class="flex items-center justify-between"><h2 class="text-base font-semibold text-gray-800">2. Manual vendor and items</h2><button type="button" @click="addManualItem()" class="text-sm font-semibold text-teal-700">+ Add item</button></div>
            <div class="mt-4"><label class="mb-1 block text-sm font-medium text-gray-700">Vendor</label><select name="vendor_id" :disabled="source !== 'manual'" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"><option value="">— Select vendor —</option>@foreach($vendors as $vendor)<option value="{{ $vendor->id }}" {{ old('vendor_id') === $vendor->id ? 'selected' : '' }}>{{ $vendor->name }}</option>@endforeach</select></div>
            <div class="mt-4 overflow-x-auto"><div class="mb-2 grid min-w-[760px] grid-cols-12 gap-2 px-1 text-xs font-semibold uppercase text-gray-500"><div class="col-span-3">Description</div><div class="col-span-2">Qty</div><div class="col-span-2">Unit</div><div class="col-span-2">Rate (Rs)</div><div class="col-span-2 text-right">Amount (Rs)</div></div><div class="min-w-[760px] space-y-2"><template x-for="(item, index) in manualItems" :key="item.key"><div class="grid grid-cols-12 gap-2 item-row"><div class="col-span-3"><input :required="source === 'manual'" :disabled="source !== 'manual'" :name="`items[${index}][description]`" x-model="item.description" placeholder="Item description" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"></div><div class="col-span-2"><input :required="source === 'manual'" :disabled="source !== 'manual'" type="number" min="0.01" step="0.01" :name="`items[${index}][quantity]`" x-model.number="item.quantity" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"></div><div class="col-span-2"><input :disabled="source !== 'manual'" :name="`items[${index}][unit]`" x-model="item.unit" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"></div><div class="col-span-2"><input :required="source === 'manual'" :disabled="source !== 'manual'" type="number" min="0" step="0.01" :name="`items[${index}][unit_rate]`" x-model.number="item.unit_rate" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"></div><div class="col-span-2 flex items-center justify-end px-2 font-mono text-sm font-semibold text-gray-800" x-text="formatMoney((Number(item.quantity) || 0) * (Number(item.unit_rate) || 0))"></div><div class="col-span-1 pt-2 text-center"><button type="button" @click="removeManualItem(index)" class="text-red-500">×</button></div>
<div class="col-span-12 rounded-lg bg-teal-50 px-3 py-2 text-sm text-teal-900" x-show="lowestRates(item).length" x-cloak>
    <p class="font-semibold">Lowest approved rate matching this item name</p>
    <template x-for="rate in lowestRates(item)" :key="rate.id">
        <div class="mt-1">
            <span class="font-medium" x-text="rate.vendor_name"></span>
            <span x-text="' · '+formatMoney(rate.unit_rate)+' / '+rate.unit+' · Valid until '+rate.valid_until"></span>
            <p x-show="rate.specification" class="text-xs mt-1" x-text="rate.specification"></p>
        </div>
    </template>
    <p class="mt-1 text-xs">Compare the unit and specification before choosing. Each purchase order is for one vendor. Select the vendor above and enter the suggested rate if suitable.</p>
</div>
</div></template></div></div>
        </section>

        <section class="rounded-xl border border-gray-200 bg-white p-6">
            <h2 class="text-base font-semibold text-gray-800">{{ $selectedQuote ? '2' : '3' }}. Delivery and standard terms</h2>
            <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2"><div class="sm:col-span-2"><label class="mb-1 block text-sm font-medium text-gray-700">Delivery address <span class="text-red-500">*</span></label><textarea name="delivery_address" required rows="2" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">{{ old('delivery_address', config('kathford.college_address')) }}</textarea></div><div><label class="mb-1 block text-sm font-medium text-gray-700">Expected delivery date <span class="text-red-500">*</span></label><input type="date" name="expected_delivery_date" required value="{{ old('expected_delivery_date') }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"></div><div class="sm:col-span-2"><label class="mb-1 block text-sm font-medium text-gray-700">Terms &amp; conditions <span class="text-red-500">*</span></label><textarea name="terms_and_conditions" required rows="3" placeholder="Delivery, warranty, payment, and acknowledgement terms" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">{{ old('terms_and_conditions') }}</textarea></div><div class="sm:col-span-2 rounded-lg border {{ $purchaseOrderChain ? 'border-teal-200 bg-teal-50 text-teal-800' : 'border-amber-200 bg-amber-50 text-amber-800' }} px-4 py-3 text-sm"><p class="font-semibold">Purchase Order approval is centrally controlled</p>@if($purchaseOrderChain)<p class="mt-1">This PO will use the administrator-configured chain: <strong>{{ $purchaseOrderChain->name }}</strong>.</p>@else<p class="mt-1">No active PO approval chain is configured. You may save the PO, but an administrator must configure one in Administration → Approval Chains before submission.</p>@endif</div></div>
            <div class="mt-5 rounded-lg border border-teal-100 bg-teal-50 p-4"><div class="mb-4 flex flex-wrap items-center gap-4"><label class="flex items-center gap-2 text-sm font-semibold text-teal-900"><input type="hidden" name="tax_applied" value="0"><input type="checkbox" name="tax_applied" value="1" x-model="taxApplied" class="rounded border-teal-300 text-teal-600"> Apply tax / VAT</label><label x-show="taxApplied" class="flex items-center gap-2 text-sm text-teal-900">Rate <input type="number" name="tax_rate" x-model.number="taxRate" min="0" max="100" step="0.01" class="w-24 rounded border border-teal-200 bg-white px-2 py-1 text-right font-mono text-sm"> %</label></div><dl class="grid gap-4 text-sm sm:grid-cols-3"><div><dt class="text-xs font-semibold uppercase text-teal-700">Gross Amount</dt><dd class="mt-1 font-mono font-semibold text-gray-900" x-text="formatMoney(totalAmount())"></dd></div><div><dt class="text-xs font-semibold uppercase text-teal-700">Tax Amount</dt><dd class="mt-1 font-mono font-semibold text-gray-900" x-text="formatMoney(taxAmount())"></dd></div><div><dt class="text-xs font-semibold uppercase text-teal-700">Net Amount</dt><dd class="mt-1 font-mono text-lg font-bold text-teal-900" x-text="formatMoney(totalAmount() + taxAmount())"></dd></div></dl></div>
        </section>
        <div class="flex flex-wrap gap-3"><button type="submit" class="rounded-lg bg-gray-100 px-6 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-200">Save PO</button><button type="submit" name="submit_for_approval" value="1" class="rounded-lg bg-[#0B1E3D] px-6 py-2.5 text-sm font-semibold text-white">Create &amp; Submit for Approval</button>@can('purchase_orders.view')
<a href="{{ route('purchase-orders.index') }}" class="py-2.5 text-sm text-gray-500">Cancel</a>
@endcan</div>
    </form>
@endcan
</div>

<script>
function purchaseOrderBuilder() {
    const initialItems = @json(old('items', []));
    return {
        approvedRates: @js($approvedRates),
        normalize(value) { return String(value || '').trim().replace(/\s+/g, ' ').toLowerCase(); },
        lowestRates(item) {
            const name = this.normalize(item.description);
            if (!name) return [];
            const matches = this.approvedRates.filter(rate => this.normalize(rate.item_name) === name);
            if (!matches.length) return [];
            const lowest = Math.min(...matches.map(rate => Number(rate.unit_rate)));
            return matches.filter(rate => Number(rate.unit_rate) === lowest);
        },
        source: @json($rfqMode ? 'rfq' : 'manual'),
        rfqSubtotal: Number(@json($selectedQuote?->items->sum('total') ?? 0)),
        taxApplied: @json((bool) old('tax_applied', false)),
        taxRate: Number(@json(old('tax_rate', 13))) || 13,
        manualItems: initialItems.map((item, index) => ({ key: Date.now() + index, description: item.description || '', quantity: Number(item.quantity || 1), unit: item.unit || 'pcs', unit_rate: Number(item.unit_rate || 0) })),
        init() { if (this.source === 'manual' && !this.manualItems.length) this.addManualItem(); this.$watch('source', (value) => { if (value === 'manual' && !this.manualItems.length) this.addManualItem(); }); },
        addManualItem() { this.manualItems.push({ key: Date.now() + this.manualItems.length, description: '', quantity: 1, unit: 'pcs', unit_rate: 0 }); },
        removeManualItem(index) { this.manualItems.splice(index, 1); },
        totalAmount() { return this.source === 'rfq' ? this.rfqSubtotal : this.manualItems.reduce((total, item) => total + ((Number(item.quantity) || 0) * (Number(item.unit_rate) || 0)), 0); },
        taxAmount() { return this.taxApplied ? this.totalAmount() * (Number(this.taxRate) || 0) / 100 : 0; },
        formatMoney(value) { return `Rs ${new Intl.NumberFormat('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(Number(value) || 0)}`; },
    };
}
</script>
@endsection
