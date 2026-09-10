@extends('layouts.app')

@section('title', 'Approval Chains')
@section('page-title', 'Approval Chains')
@section('breadcrumb', 'Administration › Approval Chains')

@section('content')
<div class="space-y-6" x-data="chainManager()">

    {{-- ── Page header ──────────────────────────────────────── --}}
    <div class="flex items-center justify-between">
        <div>
            <p class="text-sm text-gray-500 mt-0.5">
                Manage the ordered approval route for <strong>Activity Forms</strong>, <strong>Purchase Requests</strong>, <strong>Purchase Orders</strong>, and <strong>Payment Authorisations</strong>. Each chain defines sequential <strong>verifier</strong> and <strong>approver</strong> layers; only the next assigned person can act.
            </p>
        </div>
        <button @click="openCreate()" class="btn-primary">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
            New Chain
        </button>
    </div>

    <div class="kcard p-5">
        <div class="flex flex-col gap-2 border-b border-gray-100 pb-4"><h2 class="text-sm font-semibold text-gray-900">Payment Authorisation Channels</h2><p class="text-sm text-gray-500">Each scheduled payment period selects one channel. A channel fixes the approval chain for its own Payment Authorisation batch, so different schedules can follow different controls.</p></div>
        <div class="mt-4 overflow-x-auto"><table class="min-w-full text-sm"><thead class="bg-gray-50"><tr><th class="px-3 py-2 text-left text-xs font-semibold uppercase text-gray-500">Channel</th><th class="px-3 py-2 text-left text-xs font-semibold uppercase text-gray-500">Approval chain</th><th class="px-3 py-2 text-left text-xs font-semibold uppercase text-gray-500">Default account</th><th class="px-3 py-2 text-left text-xs font-semibold uppercase text-gray-500">Status</th><th class="px-3 py-2"></th></tr></thead><tbody class="divide-y divide-gray-100">@forelse($paymentChannels as $channel)<tr><td class="px-3 py-3"><p class="font-semibold text-gray-800">{{ $channel->name }}</p>@if($channel->notes)<p class="mt-0.5 text-xs text-gray-400">{{ $channel->notes }}</p>@endif</td><td class="px-3 py-3">{{ $channel->approvalChain?->name }}</td><td class="px-3 py-3">{{ $channel->paymentAccount?->name ?: 'Any account' }}</td><td class="px-3 py-3"><span class="badge {{ $channel->is_active ? 'badge-approved' : 'badge-draft' }}">{{ $channel->is_active ? 'Active' : 'Inactive' }}</span></td><td class="px-3 py-3 text-right"><details class="inline-block text-left"><summary class="cursor-pointer text-sm font-semibold text-teal-700">Edit</summary><form method="POST" action="{{ route('admin.approval-chain.payment-authorisation-channels.update', $channel) }}" class="mt-2 grid w-96 gap-2 rounded-lg border border-gray-200 bg-white p-3 shadow-lg">@csrf @method('PUT')<input name="name" value="{{ $channel->name }}" required class="rounded border border-gray-300 px-2 py-1.5 text-sm"><select name="approval_chain_id" required class="rounded border border-gray-300 px-2 py-1.5 text-sm">@foreach($chains->where('is_active', true) as $chain)<option value="{{ $chain->id }}" @selected($channel->approval_chain_id === $chain->id)>{{ $chain->name }}</option>@endforeach</select><select name="payment_account_id" class="rounded border border-gray-300 px-2 py-1.5 text-sm"><option value="">Any account</option>@foreach($paymentAccounts as $account)<option value="{{ $account->id }}" @selected($channel->payment_account_id === $account->id)>{{ $account->name }}</option>@endforeach</select><textarea name="notes" rows="2" class="rounded border border-gray-300 px-2 py-1.5 text-sm">{{ $channel->notes }}</textarea><label class="text-xs text-gray-700"><input type="checkbox" name="is_active" value="1" @checked($channel->is_active)> Active</label><button class="btn-primary justify-center">Save channel</button></form></details></td></tr>@empty<tr><td colspan="5" class="px-3 py-6 text-center text-gray-400">No channels yet. Create one below.</td></tr>@endforelse</tbody></table></div>
        <form method="POST" action="{{ route('admin.approval-chain.payment-authorisation-channels.store') }}" class="mt-5 grid grid-cols-1 gap-3 border-t border-gray-100 pt-5 md:grid-cols-4">@csrf<div><label class="mb-1 block text-xs font-semibold text-gray-600">Channel name *</label><input name="name" required placeholder="e.g. Supplier Payments" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"></div><div><label class="mb-1 block text-xs font-semibold text-gray-600">Approval chain *</label><select name="approval_chain_id" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"><option value="">Select chain</option>@foreach($chains->where('is_active', true) as $chain)<option value="{{ $chain->id }}">{{ $chain->name }}</option>@endforeach</select></div><div><label class="mb-1 block text-xs font-semibold text-gray-600">Default account</label><select name="payment_account_id" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"><option value="">Any account</option>@foreach($paymentAccounts as $account)<option value="{{ $account->id }}">{{ $account->name }}</option>@endforeach</select></div><div class="flex items-end gap-3"><label class="mb-2 text-sm text-gray-700"><input type="checkbox" name="is_active" value="1" checked> Active</label><button class="btn-primary flex-1 justify-center">Add channel</button></div><div class="md:col-span-4"><label class="mb-1 block text-xs font-semibold text-gray-600">Notes</label><input name="notes" maxlength="1000" placeholder="When should Finance use this channel?" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"></div></form>
    </div>

    <div class="kcard p-5">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <h2 class="text-sm font-semibold text-gray-900">Purchase Order approval setting</h2>
                <p class="mt-1 text-sm text-gray-500">Every Purchase Order uses this chain when submitted. PO creators cannot select or change it.</p>
            </div>
            <form method="POST" action="{{ route('admin.approval-chain.purchase-orders.update') }}" class="flex w-full max-w-xl gap-2">
                @csrf
                <select name="purchase_order_approval_chain_id" required class="min-w-0 flex-1 rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    <option value="">Select the chain for Purchase Orders…</option>
                    @foreach($chains->where('is_active', true) as $chain)
                        <option value="{{ $chain->id }}" @selected($poApprovalChainId === $chain->id)>{{ $chain->name }}</option>
                    @endforeach
                </select>
                <button class="btn-primary whitespace-nowrap">Save PO setting</button>
            </form>
        </div>
    </div>

    {{-- ── Chains list ───────────────────────────────────────── --}}
    @forelse($chains as $chain)
    <div class="kcard">
        <div class="kcard-header">
            <div class="flex items-center gap-3">
                <div class="w-2 h-2 rounded-full" style="background:{{ $chain->is_active ? '#00A99D' : '#D1D5DB' }};"></div>
                <div>
                    <h3 class="text-sm font-semibold text-gray-900">{{ $chain->name }}</h3>
                    <p class="text-xs text-gray-400 mt-0.5">
                        @if($chain->is_default)
                            <span class="badge badge-approved mr-1">Default</span>
                        @endif
                        @if(!$chain->is_active)
                            <span class="badge badge-draft mr-1">Inactive</span>
                        @endif
                        Created {{ $chain->created_at->format('d M Y') }}
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <button @click="openEdit('{{ route('admin.approval-chain.update', $chain) }}', {{ json_encode([
                    'name'         => $chain->name,
                    'notes'        => $chain->notes,
                    'is_default'   => $chain->is_default,
                    'is_active'    => $chain->is_active,
                    'verifier_ids' => $chain->verifiers->pluck('id')->values(),
                    'approver_ids' => $chain->approvers->pluck('id')->values(),
                ]) }})" class="btn-secondary" style="font-size:12px;padding:5px 12px;">
                    Edit
                </button>
                @if(!$chain->is_default)
                <form method="POST" action="{{ route('admin.approval-chain.destroy', $chain) }}"
                      onsubmit="return confirm('Remove this chain? Forms using it will lose their assignment.');">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn-secondary" style="font-size:12px;padding:5px 12px;color:#DC2626;border-color:#FECACA;">
                        Remove
                    </button>
                </form>
                @endif
            </div>
        </div>

        <div class="p-5 grid grid-cols-1 md:grid-cols-2 gap-6">
            {{-- Verifiers --}}
            <div>
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">
                    Verifiers — sequential layers
                </p>
                @forelse($chain->verifiers as $user)
                <div class="flex items-center gap-2 mb-2">
                    <div class="w-7 h-7 rounded-full flex items-center justify-center text-white text-xs font-bold flex-shrink-0"
                         style="background:#1C3557;">{{ substr($user->name, 0, 1) }}</div>
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-gray-800 leading-tight">Verifier layer {{ $loop->iteration }} · {{ $user->name }}</p>
                        <p class="text-xs text-gray-400">{{ $user->email }}</p>
                    </div>
                </div>
                @empty
                <p class="text-xs text-amber-600 bg-amber-50 rounded px-3 py-2">No verifiers assigned</p>
                @endforelse
            </div>

            {{-- Approvers --}}
            <div>
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">
                    Approvers — sequential layers
                </p>
                @forelse($chain->approvers as $user)
                <div class="flex items-center gap-2 mb-2">
                    <div class="w-7 h-7 rounded-full flex items-center justify-center text-white text-xs font-bold flex-shrink-0"
                         style="background:#00A99D;">{{ substr($user->name, 0, 1) }}</div>
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-gray-800 leading-tight">Approver layer {{ $loop->iteration }} · {{ $user->name }}</p>
                        <p class="text-xs text-gray-400">{{ $user->email }}</p>
                    </div>
                </div>
                @empty
                <p class="text-xs text-amber-600 bg-amber-50 rounded px-3 py-2">No approvers assigned</p>
                @endforelse
            </div>
        </div>

        @if($chain->notes)
        <div class="px-5 pb-4">
            <p class="text-xs text-gray-500 italic">{{ $chain->notes }}</p>
        </div>
        @endif
    </div>
    @empty
    <div class="kcard p-12 text-center">
        <svg class="w-10 h-10 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
        </svg>
        <p class="text-sm text-gray-500">No approval chains yet. Create one to start routing forms.</p>
    </div>
    @endforelse

    {{-- ══════════════════════════════════════════════════════════
         Create / Edit Modal
    ══════════════════════════════════════════════════════════ --}}
    <div x-show="modalOpen" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4"
         style="background:rgba(0,0,0,0.5);">
        <div @click.away="modalOpen=false"
             class="bg-white rounded-xl shadow-2xl w-full max-w-2xl max-h-screen overflow-y-auto">

            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                <h2 class="text-base font-semibold text-gray-900" x-text="updateUrl ? 'Edit Approval Chain' : 'New Approval Chain'"></h2>
                <button @click="modalOpen=false" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <form :action="updateUrl || '{{ route('admin.approval-chain.store') }}'"
                  method="POST" class="p-6 space-y-5">
                @csrf
                <input type="hidden" :name="updateUrl ? '_method' : ''" value="PUT">

                {{-- Chain name --}}
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Chain Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" x-model="form.name" required
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:border-transparent"
                           style="--tw-ring-color:#00A99D;"
                           placeholder="e.g. Default Chain, IT Purchase Chain">
                </div>

                {{-- Verifiers --}}
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">
                        Verifiers <span class="text-red-500">*</span>
                        <span class="font-normal text-gray-400 ml-1">— selection order defines verifier layers; use arrows to reorder</span>
                    </label>
                    <div class="border border-gray-200 rounded-lg overflow-hidden">
                        <div class="bg-gray-50 px-3 py-2 border-b border-gray-200">
                            <input type="text" x-model="verifierSearch" placeholder="Search users…"
                                   class="w-full bg-transparent text-xs focus:outline-none">
                        </div>
                        <div class="max-h-40 overflow-y-auto p-2 space-y-1">
                            @if($verifierUsers->isEmpty())<p class="p-2 text-xs text-gray-500">No active users have the Verifier role. Assign this role under Users first.</p>@endif
                            @foreach($verifierUsers as $user)
                            <label class="flex items-center gap-2 px-2 py-1.5 rounded cursor-pointer hover:bg-gray-50"
                                   x-show="!verifierSearch || @js(strtolower($user->name.' '.$user->email)).includes(verifierSearch.toLowerCase())">
                                <input type="checkbox" value="{{ $user->id }}"
                                       :checked="form.verifier_ids.includes('{{ $user->id }}')"
                                       @change="toggleVerifier('{{ $user->id }}')"
                                       class="rounded" style="accent-color:#1C3557;">
                                <div class="w-6 h-6 rounded-full flex items-center justify-center text-white text-xs font-bold flex-shrink-0"
                                     style="background:#1C3557;">{{ substr($user->name, 0, 1) }}</div>
                                <div class="min-w-0 flex-1">
                                    <p class="text-xs font-medium text-gray-800">{{ $user->name }}</p>
                                    <p class="text-xs text-gray-400">{{ $user->role?->display_name }} · {{ $user->email }}</p>
                                </div>
                                <span x-show="form.verifier_ids.includes('{{ $user->id }}')" class="inline-flex items-center gap-1 text-xs font-semibold text-slate-600"><span x-text="'L'+verifierLayer('{{ $user->id }}')"></span><button type="button" @click.prevent="moveVerifier('{{ $user->id }}', -1)" class="rounded border px-1 hover:bg-gray-100">↑</button><button type="button" @click.prevent="moveVerifier('{{ $user->id }}', 1)" class="rounded border px-1 hover:bg-gray-100">↓</button></span>
                            </label>
                            @endforeach
                        </div>
                        <div class="px-3 py-1.5 bg-gray-50 border-t border-gray-200">
                            <template x-for="id in form.verifier_ids" :key="'verifier-'+id"><input type="hidden" name="verifier_ids[]" :value="id"></template>
                            <p class="text-xs text-gray-500"><span x-text="form.verifier_ids.length"></span> selected · their order is the workflow order</p>
                        </div>
                    </div>
                </div>

                <p x-show="removedIneligible" class="rounded-lg bg-amber-50 p-3 text-xs text-amber-800">Some previous members are inactive or do not have the required role. Choose eligible replacements before saving. Existing assignments change only when you save.</p>
                {{-- Approvers --}}
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">
                        Approvers <span class="text-red-500">*</span>
                        <span class="font-normal text-gray-400 ml-1">— selection order defines approver layers; use arrows to reorder</span>
                    </label>
                    <div class="border border-gray-200 rounded-lg overflow-hidden">
                        <div class="bg-gray-50 px-3 py-2 border-b border-gray-200">
                            <input type="text" x-model="approverSearch" placeholder="Search users…"
                                   class="w-full bg-transparent text-xs focus:outline-none">
                        </div>
                        <div class="max-h-40 overflow-y-auto p-2 space-y-1">
                            @if($approverUsers->isEmpty())<p class="p-2 text-xs text-gray-500">No active users have the Approver role. Assign this role under Users first.</p>@endif
                            @foreach($approverUsers as $user)
                            <label class="flex items-center gap-2 px-2 py-1.5 rounded cursor-pointer hover:bg-gray-50"
                                   x-show="!approverSearch || @js(strtolower($user->name.' '.$user->email)).includes(approverSearch.toLowerCase())">
                                <input type="checkbox" value="{{ $user->id }}"
                                       :checked="form.approver_ids.includes('{{ $user->id }}')"
                                       @change="toggleApprover('{{ $user->id }}')"
                                       class="rounded" style="accent-color:#00A99D;">
                                <div class="w-6 h-6 rounded-full flex items-center justify-center text-white text-xs font-bold flex-shrink-0"
                                     style="background:#00A99D;">{{ substr($user->name, 0, 1) }}</div>
                                <div class="min-w-0 flex-1">
                                    <p class="text-xs font-medium text-gray-800">{{ $user->name }}</p>
                                    <p class="text-xs text-gray-400">{{ $user->role?->display_name }} · {{ $user->email }}</p>
                                </div>
                                <span x-show="form.approver_ids.includes('{{ $user->id }}')" class="inline-flex items-center gap-1 text-xs font-semibold text-slate-600"><span x-text="'L'+approverLayer('{{ $user->id }}')"></span><button type="button" @click.prevent="moveApprover('{{ $user->id }}', -1)" class="rounded border px-1 hover:bg-gray-100">↑</button><button type="button" @click.prevent="moveApprover('{{ $user->id }}', 1)" class="rounded border px-1 hover:bg-gray-100">↓</button></span>
                            </label>
                            @endforeach
                        </div>
                        <div class="px-3 py-1.5 bg-gray-50 border-t border-gray-200">
                            <template x-for="id in form.approver_ids" :key="'approver-'+id"><input type="hidden" name="approver_ids[]" :value="id"></template>
                            <p class="text-xs text-gray-500"><span x-text="form.approver_ids.length"></span> selected · their order is the workflow order</p>
                        </div>
                    </div>
                </div>

                {{-- Notes --}}
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Notes (optional)</label>
                    <textarea name="notes" x-model="form.notes" rows="2"
                              class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:border-transparent resize-none"
                              placeholder="Internal notes about when to use this chain"></textarea>
                </div>

                {{-- Flags --}}
                <div class="flex items-center gap-6">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="is_default" value="1" x-model="form.is_default"
                               class="rounded" style="accent-color:#00A99D;">
                        <span class="text-sm text-gray-700">Set as default chain</span>
                    </label>
                    <label x-show="updateUrl" x-cloak class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="is_active" value="1" x-model="form.is_active"
                               class="rounded" style="accent-color:#00A99D;">
                        <span class="text-sm text-gray-700">Active</span>
                    </label>
                </div>

                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" @click="modalOpen=false" class="btn-secondary">Cancel</button>
                    <button type="submit" class="btn-primary" x-text="updateUrl ? 'Save Changes' : 'Create Chain'"></button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function chainManager() {
    return {
        verifierUserIds: @js($verifierUsers->pluck('id')->values()),
        approverUserIds: @js($approverUsers->pluck('id')->values()),
        removedIneligible: false,
        modalOpen: false,
        updateUrl: null,
        verifierSearch: '',
        approverSearch: '',
        form: {
            name: '',
            notes: '',
            is_default: false,
            is_active: true,
            verifier_ids: [],
            approver_ids: [],
        },

        openCreate() {
            this.removedIneligible = false;
            this.updateUrl = null;
            this.form = { name: '', notes: '', is_default: false, is_active: true, verifier_ids: [], approver_ids: [] };
            this.verifierSearch = '';
            this.approverSearch = '';
            this.modalOpen = true;
        },

        openEdit(updateUrl, data) {
            this.removedIneligible = (data.verifier_ids || []).some(id => !this.verifierUserIds.includes(String(id))) || (data.approver_ids || []).some(id => !this.approverUserIds.includes(String(id)));
            this.updateUrl = updateUrl;
            this.form = {
                name: data.name,
                notes: data.notes || '',
                is_default: data.is_default,
                is_active: data.is_active,
                verifier_ids: (data.verifier_ids || []).map(String).filter(id => this.verifierUserIds.includes(id)),
                approver_ids: (data.approver_ids || []).map(String).filter(id => this.approverUserIds.includes(id)),
            };
            this.verifierSearch = '';
            this.approverSearch = '';
            this.modalOpen = true;
        },

        toggleVerifier(id) {
            const idx = this.form.verifier_ids.indexOf(String(id));
            if (idx === -1) this.form.verifier_ids.push(String(id));
            else this.form.verifier_ids.splice(idx, 1);
        },

        verifierLayer(id) {
            return this.form.verifier_ids.indexOf(String(id)) + 1;
        },

        moveVerifier(id, direction) {
            const index = this.form.verifier_ids.indexOf(String(id));
            const target = index + direction;
            if (index < 0 || target < 0 || target >= this.form.verifier_ids.length) return;
            [this.form.verifier_ids[index], this.form.verifier_ids[target]] = [this.form.verifier_ids[target], this.form.verifier_ids[index]];
        },

        toggleApprover(id) {
            const idx = this.form.approver_ids.indexOf(String(id));
            if (idx === -1) this.form.approver_ids.push(String(id));
            else this.form.approver_ids.splice(idx, 1);
        },

        approverLayer(id) {
            return this.form.approver_ids.indexOf(String(id)) + 1;
        },

        moveApprover(id, direction) {
            const index = this.form.approver_ids.indexOf(String(id));
            const target = index + direction;
            if (index < 0 || target < 0 || target >= this.form.approver_ids.length) return;
            [this.form.approver_ids[index], this.form.approver_ids[target]] = [this.form.approver_ids[target], this.form.approver_ids[index]];
        },
    };
}
</script>
@endpush
