<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Preserve the current approval behaviour on upgrade. Administrators can
        // subsequently select a different PO-specific chain in Approval Chains.
        $defaultChainId = DB::table('approval_chains')
            ->where('is_default', true)
            ->where('is_active', true)
            ->value('id');

        if ($defaultChainId) {
            DB::table('settings')->updateOrInsert(
                ['key' => 'purchase_order_approval_chain_id'],
                [
                    'value' => $defaultChainId,
                    'type' => 'string',
                    'group' => 'workflows',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );
        }
    }

    public function down(): void
    {
        DB::table('settings')->where('key', 'purchase_order_approval_chain_id')->delete();
    }
};
