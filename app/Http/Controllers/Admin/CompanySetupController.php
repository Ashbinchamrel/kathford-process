<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApprovalChain;
use App\Models\AuditLog;
use App\Models\FormCategory;
use App\Models\Setting;
use App\Models\User;
use App\Services\UploadOptimizationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class CompanySetupController extends Controller
{
    public function __construct(private readonly UploadOptimizationService $uploadOptimizer) {}

    public function index(): View
    {
        $company   = Setting::group('company');
        $categories = FormCategory::with('approvalChain.verifiers', 'approvalChain.approvers')
                        ->orderBy('sort_order')->get();
        $configured = $categories->filter(fn($c) => $c->approval_chain_id)->count();

        return view('admin.setup.index', compact('company', 'categories', 'configured'));
    }

    public function company(): View
    {
        $company = Setting::group('company');
        return view('admin.setup.company', ['company' => $company, 'fiscalYears' => \App\Models\FiscalYear::orderByDesc('name')->get()]);
    }

    public function updateCompany(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'company_name'      => ['required', 'string', 'max:200'],
            'company_address'   => ['nullable', 'string', 'max:500'],
            'company_phone'     => ['nullable', 'string', 'max:50'],
            'company_email'     => ['nullable', 'email', 'max:200'],
            'company_website'   => ['nullable', 'url', 'max:200'],
            'company_pan'       => ['nullable', 'string', 'max:50'],
            'fiscal_year_start' => ['nullable', 'string', 'max:10'],
            'currency_symbol'   => ['nullable', 'string', 'max:10'],
            'date_format'       => ['nullable', 'in:BS,AD'],
            'company_logo'      => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        if ($request->hasFile('company_logo')) {
            $existingLogo = Setting::get('company_logo_path');
            if ($existingLogo) {
                Storage::disk('public')->delete($existingLogo);
            }
            $validated['company_logo_path'] = $this->uploadOptimizer->store($request->file('company_logo'), 'company', 'public')['path'];
        }
        unset($validated['company_logo']);

        foreach ($validated as $key => $value) {
            Setting::set($key, $value ?? '', 'string', 'company');
        }

        AuditLog::record(Auth::user(), 'settings.company_updated', null, 'Company info updated');

        return redirect()->route('admin.profile.edit')
            ->with('success', 'Company information updated successfully.');
    }

    public function workflows(): View
    {
        $categories = FormCategory::with('approvalChain.verifiers', 'approvalChain.approvers')
                        ->orderBy('sort_order')->get();

        $eligibleUsers = User::active()
            ->whereHas('role', fn($q) => $q->whereIn('name', ['verifier', 'approver', 'super_admin', 'finance']))
            ->orderBy('name')->get();

        $allChains = ApprovalChain::with('verifiers', 'approvers')->active()->get();

        return view('admin.setup.workflows', compact('categories', 'eligibleUsers', 'allChains'));
    }

    public function createChain(Request $request, FormCategory $category): RedirectResponse
    {
        $request->validate([
            'verifier_ids'   => ['required', 'array', 'min:1'],
            'verifier_ids.*' => ['distinct', new \App\Rules\ApprovalMemberRole('verifier')],
            'approver_ids'   => ['required', 'array', 'min:1'],
            'approver_ids.*' => ['distinct', new \App\Rules\ApprovalMemberRole('approver')],
            'notes'          => ['nullable', 'string', 'max:500'],
        ]);

        $chain = ApprovalChain::create([
            'name'       => $category->name,
            'notes'      => $request->notes,
            'is_default' => false,
            'is_active'  => true,
        ]);

        $chain->syncVerifiers($request->verifier_ids);
        $chain->syncApprovers($request->approver_ids);

        $category->update(['approval_chain_id' => $chain->id]);

        AuditLog::record(Auth::user(), 'approval_chain.created', $chain, "Chain for {$category->name}");

        return redirect()->route('admin.setup.workflows')
            ->with('success', "Approval chain created for \"{$category->name}\".");
    }

    public function updateChain(Request $request, FormCategory $category): RedirectResponse
    {
        $request->validate([
            'chain_id'       => ['required', 'exists:approval_chains,id'],
            'verifier_ids'   => ['required', 'array', 'min:1'],
            'verifier_ids.*' => ['distinct', new \App\Rules\ApprovalMemberRole('verifier')],
            'approver_ids'   => ['required', 'array', 'min:1'],
            'approver_ids.*' => ['distinct', new \App\Rules\ApprovalMemberRole('approver')],
            'notes'          => ['nullable', 'string', 'max:500'],
            'is_active'      => ['boolean'],
        ]);

        $chain = ApprovalChain::findOrFail($request->chain_id);
        $chain->update([
            'notes'     => $request->notes,
            'is_active' => $request->boolean('is_active', true),
        ]);

        $chain->syncVerifiers($request->verifier_ids);
        $chain->syncApprovers($request->approver_ids);

        AuditLog::record(Auth::user(), 'approval_chain.updated', $chain, "Chain for {$category->name}");

        return redirect()->route('admin.setup.workflows')
            ->with('success', "Approval chain for \"{$category->name}\" updated.");
    }

    public function assignChain(Request $request, FormCategory $category): RedirectResponse
    {
        $request->validate([
            'approval_chain_id' => ['nullable', 'exists:approval_chains,id'],
        ]);

        $category->update(['approval_chain_id' => $request->approval_chain_id]);

        return redirect()->route('admin.setup.workflows')
            ->with('success', "Chain assignment updated for \"{$category->name}\".");
    }
}
