<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('rfq_quotes', function (Blueprint $table) {
            $table->decimal('tax_amount', 14, 2)->default(0)->after('total_quoted');
            $table->decimal('grand_total', 14, 2)->default(0)->after('tax_amount');
        });

        // Preserve the meaning of existing quotations: their previous total was tax-inclusive only when no tax was recorded.
        DB::table('rfq_quotes')->update([
            'grand_total' => DB::raw('COALESCE(total_quoted, 0) + COALESCE(tax_amount, 0)'),
        ]);
    }

    public function down(): void
    {
        Schema::table('rfq_quotes', function (Blueprint $table) {
            $table->dropColumn(['tax_amount', 'grand_total']);
        });
    }
};
