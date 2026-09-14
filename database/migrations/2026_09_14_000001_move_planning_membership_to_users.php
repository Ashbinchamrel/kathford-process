<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->boolean('is_board_member')->default(false);
            $t->boolean('is_cmt_member')->default(false);
        });
        $settings = json_decode(DB::table('settings')->where('key', 'planning_governance')->value('value') ?? '{}', true) ?: [];
        foreach (['board' => 'is_board_member', 'cmt' => 'is_cmt_member'] as $key => $column) {
            if (! empty($settings[$key])) {
                DB::table('users')->whereIn('id', $settings[$key])->update([$column => true]);
            }
        }
        // Preserve a single source of membership: the user record. Keep approval defaults.
        unset($settings['board'],$settings['cmt']);
        DB::table('settings')->where('key', 'planning_governance')->update(['value' => json_encode($settings)]);
    }

    public function down(): void
    {
        $settings = json_decode(DB::table('settings')->where('key', 'planning_governance')->value('value') ?? '{}', true) ?: [];
        $settings['board'] = DB::table('users')->where('is_board_member', true)->pluck('id')->all();
        $settings['cmt'] = DB::table('users')->where('is_cmt_member', true)->pluck('id')->all();
        DB::table('settings')->updateOrInsert(['key' => 'planning_governance'], ['value' => json_encode($settings), 'type' => 'json', 'group' => 'planning']);
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn(['is_board_member', 'is_cmt_member']));
    }
};
