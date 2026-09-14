<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('planning_items', function (Blueprint $t) {
            $t->json('details')->nullable();
            $t->date('starts_on')->nullable();
        });
        Schema::create('planning_checkins', function (Blueprint $t) {
            $t->id();
            $t->uuid('item_id')->index();
            $t->uuid('actor_id');
            $t->decimal('actual', 14, 2);
            $t->text('evidence');
            $t->timestamps();
        });
        Schema::create('planning_imports', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->uuid('created_by');
            $t->string('filename');
            $t->string('hash', 64);
            $t->json('rows');
            $t->uuid('document_id')->nullable();
            $t->timestamps();
            $t->unique(['created_by', 'hash']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('planning_imports');
        Schema::dropIfExists('planning_checkins');
        Schema::table('planning_items', fn (Blueprint $t) => $t->dropColumn(['details', 'starts_on']));
    }
};
