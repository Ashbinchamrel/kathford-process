<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $departments = [
            ['name' => 'Administration',         'code' => 'ADMIN'],
            ['name' => 'Finance & Accounts',      'code' => 'FIN'],
            ['name' => 'Academic Affairs',         'code' => 'ACAD'],
            ['name' => 'IT Department',            'code' => 'IT'],
            ['name' => 'Student Affairs',          'code' => 'SA'],
            ['name' => 'Library',                  'code' => 'LIB'],
            ['name' => 'Marketing & Admissions',   'code' => 'MKT'],
            ['name' => 'Human Resources',          'code' => 'HR'],
            ['name' => 'Examination',              'code' => 'EXAM'],
        ];

        foreach ($departments as $dept) {
            Department::firstOrCreate(
                ['code' => $dept['code']],
                array_merge($dept, ['is_active' => true])
            );
        }
    }
}
