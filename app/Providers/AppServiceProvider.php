<?php

namespace App\Providers;

use App\Models\Permission;
use App\Support\ChainAccess;
use App\Support\FiscalYearContext;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Fortify;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Authentication is handled exclusively by Google OAuth plus this
        // application's 2FA flow. Do not expose Fortify's password endpoints.
        Fortify::ignoreRoutes();
        $this->app->scoped(FiscalYearContext::class);
    }

    public function boot(): void
    {
        // Super Admin bypasses all gates
        Gate::before(function ($user, $ability, array $arguments = []) {
            $year = app(FiscalYearContext::class);
            if ($year->enabled && ! $year->writable() && in_array($ability, ['update', 'delete', 'verify', 'finalApprove'], true)
                && isset($arguments[0]) && is_object($arguments[0]) && method_exists($arguments[0], 'fiscalYear')) {
                return false;
            }
            $parts = explode('.', $ability);
            $transactionModules = ['activity_forms', 'rfq', 'purchase_orders', 'grn', 'checklists', 'payments', 'payment_authorisations', 'budgets'];
            if ($year->enabled && ! $year->writable() && in_array($parts[0], $transactionModules, true)
                && ! in_array($parts[1] ?? '', ['view', 'export'], true)) {
                return false;
            }
            if (! $user->is_active) {
                return false;
            }
            if ($user->isSuperAdmin()) {
                return true;
            }

            return ChainAccess::allows($user, $ability);
        });

        // Dynamically define a Gate for every permission key in the database.
        // We cache the keys for the request lifetime to avoid N+1 queries.
        $this->definePermissionGates();
    }

    private function definePermissionGates(): void
    {
        // Collect all permission keys (once per request, wrapped in try/catch
        // so boot doesn't crash before migrations have run).
        try {
            $keys = Permission::pluck('key');
        } catch (\Throwable) {
            return; // Table doesn't exist yet (first migration run)
        }

        foreach ($keys as $key) {
            Gate::define($key, function ($user) use ($key) {
                // Load permissions once per user object (cached on the model)
                if (! isset($user->_permissionKeys)) {
                    $user->_permissionKeys = $user->permissions()->pluck('key')->all();
                }

                return in_array($key, $user->_permissionKeys);
            });
        }
    }
}
