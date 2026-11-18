@php
    if (($relatedApproval ?? false) && $approvalRecord && !\App\Support\RecordVisibility::apply($approvalRecord->newQuery(), auth()->user())->whereKey($approvalRecord->getKey())->exists()) $approvalRecord = null;
    $history = $approvalRecord?->approvalActions?->sortBy('acted_at') ?? collect();
    $chain = $approvalRecord?->approvalChain;
    $submitted = $history->firstWhere('decision', 'submitted')?->acted_at;
    $last = $history->last()?->acted_at;
    $state = $approvalRecord?->statusLabel() ?? 'No approval chain';
@endphp
<div class="approval-toolbar"><button type="button" onclick="document.getElementById('record-approval').showModal()" aria-haspopup="dialog">✓ {{ $state }} · Approval timeline</button></div>
<dialog id="record-approval" class="approval-dialog" aria-labelledby="approval-heading">
    <header><h2 id="approval-heading" class="font-semibold text-lg">Approval timeline</h2><button type="button" onclick="this.closest('dialog').close()" aria-label="Close approval timeline">✕</button></header>
    <p class="text-sm mb-4">{{ $approvalLabel }}</p>
    <div class="bg-white border rounded-lg p-4 grid grid-cols-2 gap-4 text-sm">
        <div><p class="text-gray-500">Status</p><strong>{{ $state }}</strong></div>
        <div><p class="text-gray-500">{{ in_array($approvalRecord?->status, ['approved','sent_to_vendor','paid','rejected']) ? 'Recorded duration' : 'Elapsed since submission' }}</p><strong>{{ $submitted ? $submitted->diffForHumans(in_array($approvalRecord?->status, ['approved','sent_to_vendor','paid','rejected']) ? ($last ?? now()) : now(), true) : 'Not submitted' }}</strong></div>
    </div>
    @if($chain)
        <details class="mt-4 bg-white border rounded-lg p-4"><summary class="cursor-pointer font-semibold">{{ $chain->name }} · Chain members</summary>
        @foreach($chain->verifiers as $member)<p class="text-sm mt-2">Verifier {{ $loop->iteration }} · {{ $member->name }}</p>@endforeach
        @foreach($chain->approvers as $member)<p class="text-sm mt-2">Approver {{ $loop->iteration }} · {{ $member->name }}</p>@endforeach
        </details>
        @if($pending = app(\App\Services\ApprovalService::class)->pendingStepLabel($approvalRecord))<p class="mt-4 text-sm font-semibold text-amber-800">Pending: {{ $pending }}</p>@endif
    @endif
    <div class="approval-events">
    @forelse($history as $action)
        <article class="approval-event">
            <p class="font-semibold">{{ ucfirst(str_replace('_',' ',$action->decision)) }}</p>
            <p class="text-xs text-gray-500 mt-1">{{ $action->acted_at?->format('d M Y, h:i A') }} · {{ $action->layerLabel() }}</p>
            <p class="mt-3 font-medium">{{ $action->actor?->name ?? 'Former user' }}</p>
            @if($action->note)<p class="mt-2 text-sm bg-gray-50 rounded p-2 whitespace-pre-line">{{ $action->note }}</p>@endif
            @if(!$loop->first && $action->acted_at && $history->values()[$loop->index-1]->acted_at)<p class="text-xs text-gray-500 mt-3">Time since previous action: {{ $history->values()[$loop->index-1]->acted_at->diffForHumans($action->acted_at,true) }}</p>@endif
        </article>
    @empty
        <p class="text-sm text-gray-500 my-5">{{ $approvalRecord ? 'No approval actions recorded yet.' : 'This record has no linked approval history yet.' }}</p>
    @endforelse
    </div>
</dialog>
