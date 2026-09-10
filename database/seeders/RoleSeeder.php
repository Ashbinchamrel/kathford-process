<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['name' => 'super_admin', 'display_name' => 'Super Admin',   'is_system' => true],
            ['name' => 'verifier',   'display_name' => 'Verifier',       'is_system' => true],
            ['name' => 'approver',   'display_name' => 'Approver',       'is_system' => true],
            ['name' => 'finance',    'display_name' => 'Finance/Accounts','is_system' => true],
            ['name' => 'general',    'display_name' => 'General User',   'is_system' => true],
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role['name']], $role);
        }
    }
}
