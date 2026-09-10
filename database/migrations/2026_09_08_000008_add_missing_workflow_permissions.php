<?php

use App\Models\Permission;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        foreach ([
            ['module' => 'rfq', 'key' => 'rfq.delete', 'label' => 'Delete RFQs', 'sort_order' => 6],
            ['module' => 'purchase_orders', 'key' => 'purchase_orders.delete', 'label' => 'Delete Purchase Orders', 'sort_order' => 4],
            ['module' => 'purchase_orders', 'key' => 'purchase_orders.submit', 'label' => 'Submit Purchase Orders', 'sort_order' => 5],
            ['module' => 'purchase_orders', 'key' => 'purchase_orders.verify', 'label' => 'Verify Purchase Orders', 'sort_order' => 6],
            ['module' => 'purchase_orders', 'key' => 'purchase_orders.approve', 'label' => 'Approve Purchase Orders', 'sort_order' => 7],
        ] as $permission) {
            Permission::firstOrCreate(['key' => $permission['key']], $permission);
        }
    }

    public function down(): void
    {
        Permission::whereIn('key', [
            'rfq.delete', 'purchase_orders.delete', 'purchase_orders.submit',
            'purchase_orders.verify', 'purchase_orders.approve',
        ])->delete();
    }
};
