<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('purchase_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('form_number', 30)->unique();  // PRF-2081-0001

            $table->foreignUuid('creator_id')->constrained('users');
            $table->foreignUuid('department_id')->constrained('departments');

            // Reference to source activity form (optional — can be standalone)
            $table->foreignUuid('activity_form_id')->nullable()->constrained('activity_forms')->nullOnDelete();

            $table->string('activity_name', 200);
            $table->date('deadline_date')->nullable();
            $table->text('remarks')->nullable();

            $table->decimal('total_amount', 14, 2)->default(0);

            $table->string('status')->default('draft');
            // draft|submitted|pending_verification|verified|pending_approval|approved|rejected

            $table->foreignUuid('approval_chain_id')->nullable()->constrained('approval_chains')->nullOnDelete();

            // Verifier
            $table->foreignUuid('verifier_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('verifier_decision')->nullable();
            $table->text('verifier_note')->nullable();
            $table->timestamp('verified_at')->nullable();

            // Final approver
            $table->foreignUuid('approver_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('approver_decision')->nullable();
            $table->text('approver_note')->nullable();
            $table->timestamp('approved_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['creator_id', 'status']);
            $table->index('activity_form_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_requests');
    }
};
