<?php

use App\Models\Permission;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        foreach ([
            ['module' => 'payment_authorisations', 'key' => 'payment_authorisations.view', 'label' => 'View Payment Authorisations', 'sort_order' => 1],
            ['module' => 'payment_authorisations', 'key' => 'payment_authorisations.create', 'label' => 'Create Payment Authorisations', 'sort_order' => 2],
            ['module' => 'payment_authorisations', 'key' => 'payment_authorisations.submit', 'label' => 'Submit for Approval', 'sort_order' => 3],
            ['module' => 'payment_authorisations', 'key' => 'payment_authorisations.verify', 'label' => 'Verify Payment Authorisations', 'sort_order' => 4],
            ['module' => 'payment_authorisations', 'key' => 'payment_authorisations.approve', 'label' => 'Approve Payment Authorisations', 'sort_order' => 5],
            ['module' => 'payment_authorisations', 'key' => 'payment_authorisations.export', 'label' => 'Download Bank CSV', 'sort_order' => 6],
        ] as $permission) {
            Permission::firstOrCreate(['key' => $permission['key']], $permission);
        }
    }

    public function down(): void
    {
        Permission::whereIn('key', [
            'payment_authorisations.view', 'payment_authorisations.create', 'payment_authorisations.submit',
            'payment_authorisations.verify', 'payment_authorisations.approve', 'payment_authorisations.export',
        ])->delete();
    }
};
