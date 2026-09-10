<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('rfqs',fn(Blueprint $t)=>$t->foreignUuid('assigned_to')->nullable()->constrained('users'));
        if (!Schema::hasColumn('rfq_quote_items','notes')) Schema::table('rfq_quote_items',fn(Blueprint $t)=>$t->text('notes')->nullable());
    }
    public function down(): void {
        Schema::table('rfqs',fn(Blueprint $t)=>$t->dropConstrainedForeignId('assigned_to'));
        // Notes may have existed before this compatibility migration; retain them on rollback.
    }
};
