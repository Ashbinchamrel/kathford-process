@extends('planning.layout')
@section('planning-content')
@php
$rows=old('items',$items->toArray());if(!$rows)$rows=[['title'=>'']];
$type=$document->type;
@endphp
<form class="space-y-4" method="POST" action="{{ $document->exists?route('planning.update',$document):route('planning.store') }}">
@csrf @if($document->exists) @method('PUT') @endif
<input type="hidden" name="_fiscal_year_id" value="{{ $workingFiscalYear?->id }}">
@if($type==='budget')<p class="text-sm">Budget fiscal year: {{ $workingFiscalYear?->name }}</p>@endif
<input type="hidden" name="type" value="{{ $type }}"><input type="hidden" name="version" value="{{ $document->version }}">
<div class="kcard p-5 grid grid-cols-1 md:grid-cols-2 gap-4">
<div><label>Title *</label><input name="title" required value="{{ old('title',$document->title) }}"></div>
@if($type==='cmt_goals')<div><label>Prepared by</label><p class="py-2 text-sm">College Management Team</p><input type="hidden" name="department_id" value=""></div>@else<div><label>Department {{ !in_array($type,['strategy','cmt_goals'])?'*':'' }}</label><select name="department_id" @required(!in_array($type,['strategy','cmt_goals']))><option value="">College</option>@foreach($departments as $d)<option value="{{ $d->id }}" @selected(old('department_id',$document->department_id)===$d->id)>{{ $d->name }}</option>@endforeach</select></div>@endif
<div><label>Planning period / programme semester</label><select name="period_id" @required(in_array($type,['cmt_goals','goals']))><option value="">Select period</option>@foreach($periods as $p)<option value="{{ $p->id }}" @selected(old('period_id',$document->period_id)===$p->id)>{{ $p->name }} · {{ $p->programme }} {{ $p->batch }}</option>@endforeach</select></div>
<div><label>Approval chain *</label><select name="approval_chain_id" required><option value="">Select chain</option>@foreach($chains as $c)<option value="{{ $c->id }}" @selected(old('approval_chain_id',$document->approval_chain_id)===$c->id)>{{ $c->name }}</option>@endforeach</select></div>
@if($type!=='strategy')<div><label>Linked approved {{ ['cmt_goals'=>'Strategic Plan','goals'=>'CMT Goals','plan'=>'Department Goals','budget'=>'semester plan'][$type] }}</label><select name="parent_id" @required(in_array($type,['cmt_goals','goals']))><option value="">{{ in_array($type,['cmt_goals','goals'])?'Select approved parent':'Operational / unlinked' }}</option>@foreach($parents->whereIn('status',$document->previous_id?['approved','superseded']:['approved'])->where('type',['cmt_goals'=>'strategy','goals'=>'cmt_goals','plan'=>'goals','budget'=>'plan'][$type]) as $p)<option value="{{ $p->id }}" @selected(old('parent_id',$document->parent_id)===$p->id)>{{ $p->title }}</option>@endforeach</select></div>@endif
<div class="md:col-span-2"><label>Purpose / theme</label><textarea name="description" rows="2">{{ old('description',$document->description) }}</textarea></div>
</div>
<div class="kcard p-5 space-y-3" x-data="{count:{{ count($rows) }}}"><h3 class="font-semibold">{{ $type==='budget'?'Budget lines':($type==='plan'?'Activities':'Objectives and key results') }}</h3>
<div id="planning-items">@foreach($rows as $i=>$row)@include('planning.item-fields')@endforeach</div>
<template id="planning-item-template">@php $i='__INDEX__';$row=[]; @endphp @include('planning.item-fields')</template>
<button type="button" class="text-teal-700" @click="let html=document.getElementById('planning-item-template').innerHTML.replaceAll('__INDEX__',count++); document.getElementById('planning-items').insertAdjacentHTML('beforeend',html)">+ Add {{ $type==='plan'?'activity':'item' }}</button>
</div><button class="btn-primary">Save draft</button>
</form>
<script>
document.addEventListener('DOMContentLoaded',()=>{
 const parent=document.querySelector('select[name=parent_id]');if(!parent)return;
 function filterSources(){document.querySelectorAll('select.goal-source').forEach(select=>{for(const option of select.options){if(!option.value)continue;option.hidden=option.disabled=option.dataset.parent!==parent.value;}if(select.selectedOptions[0]?.disabled)select.value='';});}
 parent.addEventListener('change',filterSources);filterSources();new MutationObserver(filterSources).observe(document.getElementById('planning-items'),{childList:true});
});
</script>
@endsection
