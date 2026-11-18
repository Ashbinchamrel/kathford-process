@extends('planning.layout')
@section('planning-content')
@php
$groups=old('cmt_groups', \App\Services\Planning\CmtGoals::groups($items));
$departmentGoals=$document->type==='goals';
$legacyParent=$departmentGoals && $document->exists && $document->parent?->type==='cmt_goals';
$parentLabel=$legacyParent?'CMT Goals':'Strategic Plan';
$strategies=$parents->filter(fn($p)=>$p->type==='strategy' || ($legacyParent && $p->id===$document->parent_id))->whereIn('status',$document->previous_id?['approved','superseded']:['approved']);
$sourceData=$strategies->map(fn($p)=>\App\Services\Planning\CmtGoals::sourceData($p))->values();
@endphp
<form method="POST" id="cmt-form" class="space-y-4" action="{{ $document->exists?route('planning.update',$document):route('planning.store') }}">
@csrf @if($document->exists) @method('PUT') @endif
<input type="hidden" name="type" value="{{ $document->type }}"><input type="hidden" name="version" value="{{ $document->version }}">
<div class="kcard p-5 grid md:grid-cols-2 gap-4">
<div><label>Approved {{ $parentLabel }} *</label><select id="cmt-parent" name="parent_id" required><option value="">Choose {{ $parentLabel }}</option>@foreach($strategies as $p)<option value="{{ $p->id }}" @selected(old('parent_id',$document->parent_id)===$p->id)>{{ $p->title }} · Revision {{ $p->revision }}</option>@endforeach</select></div>
<div><label>Semester *</label><select id="cmt-period" name="semester_index" required><option value="">Choose {{ $parentLabel }} first</option></select><p id="cmt-period-hint" class="text-xs text-gray-500 mt-1 whitespace-pre-line"></p></div>
@if($departmentGoals)<div><label>Department *</label><select name="department_id" required>@foreach($departments as $d)<option value="{{ $d->id }}" @selected(old('department_id',$document->department_id??auth()->user()->department_id)===$d->id)>{{ $d->name }}</option>@endforeach</select></div>@endif
<div class="md:col-span-2"><label>Title *</label><input required name="title" value="{{ old('title',$document->title) }}" placeholder="{{ $departmentGoals?'Department':'CMT' }} Goals · Semester"></div>
<div class="md:col-span-2"><label>Semester theme</label><textarea name="description" rows="2">{{ old('description',$document->description) }}</textarea></div>
</div>
<p class="text-xs text-gray-500">One row per goal. Scroll sideways for all columns; use Tab to move between fields.</p>
<div class="kcard goal-sheet" tabindex="0" aria-label="Goal worksheet"><table><thead><tr><th>Strategic priority</th><th>Strategic Goal</th><th>{{ $departmentGoals?'Department':'CMT' }} semester goal</th><th>Accountable owner</th><th>Due date (AD / BS)</th><th>Key results · metric · baseline · target</th><th>Definition of Done</th><th>Deliverables</th><th>Monthly milestones</th><th>Proposed tasks</th><th>Notes on strategic target</th><th>Supporting owners</th><th>Actions</th></tr></thead><tbody id="cmt-groups"></tbody></table></div>
<div class="flex gap-3"><button type="button" id="cmt-add" class="btn-secondary">+ Add goal</button><button class="btn-primary">Save draft</button></div>
<p class="text-sm text-gray-500">Save your draft, then submit the complete semester goals for approval. Tasks listed here are proposals for the later semester plan.</p>
</form>
<script type="application/json" id="cmt-data">{!! json_encode(['cmtContext'=>$departmentGoals?\App\Services\Planning\CmtGoals::approvedContext($strategies->where('type','strategy')->pluck('id')):[],'departmentGoals'=>$departmentGoals,'goalLabel'=>($departmentGoals?'Department':'CMT').' semester goal','groups'=>$groups,'sources'=>$sourceData,'users'=>$users,'selectedSemester'=>old('semester_index'),'selectedPeriod'=>$document->period],JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) !!}</script>
<script src="{{ asset('js/cmt-goals.js') }}" defer></script>
@endsection
