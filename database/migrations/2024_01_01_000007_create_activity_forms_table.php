<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('activity_forms', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Form identity
            $table->string('form_number', 30)->unique(); // e.g. PAA-2081-0042
            $table->foreignUuid('category_id')->constrained('form_categories');

            // Section 1 — Header
            $table->foreignUuid('creator_id')->constrained('users');
            $table->foreignUuid('department_id')->constrained('departments');
            $table->string('activity_name', 200);
            $table->date('deadline_date');
            $table->text('remarks')->nullable();
            $table->text('unplanned_reason')->nullable(); // Only for unplanned types

            // Extra dynamic fields (JSON) — for admin-defined extra fields
            $table->json('extra_field_values')->nullable();

            // Workflow
            $table->string('status')->default('draft');
            // draft|submitted|pending_verification|verified|pending_approval|approved|rejected

            $table->foreignUuid('approval_chain_id')->nullable()->constrained('approval_chains')->nullOnDelete();

            // Approval layer 2 (Verifier)
            $table->foreignUuid('verifier_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('verifier_decision')->nullable(); // approved|modified_approved|rejected
            $table->text('verifier_note')->nullable();
            $table->timestamp('verified_at')->nullable();

            // Approval layer 3 (Approver)
            $table->foreignUuid('approver_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('approver_decision')->nullable(); // approved|modified_approved|rejected
            $table->text('approver_note')->nullable();
            $table->timestamp('approved_at')->nullable();

            $table->decimal('total_estimated_amount', 14, 2)->default(0); // Sum of line items

            $table->foreignUuid('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['creator_id', 'status']);
            $table->index(['status', 'category_id']);
            $table->index('form_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_forms');
    }
};
