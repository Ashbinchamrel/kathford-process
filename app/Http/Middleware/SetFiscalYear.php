<?php

namespace App\Http\Middleware;

use App\Models\FiscalYear;
use App\Models\Setting;
use App\Support\FiscalYearContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

class SetFiscalYear
{
    public function handle(Request $request, Closure $next)
    {
        $context = app(FiscalYearContext::class);
        $context->enabled = false;
        if ($request->user() || $request->routeIs('vendor.portal.*', 'rfq.vendor.*', 'rfq.vendor-submit')) {
            $context->activeId = (int) Setting::get('active_fiscal_year_id', 0) ?: null;
            $selected = $request->user()?->isSuperAdmin() ? $request->session()->get('fiscal_year_id', $context->activeId) : $context->activeId;
            $context->year = FiscalYear::find($selected) ?? FiscalYear::find($context->activeId);
            $context->enabled = true;
            View::share('workingFiscalYear', $context->year);
            View::share('availableFiscalYears', FiscalYear::orderByDesc('starts_on')->orderByDesc('name')->get());
            View::share('fiscalYearWritable', $context->writable());
            View::share('activeFiscalYearId', $context->activeId);
            $transactionRoute = $request->routeIs('activity-forms.*', 'rfq.*', 'purchase-orders.*', 'grn.*', 'checklists.*', 'payments.*', 'payment-authorisations.*', 'budgets.*', 'admin.transactions.*', 'vendor.portal.quotes.submit', 'vendor.portal.orders.bills.store');
            if (!$request->isMethodSafe() && $transactionRoute) {
                $context->assertWritable();
                if ($request->filled('_fiscal_year_id') && (int) $request->input('_fiscal_year_id') !== $context->year?->id) {
                    throw \Illuminate\Validation\ValidationException::withMessages(['fiscal_year' => 'The fiscal year changed in another tab. Reload this form before saving.']);
                }
            }
            if ($request->isMethodSafe() && $transactionRoute && $request->routeIs('*.create', '*.edit')) $context->assertWritable();
        }
        return $next($request);
    }
}
