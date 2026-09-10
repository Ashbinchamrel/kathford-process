<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('rfqs', function (Blueprint $table) {
            $table->foreignUuid('activity_form_id')->nullable()->after('purchase_request_id')
                ->constrained('activity_forms')->nullOnDelete();
            $table->index('activity_form_id');
        });
    }

    public function down(): void
    {
        Schema::table('rfqs', function (Blueprint $table) {
            $table->dropForeign(['activity_form_id']);
            $table->dropIndex(['activity_form_id']);
            $table->dropColumn('activity_form_id');
        });
    }
};
