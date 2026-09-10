<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->boolean('tax_applied')->default(false)->after('subtotal');
            $table->decimal('tax_rate', 5, 2)->default(13)->after('tax_applied');
        });
        Schema::table('rfq_quotes', function (Blueprint $table) {
            $table->boolean('tax_applied')->default(false)->after('total_quoted');
            $table->decimal('tax_rate', 5, 2)->default(13)->after('tax_applied');
        });
        Schema::table('payments', function (Blueprint $table) {
            $table->foreignUuid('parent_payment_id')->nullable()->after('purchase_order_id')->constrained('payments')->nullOnDelete();
            $table->boolean('tds_applied')->default(false)->after('payment_type');
            $table->decimal('tds_rate', 5, 2)->default(0)->after('tds_applied');
            $table->decimal('tds_amount', 14, 2)->default(0)->after('tds_rate');
            $table->decimal('net_amount', 14, 2)->default(0)->after('amount_due');
        });
        Schema::create('vendor_bill_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('vendor_bill_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('purchase_order_item_id')->constrained()->cascadeOnDelete();
            $table->decimal('quantity', 14, 3);
            $table->decimal('unit_rate', 14, 2);
            $table->decimal('total', 14, 2);
            $table->timestamps();
            $table->unique(['vendor_bill_id', 'purchase_order_item_id']);
        });
        Schema::create('payees', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('type');
            $table->string('bank_name')->nullable();
            $table->string('account_number')->nullable();
            $table->string('account_name')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->index(['type', 'active']);
        });
        Schema::create('payment_accounts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name')->unique();
            $table->string('code')->nullable()->unique();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
        Schema::table('payments', function (Blueprint $table) {
            $table->foreignUuid('payee_id')->nullable()->after('vendor_id')->constrained('payees')->nullOnDelete();
            $table->foreignUuid('payment_account_id')->nullable()->after('payee_id')->constrained('payment_accounts')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('payment_account_id');
            $table->dropConstrainedForeignId('payee_id');
            $table->dropConstrainedForeignId('parent_payment_id');
            $table->dropColumn(['tds_applied', 'tds_rate', 'tds_amount', 'net_amount']);
        });
        Schema::dropIfExists('payment_accounts');
        Schema::dropIfExists('payees');
        Schema::dropIfExists('vendor_bill_items');
        Schema::table('rfq_quotes', function (Blueprint $table) { $table->dropColumn(['tax_applied', 'tax_rate']); });
        Schema::table('purchase_orders', function (Blueprint $table) { $table->dropColumn(['tax_applied', 'tax_rate']); });
    }
};
