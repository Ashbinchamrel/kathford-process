<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('goods_received_notes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('grn_number', 30)->unique(); // GRN-2081-0001

            $table->foreignUuid('purchase_order_id')->constrained('purchase_orders');
            $table->foreignUuid('received_by_user_id')->constrained('users');
            $table->string('received_by_name');  // Physical receiver name
            $table->date('received_date');
            $table->text('condition_notes')->nullable();
            $table->boolean('is_partial')->default(false);

            $table->string('status')->default('draft'); // draft|confirmed
            $table->foreignUuid('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('confirmed_at')->nullable();

            $table->timestamps();
        });

        Schema::create('grn_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('grn_id')->constrained('goods_received_notes')->cascadeOnDelete();
            $table->foreignUuid('line_item_id')->constrained('form_line_items');
            $table->decimal('ordered_quantity', 10, 3);
            $table->decimal('received_quantity', 10, 3);
            $table->text('item_condition_note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grn_items');
        Schema::dropIfExists('goods_received_notes');
    }
};
