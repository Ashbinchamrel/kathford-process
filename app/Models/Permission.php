<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permission extends Model
{
    use HasUuids;

    protected $fillable = ['key', 'label', 'module', 'sort_order'];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_permissions')
            ->withPivot('granted_by', 'granted_at')
            ->withTimestamps();
    }

    /** All permissions grouped by module, ordered for UI display */
    public static function allGrouped(): array
    {
        return static::where('module', '!=', 'grn')->orderBy('module')->orderBy('sort_order')
            ->get()
            ->groupBy('module')
            ->toArray();
    }

    /** Seed the canonical permission set */
    public static function seedDefaults(): void
    {
        $definitions = [
            ['module' => 'procurement_setup', 'key' => 'procurement_setup.manage', 'label' => 'Manage Procurement Setup', 'sort_order' => 1],
            // Activity Forms
            ['module' => 'activity_forms', 'key' => 'activity_forms.view',    'label' => 'View Activity Forms',    'sort_order' => 1],
            ['module' => 'activity_forms', 'key' => 'activity_forms.create',  'label' => 'Create Activity Forms',  'sort_order' => 2],
            ['module' => 'activity_forms', 'key' => 'activity_forms.edit',    'label' => 'Edit Activity Forms',    'sort_order' => 3],
            ['module' => 'activity_forms', 'key' => 'activity_forms.delete',  'label' => 'Delete Activity Forms',  'sort_order' => 4],
            ['module' => 'activity_forms', 'key' => 'activity_forms.submit',  'label' => 'Submit for Verification','sort_order' => 5],
            ['module' => 'activity_forms', 'key' => 'activity_forms.verify',  'label' => 'Verify Activity Forms',  'sort_order' => 6],
            ['module' => 'activity_forms', 'key' => 'activity_forms.approve', 'label' => 'Approve Activity Forms', 'sort_order' => 7],
            // Purchase Requests
            ['module' => 'purchase_requests', 'key' => 'purchase_requests.view',    'label' => 'View Purchase Requests',    'sort_order' => 1],
            ['module' => 'purchase_requests', 'key' => 'purchase_requests.create',  'label' => 'Create Purchase Requests',  'sort_order' => 2],
            ['module' => 'purchase_requests', 'key' => 'purchase_requests.edit',    'label' => 'Edit Purchase Requests',    'sort_order' => 3],
            ['module' => 'purchase_requests', 'key' => 'purchase_requests.delete',  'label' => 'Delete Purchase Requests',  'sort_order' => 4],
            ['module' => 'purchase_requests', 'key' => 'purchase_requests.submit',  'label' => 'Submit Purchase Requests',  'sort_order' => 5],
            ['module' => 'purchase_requests', 'key' => 'purchase_requests.verify',  'label' => 'Verify Purchase Requests',  'sort_order' => 6],
            ['module' => 'purchase_requests', 'key' => 'purchase_requests.approve', 'label' => 'Approve Purchase Requests', 'sort_order' => 7],
            // RFQ
            ['module' => 'rfq', 'key' => 'rfq.view',         'label' => 'View RFQs',              'sort_order' => 1],
            ['module' => 'rfq', 'key' => 'rfq.create',       'label' => 'Create RFQs',            'sort_order' => 2],
            ['module' => 'rfq', 'key' => 'rfq.edit',         'label' => 'Edit RFQs',              'sort_order' => 3],
            ['module' => 'rfq', 'key' => 'rfq.send',         'label' => 'Send RFQ to Vendors',    'sort_order' => 4],
            ['module' => 'rfq', 'key' => 'rfq.accept_quote', 'label' => 'Accept Quotation',       'sort_order' => 5],
            ['module' => 'rfq', 'key' => 'rfq.delete',       'label' => 'Delete RFQs',            'sort_order' => 6],
            // Purchase Orders
            ['module' => 'purchase_orders', 'key' => 'purchase_orders.view',   'label' => 'View Purchase Orders',       'sort_order' => 1],
            ['module' => 'purchase_orders', 'key' => 'purchase_orders.create', 'label' => 'Create Purchase Orders',     'sort_order' => 2],
            ['module' => 'purchase_orders', 'key' => 'purchase_orders.edit',   'label' => 'Edit Purchase Orders',       'sort_order' => 3],
            ['module' => 'purchase_orders', 'key' => 'purchase_orders.send',   'label' => 'Send PO to Vendor',          'sort_order' => 4],
            ['module' => 'purchase_orders', 'key' => 'purchase_orders.delete', 'label' => 'Delete Purchase Orders',     'sort_order' => 5],
            ['module' => 'purchase_orders', 'key' => 'purchase_orders.submit', 'label' => 'Submit Purchase Orders',     'sort_order' => 6],
            ['module' => 'purchase_orders', 'key' => 'purchase_orders.verify', 'label' => 'Verify Purchase Orders',     'sort_order' => 7],
            ['module' => 'purchase_orders', 'key' => 'purchase_orders.approve','label' => 'Approve Purchase Orders',    'sort_order' => 8],
            // GRN
            ['module' => 'grn', 'key' => 'grn.view',    'label' => 'View Goods Received Notes', 'sort_order' => 1],
            ['module' => 'grn', 'key' => 'grn.create',  'label' => 'Record Goods Received',     'sort_order' => 2],
            ['module' => 'grn', 'key' => 'grn.confirm', 'label' => 'Confirm GRN',               'sort_order' => 3],
            // Payments
            ['module' => 'payments', 'key' => 'payments.view',      'label' => 'View Payments',          'sort_order' => 1],
            ['module' => 'payments', 'key' => 'payments.create',    'label' => 'Create Payment Schedule', 'sort_order' => 2],
            ['module' => 'payments', 'key' => 'payments.edit',      'label' => 'Edit Payments',           'sort_order' => 3],
            ['module' => 'payments', 'key' => 'payments.import_vendor_invoices', 'label' => 'Import Pending Vendor Invoices', 'sort_order' => 4],
            ['module' => 'payments', 'key' => 'payments.mark_paid', 'label' => 'Mark Payment as Paid',    'sort_order' => 5],
            ['module' => 'payments', 'key' => 'payments.authorise', 'label' => 'Create Payment Authorisations', 'sort_order' => 6],
            // Payment Authorisation
            ['module' => 'payment_authorisations', 'key' => 'payment_authorisations.view', 'label' => 'View Payment Authorisations', 'sort_order' => 1],
            ['module' => 'payment_authorisations', 'key' => 'payment_authorisations.create', 'label' => 'Create Payment Authorisations', 'sort_order' => 2],
            ['module' => 'payment_authorisations', 'key' => 'payment_authorisations.submit', 'label' => 'Submit for Approval', 'sort_order' => 3],
            ['module' => 'payment_authorisations', 'key' => 'payment_authorisations.verify', 'label' => 'Verify Payment Authorisations', 'sort_order' => 4],
            ['module' => 'payment_authorisations', 'key' => 'payment_authorisations.approve', 'label' => 'Approve Payment Authorisations', 'sort_order' => 5],
            ['module' => 'payment_authorisations', 'key' => 'payment_authorisations.export', 'label' => 'Download Bank CSV', 'sort_order' => 6],
            ['module' => 'checklists', 'key' => 'checklists.view', 'label' => 'View Procurement Checklists', 'sort_order' => 1],
            ['module' => 'checklists', 'key' => 'checklists.complete', 'label' => 'Complete and Send Checklists', 'sort_order' => 2],
            ['module' => 'budgets', 'key' => 'budgets.view', 'label' => 'View Budgets', 'sort_order' => 1],
            ['module' => 'budgets', 'key' => 'budgets.manage', 'label' => 'Manage Budgets', 'sort_order' => 2],
            // Vendors
            ['module' => 'vendors', 'key' => 'vendors.view',   'label' => 'View Vendors',   'sort_order' => 1],
            ['module' => 'vendors', 'key' => 'vendors.create', 'label' => 'Add Vendors',    'sort_order' => 2],
            ['module' => 'vendors', 'key' => 'vendors.edit',   'label' => 'Edit Vendors',   'sort_order' => 3],
            ['module' => 'vendors', 'key' => 'vendors.delete', 'label' => 'Delete Vendors', 'sort_order' => 4],
            // Admin
            ['module' => 'admin', 'key' => 'admin.users',           'label' => 'Manage Users',            'sort_order' => 1],
            ['module' => 'admin', 'key' => 'admin.departments',     'label' => 'Manage Departments',      'sort_order' => 2],
            ['module' => 'admin', 'key' => 'admin.approval_chains', 'label' => 'Manage Approval Chains',  'sort_order' => 3],
            ['module' => 'admin', 'key' => 'admin.form_categories', 'label' => 'Manage Form Categories',  'sort_order' => 4],
            ['module' => 'admin', 'key' => 'admin.audit_logs',      'label' => 'View Audit Logs',         'sort_order' => 5],
            ['module' => 'admin', 'key' => 'admin.payees',          'label' => 'Manage Payees and Payment Accounts', 'sort_order' => 6],
            ['module' => 'admin', 'key' => 'admin.profile',         'label' => 'Manage Organisation Profile', 'sort_order' => 7],
        ];

        foreach ($definitions as $def) {
            static::firstOrCreate(['key' => $def['key']], $def);
        }
    }
}
