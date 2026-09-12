@extends('layouts.app')
@section('title', 'Checklist '.$checklist->purchaseOrder?->po_number)
@section('page-title', 'Procurement Checklist')

@section('content')
@foreach($checklist->returns as $return)<div class="bg-red-50 border border-red-200 rounded-xl p-4 mb-4 text-sm"><strong>Returned to vendor · {{ $return->created_at->format('d M Y H:i') }}</strong><p>{{ $return->reason }}</p></div>@endforeach
@can('checklists.complete')
@if($checklist->status!=='returned')<details class="kcard p-5 mb-4"><summary class="font-semibold text-red-700 cursor-pointer">Return goods or invoice to vendor</summary><form method="POST" action="{{ route('checklists.return',$checklist) }}" class="mt-4 space-y-3">@csrf<input type="hidden" name="_fiscal_year_id" value="{{ $workingFiscalYear?->id }}"><label class="block text-sm">Return type<select name="return_type" class="block w-full border rounded-lg p-2"><option value="goods_and_invoice">Goods and invoice</option><option value="invoice">Invoice correction</option><option value="service_correction">Service correction</option></select></label><label class="block text-sm">Reason and required correction<textarea name="reason" required maxlength="3000" rows="3" class="block w-full border rounded-lg p-2"></textarea></label><p class="text-xs text-gray-500">The vendor will be notified. Unpaid, unauthorised payment records for this invoice will be cancelled.</p><button class="btn-danger">Return to vendor</button></form></details>@endif
@endcan
<div class="max-w-5xl space-y-5">
    @if(session('success'))<div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800"><ul class="list-disc list-inside">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <div class="bg-[#0B1E3D] rounded-xl p-6 text-white flex flex-wrap justify-between gap-4"><div><p class="text-xs font-semibold uppercase tracking-wide text-teal-300">Vendor bill control</p><h1 class="mt-1 font-mono text-2xl font-bold">{{ $checklist->purchaseOrder->po_number }}</h1><p class="mt-2 text-sm text-slate-300">Bill {{ $checklist->vendorBill->bill_number }} · {{ $checklist->vendor->name }}</p></div><div class="text-right"><span class="inline-flex rounded-full bg-white/15 px-3 py-1 text-sm font-semibold">{{ $checklist->statusLabel() }}</span><p class="mt-3 text-lg font-bold">Rs {{ number_format($checklist->vendorBill->amount, 2) }}</p></div></div>

    <div class="grid gap-5 sm:grid-cols-3"><div class="rounded-xl border border-gray-200 bg-white p-5"><p class="text-xs uppercase font-semibold text-gray-400">Bill date</p><p class="mt-2 font-medium text-gray-800">{{ $checklist->vendorBill->bill_date?->format('d M Y') }}</p></div><div class="rounded-xl border border-gray-200 bg-white p-5"><p class="text-xs uppercase font-semibold text-gray-400">Vendor bill</p><p class="mt-2 font-medium text-gray-800">{{ $checklist->vendorBill->original_name }}</p><div class="mt-2 flex gap-4"><a href="{{ route('purchase-orders.vendor-bills.view', [$checklist->purchaseOrder, $checklist->vendorBill]) }}" target="_blank" class="inline-block text-sm font-semibold text-teal-700">View →</a><a href="{{ route('purchase-orders.vendor-bills.download', [$checklist->purchaseOrder, $checklist->vendorBill]) }}" class="inline-block text-sm font-semibold text-teal-700">Download →</a></div></div><div class="rounded-xl border border-gray-200 bg-white p-5"><p class="text-xs uppercase font-semibold text-gray-400">PO total</p><p class="mt-2 font-medium text-gray-800">Rs {{ number_format($checklist->purchaseOrder->total_amount, 2) }}</p>@can('purchase_orders.view')
<a href="{{ route('purchase-orders.show', $checklist->purchaseOrder) }}" class="mt-2 inline-block text-sm font-semibold text-teal-700">View purchase order →</a>
@endcan</div></div>

    @cannot('checklists.complete')
    <section class="kcard p-6"><h2 class="font-semibold mb-3">Control questions</h2>@foreach($checklist->requiredChecks() as $key=>$label)<p class="text-sm py-2 border-b">{{ $checklist->answerChecked($key) ? '✓' : '○' }} {{ $label }}</p>@endforeach<p class="mt-3 text-sm text-gray-600">{{ $checklist->control_comments }}</p></section>
    @endcannot
    @can('checklists.complete')
<form method="POST" action="{{ route('checklists.update', $checklist) }}" class="rounded-xl border border-gray-200 bg-white overflow-hidden">@csrf
<input type="hidden" name="_fiscal_year_id" value="{{ $workingFiscalYear?->id }}"> @method('PUT')
        <div class="border-b border-gray-100 px-6 py-5"><h2 class="font-semibold text-gray-800">Control questions</h2><p class="mt-1 text-sm text-gray-500">Select the fulfilment type, then tick every required control. Questions marked * are required before the bill can go to Accounts.</p></div>
        <div class="p-6">
            <fieldset @disabled($checklist->isSentToAccounts() || $checklist->status==='returned')><div class="mb-5"><p class="mb-2 text-sm font-medium text-gray-700">Fulfilment type</p><label class="mr-5 inline-flex items-center gap-2 text-sm"><input type="radio" name="fulfillment_type" value="goods" @checked(old('fulfillment_type', $checklist->fulfillment_type) === 'goods')> Goods</label><label class="inline-flex items-center gap-2 text-sm"><input type="radio" name="fulfillment_type" value="service" @checked(old('fulfillment_type', $checklist->fulfillment_type) === 'service')> Service</label></div>
            <div x-data="{ type: @js(old('fulfillment_type',$checklist->fulfillment_type)), sets: @js($questionSets), answers: @js(old('answers',$initialAnswers)) }" @change.window="if ($event.target.name === 'fulfillment_type') { type=$event.target.value; answers={}; }" class="space-y-3">
                <template x-for="(question,key) in sets[type]" :key="type+key"><label class="flex gap-3 border rounded-lg px-4 py-3 text-sm"><input type="checkbox" :name="'answers['+key+']'" value="1" x-model="answers[key]"><span x-text="question.label+(question.required?' *':'')"></span></label></template>
                <p x-show="Object.keys(sets[type]).length===0" class="text-sm text-amber-800">No controls configured. Ask an administrator to add questions.</p>
            </div>
            <label class="mt-5 block text-sm font-medium text-gray-700">Control comments<textarea name="control_comments" rows="3" class="mt-1.5 w-full rounded-lg border-gray-300 text-sm focus:border-teal-500 focus:ring-teal-500">{{ old('control_comments', $checklist->control_comments) }}</textarea></label>
            @if(! $checklist->isSentToAccounts())<button class="mt-5 rounded-lg bg-[#0B1E3D] px-5 py-2.5 text-sm font-semibold text-white hover:bg-[#162A45]">Save checklist</button>@endif</fieldset>
        </div>
    </form>
@endcan

    @if($checklist->isReadyForAccounts())<div class="rounded-xl border border-teal-200 bg-teal-50 p-6"><h2 class="font-semibold text-teal-900">Ready for Accounts</h2><p class="mt-1 text-sm text-teal-800">All controls have been completed by {{ $checklist->completedBy?->name }}. Add an optional handover note and formally send this bill for payment processing.</p>@can('checklists.complete')
<form method="POST" action="{{ route('checklists.send-to-accounts', $checklist) }}" class="mt-4 flex flex-col gap-3 sm:flex-row">@csrf
<input type="hidden" name="_fiscal_year_id" value="{{ $workingFiscalYear?->id }}"><input name="accounts_comment" placeholder="Optional Accounts handover comment" class="flex-1 rounded-lg border-teal-300 text-sm focus:border-teal-500 focus:ring-teal-500"><button class="rounded-lg bg-teal-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-teal-700">Send to Accounts for Payment</button></form>
@endcan</div>@endif
    @if($checklist->isSentToAccounts() && ! $checklist->checklistPayment)<div class="rounded-xl border border-amber-200 bg-amber-50 p-6"><h2 class="font-semibold text-amber-900">Payment record needs recovery</h2><p class="mt-1 text-sm text-amber-800">This checklist was sent before its payment record could be created. Recover it once to create the missing payable record without duplicating the bill.</p>@can('checklists.complete')
<form method="POST" action="{{ route('checklists.send-to-accounts', $checklist) }}" class="mt-4">@csrf
<input type="hidden" name="_fiscal_year_id" value="{{ $workingFiscalYear?->id }}"><button class="rounded-lg bg-amber-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-amber-700">Recover payment record</button></form>
@endcan</div>@elseif($checklist->isSentToAccounts())<div class="rounded-xl border border-green-200 bg-green-50 p-6"><h2 class="font-semibold text-green-900">Sent to Accounts for payment</h2><p class="mt-1 text-sm text-green-800">Sent by {{ $checklist->accountsSentBy?->name }} on {{ $checklist->accounts_sent_at?->format('d M Y H:i') }}. A payment record has been created in the Payments module for Finance to process.@if($checklist->accounts_comment) Note: {{ $checklist->accounts_comment }}@endif</p></div>@endif

    <div class="rounded-xl border border-gray-200 bg-white overflow-hidden"><div class="px-6 py-4 border-b border-gray-100 flex flex-wrap items-center justify-between gap-3"><div><h2 class="font-semibold text-gray-800">PO approval record</h2><p class="mt-1 text-sm text-gray-500">Included in the printable Accounts support pack.</p></div>@can('checklists.view')
<a href="{{ route('checklists.pdf', $checklist) }}" class="rounded-lg bg-gray-100 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-200">Download PDF support pack</a>
@endcan</div><div class="divide-y divide-gray-100">@forelse($checklist->purchaseOrder->approvalActions as $action)<div class="px-6 py-3 text-sm"><span class="font-semibold text-gray-800">{{ $action->actor?->name }}</span> · {{ ucfirst(str_replace('_', ' ', $action->decision)) }} <span class="text-xs text-gray-400">{{ $action->acted_at?->format('d M Y H:i') }}</span>@if($action->note)<p class="mt-1 text-xs text-gray-500">{{ $action->note }}</p>@endif</div>@empty<div class="px-6 py-5 text-sm text-gray-500">No approval actions recorded.</div>@endforelse</div></div>
    @can('checklists.view')
<a href="{{ route('checklists.index') }}" class="inline-block text-sm text-gray-500 hover:text-gray-700">← Back to Checklists</a>
@endcan
</div>

@endsection
