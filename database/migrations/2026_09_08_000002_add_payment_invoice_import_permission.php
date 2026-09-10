<?php

use App\Models\Permission;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Permission::firstOrCreate(
            ['key' => 'payments.import_vendor_invoices'],
            [
                'module' => 'payments',
                'label' => 'Import Pending Vendor Invoices',
                'sort_order' => 4,
            ]
        );
    }

    public function down(): void
    {
        Permission::where('key', 'payments.import_vendor_invoices')->delete();
    }
};
