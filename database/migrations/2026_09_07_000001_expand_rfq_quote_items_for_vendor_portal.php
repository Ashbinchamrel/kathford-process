<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('rfq_quote_items', function (Blueprint $table) {
            if (! Schema::hasColumn('rfq_quote_items', 'description')) {
                $table->string('description', 500)->nullable()->after('line_item_id');
            }
            if (! Schema::hasColumn('rfq_quote_items', 'quantity')) {
                $table->decimal('quantity', 12, 3)->default(1)->after('description');
            }
            if (! Schema::hasColumn('rfq_quote_items', 'unit')) {
                $table->string('unit', 50)->nullable()->after('quantity');
            }
        });

        Schema::table('rfq_quote_items', function (Blueprint $table) {
            $table->foreignUuid('line_item_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('rfq_quote_items', function (Blueprint $table) {
            $table->foreignUuid('line_item_id')->nullable(false)->change();
            $table->dropColumn(['description', 'quantity', 'unit']);
        });
    }
};
