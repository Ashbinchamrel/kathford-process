<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // ── 1. Create the new members table ──────────────────────────────
        Schema::create('approval_chain_members', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('approval_chain_id')->constrained('approval_chains')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('role', 20); // 'verifier' | 'approver'
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['approval_chain_id', 'user_id', 'role'], 'acm_chain_user_role_unique');
            $table->index(['approval_chain_id', 'role']);
        });

        // ── 2. Migrate existing verifier_id / approver_id data ───────────
        $chains = DB::table('approval_chains')->get();
        foreach ($chains as $chain) {
            if ($chain->verifier_id) {
                DB::table('approval_chain_members')->insertOrIgnore([
                    'approval_chain_id' => $chain->id,
                    'user_id'           => $chain->verifier_id,
                    'role'              => 'verifier',
                    'sort_order'        => 0,
                    'created_at'        => now(),
                    'updated_at'        => now(),
                ]);
            }
            if ($chain->approver_id) {
                DB::table('approval_chain_members')->insertOrIgnore([
                    'approval_chain_id' => $chain->id,
                    'user_id'           => $chain->approver_id,
                    'role'              => 'approver',
                    'sort_order'        => 0,
                    'created_at'        => now(),
                    'updated_at'        => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_chain_members');
    }
};
