@extends('planning.layout')
@section('planning-content')
<div class="grid md:grid-cols-4 gap-4">@foreach($cards as $card)<a href="{{ $card['url'] }}" class="kcard p-5"><p class="text-sm">{{ $card['label'] }}</p><p class="text-2xl font-semibold mt-2">{{ $card['count'] }}</p><p class="text-xs text-gray-500 mt-2">{{ $card['approved'] }} approved</p></a>@endforeach</div>
<div class="kcard p-5"><div class="flex justify-between"><h2 class="font-semibold">My work · {{ $taskCount }} open</h2><a href="{{ route('planning.tasks',['mode'=>'calendar']) }}">Open calendar →</a></div><p class="text-sm text-amber-700 my-2">{{ $overdue }} overdue</p>@forelse($upcoming as $task)<div class="py-3 border-b text-sm">{{ $task->title }}<span class="block text-gray-500 text-xs">{{ $task->due_on ?? 'No due date' }} · {{ \App\Services\Planning\Dates::bs($task->due_on) }}</span></div>@empty<p class="text-sm text-gray-500">No outstanding tasks assigned to you.</p>@endforelse</div>
@endsection
