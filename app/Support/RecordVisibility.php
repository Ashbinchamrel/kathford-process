<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class RecordVisibility
{
    public const MODULES = [
        'activity-forms' => [\App\Models\ActivityForm::class, 'creator_id', 'activity_forms'],
        'rfq' => [\App\Models\Rfq::class, 'created_by', 'rfq'],
        'purchase-orders' => [\App\Models\PurchaseOrder::class, 'generated_by', 'purchase_orders'],
        'payments' => [\App\Models\Payment::class, 'created_by', 'payments'],
        'payment-authorisations' => [\App\Models\PaymentAuthorisation::class, 'created_by', 'payment_authorisations'],
        'vendors' => [\App\Models\Vendor::class, 'created_by', 'vendors'],
        'grn' => [\App\Models\GoodsReceivedNote::class, 'received_by_user_id', 'grn'],
        'checklists' => [\App\Models\ProcurementChecklist::class, null, 'checklists'],
    ];

    public static function apply(Builder $query, User $user): Builder
    {
        if ($user->isSuperAdmin()) return $query;
        foreach (self::MODULES as [$class, $owner, $module]) {
            if (! ($query->getModel() instanceof $class)) continue;
            return $query->where(function ($q) use ($owner, $module, $user) {
                if ($owner) {
                    $q->where($q->getModel()->qualifyColumn($owner), $user->id);
                } else {
                    // Checklists are generated from supplier bills, so ownership follows the PO.
                    $q->whereHas('purchaseOrder', fn ($po) => $po->where('generated_by', $user->id));
                }
                if ($module === 'rfq') $q->orWhere('assigned_to', $user->id);
                if (method_exists($q->getModel(), 'approvalActions')) {
                    $q->orWhereHas('approvalActions', fn ($actions) => $actions->where('actor_id', $user->id));
                }
                if (method_exists($q->getModel(), 'approvalChain')) {
                    foreach (['verify' => 'verifiers', 'approve' => 'approvers'] as $permission => $relation) {
                        if ($user->can($module.'.'.$permission)) {
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
