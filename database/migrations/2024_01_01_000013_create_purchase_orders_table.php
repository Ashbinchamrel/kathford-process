<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('po_number', 30)->unique();  // PO-2081-0001

            $table->foreignUuid('purchase_request_id')->constrained('purchase_requests');
            $table->foreignUuid('rfq_quote_id')->nullable()->constrained('rfq_quotes')->nullOnDelete();
            $table->foreignUuid('vendor_id')->constrained('vendors');
            $table->foreignUuid('generated_by')->constrained('users');

            // Delivery
            $table->text('delivery_address')->nullable(); // Defaults to college address
            $table->date('expected_delivery_date')->nullable();
            $table->text('terms_and_conditions')->nullable();

            $table->decimal('subtotal', 14, 2)->default(0);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('total_amount', 14, 2)->default(0);

            $table->string('status')->default('generated');
            // generated|sent_to_vendor|goods_pending|partially_received|completed|cancelled

            $table->timestamp('sent_to_vendor_at')->nullable();

            // Authorising approver (copied at time of PO generation)
            $table->string('authorised_by_name')->nullable();
            $table->timestamp('authorised_at')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_orders');
    }
};
