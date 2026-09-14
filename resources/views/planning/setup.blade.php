@extends('planning.layout')
@section('planning-content')
@include('planning.governance-setup')
<div class="kcard p-5"><h2 class="font-semibold mb-4">Planning periods and programme semesters</h2><form method="POST" action="{{ route('planning.periods.store') }}" class="grid md:grid-cols-3 gap-3">@csrf
@foreach(['name'=>'Period name','academic_year'=>'Academic year (e.g. 2083)','programme'=>'Programme (optional)','batch'=>'Batch (optional)','starts_on'=>'Start date (AD)','ends_on'=>'End date (AD)'] as $name=>$label)<div><label>{{ $label }}</label><input name="{{ $name }}" type="{{ in_array($name,['starts_on','ends_on'])?'date':'text' }}" @required(!in_array($name,['programme','batch'])) value="{{ old($name) }}"></div>@endforeach<button class="btn-primary">Add period</button></form></div>
<div class="kcard overflow-x-auto"><table class="w-full"><thead><tr><th>Period</th><th>Programme / batch</th><th>Dates</th></tr></thead><tbody>@foreach($periods as $p)<tr><td>{{ $p->name }} · {{ $p->academic_year }}</td><td>{{ $p->programme }} {{ $p->batch }}</td><td>{{ $p->starts_on }} — {{ $p->ends_on }} <span class="text-xs text-gray-500">{{ \App\Services\Planning\Dates::bs($p->starts_on) }}</span></td></tr>@endforeach</tbody></table></div>
@endsection
