<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\TwoFactorController;
use App\Http\Controllers\Admin\ApprovalChainController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\DepartmentController;
use App\Http\Controllers\Admin\FormCategoryController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\PayeeInformationController;
use App\Http\Controllers\Admin\CompanySetupController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\TransactionBulkDeleteController;
use App\Http\Controllers\ActivityFormController;
use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\BudgetController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GoodsReceivedController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PaymentAuthorisationController;
use App\Http\Controllers\ProcurementChecklistController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\RfqController;
use App\Http\Controllers\VendorController;
use App\Http\Controllers\VendorPortalController;
use Illuminate\Support\Facades\Route;

// ── Public: Auth ──────────────────────────────────────────────
Route::middleware('guest')->group(function () {
    Route::get('/login', fn() => view('auth.login'))->name('login');
    Route::post('/login', [LoginController::class, 'authenticate'])->name('login.authenticate');

    Route::get('/forgot-password', [PasswordResetController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'store'])->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'edit'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'update'])->name('password.update');
});

// ── 2FA (after password login, before full session) ───────────
Route::middleware('web')->group(function () {
    Route::get('/2fa/setup', [TwoFactorController::class, 'showSetup'])->name('2fa.setup');
    Route::post('/2fa/setup', [TwoFactorController::class, 'confirmSetup'])->name('2fa.setup.confirm');
    Route::get('/2fa/challenge', [TwoFactorController::class, 'showChallenge'])->name('2fa.challenge');
    Route::post('/2fa/challenge', [TwoFactorController::class, 'verifyChallenge'])->name('2fa.verify');
});

Route::post('/logout', [TwoFactorController::class, 'logout'])->name('logout')->middleware('auth');

// ── Vendor Portal (token-gated, no auth required) ─────────────
Route::get('/rfq/vendor/{token}', [RfqController::class, 'vendorPortal'])->name('rfq.vendor.portal');
Route::post('/rfq/vendor/{token}/submit', [RfqController::class, 'vendorSubmit'])->name('rfq.vendor-submit');

// ── Vendor Portal (separate password login; no staff 2FA) ────
Route::get('/vendor/login', [VendorPortalController::class, 'login'])->name('vendor.portal.login');
Route::post('/vendor/login', [VendorPortalController::class, 'authenticate'])->name('vendor.portal.authenticate');
Route::middleware('vendor.portal')->prefix('vendor/portal')->name('vendor.portal.')->group(function () {
    Route::get('/', [VendorPortalController::class, 'dashboard'])->name('dashboard');
    Route::get('/purchase-orders', [VendorPortalController::class, 'orders'])->name('orders.index');
    Route::get('/quotation-requests', [VendorPortalController::class, 'quotes'])->name('quotes.index');
    Route::get('/payments', [VendorPortalController::class, 'payments'])->name('payments.index');
    Route::get('/account-statement', [VendorPortalController::class, 'statement'])->name('statement');
    Route::post('/logout', [VendorPortalController::class, 'logout'])->name('logout');
    Route::get('/quotes/{quote}', [VendorPortalController::class, 'quote'])->name('quotes.show');
    Route::post('/quotes/{quote}', [VendorPortalController::class, 'submitQuote'])->name('quotes.submit');
    Route::get('/orders/{purchaseOrder}', [VendorPortalController::class, 'purchaseOrder'])->name('orders.show');
    Route::get('/orders/{purchaseOrder}/pdf', [VendorPortalController::class, 'purchaseOrderPdf'])->name('orders.pdf');
    Route::post('/orders/{purchaseOrder}/bills', [VendorPortalController::class, 'submitBill'])->name('orders.bills.store');
    Route::get('/profile', [VendorPortalController::class, 'profile'])->name('profile.edit');
    Route::put('/profile', [VendorPortalController::class, 'updateProfile'])->name('profile.update');
    Route::get('/password', [VendorPortalController::class, 'changePasswordForm'])->name('password.edit');
    Route::post('/password', [VendorPortalController::class, 'changePassword'])->name('password.update');
});

// ── Authenticated Routes ──────────────────────────────────────
Route::middleware(['auth', 'active', \App\Http\Middleware\EnsureRecordVisibility::class])->group(function () {

    Route::post('fiscal-year/select', [\App\Http\Controllers\Admin\FiscalYearController::class, 'select'])->name('fiscal-year.select');

    Route::post('dashboard/widgets',[DashboardController::class,'saveWidgets'])->name('dashboard.widgets');
    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.mark-read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.mark-all-read');

    // Attachments (download any accessible attachment)
    Route::get('/attachments/{attachment}/download', [AttachmentController::class, 'download'])->name('attachments.download');

    // Activity Forms
    Route::resource('activity-forms', ActivityFormController::class)
        ->middlewareFor(['index', 'show'], 'can:activity_forms.view')
        ->middlewareFor(['create', 'store'], 'can:activity_forms.create')
        ->middlewareFor(['edit', 'update'], 'can:activity_forms.edit')
        ->middlewareFor('destroy', 'can:activity_forms.delete');
    Route::post('activity-forms/{activityForm}/submit', [ActivityFormController::class, 'submit'])->middleware('can:activity_forms.submit')->name('activity-forms.submit');
    Route::post('activity-forms/{activityForm}/verify', [ActivityFormController::class, 'verify'])->middleware('can:activity_forms.verify')->name('activity-forms.verify');
    Route::post('activity-forms/{activityForm}/approve', [ActivityFormController::class, 'approve'])->middleware('can:activity_forms.approve')->name('activity-forms.approve');
    Route::get('activity-forms/{activityForm}/attachments/{attachment}/download', [ActivityFormController::class, 'downloadAttachment'])->middleware('can:activity_forms.view')->name('activity-forms.attachment.download');
    Route::delete('activity-forms/{activityForm}/attachments/{attachment}', [ActivityFormController::class, 'deleteAttachment'])->middleware('can:activity_forms.edit')->name('activity-forms.attachment.delete');

    // Department Budgets (finance and super-admin only; authorization is also enforced in the controller)
    Route::get('budgets', [BudgetController::class, 'index'])->middleware('can:budgets.view')->name('budgets.index');
    Route::post('budgets', [BudgetController::class, 'store'])->middleware('can:budgets.manage')->name('budgets.store');
    Route::put('budgets/{budget}', [BudgetController::class, 'update'])->middleware('can:budgets.manage')->name('budgets.update');
    Route::post('budgets/upload', [BudgetController::class, 'upload'])->middleware('can:budgets.manage')->name('budgets.upload');

    // RFQ / Quotation
    Route::resource('rfq', RfqController::class)
        ->middlewareFor(['index', 'show'], 'can:rfq.view')
        ->middlewareFor(['create', 'store'], 'can:rfq.create')
        ->middlewareFor(['edit', 'update'], 'can:rfq.edit')
        ->middlewareFor('destroy', 'can:rfq.delete');
    Route::post('rfq/{rfq}/invite-additional', [RfqController::class, 'inviteAdditional'])->middleware('can:rfq.send')->name('rfq.invite-additional');
    Route::post('rfq/{rfq}/send', [RfqController::class, 'send'])->middleware('can:rfq.send')->name('rfq.send');
    Route::get('rfq/{rfq}/compare', [RfqController::class, 'compare'])->middleware('can:rfq.view')->name('rfq.compare');
    Route::get('rfq/{rfq}/pdf', [RfqController::class, 'pdf'])->middleware('can:rfq.view')->name('rfq.pdf');
    Route::post('rfq/{rfq}/quotes/{quote}/accept', [RfqController::class, 'acceptQuote'])->middleware('can:rfq.accept_quote')->name('rfq.accept-quote');
    Route::post('rfq/{rfq}/quotes/{quote}/items/{quoteItem}/accept', [RfqController::class, 'acceptQuoteItem'])->middleware('can:rfq.accept_quote')->name('rfq.accept-quote-item');
    Route::post('rfq/{rfq}/quotes/{quote}/items/{quoteItem}/negotiate', [RfqController::class, 'requestNegotiation'])->middleware('can:rfq.accept_quote')->name('rfq.request-negotiation');
    Route::post('rfq/{rfq}/quotes/{quote}/items/{quoteItem}/reject', [RfqController::class, 'rejectQuoteItem'])->middleware('can:rfq.accept_quote')->name('rfq.reject-quote-item');
    Route::post('rfq/{rfq}/quotes/manual', [RfqController::class, 'manualQuote'])->middleware('can:rfq.create')->name('rfq.manual-quote');

    // Purchase Orders
    Route::resource('purchase-orders', PurchaseOrderController::class)
        ->middlewareFor(['index', 'show'], 'can:purchase_orders.view')
        ->middlewareFor(['create', 'store'], 'can:purchase_orders.create')
        ->middlewareFor(['edit', 'update'], 'can:purchase_orders.edit')
        ->middlewareFor('destroy', 'can:purchase_orders.delete');
    Route::get('purchase-orders/{purchaseOrder}/pdf', [PurchaseOrderController::class, 'pdf'])->middleware('can:purchase_orders.view')->name('purchase-orders.pdf');
    Route::post('purchase-orders/{purchaseOrder}/send', [PurchaseOrderController::class, 'sendToVendor'])->middleware('can:purchase_orders.send')->name('purchase-orders.send');
    Route::post('purchase-orders/{purchaseOrder}/submit', [PurchaseOrderController::class, 'submit'])->middleware('can:purchase_orders.submit')->name('purchase-orders.submit');
    Route::post('purchase-orders/{purchaseOrder}/verify', [PurchaseOrderController::class, 'verify'])->middleware('can:purchase_orders.verify')->name('purchase-orders.verify');
    Route::post('purchase-orders/{purchaseOrder}/approve', [PurchaseOrderController::class, 'approve'])->middleware('can:purchase_orders.approve')->name('purchase-orders.approve');
    Route::get('purchase-orders/{purchaseOrder}/vendor-bills/{vendorBill}/download', [PurchaseOrderController::class, 'downloadVendorBill'])->middleware('can:purchase_orders.view')->name('purchase-orders.vendor-bills.download');

    // Bill-driven control checklist (replaces the operational use of Goods Received).
    Route::get('checklists', [ProcurementChecklistController::class, 'index'])->middleware('can:checklists.view')->name('checklists.index');
    Route::get('checklists/{checklist}', [ProcurementChecklistController::class, 'show'])->middleware('can:checklists.view')->name('checklists.show');
    Route::post('checklists/{checklist}/return', [ProcurementChecklistController::class,'returnToVendor'])->middleware('can:checklists.complete')->name('checklists.return');
    Route::put('checklists/{checklist}', [ProcurementChecklistController::class, 'update'])->middleware('can:checklists.complete')->name('checklists.update');
    Route::post('checklists/{checklist}/send-to-accounts', [ProcurementChecklistController::class, 'sendToAccounts'])->middleware('can:checklists.complete')->name('checklists.send-to-accounts');
    Route::get('checklists/{checklist}/pdf', [ProcurementChecklistController::class, 'pdf'])->middleware('can:checklists.view')->name('checklists.pdf');

    // Vendors
    Route::resource('vendors', VendorController::class)
        ->middlewareFor(['index', 'show'], 'can:vendors.view')
        ->middlewareFor(['create', 'store'], 'can:vendors.create')
        ->middlewareFor(['edit', 'update'], 'can:vendors.edit')
        ->middlewareFor('destroy', 'can:vendors.delete');
    Route::get('vendors/{vendor}/statement', [VendorController::class, 'statement'])->middleware('can:vendors.view')->name('vendors.statement');
    Route::post('vendors/{vendor}/portal-access', [VendorController::class, 'configurePortal'])->middleware('can:vendors.edit')->name('vendors.portal-access');

    // Payments
    Route::get('payments/import-template', [PaymentController::class, 'importTemplate'])->middleware('can:payments.import_vendor_invoices')->name('payments.import-template');
    Route::post('payments/import', [PaymentController::class, 'import'])->middleware('can:payments.import_vendor_invoices')->name('payments.import');
    Route::resource('payments', PaymentController::class)->only(['index', 'create', 'store', 'show', 'edit', 'update'])
        ->middlewareFor(['index', 'show'], 'can:payments.view')
        ->middlewareFor(['create', 'store'], 'can:payments.create')
        ->middlewareFor(['edit', 'update'], 'can:payments.edit');
    Route::post('payments/{payment}/mark-paid', [PaymentController::class, 'markPaid'])->middleware('can:payments.mark_paid')->name('payments.mark-paid');

    Route::resource('payment-authorisations', PaymentAuthorisationController::class)->only(['index', 'create', 'store', 'show'])
        ->middlewareFor(['index', 'show'], 'can:payment_authorisations.view')
        ->middlewareFor(['create', 'store'], 'can:payment_authorisations.create');
    Route::post('payment-authorisations/{paymentAuthorisation}/submit', [PaymentAuthorisationController::class, 'submit'])->middleware('can:payment_authorisations.submit')->name('payment-authorisations.submit');
    Route::post('payment-authorisations/{paymentAuthorisation}/verify', [PaymentAuthorisationController::class, 'verify'])->middleware('can:payment_authorisations.verify')->name('payment-authorisations.verify');
    Route::post('payment-authorisations/{paymentAuthorisation}/approve', [PaymentAuthorisationController::class, 'approve'])->middleware('can:payment_authorisations.approve')->name('payment-authorisations.approve');
    Route::get('payment-authorisations/{paymentAuthorisation}/csv', [PaymentAuthorisationController::class, 'csv'])->middleware('can:payment_authorisations.export')->name('payment-authorisations.csv');

    Route::get('settings', function () {
        abort_unless(auth()->user()->isSuperAdmin() || auth()->user()->can('procurement_setup.manage'), 403);
        return view('settings.index');
    })->name('settings.index');
    Route::prefix('admin')->name('admin.')->middleware('can:procurement_setup.manage')->group(function () {
        Route::post('procurement-setup/assignment', [\App\Http\Controllers\Admin\ProcurementSetupController::class,'saveAssignment'])->name('procurement.assignment');
        Route::get('procurement-setup', [\App\Http\Controllers\Admin\ProcurementSetupController::class,'index'])->name('procurement.index');
        Route::post('procurement-setup/rates', [\App\Http\Controllers\Admin\ProcurementSetupController::class,'saveRate'])->name('procurement.rates.store');
        Route::get('procurement-setup/rates/template', [\App\Http\Controllers\Admin\ProcurementSetupController::class,'rateTemplate'])->name('procurement.rates.template');
        Route::post('procurement-setup/rates/import', [\App\Http\Controllers\Admin\ProcurementSetupController::class,'importRates'])->name('procurement.rates.import');
        Route::put('procurement-setup/rates/{vendorRate}', [\App\Http\Controllers\Admin\ProcurementSetupController::class,'saveRate'])->name('procurement.rates.update');
        Route::delete('procurement-setup/rates/{vendorRate}', [\App\Http\Controllers\Admin\ProcurementSetupController::class,'deleteRate'])->name('procurement.rates.destroy');
        Route::post('procurement-setup/questions', [\App\Http\Controllers\Admin\ProcurementSetupController::class,'saveQuestion'])->name('procurement.questions.store');
        Route::put('procurement-setup/questions/{question}', [\App\Http\Controllers\Admin\ProcurementSetupController::class,'saveQuestion'])->name('procurement.questions.update');
        Route::delete('procurement-setup/questions/{question}', [\App\Http\Controllers\Admin\ProcurementSetupController::class,'deleteQuestion'])->name('procurement.questions.destroy');
    });

    // ── Admin Panel ───────────────────────────────────────────
    Route::prefix('admin')->name('admin.')->middleware('role:super_admin')->group(function () {
        Route::delete('transactions/{module}', [TransactionBulkDeleteController::class, 'destroy'])->name('transactions.bulk-delete');
        Route::resource('users', UserController::class);
        Route::post('users/{user}/reset-2fa', [UserController::class, 'reset2fa'])->name('users.reset-2fa');

        Route::resource('departments', DepartmentController::class);

        Route::get('approval-chain', [ApprovalChainController::class, 'index'])->name('approval-chain.index');
        Route::post('approval-chain', [ApprovalChainController::class, 'store'])->name('approval-chain.store');
        Route::post('approval-chain/purchase-orders', [ApprovalChainController::class, 'updatePurchaseOrderChain'])->name('approval-chain.purchase-orders.update');
        Route::post('approval-chain/payment-authorisations', [ApprovalChainController::class, 'updatePaymentAuthorisationChain'])->name('approval-chain.payment-authorisations.update');
        Route::post('approval-chain/payment-authorisation-channels', [ApprovalChainController::class, 'storePaymentAuthorisationChannel'])->name('approval-chain.payment-authorisation-channels.store');
        Route::put('approval-chain/payment-authorisation-channels/{paymentAuthorisationChannel}', [ApprovalChainController::class, 'updatePaymentAuthorisationChannel'])->name('approval-chain.payment-authorisation-channels.update');
        Route::put('approval-chain/{approvalChain}', [ApprovalChainController::class, 'update'])->name('approval-chain.update');
        Route::delete('approval-chain/{approvalChain}', [ApprovalChainController::class, 'destroy'])->name('approval-chain.destroy');

        Route::resource('form-categories', FormCategoryController::class)->except(['show']);
        Route::post('fiscal-years', [\App\Http\Controllers\Admin\FiscalYearController::class, 'store'])->name('fiscal-years.store');
        Route::put('fiscal-years/{fiscalYear}', [\App\Http\Controllers\Admin\FiscalYearController::class, 'update'])->name('fiscal-years.update');
        Route::post('fiscal-years/{fiscalYear}/activate', [\App\Http\Controllers\Admin\FiscalYearController::class, 'activate'])->name('fiscal-years.activate');
        Route::get('profile', [CompanySetupController::class, 'company'])->name('profile.edit');
        Route::put('profile', [CompanySetupController::class, 'updateCompany'])->name('profile.update');
        Route::get('payee-information', [PayeeInformationController::class, 'index'])->name('payees.index');
        Route::post('payee-information/payees', [PayeeInformationController::class, 'storePayee'])->name('payees.store');
        Route::put('payee-information/payees/{payee}', [PayeeInformationController::class, 'updatePayee'])->name('payees.update');
        Route::delete('payee-information/payees/{payee}', [PayeeInformationController::class, 'destroyPayee'])->name('payees.destroy');
        Route::post('payee-information/accounts', [PayeeInformationController::class, 'storeAccount'])->name('payment-accounts.store');
        Route::put('payee-information/accounts/{paymentAccount}', [PayeeInformationController::class, 'updateAccount'])->name('payment-accounts.update');
        Route::delete('payee-information/accounts/{paymentAccount}', [PayeeInformationController::class, 'destroyAccount'])->name('payment-accounts.destroy');

        Route::get('audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');

        // Permission management
        Route::get('users/{user}/permissions', [PermissionController::class, 'edit'])->name('permissions.edit');
        Route::put('users/{user}/permissions', [PermissionController::class, 'update'])->name('permissions.update');
        Route::post('users/{user}/permissions/template', [PermissionController::class, 'applyTemplate'])->name('permissions.template');
        Route::get('audit-logs/{auditLog}', [AuditLogController::class, 'show'])->name('audit-logs.show');
    });
});

require __DIR__.'/planning.php';
