<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('planning_support_requests', function (Blueprint $t) {
            $t->dropUnique(['item_id']);
            $t->unique(['item_id', 'department_id']);
        });
    }

    public function down(): void
    { /* Multiple support recipients must not be discarded by rollback. */
    }
};
