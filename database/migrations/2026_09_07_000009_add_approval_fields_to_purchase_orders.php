<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->foreignUuid('approval_chain_id')->nullable()->after('generated_by')->constrained('approval_chains')->nullOnDelete();
            $table->foreignUuid('verifier_id')->nullable()->after('approval_chain_id')->constrained('users')->nullOnDelete();
            $table->string('verifier_decision')->nullable()->after('verifier_id');
            $table->text('verifier_note')->nullable()->after('verifier_decision');
            $table->timestamp('verified_at')->nullable()->after('verifier_note');
            $table->foreignUuid('approver_id')->nullable()->after('verified_at')->constrained('users')->nullOnDelete();
            $table->string('approver_decision')->nullable()->after('approver_id');
            $table->text('approver_note')->nullable()->after('approver_decision');
            $table->timestamp('approved_at')->nullable()->after('approver_note');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropForeign(['approval_chain_id', 'verifier_id', 'approver_id']);
            $table->dropColumn(['approval_chain_id', 'verifier_id', 'verifier_decision', 'verifier_note', 'verified_at', 'approver_id', 'approver_decision', 'approver_note', 'approved_at']);
        });
    }
};
