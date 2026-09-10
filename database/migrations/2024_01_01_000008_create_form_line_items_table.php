<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('form_line_items', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Polymorphic: can belong to activity_forms OR purchase_requests
            $table->string('itemable_type');  // App\Models\ActivityForm | App\Models\PurchaseRequest
            $table->uuid('itemable_id');

            $table->string('item_name', 255);
            $table->decimal('quantity', 10, 3)->default(1);
            $table->string('unit', 30)->nullable();      // pcs, kg, set, service, etc.
            $table->decimal('rate', 12, 2)->default(0);
            $table->decimal('amount', 14, 2)->default(0); // Stored = qty * rate
            $table->text('item_remarks')->nullable();

            // Purchase Request specific — per-item vendor assignment
            $table->foreignUuid('vendor_id')->nullable()->constrained('vendors')->nullOnDelete();
            $table->decimal('quoted_rate', 12, 2)->nullable(); // From accepted quote

            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['itemable_type', 'itemable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_line_items');
    }
};
