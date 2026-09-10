<?php
namespace App\Support;
use App\Models\User;
class DashboardReports {
    public const REPORTS=[
        'my_forms'=>['My Activity Forms','activity_forms.view'],
        'verification'=>['Awaiting My Verification','activity_forms.verify'],
        'approval'=>['Awaiting My Approval','activity_forms.approve'],
        'rfqs'=>['My RFQs and Quotations','rfq.view'],
        'purchase_orders'=>['My Purchase Orders','purchase_orders.view'],
        'payments'=>['My Payment Schedule','payments.view'],
        'payment_authorisations'=>['My Payment Authorisations','payment_authorisations.view'],
        'checklists'=>['My Checklists','checklists.view'],
    ];
    public static function available(User $user): array {
        return array_filter(self::REPORTS,fn($report)=>$user->can($report[1]) && (!in_array($report[1],['activity_forms.verify','activity_forms.approve']) || $user->can('activity_forms.view')));
    }
}
