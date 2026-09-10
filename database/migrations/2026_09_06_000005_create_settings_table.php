<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->text('value')->nullable();
            $table->string('type', 20)->default('string'); // string|boolean|integer|json
            $table->string('group', 50)->default('general');
            $table->timestamps();
        });

        // Seed defaults
        $defaults = [
            ['key' => 'company_name',          'value' => 'Kathford International College', 'type' => 'string',  'group' => 'company'],
            ['key' => 'company_address',        'value' => 'Balkumari, Lalitpur, Nepal',     'type' => 'string',  'group' => 'company'],
            ['key' => 'company_phone',          'value' => '',                               'type' => 'string',  'group' => 'company'],
            ['key' => 'company_email',          'value' => '',                               'type' => 'string',  'group' => 'company'],
            ['key' => 'company_website',        'value' => '',                               'type' => 'string',  'group' => 'company'],
            ['key' => 'company_pan',            'value' => '',                               'type' => 'string',  'group' => 'company'],
            ['key' => 'fiscal_year_start',      'value' => '07-16',                         'type' => 'string',  'group' => 'company'],
            ['key' => 'currency_symbol',        'value' => 'NPR',                           'type' => 'string',  'group' => 'company'],
            ['key' => 'date_format',            'value' => 'BS',                            'type' => 'string',  'group' => 'company'],
        ];
        foreach ($defaults as $row) {
            \DB::table('settings')->insertOrIgnore(array_merge($row, [
                'created_at' => now(), 'updated_at' => now(),
            ]));
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
