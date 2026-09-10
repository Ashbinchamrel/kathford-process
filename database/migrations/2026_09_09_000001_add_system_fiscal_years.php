<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Support\FiscalYearRecords;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('fiscal_years', function (Blueprint $table) {
            $table->id();
            $table->string('name', 30)->unique();
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->boolean('is_legacy')->default(false);
            $table->timestamps();
        });
        foreach (array_keys(FiscalYearRecords::PARENTS) as $name) {
            Schema::table($name, fn (Blueprint $table) => $table->foreignId('fiscal_year_id')->nullable()->constrained('fiscal_years')->restrictOnDelete());
        }
        // Labels are authoritative. Do not guess AD/BS boundaries or unrelated records' years.
        $labels = DB::table('department_budgets')->distinct()->orderBy('fiscal_year')->pluck('fiscal_year');
        $active = null;
        foreach ($labels as $label) {
            $active = DB::table('fiscal_years')->insertGetId(['name' => $label, 'created_at' => now(), 'updated_at' => now()]);
            DB::table('department_budgets')->where('fiscal_year', $label)->update(['fiscal_year_id' => $active]);
        }
        // Follow known source links, including backwards from payment batches.
        for ($pass = 0; $pass < 12; $pass++) {
            $changed = 0;
            foreach (FiscalYearRecords::PARENTS as $table => $parents) {
                foreach ($parents as $key => $parent) {
                    if (!Schema::hasColumn($table, $key)) continue;
                    $rows = DB::table($table.' as child')->join($parent.' as parent', 'child.'.$key, '=', 'parent.id')
                        ->whereNull('child.fiscal_year_id')->whereNotNull('parent.fiscal_year_id')
                        ->select('child.id', 'parent.fiscal_year_id')->get();
                    foreach ($rows as $row) $changed += DB::table($table)->where('id', $row->id)->update(['fiscal_year_id' => $row->fiscal_year_id]);
                }
            }
            foreach (DB::table('payment_authorisations')->whereNull('fiscal_year_id')->pluck('id') as $id) {
                $years = DB::table('payments')->where('payment_authorisation_id', $id)->whereNotNull('fiscal_year_id')->distinct()->pluck('fiscal_year_id');
                if ($years->count() === 1) $changed += DB::table('payment_authorisations')->where('id', $id)->update(['fiscal_year_id' => $years->first()]);
            }
            if (!$changed) break;
        }
        $legacy = DB::table('fiscal_years')->insertGetId(['name' => 'Unassigned historical data', 'is_legacy' => true, 'created_at' => now(), 'updated_at' => now()]);
        foreach (array_keys(FiscalYearRecords::PARENTS) as $table) {
            DB::table($table)->whereNull('fiscal_year_id')->update(['fiscal_year_id' => $legacy]);
        }
        if ($active) DB::table('settings')->updateOrInsert(['key' => 'active_fiscal_year_id'], ['value' => (string) $active, 'type' => 'integer', 'group' => 'company', 'created_at' => now(), 'updated_at' => now()]);
    }

    public function down(): void
    {
        foreach (array_reverse(array_keys(FiscalYearRecords::PARENTS)) as $name) {
            Schema::table($name, fn (Blueprint $table) => $table->dropConstrainedForeignId('fiscal_year_id'));
        }
        DB::table('settings')->where('key', 'active_fiscal_year_id')->delete();
        Schema::dropIfExists('fiscal_years');
    }
};
