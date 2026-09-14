<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Available-in-Store items skip the vendor/PO flow entirely, so
        // tracking belongs on the RFQ item itself, not the (never-created)
        // purchase order item.
        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('store_issued_by');
            $table->dropColumn(['available_in_store', 'store_issued_at']);
        });

        Schema::table('rfq_items', function (Blueprint $table) {
            $table->timestamp('store_issued_at')->nullable()->after('available_in_store');
            $table->foreignUuid('store_issued_by')->nullable()->after('store_issued_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('rfq_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('store_issued_by');
            $table->dropColumn('store_issued_at');
        });

        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->boolean('available_in_store')->default(false)->after('rfq_quote_item_id');
            $table->timestamp('store_issued_at')->nullable()->after('available_in_store');
            $table->foreignUuid('store_issued_by')->nullable()->after('store_issued_at')->constrained('users')->nullOnDelete();
        });
    }
};
