<?php

require_once __DIR__.'/../vendor/autoload.php';

use App\Models\ActivityForm;
use App\Models\User;
use App\Support\RecordVisibility;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use PHPUnit\Framework\TestCase;

final class RecordVisibilityTest extends TestCase
{
    protected function setUp(): void
    {
        $app = require __DIR__.'/../bootstrap/app.php';
        $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        Schema::create('activity_forms', function (Blueprint $t) {
            $t->string('id')->primary(); $t->string('creator_id'); $t->string('status'); $t->string('approval_chain_id')->nullable(); $t->softDeletes();
        });
        Schema::create('approval_actions', function (Blueprint $t) { $t->string('actionable_type'); $t->string('actionable_id'); $t->string('actor_id'); });
        Schema::create('approval_chains', function (Blueprint $t) { $t->string('id'); });
        Schema::create('users', function (Blueprint $t) { $t->string('id'); $t->softDeletes(); });
        Schema::create('approval_chain_members', function (Blueprint $t) { $t->string('approval_chain_id'); $t->string('user_id'); $t->string('role'); $t->integer('sort_order'); });
        DB::table('activity_forms')->insert([
            ['id' => 'own', 'creator_id' => 'alice', 'status' => 'draft'],
            ['id' => 'other', 'creator_id' => 'bob', 'status' => 'approved'],
        ]);
    }

    protected function tearDown(): void
    {
        restore_error_handler();
        restore_exception_handler();
        parent::tearDown();
    }

    private function user(bool $admin = false): User
    {
        $user = new class extends User {
            public bool $admin = false;
            public bool $reviewer = false;
            public function isSuperAdmin(): bool { return $this->admin; }
            public function can($abilities, $arguments = []): bool { return $this->reviewer && $abilities === 'activity_forms.verify'; }
        };
        $user->id = 'alice'; $user->admin = $admin;
        return $user;
    }

    public function test_lists_and_direct_record_queries_exclude_other_creators(): void
    {
        $this->assertSame(['own'], RecordVisibility::apply(ActivityForm::query(), $this->user())->pluck('id')->all());
        $this->assertFalse(RecordVisibility::apply(ActivityForm::query(), $this->user())->whereKey('other')->exists());
        $this->assertSame(['own'], ActivityForm::forUser($this->user())->pluck('id')->all());
    }

    public function test_super_admin_retains_oversight(): void
    {
        $this->assertSame(2, RecordVisibility::apply(ActivityForm::query(), $this->user(true))->count());
    }

    public function test_chain_membership_does_not_expose_unrelated_history(): void
    {
        DB::table('users')->insert(['id' => 'alice']);
        DB::table('approval_chains')->insert(['id' => 'chain']);
        DB::table('approval_chain_members')->insert(['approval_chain_id' => 'chain', 'user_id' => 'alice', 'role' => 'verifier', 'sort_order' => 0]);
        DB::table('activity_forms')->where('id', 'other')->update(['approval_chain_id' => 'chain']);
        DB::table('activity_forms')->insert([
            ['id' => 'pending', 'creator_id' => 'bob', 'status' => 'pending_verification', 'approval_chain_id' => 'chain'],
            ['id' => 'approval', 'creator_id' => 'bob', 'status' => 'pending_approval', 'approval_chain_id' => 'chain'],
        ]);
        $user = $this->user(); $user->reviewer = true;
        $this->assertEqualsCanonicalizing(['own', 'pending'], ActivityForm::forUser($user)->pluck('id')->all());
        $this->assertFalse(RecordVisibility::apply(ActivityForm::query(), $user)->whereKey('other')->exists());
        DB::table('approval_actions')->insert(['actionable_type' => ActivityForm::class, 'actionable_id' => 'other', 'actor_id' => 'alice']);
        $this->assertTrue(ActivityForm::forUser($user)->whereKey('other')->exists());
    }
}
