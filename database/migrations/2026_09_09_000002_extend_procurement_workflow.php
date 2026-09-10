<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('rfqs', function (Blueprint $t) {
            $t->foreignUuid('budget_id')->nullable()->constrained('department_budgets');
            $t->foreignUuid('handoff_activity_id')->nullable()->unique()->constrained('activity_forms');
            $t->json('preparation_items')->nullable();
        });
        Schema::create('vendor_rates', function (Blueprint $t) {
            $t->id(); $t->foreignUuid('vendor_id')->constrained('vendors');
            $t->string('item_name'); $t->string('unit', 50); $t->decimal('unit_rate', 15, 2);
            $t->date('valid_from'); $t->date('valid_until'); $t->date('review_on')->nullable();
            $t->text('specification')->nullable(); $t->boolean('is_active')->default(true);
            $t->foreignUuid('approved_by')->constrained('users'); $t->timestamps(); $t->softDeletes();
        });
        Schema::create('rfq_items', function (Blueprint $t) {
            $t->id(); $t->foreignUuid('rfq_id')->constrained('rfqs');
            $t->foreignUuid('source_line_item_id')->nullable()->constrained('form_line_items');
            $t->string('description', 500); $t->decimal('quantity',15,3); $t->string('unit',50)->nullable();
            $t->text('request_remarks')->nullable(); $t->boolean('quotation_not_required')->default(false);
            $t->foreignId('vendor_rate_id')->nullable()->constrained('vendor_rates');
            $t->decimal('approved_rate',15,2)->nullable();
            $t->foreignUuid('payment_id')->nullable()->constrained('payments'); $t->timestamps();
        });
        Schema::table('rfq_quote_items', fn(Blueprint $t) => $t->foreignId('rfq_item_id')->nullable()->constrained('rfq_items'));
        Schema::create('checklist_questions', function (Blueprint $t) {
            $t->id(); $t->string('fulfillment_type',20); $t->string('label',500); $t->boolean('is_required')->default(true);
            $t->boolean('is_active')->default(true); $t->integer('sort_order')->default(0); $t->timestamps(); $t->softDeletes();
        });
        foreach (['goods','service'] as $type) {
            foreach (['Gate entry confirmed', $type === 'goods' ? 'Goods received' : 'Service received', 'Quality checked', 'Vendor invoice received', $type === 'goods' ? 'Store entry completed' : 'Job completion confirmed'] as $i => $label) {
                DB::table('checklist_questions')->insert(['fulfillment_type'=>$type,'label'=>$label,'sort_order'=>$i,'created_at'=>now(),'updated_at'=>now()]);
            }
        }
        Schema::table('procurement_checklists', function (Blueprint $t) { $t->json('question_snapshot')->nullable(); $t->json('answers')->nullable(); });
        Schema::create('procurement_returns', function (Blueprint $t) {
            $t->id(); $t->foreignUuid('checklist_id')->constrained('procurement_checklists');
            $t->foreignUuid('vendor_id')->constrained('vendors'); $t->foreignUuid('returned_by')->constrained('users');
            $t->text('reason'); $t->string('return_type',30); $t->string('status',30)->default('returned'); $t->timestamps();
        });
        Schema::table('users', fn(Blueprint $t) => $t->json('dashboard_widgets')->nullable());
    }
    public function down(): void
    {
        Schema::table('users', fn(Blueprint $t) => $t->dropColumn('dashboard_widgets'));
        Schema::dropIfExists('procurement_returns');
        Schema::table('procurement_checklists', fn(Blueprint $t) => $t->dropColumn(['question_snapshot','answers']));
        Schema::dropIfExists('checklist_questions');
        Schema::table('rfq_quote_items', fn(Blueprint $t) => $t->dropConstrainedForeignId('rfq_item_id'));
        Schema::dropIfExists('rfq_items'); Schema::dropIfExists('vendor_rates');
        Schema::table('rfqs', function(Blueprint $t) { $t->dropConstrainedForeignId('budget_id'); $t->dropConstrainedForeignId('handoff_activity_id'); $t->dropColumn('preparation_items'); });
    }
};
