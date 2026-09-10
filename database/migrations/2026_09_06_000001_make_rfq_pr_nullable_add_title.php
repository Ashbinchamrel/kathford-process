<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('rfqs', function (Blueprint $table) {
            // Make PR link optional (standalone RFQ support)
            $table->foreignUuid('purchase_request_id')
                  ->nullable()->change();
            // Add title field
            $table->string('title', 255)->nullable()->after('rfq_number');
        });
    }

    public function down(): void
    {
        Schema::table('rfqs', function (Blueprint $table) {
            $table->dropColumn('title');
            $table->foreignUuid('purchase_request_id')->nullable(false)->change();
        });
    }
};
