@extends('planning.layout')
@section('planning-content')
@php
$groups=old('cmt_groups', \App\Services\Planning\CmtGoals::groups($items));
$strategies=$parents->where('type','strategy')->whereIn('status',$document->previous_id?['approved','superseded']:['approved']);
$sourceData=$strategies->map(fn($p)=>['id'=>$p->id,'semesters'=>collect($p->strategy_data['semesters']??[])->map(fn($s)=>$s+['bs'=>\App\Services\Planning\Dates::bs($s['starts_on']).' – '.\App\Services\Planning\Dates::bs($s['ends_on'])])->all(), 'rows'=>$p->items->map(fn($i)=>['id'=>$i->id,'title'=>$i->title,'strategy'=>$i->details['strategy']??[]])->values()])->values();
@endphp
<form method="POST" id="cmt-form" class="space-y-4" action="{{ $document->exists?route('planning.update',$document):route('planning.store') }}">
@csrf @if($document->exists) @method('PUT') @endif
<input type="hidden" name="type" value="cmt_goals"><input type="hidden" name="version" value="{{ $document->version }}">
<div class="kcard p-5 grid md:grid-cols-2 gap-4">
<div><label>Approved Strategic Plan *</label><select id="cmt-parent" name="parent_id" required><option value="">Choose a Strategic Plan</option>@foreach($strategies as $p)<option value="{{ $p->id }}" @selected(old('parent_id',$document->parent_id)===$p->id)>{{ $p->title }} · Revision {{ $p->revision }}</option>@endforeach</select></div>
<div><label>Semester *</label><select id="cmt-period" name="semester_index" required><option value="">Choose a Strategic Plan first</option></select><p id="cmt-period-hint" class="text-xs text-gray-500 mt-1"></p></div>
<div><label>Title *</label><input required name="title" value="{{ old('title',$document->title) }}" placeholder="CMT Goals · April–September 2026"></div>
<div><label>Approval chain</label><p class="py-2 text-sm">{{ $chains->firstWhere('id',\App\Services\Planning\Governance::defaultChain('cmt_goals'))?->name ?? 'Not configured' }}</p>@can('planning.setup')<a class="text-sm text-teal-700" href="{{ route('planning.setup') }}">Manage in Form settings →</a>@endcan</div>
<div class="md:col-span-2"><label>Semester theme</label><textarea name="description" rows="2">{{ old('description',$document->description) }}</textarea></div>
</div>
<div class="kcard overflow-x-auto"><table class="w-full text-sm" style="min-width:850px"><thead><tr><th>Strategic priority</th><th>Strategic Goal</th><th style="width:25%">CMT semester goal</th><th style="min-width:170px">Accountable owner</th><th style="min-width:170px">Due date (AD)</th><th></th></tr></thead><tbody id="cmt-groups"></tbody></table></div>
<div class="flex gap-3"><button type="button" id="cmt-add" class="btn-secondary">+ Add goal</button><button class="btn-primary">Save draft</button></div>
<p class="text-sm text-gray-500">Save your draft, then submit the complete semester goals for approval. Tasks listed here are proposals for the later semester plan.</p>
</form>
<script type="application/json" id="cmt-data">{!! json_encode(['groups'=>$groups,'sources'=>$sourceData,'users'=>$users,'selectedSemester'=>old('semester_index'),'selectedPeriod'=>$document->period],JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) !!}</script>
<script src="{{ asset('js/cmt-goals.js') }}" defer></script>
@endsection
