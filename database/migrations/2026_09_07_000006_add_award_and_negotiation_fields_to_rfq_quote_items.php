<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('rfq_quote_items', function (Blueprint $table) {
            $table->string('award_status')->default('pending')->after('total');
            $table->foreignUuid('accepted_by')->nullable()->after('award_status')->constrained('users')->nullOnDelete();
            $table->timestamp('accepted_at')->nullable()->after('accepted_by');
            $table->text('negotiation_message')->nullable()->after('accepted_at');
            $table->foreignUuid('negotiation_requested_by')->nullable()->after('negotiation_message')->constrained('users')->nullOnDelete();
            $table->timestamp('negotiation_requested_at')->nullable()->after('negotiation_requested_by');
            $table->index(['rfq_quote_id', 'award_status']);
        });
    }

    public function down(): void
    {
        Schema::table('rfq_quote_items', function (Blueprint $table) {
            $table->dropIndex(['rfq_quote_id', 'award_status']);
            $table->dropForeign(['accepted_by']);
            $table->dropForeign(['negotiation_requested_by']);
            $table->dropColumn([
                'award_status', 'accepted_by', 'accepted_at', 'negotiation_message',
                'negotiation_requested_by', 'negotiation_requested_at',
            ]);
        });
    }
};
