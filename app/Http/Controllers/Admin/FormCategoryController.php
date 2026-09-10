<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApprovalChain;
use App\Models\AuditLog;
use App\Models\FormCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class FormCategoryController extends Controller
{

    public function index(): View
    {
        $categories = FormCategory::with('approvalChain')->withTrashed()->orderBy('sort_order')->get();
        $chains     = ApprovalChain::active()->get();
        return view('admin.form-categories.index', compact('categories', 'chains'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name'                     => ['required', 'string', 'max:100'],
            'code'                     => ['required', 'string', 'max:20', 'alpha_num', 'uppercase', 'unique:form_categories,code'],
            'description'              => ['nullable', 'string', 'max:500'],
            'category_group'           => ['required', 'in:activity,purchase,vendor,payment'],
            'requires_reason'          => ['boolean'],
            'requires_logistic_table'  => ['boolean'],
            'requires_attachments'     => ['boolean'],
            'requires_vendor_selection'=> ['boolean'],
            'auto_generate_next'       => ['boolean'],
            'bypasses_procurement_to_payment' => ['boolean'],
            'approval_chain_id'        => ['nullable', 'exists:approval_chains,id'],
            'sort_order'               => ['integer', 'min:0'],
        ]);

        $category = FormCategory::create([
            'name'                      => $request->name,
            'code'                      => strtoupper($request->code),
            'description'               => $request->description,
            'category_group'            => $request->category_group,
            'requires_reason'           => $request->boolean('requires_reason'),
            'requires_logistic_table'   => $request->boolean('requires_logistic_table', true),
            'requires_attachments'      => $request->boolean('requires_attachments', true),
            'requires_vendor_selection' => $request->boolean('requires_vendor_selection'),
            'auto_generate_next'        => $request->boolean('auto_generate_next'),
            'bypasses_procurement_to_payment' => $request->boolean('bypasses_procurement_to_payment'),
            'approval_chain_id'         => $request->approval_chain_id,
            'sort_order'                => $request->sort_order ?? 0,
            'is_active'                 => true,
            'is_system'                 => false,
            'extra_fields'              => [],
        ]);

        AuditLog::record(Auth::user(), 'form_category.created', $category, $category->name);

        return redirect()->route('admin.form-categories.index')
            ->with('success', "Form category \"{$category->name}\" created.");
    }

    public function edit(FormCategory $formCategory): View
    {
        $chains = ApprovalChain::active()->get();
        return view('admin.form-categories.edit', compact('formCategory', 'chains'));
    }

    public function update(Request $request, FormCategory $formCategory): RedirectResponse
    {
        $request->validate([
            'name'                     => ['required', 'string', 'max:100'],
            'description'              => ['nullable', 'string', 'max:500'],
            'requires_reason'          => ['boolean'],
            'requires_logistic_table'  => ['boolean'],
            'requires_attachments'     => ['boolean'],
            'requires_vendor_selection'=> ['boolean'],
            'auto_generate_next'       => ['boolean'],
            'bypasses_procurement_to_payment' => ['boolean'],
            'approval_chain_id'        => ['nullable', 'exists:approval_chains,id'],
            'sort_order'               => ['integer', 'min:0'],
            'is_active'                => ['boolean'],
            'extra_fields'             => ['nullable', 'json'],
        ]);

        $extraFields = $request->extra_fields
            ? json_decode($request->extra_fields, true)
            : ($formCategory->extra_fields ?? []);

        $formCategory->update([
            'name'                      => $request->name,
            'description'               => $request->description,
            'requires_reason'           => $request->boolean('requires_reason'),
            'requires_logistic_table'   => $request->boolean('requires_logistic_table'),
            'requires_attachments'      => $request->boolean('requires_attachments'),
            'requires_vendor_selection' => $request->boolean('requires_vendor_selection'),
            'auto_generate_next'        => $request->boolean('auto_generate_next'),
            'bypasses_procurement_to_payment' => $request->boolean('bypasses_procurement_to_payment'),
            'approval_chain_id'         => $request->approval_chain_id,
            'sort_order'                => $request->sort_order ?? 0,
            'is_active'                 => $request->boolean('is_active', true),
            'extra_fields'              => $extraFields,
        ]);

        AuditLog::record(Auth::user(), 'form_category.updated', $formCategory, $formCategory->name);

        return redirect()->route('admin.form-categories.index')
            ->with('success', "Form category updated.");
    }

    public function destroy(FormCategory $formCategory): RedirectResponse
    {
        if ($formCategory->is_system) {
            return back()->withErrors(['error' => 'System form categories cannot be deleted.']);
        }
        if ($formCategory->forms()->exists()) {
            return back()->withErrors(['error' => 'Cannot delete a category that has forms. Deactivate it instead.']);
        }

        AuditLog::record(Auth::user(), 'form_category.deleted', $formCategory, $formCategory->name);
        $formCategory->delete();

        return redirect()->route('admin.form-categories.index')
            ->with('success', 'Form category removed.');
    }
}
