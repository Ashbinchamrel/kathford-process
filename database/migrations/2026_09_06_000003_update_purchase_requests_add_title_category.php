<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('purchase_requests', function (Blueprint $table) {
            // Add title column (maps to what controller saves as 'title')
            $table->string('title', 255)->nullable()->after('form_number');
            // Add description (controller uses 'description', table had 'remarks')
            $table->text('description')->nullable()->after('title');
            // Add category_id (controller links PR to a FormCategory)
            $table->foreignUuid('category_id')
                  ->nullable()
                  ->after('description')
                  ->constrained('form_categories')
                  ->nullOnDelete();
            // Make department_id nullable — controller doesn't always set it
            $table->foreignUuid('department_id')
                  ->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('purchase_requests', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropColumn(['title', 'description', 'category_id']);
            $table->foreignUuid('department_id')->nullable(false)->change();
        });
    }
};
