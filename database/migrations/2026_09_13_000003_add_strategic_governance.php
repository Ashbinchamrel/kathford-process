<?php

use App\Models\Permission;
use App\Services\Planning\Access;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('planning_documents', function (Blueprint $t) {
            $t->json('strategy_data')->nullable();
        });
        foreach (Access::permissions() as $key => $label) {
            Permission::updateOrCreate(['key' => $key], ['label' => $label, 'module' => 'planning', 'sort_order' => 100]);
        }
    }

    public function down(): void
    {
        Schema::table('planning_documents', fn (Blueprint $t) => $t->dropColumn('strategy_data'));
    }
};
