@extends('planning.layout')
@section('planning-content')
@php
$matrix=old('matrix',$matrix); $rows=old('rows',$rows);
$initial=['matrix'=>$matrix,'rows'=>array_values($rows)];
@endphp
<form method="POST" action="{{ $document->exists?route('planning.update',$document):route('planning.store') }}" class="space-y-4" x-data="strategyEditor(@js($initial))">
@csrf @if($document->exists) @method('PUT') @endif
<input type="hidden" name="type" value="strategy"><input type="hidden" name="version" value="{{ $document->version }}">
<div class="flex justify-between items-center"><h2 class="font-semibold">{{ $document->exists?'Edit':'New' }} Strategic Plan · Board</h2><button class="btn-primary">Save draft</button></div>
<div class="kcard p-5 grid md:grid-cols-2 gap-4">
<div><label for="strategy-title">Plan title *</label><input id="strategy-title" name="title" required maxlength="200" value="{{ old('title',$document->title) }}"></div>
<div><label>Approval chain *</label><select name="approval_chain_id" required><option value="">Choose approval chain</option>@foreach($chains as $chain)<option value="{{ $chain->id }}" @selected(old('approval_chain_id',$document->approval_chain_id)===$chain->id)>{{ $chain->name }}</option>@endforeach</select></div>
<div><label>Long-term period starts (AD) *</label><input type="date" name="matrix[starts_on]" x-model="matrix.starts_on" required></div><div><label>Long-term period ends (AD) *</label><input type="date" name="matrix[ends_on]" x-model="matrix.ends_on" required></div>
<div class="md:col-span-2"><label>Plan context</label><textarea name="description" rows="2">{{ old('description',$document->description) }}</textarea></div>
@if(isset($document->strategy_data['review_reason']))<p class="md:col-span-2 text-sm text-amber-800">Semester review · {{ $document->strategy_data['review_reason'] }}. The current approved revision remains in use until this revision is approved.</p>@endif
</div>
<div class="kcard p-5 space-y-3"><div class="flex justify-between"><h3 class="font-semibold">Semester timeline</h3><span class="text-xs text-gray-500">Four semesters for a two-year plan; adjust as needed.</span></div>
<template x-for="(semester,s) in matrix.semesters" :key="s"><div class="grid md:grid-cols-3 gap-3 border-b pb-3"><div><label x-text="'Semester '+(s+1)+' label *'"></label><input :name="`matrix[semesters][${s}][label]`" x-model="semester.label" required placeholder="April 2026–September 2026"></div><div><label>Starts (AD) *</label><input type="date" :name="`matrix[semesters][${s}][starts_on]`" x-model="semester.starts_on" required></div><div><label>Ends (AD) *</label><input type="date" :name="`matrix[semesters][${s}][ends_on]`" x-model="semester.ends_on" required></div></div></template>
<div class="flex gap-4 text-sm"><button type="button" class="text-teal-700" @click="addSemester()" x-show="matrix.semesters.length<12">+ Add semester</button><button type="button" class="text-red-600" @click="removeSemester()" x-show="matrix.semesters.length>1">Remove last semester</button></div></div>
<div class="kcard overflow-hidden"><div class="p-4 flex justify-between"><h3 class="font-semibold">Strategic priorities · Goals · Strategy · Target KPIs</h3><span class="text-xs text-gray-500">Scroll across to see all semesters →</span></div>
<div class="overflow-x-auto"><table class="text-sm strategy-matrix"><thead><tr><th>SN</th><th>Strategic priority</th><th>Goal</th><th>Strategy</th><template x-for="(semester,s) in matrix.semesters" :key="s"><th x-text="semester.label || 'Semester '+(s+1)"></th></template><th></th></tr></thead><tbody>
<template x-for="(row,i) in rows" :key="row._key"><tr><td x-text="serial(i)"></td><td><textarea :name="`rows[${i}][priority]`" x-model="row.priority" rows="5" required aria-label="Strategic priority"></textarea></td><td><textarea :name="`rows[${i}][goal]`" x-model="row.goal" rows="5" aria-label="Goal"></textarea></td><td><textarea :name="`rows[${i}][strategy]`" x-model="row.strategy" rows="5" aria-label="Strategy"></textarea></td><template x-for="(semester,s) in matrix.semesters" :key="s"><td><textarea :name="`rows[${i}][targets][${s}]`" x-model="row.targets[s]" rows="5" :aria-label="'Target KPIs for '+semester.label" placeholder="Target KPI and expected result"></textarea></td></template><td><button type="button" class="text-red-600" @click="rows.splice(i,1)" x-show="rows.length>1">Remove</button></td></tr></template>
</tbody></table></div><div class="p-4 flex gap-4"><button type="button" class="text-teal-700" @click="addRow(false)">+ Add priority</button><button type="button" class="text-teal-700" @click="addRow(true)">+ Add goal to last priority</button></div></div>
<button class="btn-primary">Save draft</button><a class="ml-3" href="{{ route('planning.index',['type'=>'strategy']) }}">Cancel</a>
</form>
<style>.strategy-matrix{width:max-content;min-width:100%}.strategy-matrix td{vertical-align:top}.strategy-matrix th:not(:first-child):not(:last-child),.strategy-matrix td:not(:first-child):not(:last-child){min-width:240px;max-width:300px}.strategy-matrix th{background:#f8fafc}.strategy-matrix textarea{min-width:220px}</style>
<script>
function strategyEditor(initial){let seq=0;return {...initial,init(){this.rows=this.rows.map(r=>({...r,_key:++seq}));},serial(i){let n=0,last=null;for(let j=0;j<=i;j++){if(this.rows[j].priority!==last){n++;last=this.rows[j].priority;}}return n;},addRow(same){this.rows.push({_key:++seq,priority:same?(this.rows.at(-1)?.priority||''):'',goal:'',strategy:'',targets:this.matrix.semesters.map(()=> '')});},addSemester(){if(this.matrix.semesters.length>=12)return;this.matrix.semesters.push({label:'',starts_on:'',ends_on:''});this.rows.forEach(r=>r.targets.push(''));},removeSemester(){if(this.matrix.semesters.length<=1)return;if(this.rows.some(r=>r.targets.at(-1))&&!confirm('Remove the last semester and its target values?'))return;this.matrix.semesters.pop();this.rows.forEach(r=>r.targets.pop());}};}
</script>
@endsection
