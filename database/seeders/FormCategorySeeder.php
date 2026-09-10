<?php

namespace Database\Seeders;

use App\Models\FormCategory;
use Illuminate\Database\Seeder;

class FormCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name'                      => 'Planned Academic Activity',
                'code'                      => 'PAA',
                'description'               => 'Approval form for planned academic activities',
                'category_group'            => 'activity',
                'requires_reason'           => false,
                'requires_logistic_table'   => true,
                'requires_attachments'      => true,
                'requires_vendor_selection' => false,
                'auto_generate_next'        => false,
                'sort_order'                => 10,
                'is_active'                 => true,
                'is_system'                 => true,
            ],
            [
                'name'                      => 'Planned Operation Activity',
                'code'                      => 'POA',
                'description'               => 'Approval form for planned operation/administrative activities',
                'category_group'            => 'activity',
                'requires_reason'           => false,
                'requires_logistic_table'   => true,
                'requires_attachments'      => true,
                'requires_vendor_selection' => false,
                'auto_generate_next'        => false,
                'sort_order'                => 20,
                'is_active'                 => true,
                'is_system'                 => true,
            ],
            [
                'name'                      => 'Unplanned Academic Activity',
                'code'                      => 'UAA',
                'description'               => 'Approval form for unplanned academic activities (requires reason)',
                'category_group'            => 'activity',
                'requires_reason'           => true,
                'requires_logistic_table'   => true,
                'requires_attachments'      => true,
                'requires_vendor_selection' => false,
                'auto_generate_next'        => false,
                'sort_order'                => 30,
                'is_active'                 => true,
                'is_system'                 => true,
            ],
            [
                'name'                      => 'Unplanned Operation Activity',
                'code'                      => 'UOA',
                'description'               => 'Approval form for unplanned operation activities (requires reason)',
                'category_group'            => 'activity',
                'requires_reason'           => true,
                'requires_logistic_table'   => true,
                'requires_attachments'      => true,
                'requires_vendor_selection' => false,
                'auto_generate_next'        => false,
                'sort_order'                => 40,
                'is_active'                 => true,
                'is_system'                 => true,
            ],
            [
                'name'                      => 'Purchase Request',
                'code'                      => 'PR',
                'description'               => 'Purchase request referencing an approved activity form',
                'category_group'            => 'purchase',
                'requires_reason'           => false,
                'requires_logistic_table'   => true,
                'requires_attachments'      => false,
                'requires_vendor_selection' => true,
                'auto_generate_next'        => false,
                'sort_order'                => 50,
                'is_active'                 => true,
                'is_system'                 => true,
            ],
        ];

        foreach ($categories as $cat) {
            FormCategory::firstOrCreate(
                ['code' => $cat['code']],
                array_merge($cat, ['extra_fields' => []])
            );
        }
    }
}
