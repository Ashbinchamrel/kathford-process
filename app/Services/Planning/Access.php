<?php

namespace App\Services\Planning;

use App\Models\Department;
use App\Models\Planning\Document;
use App\Models\User;
use App\Support\ChainAccess;

class Access
{
    const TYPES = ['strategy' => 'Strategic Plans', 'cmt_goals' => 'CMT Goals', 'goals' => 'Department Goals', 'plan' => 'Semester Plans', 'budget' => 'Budget Proposals'];

    public static function permissions(): array
    {
        $p = ['planning.cmt_goals_report' => 'Record CMT goal progress', 'planning.goals_report' => 'Record Department goal progress', 'planning.strategy_edit' => 'Edit Strategic Plan drafts', 'planning.strategy_review' => 'Create semester reviews of approved Strategic Plans', 'planning.view' => 'Open Planning', 'planning.all_departments' => 'Access all departments', 'planning.setup' => 'Manage periods and Planning setup', 'planning.tasks' => 'Manage team tasks and sprints', 'planning.support' => 'Respond to support requests'];
        foreach (self::TYPES as $type => $label) {
            foreach (['view', 'create', 'submit', 'verify', 'approve'] as $action) {
                $p["planning.{$type}_{$action}"] = ucfirst($action).' '.$label;
            }
        }

        return $p;
    }

    public static function department(User $u, ?string $id): bool
    {
        return $u->isSuperAdmin() || $u->can('planning.all_departments') || ($id && ($u->department_id === $id || Department::whereKey($id)->where('head_user_id', $u->id)->exists()));
    }

    public static function documents($q, User $u)
    {
        if ($u->isSuperAdmin() || $u->can('planning.all_departments')) {
            return $q;
        }

        $visibleTypes = array_values(array_filter(array_keys(self::TYPES), fn ($type) => ChainAccess::explicit($u, 'planning.'.$type.'_view')));

        return $q->where(fn ($q) => $q->whereIn('type', $visibleTypes)->orWhereIn('id', ChainAccess::planningIds($u)))->where(function ($q) use ($u) {
            $q->whereIn('id', ChainAccess::planningIds($u))->orWhere('created_by', $u->id)->orWhere(function ($q) use ($u) {
                if (Governance::member($u, 'board')) {
                    $q->where('type', 'strategy');
                } else {
                    $q->whereRaw('1=0');
                }
            })->orWhere(function ($q) use ($u) {
                $q->whereNotNull('department_id')->where('department_id', $u->department_id ?? 'none');
            })->orWhere(function ($q) use ($u) {
                if (Governance::member($u, 'cmt')) {
                    $q->where('type', 'cmt_goals');
                } else {
                    $q->whereRaw('1=0');
                }
            })->orWhere(fn ($q) => $q->where('type', 'cmt_goals')->whereIn('status', ['approved', 'superseded']))->orWhere(function ($q) use ($u) {
                $q->where('status', 'approved')->whereIn('type', array_filter([Governance::member($u, 'board') ? 'cmt_goals' : null, Governance::member($u, 'cmt') ? 'goals' : null]));
            })->orWhereIn('department_id', Department::where('head_user_id', $u->id)->select('id'))->orWhereJsonContains('steps', ['user_id' => $u->id])->orWhere(fn ($q) => $q->where('type', 'strategy')->whereIn('status', ['approved', 'superseded']));
        });
    }

    public static function document(Document $d, User $u): void
    {
        abort_unless($u->can('planning.'.$d->type.'_view') && self::documents(Document::query(), $u)->whereKey($d->id)->exists(), 403);
    }

    public static function tasks($q, User $u)
    {
        return $q->where(function ($q) use ($u) {
            $q->where(fn ($q) => $q->where('is_private', true)->where('created_by', $u->id))->orWhere(function ($q) use ($u) {
                $q->where('is_private', false);
                if (! $u->isSuperAdmin() && ! $u->can('planning.all_departments')) {
                    $q->where(fn ($q) => $q->where('assignee_id', $u->id)->orWhere('created_by', $u->id)->orWhere('reviewer_id', $u->id)->orWhere('department_id', $u->department_id ?? 'none'));
                }
            });
        });
    }
}
