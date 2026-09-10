<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_authorisation_channels', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 100)->unique();
            $table->foreignUuid('approval_chain_id')->constrained('approval_chains')->restrictOnDelete();
            $table->foreignUuid('payment_account_id')->nullable()->constrained('payment_accounts')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->foreignUuid('payment_authorisation_channel_id')->nullable()->after('payment_authorisation_id')
                ->constrained('payment_authorisation_channels')->nullOnDelete();
            $table->index(['payment_authorisation_channel_id', 'schedule_month', 'schedule_week'], 'payment_schedule_channel_period_index');
        });

        Schema::table('payment_authorisations', function (Blueprint $table) {
            $table->foreignUuid('payment_authorisation_channel_id')->nullable()->after('approval_chain_id')
                ->constrained('payment_authorisation_channels')->nullOnDelete();
        });

        // Preserve the existing centrally configured route as a usable first channel.
        $chainId = DB::table('settings')->where('key', 'payment_authorisation_approval_chain_id')->value('value');
        if ($chainId && DB::table('approval_chains')->where('id', $chainId)->exists()) {
            $channelId = (string) Str::uuid();
            DB::table('payment_authorisation_channels')->insert([
                'id' => $channelId,
                'name' => 'General Payment Authorisation',
                'approval_chain_id' => $chainId,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::table('payments')->whereNull('payment_authorisation_id')->where('status', 'scheduled')
                ->update(['payment_authorisation_channel_id' => $channelId]);
        }
    }

    public function down(): void
    {
        Schema::table('payment_authorisations', function (Blueprint $table) {
            $table->dropForeign(['payment_authorisation_channel_id']);
            $table->dropColumn('payment_authorisation_channel_id');
        });
        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex('payment_schedule_channel_period_index');
            $table->dropForeign(['payment_authorisation_channel_id']);
            $table->dropColumn('payment_authorisation_channel_id');
        });
        Schema::dropIfExists('payment_authorisation_channels');
    }
};
