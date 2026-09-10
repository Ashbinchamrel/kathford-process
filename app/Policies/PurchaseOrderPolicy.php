<?php

namespace App\Policies;

use App\Models\PurchaseOrder;
use App\Models\User;

class PurchaseOrderPolicy
{
    public function view(User $user, PurchaseOrder $purchaseOrder): bool
    {
        if ($user->isSuperAdmin() || $user->isFinance() || $purchaseOrder->generated_by === $user->id) return true;

        return $purchaseOrder->approvalChain?->hasVerifier($user->id)
            || $purchaseOrder->approvalChain?->hasApprover($user->id);
    }

    public function update(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $user->isSuperAdmin()
            || ($purchaseOrder->generated_by === $user->id && $purchaseOrder->isEditable());
    }

    public function delete(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $this->update($user, $purchaseOrder);
    }

    public function verify(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return app(\App\Services\ApprovalService::class)->canVerify($purchaseOrder, $user);
    }

    public function finalApprove(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return app(\App\Services\ApprovalService::class)->canApprove($purchaseOrder, $user);
    }
}
