<?php

namespace App\Services\Planning;

use App\Models\Department;
use App\Models\Planning\Document;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class Governance
{
    public static function settings(): array
    {
        return Setting::get('planning_governance', []) ?: [];
    }

    public static function member(User $u, string $group): bool
    {
        return (bool) match ($group) {
            'board' => $u->is_board_member, 'cmt' => $u->is_cmt_member, default => false
        };
    }

    public static function canWrite(User $u, string $type, string $action = 'create'): bool
    {
        if (! $u->can('planning.'.$type.'_'.$action) || ! $u->can('planning.'.$type.'_view')) {
            return false;
        }

        return match ($type) {
            'strategy' => $u->isSuperAdmin() || self::member($u, 'board'), 'cmt_goals' => $u->isSuperAdmin() || self::member($u, 'cmt'), default => true
        };
    }

    public static function dashboardLevels(User $user): array
    {
        if (! $user->is_active) {
            return [];
        }
        $levels = [];
        if (self::member($user, 'board')) {
            $levels['board'] = 'Board';
        }
        if (self::member($user, 'cmt')) {
            $levels['cmt'] = 'CMT';
        }
        if (Department::active()->where('head_user_id', $user->id)->exists()) {
            $levels['department'] = 'Department';
        }

        return $levels;
    }

    public static function defaultChain(string $type): ?string
    {
        return self::settings()['chains'][$type] ?? null;
    }

    public static function parentType(string $type): ?string
    {
        return ['cmt_goals' => 'strategy', 'goals' => 'cmt_goals', 'plan' => 'goals', 'budget' => 'plan'][$type] ?? null;
    }

    public static function validate(Document $d): void
    {
        if ($d->type === 'strategy') {
            StrategyMatrix::validate($d->strategy_data ?? [], $d->items->map(fn ($i) => $i->details['strategy'] ?? [])->all());

            return;
        }
        if (! in_array($d->type, ['cmt_goals', 'goals'])) {
            return;
        }
        $parent = $d->parent;
        if (! $parent || $parent->type !== self::parentType($d->type) || ! in_array($parent->status, $d->previous_id ? ['approved', 'superseded'] : ['approved'])) {
            self::fail('Link to the approved '.(self::parentType($d->type) === 'strategy' ? 'Strategic Plan' : 'CMT Goals').' first.');
        }
        if (! $d->period_id) {
            self::fail('Choose the semester for these goals.');
        }
        $period = $d->period;
        if ($d->type === 'goals' && $parent->period_id !== $d->period_id) {
            self::fail('Department Goals must use the same semester as the linked CMT Goals.');
        }
        if ($d->type === 'cmt_goals' && $parent->strategy_data) {
            $semesters = $parent->strategy_data['semesters'] ?? [];
            if (! collect($semesters)->contains(fn ($s) => $s['starts_on'] <= $period->starts_on && $s['ends_on'] >= $period->ends_on)) {
                self::fail('The goal semester must fall within a semester of the approved strategy.');
            }
        }
        foreach ($d->items as $item) {
            if (! $parent->items()->whereKey($item->source_id)->exists()) {
                self::fail('Link every goal to a row in the selected approved parent.');
            }
            if (blank($item->definition_of_done) || blank($item->metric) || $item->target === null || ! $item->owner_id || ! $item->due_on) {
                self::fail('Each goal needs an owner, metric, target, due date and Definition of Done.');
            }
            if ($item->due_on < $period->starts_on || $item->due_on > $period->ends_on) {
                self::fail('Goal due dates must fall within the selected semester.');
            }
        }
    }

    private static function fail(string $message): never
    {
        throw ValidationException::withMessages(['planning' => $message]);
    }
}
