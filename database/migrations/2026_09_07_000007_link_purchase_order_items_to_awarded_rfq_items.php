<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->foreignUuid('rfq_quote_item_id')->nullable()->after('purchase_order_id')
                ->constrained('rfq_quote_items')->nullOnDelete();
            $table->text('request_remarks')->nullable()->after('unit');
            $table->unique('rfq_quote_item_id');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->dropUnique(['rfq_quote_item_id']);
            $table->dropForeign(['rfq_quote_item_id']);
            $table->dropColumn(['rfq_quote_item_id', 'request_remarks']);
        });
    }
};
