@extends('planning.layout')
@section('planning-content')
@php
$rows=old('items',$items->toArray());if(!$rows)$rows=[['title'=>'']];
$type=$document->type;
@endphp
<form class="space-y-4" method="POST" action="{{ $document->exists?route('planning.update',$document):route('planning.store') }}">
@csrf @if($document->exists) @method('PUT') @endif
<input type="hidden" name="type" value="{{ $type }}"><input type="hidden" name="version" value="{{ $document->version }}">
<div class="kcard p-5 grid grid-cols-1 md:grid-cols-2 gap-4">
<div><label>Title *</label><input name="title" required value="{{ old('title',$document->title) }}"></div>
<div><label>Department {{ $type!=='strategy'?'*':'' }}</label><select name="department_id" @required($type!=='strategy')><option value="">College</option>@foreach($departments as $d)<option value="{{ $d->id }}" @selected(old('department_id',$document->department_id)===$d->id)>{{ $d->name }}</option>@endforeach</select></div>
<div><label>Planning period / programme semester</label><select name="period_id"><option value="">Select period</option>@foreach($periods as $p)<option value="{{ $p->id }}" @selected(old('period_id',$document->period_id)===$p->id)>{{ $p->name }} · {{ $p->programme }} {{ $p->batch }}</option>@endforeach</select></div>
<div><label>Approval chain *</label><select name="approval_chain_id" required><option value="">Select chain</option>@foreach($chains as $c)<option value="{{ $c->id }}" @selected(old('approval_chain_id',$document->approval_chain_id)===$c->id)>{{ $c->name }}</option>@endforeach</select></div>
@if($type!=='strategy')<div><label>Linked approved {{ ['goals'=>'strategy','plan'=>'semester goals','budget'=>'semester plan'][$type] }}</label><select name="parent_id"><option value="">Operational / unlinked</option>@foreach($parents->where('type',['goals'=>'strategy','plan'=>'goals','budget'=>'plan'][$type]) as $p)<option value="{{ $p->id }}" @selected(old('parent_id',$document->parent_id)===$p->id)>{{ $p->title }}</option>@endforeach</select></div>@endif
<div class="md:col-span-2"><label>Purpose / theme</label><textarea name="description" rows="2">{{ old('description',$document->description) }}</textarea></div>
</div>
<div class="kcard p-5 space-y-3" x-data="{count:{{ count($rows) }}}"><h3 class="font-semibold">{{ $type==='budget'?'Budget lines':($type==='plan'?'Activities':'Objectives and key results') }}</h3>
<div id="planning-items">@foreach($rows as $i=>$row)@include('planning.item-fields')@endforeach</div>
<template id="planning-item-template">@php $i='__INDEX__';$row=[]; @endphp @include('planning.item-fields')</template>
<button type="button" class="text-teal-700" @click="let html=document.getElementById('planning-item-template').innerHTML.replaceAll('__INDEX__',count++); document.getElementById('planning-items').insertAdjacentHTML('beforeend',html)">+ Add {{ $type==='plan'?'activity':'item' }}</button>
</div><button class="btn-primary">Save draft</button>
</form>
@endsection
