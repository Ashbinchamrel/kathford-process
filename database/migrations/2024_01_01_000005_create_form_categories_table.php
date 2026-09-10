<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('form_categories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');                         // "Planned Academic Activity"
            $table->string('code', 20)->unique();           // "PAA" — used in form numbering
            $table->text('description')->nullable();
            $table->string('category_group')->default('activity');
            // Groups: activity | purchase | vendor | payment
            // Controls which base template/workflow is used

            $table->boolean('requires_reason')->default(false);
            // Adds "Reason for Unplanned Activity" field when true

            $table->boolean('requires_logistic_table')->default(true);
            $table->boolean('requires_attachments')->default(true);
            $table->boolean('requires_vendor_selection')->default(false);
            // For purchase forms — enables vendor dropdown in logistic table

            $table->boolean('auto_generate_next')->default(false);
            // After approval, auto-suggest creating a Purchase Request

            $table->json('extra_fields')->nullable();
            // Admin-defined extra fields beyond the base template
            // Format: [{"name":"field_key","label":"Display Label","type":"text|number|date|select|textarea","required":true,"options":["opt1","opt2"]}]

            $table->foreignUuid('approval_chain_id')->nullable()->constrained('approval_chains')->nullOnDelete();
            // NULL = use default chain

            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_system')->default(false); // Cannot be deleted
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_categories');
    }
};
