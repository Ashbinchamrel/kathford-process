@extends('layouts.app')
@section('title', $form->form_number)
@section('page-title', $form->form_number)

@section('content')
<div class="max-w-4xl space-y-5" x-data="{ previewUrl: null, previewName: '' }">

    {{-- ══ Success / Error flash ══ --}}
    @if(session('success'))
    <div class="rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700 flex items-center gap-2">
        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        {{ session('success') }}
    </div>
    @endif
    @if(session('error'))
    <div class="rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">{{ session('error') }}</div>
    @endif

    @if($errors->any())<div class="rounded-xl bg-red-50 p-4 text-sm text-red-800">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
    {{-- ══ Status Banner ══ --}}
    @php
        $bannerCfg = [
            'draft'               => ['bg-gray-50 border-gray-200 text-gray-600',   'Draft',              'Complete your form and submit it for verification.'],
            'pending_verification'=> ['bg-blue-50 border-blue-200 text-blue-700',   'Awaiting Verification','A verifier will review this form shortly.'],
            'verified'            => ['bg-teal-50 border-teal-200 text-teal-700',   'Verified',           'This form has been verified and is awaiting final approval.'],
            'pending_approval'    => ['bg-yellow-50 border-yellow-200 text-yellow-700','Awaiting Approval', 'An approver will make the final decision shortly.'],
            'approved'            => ['bg-green-50 border-green-200 text-green-700','Approved ✓',         'This form is approved. You can now create an RFQ.'],
            'rejected'            => ['bg-red-50 border-red-200 text-red-700',      'Rejected',           'This form was rejected. Please review the remarks and resubmit.'],
        ];
        [$bannerClass, $bannerTitle, $bannerMsg] = $bannerCfg[$form->status] ?? ['bg-gray-50 border-gray-200 text-gray-600', ucfirst($form->status), ''];
    @endphp
    <div class="rounded-xl border px-5 py-4 {{ $bannerClass }}">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide opacity-60 mb-0.5">Status</p>
                <p class="font-bold text-lg">{{ $bannerTitle }}</p>
                @if($bannerMsg)<p class="text-sm mt-0.5 opacity-80">{{ $bannerMsg }}</p>@endif
            </div>
            <div class="text-right flex-shrink-0">
                <p class="text-xs font-semibold uppercase tracking-wide opacity-60">Form Number</p>
                <p class="font-mono font-bold text-lg">{{ $form->form_number }}</p>
            </div>
        </div>
    </div>

    {{-- ══ Details Card ══ --}}
    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <h2 class="font-semibold text-gray-800 mb-4 text-sm uppercase tracking-wide">Form Details</h2>
        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-4 text-sm">
            <div class="sm:col-span-2">
                <dt class="text-xs text-gray-400 font-semibold uppercase mb-1">Activity Name</dt>
                <dd class="text-gray-900 font-medium text-base">{{ $form->activity_name }}</dd>
            </div>
            <div>
                <dt class="text-xs text-gray-400 font-semibold uppercase mb-1">Requested By</dt>
                <dd class="text-gray-700">{{ $form->creator?->name ?? '—' }}</dd>
            </div>
            <div>
                <dt class="text-xs text-gray-400 font-semibold uppercase mb-1">Department</dt>
                <dd class="text-gray-700">{{ $form->department?->name ?? '—' }}</dd>
            </div>
            <div>
                <dt class="text-xs text-gray-400 font-semibold uppercase mb-1">Category</dt>
                <dd class="text-gray-700">{{ $form->category?->name ?? '—' }}</dd>
            </div>
            <div>
                <dt class="text-xs text-gray-400 font-semibold uppercase mb-1">Required By (Deadline)</dt>
                <dd class="text-gray-700">{{ $form->deadline_date ? $form->deadline_date->format('d M Y') : '—' }}</dd>
            </div>
            @if(true)
            <div class="sm:col-span-2">
                <dt class="text-xs text-gray-400 font-semibold uppercase mb-1">Remarks</dt>
                <dd class="text-gray-700">{{ $form->remarks ?? '—' }}</dd>
            </div>
            @endif
            @if($form->unplanned_reason)
            <div class="sm:col-span-2">
                <dt class="text-xs text-gray-400 font-semibold uppercase mb-1">Reason (Unplanned)</dt>
                <dd class="text-gray-700">{{ $form->unplanned_reason }}</dd>
            </div>
            @endif
        </dl>
    </div>

    @if($form->budget)
    @php
        $reservedBeforeThisForm = $form->budget->reservedAmount($form->id);
        $remainingBeforeThisForm = $form->budget->remainingAmount($form->id);
    @endphp
    @php
        $variance = max(0, (float) $form->total_estimated_amount - $remainingBeforeThisForm);
    @endphp
    <div class="bg-white rounded-xl border {{ $variance > 0 ? 'border-amber-300' : 'border-teal-200' }} p-6">
        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3 mb-4">
            <div>
                <h2 class="font-semibold text-gray-800 text-sm uppercase tracking-wide">Department Budget</h2>
                <p class="text-sm text-gray-600 mt-1">{{ $form->budget->activity_title }} · FY {{ $form->budget->fiscal_year }}</p>
            </div>
            @if($variance > 0)
                <span class="badge badge-pending">Budget variance · reference only</span>
            @else
                <span class="badge badge-approved">Budget availability shown</span>
            @endif
        </div>
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 text-sm">
            <div><p class="text-xs uppercase font-semibold text-gray-400">Allocation</p><p class="font-semibold mt-1">Rs {{ number_format($form->budget->allocated_amount, 2) }}</p></div>
            <div><p class="text-xs uppercase font-semibold text-gray-400">Current commitments</p><p class="font-semibold mt-1">Rs {{ number_format($reservedBeforeThisForm, 2) }}</p></div>
            <div><p class="text-xs uppercase font-semibold text-gray-400">Remaining balance</p><p class="font-semibold mt-1">Rs {{ number_format($remainingBeforeThisForm, 2) }}</p></div>
            <div><p class="text-xs uppercase font-semibold text-gray-400">This request</p><p class="font-semibold mt-1">Rs {{ number_format($form->total_estimated_amount, 2) }}</p></div>
        </div>
        @if($variance > 0)
            <p class="mt-4 rounded-lg bg-amber-50 border border-amber-200 px-3 py-2 text-sm text-amber-800">This request is Rs {{ number_format($variance, 2) }} above the current remaining balance. It is recorded as a forecast only; the normal approval process is unchanged.</p>
        @endif
    </div>
    @endif

    {{-- ══ Approval Chain Members ══ --}}
    @if($form->approvalChain)
    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-2"><h2 class="font-semibold text-gray-800 text-sm uppercase tracking-wide">Approval Chain — {{ $form->approvalChain->name }}</h2>@if($step = app(\App\Services\ApprovalService::class)->pendingStepLabel($form))<span class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-800">Pending: {{ $step }}</span>@endif</div>
        @php
            $approvalService=app(\App\Services\ApprovalService::class);
            $remainingVerifiers=$form->isPendingVerification() ? $form->approvalChain->verifiers->slice($approvalService->currentVerifierLayer($form)-1) : collect();
            $remainingApprovers=$form->isPendingVerification() ? $form->approvalChain->approvers : ($form->isPendingApproval() ? $form->approvalChain->approvers->slice($approvalService->currentApproverLayer($form)-1) : collect());
        @endphp
        @if($remainingVerifiers->isNotEmpty() || $remainingApprovers->isNotEmpty())<div class="mb-4 rounded-lg bg-amber-50 p-3 text-sm"><strong>Remaining authorities:</strong> {{ $remainingVerifiers->merge($remainingApprovers)->pluck('name')->implode(' → ') }}</div>@endif
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
            <div>
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Creator (Layer 1)</p>
                <p class="text-sm font-medium text-gray-800">{{ $form->creator?->name ?? 'Former user' }}</p>
                <p class="text-xs text-gray-500">Created {{ $form->created_at?->format('d M Y H:i') }}</p>
            </div>
            <div>
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Verifiers (Layer 2)</p>
                @forelse($form->approvalChain->verifiers as $v)
                <div class="flex items-center gap-2 mb-2">
                    <div class="w-7 h-7 rounded-full flex items-center justify-center text-white text-xs font-bold flex-shrink-0" style="background:#1C3557;">{{ substr($v->name,0,1) }}</div>
                    <div>
                        <p class="text-sm font-medium text-gray-800 leading-tight">Verifier layer {{ $loop->iteration }} · {{ $v->name }}</p>
                        @php
$authorityActions = $form->approvalActions->where('actor_id', $v->id)->where('decision', '!=', 'submitted');
@endphp
                        @forelse($authorityActions as $authorityAction)
                            <p class="text-xs text-gray-600">{{ ucfirst(str_replace('_', ' ', $authorityAction->decision)) }} · {{ $authorityAction->acted_at?->format('d M Y H:i') }}</p>
                        @empty
                            <p class="text-xs text-gray-400">Not yet completed</p>
                        @endforelse
                    </div>
                </div>
                @empty
                <p class="text-xs text-gray-400">No verifiers assigned</p>
                @endforelse
            </div>
            <div>
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Approvers (Layer 3)</p>
                @forelse($form->approvalChain->approvers as $a)
                <div class="flex items-center gap-2 mb-2">
                    <div class="w-7 h-7 rounded-full flex items-center justify-center text-white text-xs font-bold flex-shrink-0" style="background:#00A99D;">{{ substr($a->name,0,1) }}</div>
                    <div>
                        <p class="text-sm font-medium text-gray-800 leading-tight">Approver layer {{ $loop->iteration }} · {{ $a->name }}</p>
                        @php
$authorityActions = $form->approvalActions->where('actor_id', $a->id)->where('decision', '!=', 'submitted');
@endphp
                        @forelse($authorityActions as $authorityAction)
                            <p class="text-xs text-gray-600">{{ ucfirst(str_replace('_', ' ', $authorityAction->decision)) }} · {{ $authorityAction->acted_at?->format('d M Y H:i') }}</p>
                        @empty
                            <p class="text-xs text-gray-400">Not yet completed</p>
                        @endforelse
                    </div>
                </div>
                @empty
                <p class="text-xs text-gray-400">No approvers assigned</p>
                @endforelse
            </div>
        </div>
    </div>
    @endif

    {{-- ══ Budget / Line Items ══ --}}
    @if($form->lineItems->isNotEmpty())
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 font-semibold text-gray-800">Budget Items</div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">#</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Item</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Unit</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Qty</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Rate (Rs)</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Amount (Rs)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach($form->lineItems as $idx => $item)
                    <tr class="hover:bg-gray-50">
                        <td class="px-5 py-3 text-gray-400">{{ $idx + 1 }}</td>
                        <td class="px-5 py-3 text-gray-800 font-medium">{{ $item->item_name }}</td>
                        <td class="px-5 py-3 text-gray-500">{{ $item->unit ?? '—' }}</td>
                        <td class="px-5 py-3 text-right font-mono">{{ $item->quantity }}</td>
                        <td class="px-5 py-3 text-right font-mono">{{ number_format($item->rate, 2) }}</td>
                        <td class="px-5 py-3 text-right font-mono font-semibold">{{ number_format($item->amount, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-gray-50">
                    <tr>
                        <td colspan="5" class="px-5 py-3 text-right text-sm font-bold text-gray-700">Estimated Total</td>
                        <td class="px-5 py-3 text-right font-mono font-bold text-gray-900 text-base">Rs {{ number_format($form->total_estimated_amount, 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
    @endif

    {{-- ══ Attachments ══ --}}
    @if($form->attachments->isNotEmpty())
    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <h2 class="font-semibold text-gray-800 mb-3 text-sm uppercase tracking-wide">Attachments</h2>
        <ul class="space-y-2">
            @foreach($form->attachments as $att)
            <li class="flex items-center gap-3 text-sm">
                <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                @if($att->external_link)
                <a href="{{ $att->external_link }}" class="text-teal-600 hover:underline flex-1" target="_blank" rel="noopener noreferrer">{{ $att->original_name ?? $att->external_link }}</a>
                @else
                @php
$ext = strtolower(pathinfo($att->original_name ?? '', PATHINFO_EXTENSION));
@endphp
                @if(in_array($ext, ['png','jpg','jpeg','gif','webp','svg']))
                <button type="button"
                    @click="previewUrl='{{ route('activity-forms.attachment.download', [$form, $att]) }}'; previewName='{{ addslashes($att->original_name) }}'"
                    class="text-teal-600 hover:underline flex-1 text-left">{{ $att->original_name }}</button>
                @else
                @can('activity_forms.view')
<a href="{{ route('activity-forms.attachment.download', [$form, $att]) }}" class="text-teal-600 hover:underline flex-1" target="_blank" rel="noopener noreferrer">{{ $att->original_name }}</a>
@endcan
                @endif
                @endif
                <span class="text-gray-400 text-xs">{{ $att->humanSize() }}</span>
                @if($form->isEditable() && $form->creator_id === auth()->id())
                @can('activity_forms.edit')
<form method="POST" action="{{ route('activity-forms.attachment.delete', [$form, $att]) }}" class="inline">
                    @csrf
<input type="hidden" name="_fiscal_year_id" value="{{ $workingFiscalYear?->id }}"> @method('DELETE')
                    <button type="submit" class="text-red-400 hover:text-red-600 text-xs">Remove</button>
                </form>
@endcan
                @endif
            </li>
            @endforeach
        </ul>
    </div>
    @endif

    {{-- ══ Approval Timeline ══ --}}
    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <h2 class="font-semibold text-gray-800 mb-4 text-sm uppercase tracking-wide">Approval History</h2>
        @if($form->approvalActions->isEmpty())
            <p class="text-sm text-gray-400">Not yet submitted for approval.</p>
        @else
        <ol class="relative border-l-2 border-gray-100 space-y-5 ml-3">
            @foreach($form->approvalActions as $action)
            @php
                $dot = [
                    'approved'          => 'bg-green-400',
                    'modified_approved' => 'bg-teal-400',
                    'rejected'          => 'bg-red-400',
                    'submitted'         => 'bg-blue-400',
                ][$action->decision] ?? 'bg-gray-300';
            @endphp
            <li class="ml-5">
                <span class="absolute -left-1.5 mt-1 w-3 h-3 rounded-full {{ $dot }} ring-2 ring-white"></span>
                <p class="text-xs text-gray-400">
                    {{ $action->acted_at ? \Carbon\Carbon::parse($action->acted_at)->format('d M Y H:i') : '' }}
                    · {{ $action->layerLabel() }}
                </p>
                <p class="text-sm font-semibold text-gray-800">
                    {{ $action->actor?->name }}
                    <span class="font-normal text-gray-600">—
                        {{ $action->decision === 'modified_approved' ? 'Modified & Approved' : ucfirst($action->decision) }}
                    </span>
                </p>
                @if($action->note)
                <p class="text-xs text-gray-500 mt-0.5 italic">"{{ $action->note }}"</p>
                @endif
            </li>
            @endforeach
        </ol>
        @endif
    </div>

    {{-- ══ Linked RFQs ══ --}}
    @if($form->rfqs->isNotEmpty())
    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <h2 class="font-semibold text-gray-800 mb-3 text-sm uppercase tracking-wide">Linked RFQs</h2>
        <div class="space-y-2">
            @foreach($form->rfqs as $rfq)
            <div class="flex items-center justify-between py-2 border-b border-gray-50 last:border-0">
                <div>
                    <span class="font-mono text-xs font-bold text-gray-700">{{ $rfq->rfq_number }}</span>
                    <span class="ml-2 text-sm text-gray-600">{{ $rfq->title }}</span>
                </div>
                <div class="flex items-center gap-3">
                    <span class="text-xs font-medium px-2 py-0.5 rounded-full
                        {{ $rfq->status === 'closed' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                        {{ ucfirst(str_replace('_',' ',$rfq->status)) }}
                    </span>
                    @can('rfq.view')
<a href="{{ route('rfq.show', $rfq) }}" class="text-teal-600 hover:text-teal-700 text-sm font-medium">View →</a>
@endcan
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- ══ Action Buttons ══ --}}
    <div class="flex flex-wrap gap-3 items-center">

        {{-- SUBMIT (owner, draft) --}}
        @if($form->status === 'draft' && $form->creator_id === auth()->id())
        @can('activity_forms.submit')
<form method="POST" action="{{ route('activity-forms.submit', $form) }}">
            @csrf
<input type="hidden" name="_fiscal_year_id" value="{{ $workingFiscalYear?->id }}">
            <button type="submit" class="btn-primary">Submit for Verification</button>
        </form>
@endcan
        @endif

        {{-- EDIT (owner, editable) --}}
        @if($form->isEditable() && $form->creator_id === auth()->id())
        @can('activity_forms.edit')
<a href="{{ route('activity-forms.edit', $form) }}" class="btn-secondary">Edit Form</a>
@endcan
        @endif

        {{-- VERIFY button — only if user can verify --}}
        @can('verify', $form)
        <button type="button"
            onclick="document.getElementById('verify-modal').classList.remove('hidden')"
            class="px-5 py-2 rounded-lg text-sm font-semibold text-white"
            style="background:#1C3557;">
            Verify / Decision
        </button>
        @endcan

        {{-- APPROVE button — only if user can finalApprove --}}
        @can('finalApprove', $form)
        <button type="button"
            onclick="document.getElementById('approve-modal').classList.remove('hidden')"
            class="px-5 py-2 rounded-lg text-sm font-semibold text-white bg-green-600 hover:bg-green-700">
            Approve / Reject
        </button>
        @endcan

        {{-- CREATE RFQ — when approved, only for owner or finance --}}
        @if($form->isApproved() && (auth()->id() === $form->creator_id || auth()->user()->hasAnyRole(['finance','super_admin'])))
        @can('rfq.create')
<a href="{{ route('rfq.create', ['activity_form_id' => $form->id]) }}"
           class="px-5 py-2 rounded-lg text-sm font-semibold text-white"
           style="background:#00A99D;">
            + Create RFQ
        </a>
@endcan
        @endif

        @can('activity_forms.view')
<a href="{{ route('activity-forms.index') }}" class="text-gray-500 hover:text-gray-700 text-sm py-2 ml-auto">← Back to List</a>
@endcan
    </div>
</div>

{{-- ══ VERIFY MODAL ══ --}}
<div id="verify-modal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-md p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-bold text-gray-900 text-base">Verification Decision</h3>
            <button type="button" onclick="document.getElementById('verify-modal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        @can('activity_forms.verify')
<form method="POST" action="{{ route('activity-forms.verify', $form) }}">
            @csrf
<input type="hidden" name="_fiscal_year_id" value="{{ $workingFiscalYear?->id }}">
            <div class="space-y-4">
                <div class="flex gap-4">
                    <label class="flex items-center gap-2 cursor-pointer px-3 py-2 rounded-lg border border-gray-200 hover:bg-gray-50 flex-1">
                        <input type="radio" name="decision" value="approved" class="text-teal-500" required>
                        <span class="text-sm font-medium text-green-700">Approve</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer px-3 py-2 rounded-lg border border-gray-200 hover:bg-gray-50 flex-1">
                        <input type="radio" name="decision" value="modified_approved" class="text-yellow-500">
                        <span class="text-sm font-medium text-yellow-700">Modify</span>
                    </label>
                </div>
                <label class="flex items-center gap-2 cursor-pointer px-3 py-2 rounded-lg border border-red-200 bg-red-50 hover:bg-red-100">
                    <input type="radio" name="decision" value="rejected" class="text-red-500">
                    <span class="text-sm font-medium text-red-700">Reject</span>
                </label>
                <textarea name="note" rows="3" placeholder="Remarks / Note (required for rejection)…"
                          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-400 outline-none resize-none"></textarea>
                <div class="flex gap-3 pt-1">
                    <button type="submit" class="flex-1 py-2.5 rounded-lg text-sm font-semibold text-white" style="background:#1C3557;">Submit Decision</button>
                    <button type="button" onclick="document.getElementById('verify-modal').classList.add('hidden')" class="px-4 py-2 text-gray-500 text-sm border border-gray-200 rounded-lg hover:bg-gray-50">Cancel</button>
                </div>
            </div>
        </form>
@endcan
    </div>
</div>

{{-- ══ APPROVE MODAL ══ --}}
<div id="approve-modal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-md p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-bold text-gray-900 text-base">Final Approval Decision</h3>
            <button type="button" onclick="document.getElementById('approve-modal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        @can('activity_forms.approve')
<form method="POST" action="{{ route('activity-forms.approve', $form) }}">
            @csrf
<input type="hidden" name="_fiscal_year_id" value="{{ $workingFiscalYear?->id }}">
            <div class="space-y-4">
                <div class="flex gap-4">
                    <label class="flex items-center gap-2 cursor-pointer px-3 py-2 rounded-lg border border-green-200 bg-green-50 hover:bg-green-100 flex-1">
                        <input type="radio" name="decision" value="approved" class="text-green-500" required>
                        <span class="text-sm font-medium text-green-700">Approve</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer px-3 py-2 rounded-lg border border-gray-200 hover:bg-gray-50 flex-1">
                        <input type="radio" name="decision" value="modified_approved" class="text-yellow-500">
                        <span class="text-sm font-medium text-yellow-700">Modify</span>
                    </label>
                </div>
                <label class="flex items-center gap-2 cursor-pointer px-3 py-2 rounded-lg border border-red-200 bg-red-50 hover:bg-red-100">
                    <input type="radio" name="decision" value="rejected" class="text-red-500">
                    <span class="text-sm font-medium text-red-700">Reject</span>
                </label>
                <textarea name="note" rows="3" placeholder="Remarks / Note (required for rejection)…"
                          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-400 outline-none resize-none"></textarea>
                <div class="flex gap-3 pt-1">
                    <button type="submit" class="flex-1 py-2.5 bg-green-600 hover:bg-green-700 rounded-lg text-sm font-semibold text-white">Submit Decision</button>
                    <button type="button" onclick="document.getElementById('approve-modal').classList.add('hidden')" class="px-4 py-2 text-gray-500 text-sm border border-gray-200 rounded-lg hover:bg-gray-50">Cancel</button>
                </div>
            </div>
        </form>
@endcan
    </div>
    {{-- ══ Image Preview Lightbox ══ --}}
    <div x-show="previewUrl" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/75 p-4"
         @click.self="previewUrl=null; previewName=''"
         @keydown.escape.window="previewUrl=null; previewName=''">
        <div class="relative max-w-5xl w-full flex flex-col items-center">
            <button @click="previewUrl=null; previewName=''"
                    class="absolute -top-10 right-0 text-white text-2xl leading-none hover:text-gray-300">&times;</button>
            <img :src="previewUrl" :alt="previewName"
                 class="max-h-[80vh] w-auto rounded-lg shadow-2xl object-contain bg-white">
            <p class="text-white text-sm mt-3 opacity-75" x-text="previewName"></p>
        </div>
    </div>
</div>
@endsection
