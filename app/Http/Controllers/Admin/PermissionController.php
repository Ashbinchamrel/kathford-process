<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PermissionController extends Controller
{
    /** Show permission matrix for a user */
    public function edit(User $user): View
    {
        $user->loadMissing(['role', 'roles']);

        abort_if($user->isSuperAdmin(), 403, 'Super Admin permissions cannot be modified.');

        $moduleOrder = [
            'activity_forms', 'rfq', 'purchase_orders', 'checklists',
            'payments', 'payment_authorisations', 'vendors', 'budgets', 'procurement_setup', 'planning', 'admin',
        ];
        $moduleMeta = [
            'planning' => ['title' => 'Planning', 'description' => 'Plans, goals, budget approvals, support requests and team work.'],
            'procurement_setup' => ['title' => 'Procurement Setup', 'description' => 'Manage approved vendor rates, Excel uploads, RFQ assignment and checklist questions.'],
            'activity_forms' => ['title' => 'Activity Forms', 'description' => 'Create requests and manage the approval workflow.'],
            'rfq' => ['title' => 'RFQ & Quotations', 'description' => 'Invite vendors, review quotations, and award items.'],
            'purchase_orders' => ['title' => 'Purchase Orders', 'description' => 'Prepare, approve, and issue purchase orders.'],
            'checklists' => ['title' => 'Procurement Checklist', 'description' => 'Complete delivery controls and send bills to Finance.'],
            'payments' => ['title' => 'Payment Schedule', 'description' => 'Plan, adjust, and process scheduled payments.'],
            'payment_authorisations' => ['title' => 'Payment Authorisation', 'description' => 'Prepare approval batches and export approved bank files.'],
            'vendors' => ['title' => 'Vendors', 'description' => 'Maintain approved supplier information and portal access.'],
            'budgets' => ['title' => 'Budgets', 'description' => 'Review and maintain department budget allocations.'],
            'admin' => ['title' => 'Administration', 'description' => 'Manage configuration, users, and organisation controls.'],
        ];
        $allPermissions = Permission::whereIn('module', $moduleOrder)->orderBy('sort_order')->get()->groupBy('module');
        $groupedPermissions = collect($moduleOrder)
            ->filter(fn (string $module) => $allPermissions->has($module))
            ->mapWithKeys(fn (string $module) => [$module => ['meta' => $moduleMeta[$module], 'permissions' => $allPermissions[$module]]]);
        $userPermissionKeys  = $user->permissions()->pluck('permissions.key')->all();

        return view('admin.permissions.edit', compact('user', 'groupedPermissions', 'userPermissionKeys'));
    }

    /** Save permission checkboxes for a user */
    public function update(Request $request, User $user): RedirectResponse
    {
        abort_if($user->isSuperAdmin(), 403, 'Super Admin permissions cannot be modified.');

        $request->validate([
            'permissions'   => ['nullable', 'array'],
            'permissions.*' => ['exists:permissions,id'],
        ]);

        $permissionIds = $request->input('permissions', []);

        // Sync with pivot data
        $syncData = [];
        foreach ($permissionIds as $pid) {
            $syncData[$pid] = ['granted_by' => auth()->id(), 'granted_at' => now()];
        }

        $user->permissions()->sync($syncData);

        // Clear cached permission keys so Gate re-evaluates
        unset($user->_permissionKeys);

        AuditLog::record(
            auth()->user(),
            'admin.permissions.updated',
            $user,
            "Updated permissions for {$user->name}: " . count($permissionIds) . ' permissions granted'
        );

        return redirect()->route('admin.permissions.edit', $user)
            ->with('success', "Permissions updated for {$user->name}.");
    }

    /** Quick preset: grant all permissions matching a role template */
    public function applyTemplate(Request $request, User $user): RedirectResponse
    {
        abort_if($user->isSuperAdmin(), 403);

        $request->validate(['template' => ['required', 'in:verifier,approver,finance,general,none']]);

        $permissionMap = [
            'verifier' => [
                'activity_forms.view', 'activity_forms.verify',
                'purchase_orders.view', 'purchase_orders.verify',
                'checklists.view', 'checklists.complete',
                'payment_authorisations.view', 'payment_authorisations.verify',
            ],
            'approver' => [
                'activity_forms.view', 'activity_forms.approve',
                'rfq.view', 'rfq.accept_quote',
                'purchase_orders.view', 'purchase_orders.approve',
                'payment_authorisations.view', 'payment_authorisations.approve',
            ],
            'finance' => [
                'checklists.view', 'checklists.complete',
                'payments.view', 'payments.create', 'payments.edit', 'payments.import_vendor_invoices', 'payments.mark_paid',
                'payment_authorisations.view', 'payment_authorisations.create', 'payment_authorisations.submit', 'payment_authorisations.export',
                'vendors.view', 'vendors.create', 'vendors.edit',
                'budgets.view', 'budgets.manage',
            ],
            'general' => ['activity_forms.view', 'activity_forms.create', 'activity_forms.edit', 'activity_forms.submit'],
            'none' => [],
        ];

        $permissionKeys = $permissionMap[$request->template];

        if (empty($permissionKeys)) {
            $user->permissions()->detach();
        } else {
            $perms = Permission::whereIn('key', $permissionKeys)->get();
            $syncData = [];
            foreach ($perms as $p) {
                $syncData[$p->id] = ['granted_by' => auth()->id(), 'granted_at' => now()];
            }
            $user->permissions()->sync($syncData);
        }

        AuditLog::record(
            auth()->user(),
            'admin.permissions.template',
            $user,
            "Applied template '{$request->template}' for {$user->name}"
        );

        return redirect()->route('admin.permissions.edit', $user)
            ->with('success', "Template '{$request->template}' applied to {$user->name}.");
    }
}
