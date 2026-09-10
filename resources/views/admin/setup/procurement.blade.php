@extends('layouts.app')
@section('title','Procurement Setup')
@section('page-title','Procurement Setup')
@section('content')
<div class="max-w-6xl space-y-5">
<section class="kcard p-5"><h2 class="font-semibold">RFQ preparation assignment</h2><form method="POST" action="{{ route('admin.procurement.assignment') }}" class="mt-3 space-y-3">@csrf<p class="text-sm text-gray-500">Choose who receives future approved activities. Only users with RFQ view and create permissions are listed. With no assignment, the activity creator and Super Admin retain access.</p><select name="rfq_preparer_user_id" class="w-full border rounded-lg p-2 text-sm"><option value="">No designated preparer</option>@foreach($preparers as $preparer)<option value="{{ $preparer->id }}" @selected(\App\Models\Setting::get('rfq_preparer_user_id')===$preparer->id)>{{ $preparer->name }}</option>@endforeach</select><button class="btn-primary">Save assignment</button></form></section>
<section class="kcard p-5"><h2 class="text-lg font-semibold">Approved vendor rates</h2><p class="text-sm text-gray-500 mt-1">Approve fixed prices for a defined period. RFQ preparers can reuse current rates; quotation comparison and purchase-order approval still apply.</p>
<form method="POST" enctype="multipart/form-data" action="{{ route('admin.procurement.rates.import') }}" class="border rounded-lg p-4 mt-4 space-y-3">
@csrf<h3 class="font-semibold">Bulk upload from Excel</h3>
<a href="{{ route('admin.procurement.rates.template') }}" class="text-teal-700 underline text-sm">Download Excel template</a>
<p class="text-sm text-gray-500">Select one vendor for this file. Fill one rate per row using dates in YYYY-MM-DD format (Excel dates also work). Specification is optional; is_active accepts Yes/No and defaults to Yes. Maximum 1,000 rows / 5 MB. Matching item, unit and validity dates update the existing rate. Other rows add new rates. Errors prevent the entire upload.</p>
<label class="block text-sm">Vendor<select name="vendor_id" required class="block w-full border rounded-lg p-2"><option value="">Select vendor</option>@foreach($vendors as $vendor)<option value="{{ $vendor->id }}">{{ $vendor->name }}</option>@endforeach</select></label>
<input type="file" name="rates_file" accept=".xlsx,.xls,.csv" required class="block text-sm"><button class="btn-primary">Upload and approve rates</button>
</form>
<div x-data="{vendorFilter:'', editing:null, vendorIds:@js($rates->pluck('vendor_id')->unique()->values())}" class="mt-4">
<label class="block text-sm font-medium">Show rates for vendor<select x-model="vendorFilter" @change="editing=null" class="block w-full border rounded-lg p-2"><option value="">Select vendor</option>@foreach($vendors as $vendor)<option value="{{ $vendor->id }}">{{ $vendor->name }}</option>@endforeach</select></label>
<p x-show="!vendorFilter" class="mt-3 text-sm text-gray-500">Select a vendor to view approved rates.</p>
<div x-show="vendorFilter" x-cloak class="mt-4 overflow-x-auto border rounded-lg">
<table class="w-full text-sm text-left">
<thead class="bg-gray-50 text-gray-600"><tr>
@foreach(['Item','Unit','Unit rate (Rs)','Valid from','Valid until','Status','Actions'] as $heading)<th class="p-3 whitespace-nowrap">{{ $heading }}</th>@endforeach
</tr></thead>
@foreach($rates as $rate)
<tbody x-show="vendorFilter === @js($rate->vendor_id)" x-cloak class="border-t">
<tr>
<td class="p-3 font-medium">{{ $rate->item_name }}@if($rate->specification)<p class="text-xs text-gray-500 font-normal mt-1">{{ $rate->specification }}</p>@endif</td>
<td class="p-3">{{ $rate->unit }}</td>
<td class="p-3 whitespace-nowrap">{{ number_format($rate->unit_rate,2) }}</td>
<td class="p-3 whitespace-nowrap">{{ $rate->valid_from->format('d M Y') }}</td>
<td class="p-3 whitespace-nowrap">{{ $rate->valid_until->format('d M Y') }}</td>
<td class="p-3">{{ !$rate->is_active ? 'Inactive' : ($rate->valid_until->lt(today()) ? 'Expired' : ($rate->valid_from->gt(today()) ? 'Upcoming' : 'Active')) }}</td>
<td class="p-3"><div class="flex gap-3 items-center">
<button type="button" class="text-teal-700 font-medium" @click="editing = editing === {{ $rate->id }} ? null : {{ $rate->id }}" :aria-expanded="editing === {{ $rate->id }}">Edit</button>
<form method="POST" action="{{ route('admin.procurement.rates.destroy',$rate) }}">@csrf @method('DELETE')<button class="text-red-600">Remove</button></form>
</div></td>
</tr>
<tr x-show="editing === {{ $rate->id }}" x-cloak><td colspan="7" class="p-4 bg-gray-50">
<form method="POST" action="{{ route('admin.procurement.rates.update',$rate) }}">@csrf @method('PUT') @include('admin.setup.rate-fields',['rate'=>$rate])<button class="btn-primary mt-3">Approve changes</button><button type="button" @click="editing=null" class="ml-3 text-sm text-gray-600">Cancel</button></form>
</td></tr>
</tbody>
@endforeach
<tbody x-show="vendorFilter && !vendorIds.includes(vendorFilter)" x-cloak><tr><td colspan="7" class="p-4 text-gray-500">No approved rates saved for this vendor.</td></tr></tbody>
</table>
</div>
</div><details class="border rounded-lg p-4 mt-4"><summary class="cursor-pointer font-semibold text-sm">Add approved rate</summary><form method="POST" action="{{ route('admin.procurement.rates.store') }}" class="mt-4">@csrf @include('admin.setup.rate-fields',['rate'=>null])<button class="btn-primary mt-3">Approve rate</button></form></details></section>
<section class="kcard p-5"><h2 class="text-lg font-semibold">Checklist questions</h2><p class="mt-1 text-sm text-gray-500">Manage goods and service controls separately. Saved checklist questions are preserved for audit history.</p>
@foreach($questions as $question)<details class="border rounded-lg p-3 mt-3"><summary class="cursor-pointer text-sm">{{ ucfirst($question->fulfillment_type) }} · {{ $question->label }}</summary><form method="POST" action="{{ route('admin.procurement.questions.update',$question) }}" class="mt-3">@csrf @method('PUT') @include('admin.setup.question-fields',['question'=>$question])<button class="btn-primary mt-3">Save question</button></form><form method="POST" action="{{ route('admin.procurement.questions.destroy',$question) }}" class="mt-2">@csrf @method('DELETE')<button class="btn-danger">Remove question</button></form></details>@endforeach
<form method="POST" action="{{ route('admin.procurement.questions.store') }}" class="mt-5">@csrf<h3 class="font-semibold mb-3">Add question</h3>@include('admin.setup.question-fields',['question'=>null])<button class="btn-primary mt-3">Add question</button></form></section>
</div>
@endsection
