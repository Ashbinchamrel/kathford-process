<?php

namespace App\Support;

use App\Models\ActivityForm;
use App\Models\PaymentAuthorisation;
use App\Models\Planning\Document;
use App\Models\PurchaseOrder;
use App\Models\User;

/** Approval assignment grants document access, never general queue access. */
class ChainAccess
{
    public const MODELS = [
        'activity_forms' => ActivityForm::class,
        'purchase_orders' => PurchaseOrder::class,
        'payment_authorisations' => PaymentAuthorisation::class,
    ];

    public static function explicit(User $user, string $key): bool
    {
        return $user->isSuperAdmin() || $user->permissions()->where('key', $key)->exists();
    }

    public static function decisionKey(string $key): bool
    {
        return (bool) preg_match('/^(activity_forms|purchase_orders|payment_authorisations)\.(verify|approve)$|^planning\.(strategy|cmt_goals|goals|plan|budget)_(verify|approve)$/', $key);
    }

    public static function assignedPlanning($query, User $user, ?string $role = null)
    {
        if ($query->getModel()->getConnection()->getDriverName() === 'sqlite') {
            return $query->whereRaw("exists (select 1 from json_each(planning_documents.steps) step where json_extract(step.value, '$.user_id') = ?".($role ? " and json_extract(step.value, '$.action') = ?" : '').')', $role ? [$user->id, $role] : [$user->id]);
        }

        return $query->whereJsonContains('steps', array_filter(['user_id' => $user->id, 'action' => $role]));
    }

    public static function planningIds(User $user): array
    {
        $docs = self::assignedPlanning(Document::query(), $user)->get(['id', 'parent_id'])->merge(Document::where('type', 'cmt_goals')->where('status', 'approved')->whereHas('items', fn ($q) => $q->whereJsonContains('details->supporting_owner_ids', $user->id))->get(['id', 'parent_id']));
        $ids = $docs->pluck('id')->all();
        $parents = $docs->pluck('parent_id')->filter()->all();
        // The longest Planning hierarchy is strategy -> CMT -> department -> plan -> budget.
        for ($depth = 0; $parents && $depth < 5; $depth++) {
            $docs = Document::whereIn('id', array_diff($parents, $ids))->get(['id', 'parent_id']);
            $ids = array_merge($ids, $docs->pluck('id')->all());
            $parents = $docs->pluck('parent_id')->filter()->all();
        }

        return array_unique($ids);
    }

    public static function allows(User $user, string $key): ?bool
    {
        if (! $user->is_active) {
            return null;
        }
        [$module, $action] = array_pad(explode('.', $key, 2), 2, '');
        if (($action === 'view' || ($module === 'planning' && str_ends_with($action, '_view'))) && self::explicit($user, $key)) {
            return null;
        }
        if (isset(self::MODELS[$module]) && in_array($action, ['view', 'verify', 'approve'])) {
            $model = self::MODELS[$module];
            $query = $model::query();
            if ($action === 'view') {
                $assigned = $query->where(function ($q) use ($user) {
                    $q->whereHas('approvalChain.verifiers', fn ($m) => $m->where('users.id', $user->id))
                        ->orWhereHas('approvalChain.approvers', fn ($m) => $m->where('users.id', $user->id))
                        ->orWhereHas('approvalActions', fn ($a) => $a->where('actor_id', $user->id));
                })->where('status', '!=', 'draft')->exists();

                return $assigned ? true : null;
            }
            $relation = $action === 'verify' ? 'verifiers' : 'approvers';

            return $query->where('status', $action === 'verify' ? 'pending_verification' : 'pending_approval')
                ->whereHas('approvalChain.'.$relation, fn ($m) => $m->where('users.id', $user->id))->exists();
        }
        if ($module === 'planning') {
            if ($action === 'view' || str_ends_with($action, '_view')) {
                $q = Document::whereIn('id', self::planningIds($user));
                if ($action !== 'view') {
                    $q->where('type', substr($action, 0, -5));
                }

                return $q->exists() ? true : null;
            }
            if (self::decisionKey($key)) {
                $role = str_ends_with($action, '_verify') ? 'verify' : 'approve';
                $type = substr($action, 0, -strlen($role) - 1);

                return self::assignedPlanning(Document::where('type', $type)->whereIn('status', ['pending_verification', 'pending_approval']), $user, $role)->exists();
            }
        }

        return null;
    }
}
