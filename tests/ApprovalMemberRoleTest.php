<?php
require_once __DIR__.'/../vendor/autoload.php';

use App\Rules\ApprovalMemberRole;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\TestCase;

final class ApprovalMemberRoleTest extends TestCase
{
    public function test_only_active_matching_roles_are_accepted(): void
    {
        $app = require __DIR__.'/../bootstrap/app.php';
        $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        try {
            Schema::create('roles', function (Blueprint $t) { $t->string('id'); $t->string('name'); });
            Schema::create('users', function (Blueprint $t) { $t->string('id'); $t->string('role_id'); $t->boolean('is_active'); $t->softDeletes(); });
            Schema::create('user_roles', function (Blueprint $t) { $t->id(); $t->string('user_id'); $t->string('role_id'); });
            foreach (['verifier','approver','super_admin','finance','general'] as $role) {
                DB::table('roles')->insert(['id' => $role, 'name' => $role]);
                DB::table('users')->insert(['id' => $role, 'role_id' => $role, 'is_active' => true]);
            }
            foreach (['verifier', 'approver'] as $requiredRole) {
                foreach (['verifier','approver','super_admin','finance','general','missing'] as $id) {
                    $validator = Validator::make(['id' => $id], ['id' => [new ApprovalMemberRole($requiredRole)]]);
                    $this->assertSame($id === $requiredRole, $validator->passes());
                }
            }
            DB::table('users')->where('id', 'verifier')->update(['is_active' => false]);
            $this->assertFalse(Validator::make(['id' => 'verifier'], ['id' => [new ApprovalMemberRole('verifier')]])->passes());
            DB::table('users')->where('id', 'approver')->update(['deleted_at' => now()]);
            $this->assertFalse(Validator::make(['id' => 'approver'], ['id' => [new ApprovalMemberRole('approver')]])->passes());
        } finally {
            restore_error_handler(); restore_exception_handler();
        }
    }
}
