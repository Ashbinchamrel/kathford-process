<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_authorisations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('authorisation_number', 40)->unique();
            $table->date('schedule_month');
            $table->unsignedTinyInteger('schedule_week');
            $table->decimal('total_amount', 14, 2)->default(0);
            $table->text('notes')->nullable();
            $table->foreignUuid('created_by')->constrained('users');
            $table->foreignUuid('approval_chain_id')->nullable()->constrained('approval_chains')->nullOnDelete();
            $table->string('status')->default('generated');
            $table->foreignUuid('verifier_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('verifier_decision')->nullable();
            $table->text('verifier_note')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->foreignUuid('approver_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('approver_decision')->nullable();
            $table->text('approver_note')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('csv_exported_at')->nullable();
            $table->timestamps();
            $table->index(['schedule_month', 'schedule_week', 'status']);
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->foreignUuid('purchase_order_id')->nullable()->change();
            $table->foreignUuid('vendor_bill_id')->nullable()->after('vendor_id')->constrained('vendor_bills')->nullOnDelete();
            $table->foreignUuid('payment_authorisation_id')->nullable()->after('vendor_bill_id')->constrained('payment_authorisations')->nullOnDelete();
            $table->string('source', 30)->default('manual')->after('payment_number');
            $table->string('bill_number', 100)->nullable()->after('payment_method');
            $table->string('activity_name')->nullable()->after('bill_number');
            $table->string('activity_reference', 100)->nullable()->after('activity_name');
            $table->date('schedule_month')->nullable()->after('scheduled_date');
            $table->unsignedTinyInteger('schedule_week')->nullable()->after('schedule_month');
            $table->index(['schedule_month', 'schedule_week', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['vendor_bill_id', 'payment_authorisation_id']);
            $table->dropIndex(['schedule_month', 'schedule_week', 'status']);
            $table->dropColumn(['vendor_bill_id', 'payment_authorisation_id', 'source', 'bill_number', 'activity_name', 'activity_reference', 'schedule_month', 'schedule_week']);
            $table->foreignUuid('purchase_order_id')->nullable(false)->change();
        });
        Schema::dropIfExists('payment_authorisations');
    }
};
