<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApprovalChain;
use App\Models\AuditLog;
use App\Models\Setting;
use App\Models\PaymentAccount;
use App\Models\PaymentAuthorisationChannel;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ApprovalChainController extends Controller
{

    public function index(): View
    {
        $chains    = ApprovalChain::with(['verifiers', 'approvers'])->latest()->get();
        $verifierUsers = User::active()->withRole('verifier')->with('role')->orderBy('name')->get();
        $approverUsers = User::active()->withRole('approver')->with('role')->orderBy('name')->get();

        $poApprovalChainId = Setting::get('purchase_order_approval_chain_id');
        $paymentChannels = PaymentAuthorisationChannel::with(['approvalChain', 'paymentAccount'])->orderBy('name')->get();
        $paymentAccounts = PaymentAccount::where('active', true)->orderBy('name')->get();

        return view('admin.approval-chain.index', compact('chains', 'verifierUsers', 'approverUsers', 'poApprovalChainId', 'paymentChannels', 'paymentAccounts'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name'         => ['required', 'string', 'max:100'],
            'verifier_ids' => ['required', 'array', 'min:1'],
            'verifier_ids.*' => ['distinct', new \App\Rules\ApprovalMemberRole('verifier')],
            'approver_ids' => ['required', 'array', 'min:1'],
            'approver_ids.*' => ['distinct', new \App\Rules\ApprovalMemberRole('approver')],
            'notes'        => ['nullable', 'string', 'max:500'],
            'is_default'   => ['boolean'],
        ]);

        if ($request->boolean('is_default')) {
            ApprovalChain::where('is_default', true)->update(['is_default' => false]);
        }

        $chain = ApprovalChain::create([
            'name'       => $request->name,
            'notes'      => $request->notes,
            'is_default' => $request->boolean('is_default'),
            'is_active'  => true,
        ]);

        $verifierIds = array_values(array_unique($request->verifier_ids));
        $approverIds = array_values(array_unique($request->approver_ids));
        $chain->syncVerifiers($verifierIds);
        $chain->syncApprovers($approverIds);

        AuditLog::record(Auth::user(), 'approval_chain.created', $chain, $chain->name);

        return redirect()->route('admin.approval-chain.index')
            ->with('success', "Approval chain \"{$chain->name}\" created with " .
                count($verifierIds) . " verifier layer(s) and " .
                count($approverIds) . " approver layer(s).");
    }

    public function update(Request $request, ApprovalChain $approvalChain): RedirectResponse
    {
        $request->validate([
            'name'         => ['required', 'string', 'max:100'],
            'verifier_ids' => ['required', 'array', 'min:1'],
            'verifier_ids.*' => ['distinct', new \App\Rules\ApprovalMemberRole('verifier')],
            'approver_ids' => ['required', 'array', 'min:1'],
            'approver_ids.*' => ['distinct', new \App\Rules\ApprovalMemberRole('approver')],
            'notes'        => ['nullable', 'string', 'max:500'],
            'is_default'   => ['boolean'],
            'is_active'    => ['boolean'],
        ]);

        if ($request->boolean('is_default') && ! $approvalChain->is_default) {
            ApprovalChain::where('is_default', true)->update(['is_default' => false]);
        }

        $approvalChain->update([
            'name'       => $request->name,
            'notes'      => $request->notes,
            'is_default' => $request->boolean('is_default'),
            'is_active'  => $request->boolean('is_active', true),
        ]);

        $approvalChain->syncVerifiers(array_values(array_unique($request->verifier_ids)));
        $approvalChain->syncApprovers(array_values(array_unique($request->approver_ids)));

        AuditLog::record(Auth::user(), 'approval_chain.updated', $approvalChain, $approvalChain->name);

        return redirect()->route('admin.approval-chain.index')
            ->with('success', 'Approval chain updated.');
    }

    public function destroy(ApprovalChain $approvalChain): RedirectResponse
    {
        if ($approvalChain->is_default) {
            return back()->withErrors(['error' => 'Cannot delete the default approval chain.']);
        }

        AuditLog::record(Auth::user(), 'approval_chain.deleted', $approvalChain, $approvalChain->name);
        $approvalChain->delete();

        return redirect()->route('admin.approval-chain.index')
            ->with('success', 'Approval chain removed.');
    }

    /** Set the single centrally-managed chain used for every newly submitted PO. */
    public function updatePurchaseOrderChain(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'purchase_order_approval_chain_id' => ['required', 'exists:approval_chains,id'],
        ]);

        $chain = ApprovalChain::active()->find($data['purchase_order_approval_chain_id']);
        if (! $chain) {
            return back()->withErrors(['purchase_order_approval_chain_id' => 'Select an active approval chain.']);
        }

        Setting::set('purchase_order_approval_chain_id', $chain->id, 'string', 'workflows');
        AuditLog::record(Auth::user(), 'settings.purchase_order_approval_chain_updated', $chain, $chain->name);

        return back()->with('success', "Purchase Orders will now use the centrally configured \"{$chain->name}\" approval chain.");
    }

    public function updatePaymentAuthorisationChain(Request $request): RedirectResponse
    {
        $data = $request->validate(['payment_authorisation_approval_chain_id' => ['required', 'exists:approval_chains,id']]);
        $chain = ApprovalChain::active()->find($data['payment_authorisation_approval_chain_id']);
        if (! $chain) return back()->withErrors(['payment_authorisation_approval_chain_id' => 'Select an active approval chain.']);
        Setting::set('payment_authorisation_approval_chain_id', $chain->id, 'string', 'workflows');
        AuditLog::record(Auth::user(), 'settings.payment_authorisation_approval_chain_updated', $chain, $chain->name);
        return back()->with('success', "Payment Authorisations will now use the centrally configured \"{$chain->name}\" approval chain.");
    }

    public function storePaymentAuthorisationChannel(Request $request): RedirectResponse
    {
        $data = $this->validatedPaymentChannel($request);
        $channel = PaymentAuthorisationChannel::create($data);
        AuditLog::record(Auth::user(), 'payment_authorisation_channel.created', $channel, $channel->name);
        return back()->with('success', "Payment Authorisation Channel \"{$channel->name}\" created.");
    }

    public function updatePaymentAuthorisationChannel(Request $request, PaymentAuthorisationChannel $paymentAuthorisationChannel): RedirectResponse
    {
        $data = $this->validatedPaymentChannel($request, $paymentAuthorisationChannel->id);
        $paymentAuthorisationChannel->update($data);
        AuditLog::record(Auth::user(), 'payment_authorisation_channel.updated', $paymentAuthorisationChannel, $paymentAuthorisationChannel->name);
        return back()->with('success', 'Payment Authorisation Channel updated. Existing authorisation batches retain their saved approval chain.');
    }

    private function validatedPaymentChannel(Request $request, ?string $ignoreId = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:payment_authorisation_channels,name'.($ignoreId ? ','.$ignoreId : '')],
            'approval_chain_id' => ['required', 'exists:approval_chains,id'],
            'payment_account_id' => ['nullable', 'exists:payment_accounts,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        abort_unless(ApprovalChain::active()->whereKey($data['approval_chain_id'])->exists(), 422, 'Select an active approval chain.');
        $data['is_active'] = $request->boolean('is_active', true);
        return $data;
    }
}
