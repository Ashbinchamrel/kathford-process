<?php

namespace App\Support;

/** Parent records determine the year of their dependent transactions. */
class FiscalYearRecords
{
    public const PARENTS = [
        'department_budgets' => [],
        'activity_forms' => ['budget_id' => 'department_budgets'],
        'purchase_requests' => ['activity_form_id' => 'activity_forms'],
        'rfqs' => ['budget_id' => 'department_budgets', 'activity_form_id' => 'activity_forms', 'purchase_request_id' => 'purchase_requests'],
        'rfq_quotes' => ['rfq_id' => 'rfqs'],
        'purchase_orders' => ['rfq_quote_id' => 'rfq_quotes', 'purchase_request_id' => 'purchase_requests'],
        'goods_received_notes' => ['purchase_order_id' => 'purchase_orders'],
        'vendor_bills' => ['purchase_order_id' => 'purchase_orders'],
        'procurement_checklists' => ['vendor_bill_id' => 'vendor_bills', 'purchase_order_id' => 'purchase_orders'],
        'payment_authorisations' => [],
        'payments' => ['activity_form_id' => 'activity_forms', 'purchase_order_id' => 'purchase_orders', 'vendor_bill_id' => 'vendor_bills', 'parent_payment_id' => 'payments', 'payment_authorisation_id' => 'payment_authorisations'],
    ];
}
