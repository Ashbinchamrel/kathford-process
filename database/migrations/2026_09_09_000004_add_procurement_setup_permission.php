<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    public function up(): void
    {
        \App\Models\Permission::firstOrCreate(['key'=>'procurement_setup.manage'], [
            'module'=>'procurement_setup', 'label'=>'Manage Procurement Setup', 'sort_order'=>1,
        ]);
    }
    public function down(): void
    {
        // Keep existing user permission assignments when rolling back.
    }
};
