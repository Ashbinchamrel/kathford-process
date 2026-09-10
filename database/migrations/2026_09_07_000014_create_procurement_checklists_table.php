<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('procurement_checklists', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('vendor_bill_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignUuid('purchase_order_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('vendor_id')->constrained()->cascadeOnDelete();
            $table->string('fulfillment_type', 20)->default('goods');
            $table->boolean('gate_entry_checked')->default(false);
            $table->boolean('received_checked')->default(false);
            $table->boolean('quality_checked')->default(false);
            $table->boolean('invoice_received_checked')->default(false);
            $table->boolean('store_entry_checked')->default(false);
            $table->boolean('job_completion_checked')->default(false);
            $table->text('control_comments')->nullable();
            $table->string('status', 40)->default('pending_controls');
            $table->foreignUuid('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->foreignUuid('accounts_sent_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('accounts_sent_at')->nullable();
            $table->text('accounts_comment')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('procurement_checklists');
    }
};
