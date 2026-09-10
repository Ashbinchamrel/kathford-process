@extends('layouts.app')
@section('title','Planning')
@section('page-title','Planning')
@section('content')
<style>.planning input:not([type=checkbox]),.planning select,.planning textarea{border:1px solid #cbd5e1;border-radius:8px;padding:9px 12px;width:100%;font-size:14px}.planning label{display:block;font-size:12px;color:#64748b;margin-bottom:5px}.planning th,.planning td{padding:12px;text-align:left;border-bottom:1px solid #e2e8f0}.planning th{font-size:12px;color:#64748b}.planning a{color:#0f766e}.planning .nav-active{background:#102444;color:white}.planning details>summary{cursor:pointer}.planning .task-card{border:1px solid #e2e8f0;background:white;padding:14px;border-radius:10px}</style>
<div class="planning space-y-4">
<nav class="kcard p-3 flex flex-wrap gap-2 text-sm">
@foreach(\App\Services\Planning\Access::TYPES as $key=>$label)
@can('planning.'.$key.'_view')<a class="px-3 py-2 rounded-lg {{ request('type','strategy')===$key && !request()->routeIs('planning.tasks','planning.setup','planning.support') ? 'nav-active':'' }}" href="{{ route('planning.index',['type'=>$key]) }}">{{ $label }}</a>@endcan
@endforeach
@can('planning.support')<a class="px-3 py-2" href="{{ route('planning.support') }}">Support requests</a>@endcan
<a class="px-3 py-2" href="{{ route('planning.tasks') }}">Team work</a><a class="px-3 py-2" href="{{ route('planning.tasks',['mode'=>'calendar']) }}">My calendar</a>
@can('planning.setup')<a class="px-3 py-2" href="{{ route('planning.setup') }}">Setup</a>@endcan
</nav>
@yield('planning-content')
</div>
@endsection
