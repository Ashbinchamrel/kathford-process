@extends('layouts.app')
@section('title', $rfq->rfq_number)
@section('page-title', 'RFQ: ' . $rfq->rfq_number)

@section('content')
@if($rfq->handoff_activity_id && !$rfq->requestItems()->exists() && !$rfq->quotes()->exists())
@can('rfq.create')<div class="kcard p-5 mb-4"><p class="text-sm mb-3">This approved activity is ready for RFQ preparation.</p><a href="{{ route('rfq.create',['rfq_id'=>$rfq->id]) }}" class="btn-primary">Complete RFQ preparation</a></div>@endcan
@endif
@can('rfq.send')
@if($additionalVendors->isNotEmpty() && $rfq->quotes->isNotEmpty() && !in_array($rfq->status,['closed','cancelled']))
<details class="kcard p-5 mb-4"><summary class="font-semibold cursor-pointer">Request quotations from additional vendors</summary>
<form method="POST" action="{{ route('rfq.invite-additional',$rfq) }}" class="mt-4 space-y-3">@csrf<input type="hidden" name="_fiscal_year_id" value="{{ $workingFiscalYear?->id }}"><p class="text-sm text-gray-500">Choose additional vendors to quote on these items. No amount is required.</p><select name="vendor_ids[]" multiple required class="w-full border rounded-lg p-3 text-sm">@foreach($additionalVendors as $vendor)<option value="{{ $vendor->id }}">{{ $vendor->name }}</option>@endforeach</select><button class="btn-primary">Send quotation requests</button></form></details>
@endif
@endcan
@if($rfq->requestItems()->where('quotation_not_required',true)->exists())
<div class="kcard p-5 mb-4"><h2 class="font-semibold mb-3">Items sent directly to Payment Schedule</h2>@foreach($rfq->requestItems()->where('quotation_not_required',true)->get() as $item)<p class="text-sm">{{ $item->description }} · Approved Activity {{ $rfq->activityForm?->form_number }}</p>@endforeach</div>
@endif
<div class="max-w-5xl space-y-5">
    {{-- Header --}}
    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <div class="flex flex-wrap gap-6 justify-between">
            <div>
                <p class="text-xs text-gray-400 font-semibold uppercase mb-1">Activity Form</p>
                @if($rfq->activityForm)
                @can('activity_forms.view')
<a href="{{ route('activity-forms.show', $rfq->activityForm) }}" class="text-teal-600 hover:underline font-medium">{{ $rfq->activityForm->form_number }}</a>
@endcan
                @else
                <span class="text-gray-500">Standalone RFQ</span>
                @endif
                <p class="text-gray-700 text-sm mt-0.5">{{ $rfq->activityForm?->activity_name }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-400 font-semibold uppercase mb-1">Status</p>
                <span class="px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-700">{{ $rfq->statusLabel() }}</span>
            </div>
            <div>
                <p class="text-xs text-gray-400 font-semibold uppercase mb-1">Deadline</p>
                <p class="text-gray-700 font-medium">{{ $rfq->deadline ? $rfq->deadline->format('d M Y') : '—' }}</p>
            </div>
        </div>
        @if($rfq->notes)
        <div class="mt-4 pt-4 border-t border-gray-100">
            <p class="text-xs text-gray-400 font-semibold uppercase mb-1">Scope / Instructions</p>
            <p class="text-sm text-gray-700">{{ $rfq->notes }}</p>
        </div>
        @endif
    </div>

    {{-- Quotes from Vendors --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
            <h2 class="font-semibold text-gray-800">Vendor Quotes ({{ $rfq->quotes->count() }})</h2>
            <div class="flex gap-2">
                @can('rfq.view')
<a href="{{ route('rfq.pdf', $rfq) }}" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-sm font-semibold">Download PDF</a>
@endcan
                @if($rfq->status === 'draft')

                @if(in_array($rfq->status, ['draft', 'open']))
                @can('rfq.edit')
<a href="{{ route('rfq.edit', $rfq) }}" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-sm font-semibold">Edit</a>
@endcan
                @can('rfq.delete')
<form method="POST" action="{{ route('rfq.destroy', $rfq) }}" onsubmit="return confirm('Delete this RFQ?')" class="inline">
                    @csrf
<input type="hidden" name="_fiscal_year_id" value="{{ $workingFiscalYear?->id }}"> @method('DELETE')
                    <button class="px-4 py-2 bg-red-50 hover:bg-red-100 text-red-600 rounded-lg text-sm font-semibold">Delete</button>
                </form>
@endcan
                @endif
                @can('rfq.send')
<form method="POST" action="{{ route('rfq.send', $rfq) }}">
                    @csrf
<input type="hidden" name="_fiscal_year_id" value="{{ $workingFiscalYear?->id }}">
                    <button class="px-4 py-2 bg-teal-500 hover:bg-teal-600 text-white rounded-lg text-sm font-semibold">Send Invitations</button>
                </form>
@endcan
                @endif
                @if(in_array($rfq->status, ['sent', 'quotes_received', 'partially_awarded', 'items_awarded']))
                @can('rfq.view')
<a href="{{ route('rfq.compare', $rfq) }}" class="px-4 py-2 bg-purple-500 hover:bg-purple-600 text-white rounded-lg text-sm font-semibold">Compare Quotes</a>
@endcan
                <button onclick="document.getElementById('manual-quote-modal').classList.remove('hidden')" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-sm font-medium">Enter Manual Quote</button>
                @endif
            </div>
        </div>
        <div class="divide-y divide-gray-100">
            @forelse($rfq->quotes as $quote)
            @php
            $qs=['pending'=>'bg-gray-100 text-gray-500','submitted'=>'bg-blue-100 text-blue-700','accepted'=>'bg-green-100 text-green-700','rejected'=>'bg-red-100 text-red-700','manual'=>'bg-orange-100 text-orange-700'];
            @endphp
            <div class="px-6 py-4 flex items-center justify-between">
                <div>
                    <p class="font-medium text-gray-800">{{ $quote->vendor?->name }}</p>
                    <p class="text-xs text-gray-400">{{ $quote->vendor?->email }}</p>
                    @if($quote->submitted_at)
                    <p class="text-xs text-gray-400 mt-0.5">Submitted {{ $quote->submitted_at->format('d M Y H:i') }}</p>
                    @endif
                </div>
                <div class="flex items-center gap-4">
                    @if($quote->total_quoted !== null)
                    <div class="text-right"><p class="font-mono font-bold text-gray-800">Rs {{ number_format($quote->grand_total, 2) }}</p><p class="mt-0.5 text-xs text-gray-400">Total Rs {{ number_format($quote->total_quoted, 2) }} + Tax Rs {{ number_format($quote->tax_amount, 2) }}</p></div>
                    @endif
                    <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $qs[$quote->status] ?? 'bg-gray-100 text-gray-500' }}">{{ $quote->vendorStatusLabel() }}</span>
                </div>
            </div>
            @empty
            <div class="px-6 py-8 text-center text-gray-400 text-sm">No vendor quotes yet.</div>
            @endforelse
        </div>
    </div>

    @can('rfq.view')
<a href="{{ route('rfq.index') }}" class="text-gray-500 hover:text-gray-700 text-sm">← Back to RFQs</a>
@endcan
</div>

{{-- Manual Quote Modal --}}
<div id="manual-quote-modal" class="hidden fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-md p-6">
        <h3 class="font-bold text-gray-900 mb-4">Enter Manual Quote</h3>
        @can('rfq.create')
<form method="POST" action="{{ route('rfq.manual-quote', $rfq) }}">
            @csrf
<input type="hidden" name="_fiscal_year_id" value="{{ $workingFiscalYear?->id }}">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Vendor <span class="text-red-500">*</span></label>
                    <select name="vendor_id" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                        <option value="">Select vendor…</option>
                        @foreach($rfq->quotes as $q)
                        <option value="{{ $q->vendor_id }}">{{ $q->vendor?->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Total Amount (Rs) <span class="text-red-500">*</span></label>
                    <input id="manual-total-amount" type="number" name="total_amount" step="0.01" min="0" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tax Amount (Rs)</label>
                    <input id="manual-tax-amount" type="number" name="tax_amount" value="0" step="0.01" min="0" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                </div>
                <div class="rounded-lg bg-teal-50 px-3 py-2 text-sm text-teal-900"><span class="font-medium">Grand Total:</span> <span id="manual-grand-total" class="font-mono font-bold">Rs 0.00</span></div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                    <textarea name="notes" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none resize-none"></textarea>
                </div>
            </div>
            <div class="flex gap-3 mt-5">
                <button type="submit" class="flex-1 py-2 bg-teal-500 hover:bg-teal-600 text-white rounded-lg text-sm font-semibold">Save Quote</button>
                <button type="button" onclick="document.getElementById('manual-quote-modal').classList.add('hidden')" class="px-4 py-2 text-gray-500 text-sm">Cancel</button>
            </div>
        </form>
@endcan
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const total = document.getElementById('manual-total-amount');
    const tax = document.getElementById('manual-tax-amount');
    const grand = document.getElementById('manual-grand-total');
    const recalculate = () => grand.textContent = `Rs ${((Number.parseFloat(total.value) || 0) + (Number.parseFloat(tax.value) || 0)).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
    total?.addEventListener('input', recalculate); tax?.addEventListener('input', recalculate); recalculate();
});
</script>
@endsection
