@extends('layouts.app')
@section('title','Planning')
@section('page-title','Planning')
@section('content')
<style>.planning input:not([type=checkbox]),.planning select,.planning textarea{border:1px solid #cbd5e1;border-radius:8px;padding:9px 12px;width:100%;font-size:14px}.planning label{display:block;font-size:12px;color:#64748b;margin-bottom:5px}.planning th,.planning td{padding:12px;text-align:left;border-bottom:1px solid #e2e8f0}.planning th{font-size:12px;color:#64748b}.planning a{color:#0f766e}.planning .nav-active{background:#102444;color:white}.planning details>summary{cursor:pointer}.planning .task-card{border:1px solid #e2e8f0;background:white;padding:14px;border-radius:10px}
.goal-sheet,.activity-sheet{overflow:auto;max-height:70vh;position:relative}
.goal-sheet>table{width:max-content;min-width:100%;border-collapse:separate;border-spacing:0}
.planning .goal-sheet>table>thead>tr>th{position:sticky;top:0;background:#f1f5f9;z-index:2;white-space:nowrap}
.planning .goal-sheet>table>tbody>tr>td{min-width:230px;width:230px;vertical-align:top;border-right:1px solid #e2e8f0;padding:10px}
.planning .goal-sheet>table>tbody>tr>td:nth-child(3){min-width:300px;position:sticky;left:0;background:white;z-index:1;border-right:2px solid #cbd5e1}
.planning .goal-sheet>table>tbody>tr>td:nth-child(6){min-width:650px}
.planning .goal-sheet>table>tbody>tr>td:last-child{min-width:85px;width:85px}
.planning .goal-sheet>table>thead>tr>th:nth-child(3){left:0;z-index:3;min-width:300px}
.goal-sheet textarea{min-height:90px;resize:vertical}.goal-sheet [data-mention-suggestions]{position:relative}
.planning .goal-sheet td .border-t{border:0;padding-top:0}.goal-sheet [data-cmt-context] td{position:static!important}
.activity-sheet>div{display:flex;flex-wrap:nowrap;width:max-content;gap:0;padding:0;margin:0;border-radius:0;align-items:stretch}
.activity-sheet>div>div{flex:0 0 230px;width:230px;padding:10px;border-right:1px solid #e2e8f0}
.activity-sheet>div>div:first-child{flex-basis:300px;width:300px;position:sticky;left:0;background:white;z-index:1;border-right:2px solid #cbd5e1}
.activity-sheet>div>button{width:100px;padding:10px}.activity-sheet>div>div>label{background:#f1f5f9;padding:6px;min-height:30px}
.activity-sheet textarea{min-height:90px}.activity-sheet details>div{display:block}.activity-sheet details>div>div{margin-top:8px}
</style>
<div class="planning space-y-4">
<nav class="kcard p-3 flex flex-wrap gap-2 text-sm">
<a class="px-3 py-2 rounded-lg {{ request()->routeIs('planning.overview','planning.dashboard')?'nav-active':'' }}" href="{{ route('planning.overview') }}">Overview</a>
@foreach(\App\Services\Planning\Access::TYPES as $key=>$label)
@can('planning.'.$key.'_view')<a class="px-3 py-2 rounded-lg {{ request('type',isset($document)?$document->type:'')===$key && request()->routeIs('planning.index','planning.show','planning.edit','planning.create') ? 'nav-active':'' }}" href="{{ route('planning.index',['type'=>$key]) }}">{{ $label }}</a>@endcan
@endforeach
@can('planning.support')<a class="px-3 py-2" href="{{ route('planning.support') }}">Support requests</a>@endcan
<a class="px-3 py-2" href="{{ route('planning.tasks') }}">Team work</a><a class="px-3 py-2" href="{{ route('planning.tasks',['mode'=>'calendar']) }}">My calendar</a>
@can('planning.setup')<a class="px-3 py-2" href="{{ route('planning.setup') }}">Setup</a>@endcan
</nav>
@yield('planning-content')
<script src="{{ asset('js/planning-dates.js') }}" defer></script>
</div>
@endsection
