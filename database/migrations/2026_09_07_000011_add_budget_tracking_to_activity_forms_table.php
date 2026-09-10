<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activity_forms', function (Blueprint $table) {
            $table->foreignUuid('budget_id')->nullable()->after('department_id')
                ->constrained('department_budgets')->nullOnDelete();
            $table->boolean('budget_exception')->default(false)->after('total_estimated_amount');
            $table->text('budget_exception_reason')->nullable()->after('budget_exception');
            $table->timestamp('budget_checked_at')->nullable()->after('budget_exception_reason');
            $table->index('budget_id');
        });
    }

    public function down(): void
    {
        Schema::table('activity_forms', function (Blueprint $table) {
            $table->dropForeign(['budget_id']);
            $table->dropIndex(['budget_id']);
            $table->dropColumn(['budget_id', 'budget_exception', 'budget_exception_reason', 'budget_checked_at']);
        });
    }
};
