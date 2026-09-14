<?php

namespace App\Support;

use App\Models\ActivityForm;
use App\Models\GoodsReceivedNote;
use App\Models\Payment;
use App\Models\PaymentAuthorisation;
use App\Models\ProcurementChecklist;
use App\Models\PurchaseOrder;
use App\Models\Rfq;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Builder;

class RecordVisibility
{
    public const MODULES = [
        'activity-forms' => [ActivityForm::class, 'creator_id', 'activity_forms'],
        'rfq' => [Rfq::class, 'created_by', 'rfq'],
        'purchase-orders' => [PurchaseOrder::class, 'generated_by', 'purchase_orders'],
        'payments' => [Payment::class, 'created_by', 'payments'],
        'payment-authorisations' => [PaymentAuthorisation::class, 'created_by', 'payment_authorisations'],
        'vendors' => [Vendor::class, 'created_by', 'vendors'],
        'grn' => [GoodsReceivedNote::class, 'received_by_user_id', 'grn'],
        'checklists' => [ProcurementChecklist::class, null, 'checklists'],
    ];

    /**
     * Modules that are back-office work queues (RFQ, PO, Payments, ...) rather
     * than personal submissions: anyone granted the module's `.view`
     * permission can see every record, not just ones they created or were
     * personally assigned/chained to. Activity Forms are deliberately
     * excluded — those are individual staff submissions, and their `.view`
     * permission is typically granted broadly, so they stay scoped to
     * ownership + approval-chain membership.
     */
    public const QUEUE_MODULES = [
        'rfq', 'purchase_orders', 'payments', 'payment_authorisations', 'vendors', 'grn', 'checklists',
    ];

    public static function apply(Builder $query, User $user): Builder
    {
        if ($user->isSuperAdmin()) {
            return $query;
        }
        foreach (self::MODULES as [$class, $owner, $module]) {
            if (! ($query->getModel() instanceof $class)) {
                continue;
            }
            if (in_array($module, self::QUEUE_MODULES, true) && ChainAccess::explicit($user, $module.'.view')) {
                return $query;
            }

            return $query->where(function ($q) use ($owner, $module, $user) {
                if ($owner) {
                    $q->where($q->getModel()->qualifyColumn($owner), $user->id);
                } else {
                    // Checklists are generated from supplier bills, so ownership follows the PO.
                    $q->whereHas('purchaseOrder', fn ($po) => $po->where('generated_by', $user->id));
                }
                if ($module === 'rfq') {
                    $q->orWhere('assigned_to', $user->id);
                }
                if (method_exists($q->getModel(), 'approvalActions')) {
                    $q->orWhereHas('approvalActions', fn ($actions) => $actions->where('actor_id', $user->id));
                }
                if (method_exists($q->getModel(), 'approvalChain')) {
                    foreach (['verify' => 'verifiers', 'approve' => 'approvers'] as $permission => $relation) {
                        if ($user->is_active) {
                            $q->orWhere(fn ($assigned) => $assigned->where('status', $permission === 'verify' ? 'pending_verification' : 'pending_approval')
                                ->whereHas('approvalChain.'.$relation, fn ($members) => $members->where('users.id', $user->id)));
                        }
                    }
                }
            });
        }

        return $query;
    }
}
