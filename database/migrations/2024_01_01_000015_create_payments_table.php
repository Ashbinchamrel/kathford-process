<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('payment_number', 30)->unique(); // PAY-2081-0001

            $table->foreignUuid('purchase_order_id')->constrained('purchase_orders');
            $table->foreignUuid('vendor_id')->constrained('vendors');
            $table->foreignUuid('created_by')->constrained('users');

            $table->decimal('po_total', 14, 2);          // Snapshot of PO total
            $table->decimal('amount_due', 14, 2);         // This payment amount
            $table->decimal('amount_paid', 14, 2)->default(0);

            $table->date('scheduled_date');               // Planned payment date
            $table->date('actual_date')->nullable();      // When actually paid

            $table->string('payment_method');
            // bank_transfer|cheque|cash

            $table->string('payment_reference')->nullable(); // Cheque #, transfer ref
            $table->string('bank_name')->nullable();
            $table->string('bank_account_number')->nullable(); // Snapshot from vendor

            $table->string('status')->default('scheduled');
            // scheduled|processing|paid|cancelled

            $table->text('notes')->nullable();

            $table->foreignUuid('marked_paid_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('marked_paid_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['purchase_order_id', 'status']);
            $table->index(['scheduled_date', 'status']); // For due date reminders
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
