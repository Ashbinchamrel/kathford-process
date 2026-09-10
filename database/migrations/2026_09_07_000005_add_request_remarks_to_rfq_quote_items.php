<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('rfq_quote_items', function (Blueprint $table) {
            $table->text('request_remarks')->nullable()->after('unit');
        });
    }

    public function down(): void
    {
        Schema::table('rfq_quote_items', function (Blueprint $table) {
            $table->dropColumn('request_remarks');
        });
    }
};
