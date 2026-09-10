<?php

namespace App\Policies;

use App\Models\ActivityForm;
use App\Models\User;

class ActivityFormPolicy
{
    public function view(User $user, ActivityForm $form): bool
    {
        return $user->can('activity_forms.view') && ActivityForm::forUser($user)->whereKey($form->id)->exists();
    }

    public function update(User $user, ActivityForm $form): bool
    {
        if ($user->isSuperAdmin()) return true;
        // Creator can edit while draft or rejected
        return $user->can('activity_forms.edit') && $form->creator_id === $user->id && $form->isEditable();
    }

    public function delete(User $user, ActivityForm $form): bool
    {
        if ($user->isSuperAdmin()) return true;
        return $user->can('activity_forms.delete') && $form->creator_id === $user->id && $form->status === 'draft';
    }

    /** Only the current ordered verifier layer can verify. */
    public function verify(User $user, ActivityForm $form): bool
    {
        return $user->can('activity_forms.verify') && app(\App\Services\ApprovalService::class)->canVerify($form, $user);
    }

    /** Only the current ordered approver layer can approve. */
    public function finalApprove(User $user, ActivityForm $form): bool
    {
        return $user->can('activity_forms.approve') && app(\App\Services\ApprovalService::class)->canApprove($form, $user);
    }
}
