<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('rfqs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('rfq_number', 30)->unique();  // RFQ-2081-0001

            $table->foreignUuid('purchase_request_id')->constrained('purchase_requests');
            $table->foreignUuid('created_by')->constrained('users');

            $table->string('status')->default('open'); // open|closed|cancelled
            $table->date('deadline')->nullable();       // Vendors must quote by this date
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // One RFQ → multiple vendor quotations
        Schema::create('rfq_quotes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('rfq_id')->constrained('rfqs')->cascadeOnDelete();
            $table->foreignUuid('vendor_id')->constrained('vendors');

            // Token-based access for vendor portal
            $table->string('vendor_token', 80)->unique()->nullable();
            $table->timestamp('token_expires_at')->nullable();
            $table->boolean('token_used')->default(false);

            // Quote entry method
            $table->string('entry_method')->default('portal'); // portal|manual
            $table->foreignUuid('entered_by')->nullable()->constrained('users')->nullOnDelete();
            // NULL = vendor submitted via portal; set if staff entered manually

            // Quote status
            $table->string('status')->default('invited'); // invited|submitted|accepted|rejected

            $table->date('quote_date')->nullable();
            $table->date('valid_until')->nullable();
            $table->string('delivery_timeline')->nullable();
            $table->text('payment_terms')->nullable();
            $table->text('notes')->nullable();
            $table->decimal('total_quoted', 14, 2)->nullable();

            $table->timestamp('submitted_at')->nullable();
            $table->foreignUuid('accepted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('accepted_at')->nullable();

            $table->timestamps();

            $table->index(['rfq_id', 'status']);
            $table->index('vendor_token');
        });

        // Per-item pricing on each quote
        Schema::create('rfq_quote_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('rfq_quote_id')->constrained('rfq_quotes')->cascadeOnDelete();
            $table->foreignUuid('line_item_id')->constrained('form_line_items')->cascadeOnDelete();
            $table->decimal('unit_rate', 12, 2);
            $table->decimal('total', 14, 2);   // unit_rate * quantity
            $table->text('item_notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rfq_quote_items');
        Schema::dropIfExists('rfq_quotes');
        Schema::dropIfExists('rfqs');
    }
};
