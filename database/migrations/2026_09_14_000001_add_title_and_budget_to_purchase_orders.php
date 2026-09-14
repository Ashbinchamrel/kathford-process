<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->string('title')->nullable()->after('po_number');
            $table->foreignUuid('budget_id')->nullable()->after('rfq_quote_id')->constrained('department_budgets')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('budget_id');
            $table->dropColumn('title');
        });
    }
};
