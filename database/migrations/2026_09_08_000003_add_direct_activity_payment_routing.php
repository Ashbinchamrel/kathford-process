<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('form_categories', function (Blueprint $table) {
            $table->boolean('bypasses_procurement_to_payment')->default(false)->after('auto_generate_next');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->foreignUuid('activity_form_id')->nullable()->after('purchase_order_id')->constrained('activity_forms')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['activity_form_id']);
            $table->dropColumn('activity_form_id');
        });

        Schema::table('form_categories', function (Blueprint $table) {
            $table->dropColumn('bypasses_procurement_to_payment');
        });
    }
};
