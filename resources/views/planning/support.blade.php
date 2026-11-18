@extends('planning.layout')
@section('planning-content')
<div class="kcard overflow-hidden">
<x-super-admin-bulk-delete module="planning_support" route="planning.bulk-delete" />
<div class="p-4">
<h2 class="font-semibold mb-3">Support requests</h2>
@if(auth()->user()->isSuperAdmin() && $requests->isNotEmpty())<label class="flex items-center gap-2 text-xs text-gray-500 mb-2"><input type="checkbox" aria-label="Select all requests" data-bulk-delete-toggle="planning_support"> Select all</label>@endif
@forelse($requests as $support)
<details class="border-b p-3">
<summary class="flex items-center gap-2">@if(auth()->user()->isSuperAdmin())<input type="checkbox" value="{{ $support->id }}" aria-label="Select request" data-bulk-delete-record="planning_support" onclick="event.stopPropagation()">@endif<span>{{ $support->item->title }} · {{ ucfirst($support->status) }}</span></summary>
<p class="text-sm my-3">{{ $support->item->description }}</p>
<p class="text-sm">{{ $support->item->requires_budget?'Budget assessment required':'Support without a budget request' }}</p>
<form method="POST" action="{{ route('planning.support.respond',$support) }}" class="space-y-3 mt-3">@csrf<select name="status">@foreach(['requested','accepted','completed','declined'] as $status)<option @selected($support->status===$status)>{{ $status }}</option>@endforeach</select><textarea name="response" required placeholder="Response / next steps">{{ $support->response }}</textarea><button class="btn-primary">Save response</button></form>
</details>
@empty
<p>No requests assigned to your department.</p>
@endforelse
{{ $requests->links() }}
</div>
</div>
@endsection
