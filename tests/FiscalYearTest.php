<?php

require_once __DIR__.'/../vendor/autoload.php';

use App\Models\ActivityForm;
use App\Models\DepartmentBudget;
use App\Models\FiscalYear;
use App\Support\FiscalYearContext;
use App\Support\FiscalYearRecords;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\TestCase;

final class FiscalYearTest extends TestCase
{
    protected function setUp(): void
    {
        $app = require __DIR__.'/../bootstrap/app.php';
        $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        Schema::create('settings', function (Blueprint $t) {
            $t->string('key')->primary(); $t->string('value')->nullable(); $t->string('type'); $t->string('group'); $t->timestamps();
        });
        foreach (FiscalYearRecords::PARENTS as $table => $parents) {
            Schema::create($table, function (Blueprint $t) use ($table, $parents) {
                $t->string('id')->primary(); $t->timestamps(); $t->softDeletes();
                foreach ($parents as $key => $_) $t->string($key)->nullable();
                if ($table === 'department_budgets') { $t->string('fiscal_year'); $t->string('activity_title')->nullable(); }
                if ($table === 'activity_forms') { $t->string('creator_id')->nullable(); $t->string('status')->nullable(); }
            });
        }
        DB::table('department_budgets')->insert([['id' => 'b1', 'fiscal_year' => '2025/2026'], ['id' => 'b2', 'fiscal_year' => '2026/2027']]);
        DB::table('activity_forms')->insert([
            ['id' => '11111111-1111-4111-8111-111111111111', 'budget_id' => 'b1'], ['id' => 'current', 'budget_id' => 'b2'], ['id' => 'unknown', 'budget_id' => null],
        ]);
        DB::table('rfqs')->insert(['id' => 'rfq1', 'activity_form_id' => 'current']);
        DB::table('rfq_quotes')->insert(['id' => 'quote1', 'rfq_id' => 'rfq1']);
        DB::table('purchase_orders')->insert(['id' => 'po1', 'rfq_quote_id' => 'quote1']);
        DB::table('payment_authorisations')->insert(['id' => 'pa1']);
        DB::table('payments')->insert(['id' => 'pay1', 'purchase_order_id' => 'po1', 'payment_authorisation_id' => 'pa1']);
        (require __DIR__.'/../database/migrations/2026_09_09_000001_add_system_fiscal_years.php')->up();
    }

    protected function tearDown(): void
    {
        restore_error_handler(); restore_exception_handler(); parent::tearDown();
    }

    private function select(string $name, bool $active = true): FiscalYear
    {
        $context = app(FiscalYearContext::class);
        $context->year = FiscalYear::where('name', $name)->firstOrFail();
        $context->activeId = $active ? $context->year->id : 999;
        $context->enabled = true;
        return $context->year;
    }

    public function test_regular_user_cannot_override_active_year_with_a_stale_session(): void
    {
        $active = FiscalYear::where('name','2026/2027')->firstOrFail();
        \App\Models\Setting::set('active_fiscal_year_id',$active->id,'integer');
        $user = new class extends \App\Models\User { public function isSuperAdmin(): bool { return false; } };
        $request = \Illuminate\Http\Request::create('/activity-forms');
        $request->setUserResolver(fn()=>$user);
        $session = new \Illuminate\Session\Store('test', new \Illuminate\Session\ArraySessionHandler(120));
        $session->put('fiscal_year_id',FiscalYear::where('name','2025/2026')->value('id'));
        $request->setLaravelSession($session);
        (new \App\Http\Middleware\SetFiscalYear)->handle($request,fn()=>null);
        $this->assertSame($active->id,app(FiscalYearContext::class)->year->id);
    }

    public function test_migration_preserves_years_and_propagates_source_links(): void
    {
        $id = FiscalYear::where('name', '2026/2027')->value('id');
        foreach (['rfqs', 'rfq_quotes', 'purchase_orders', 'payments', 'payment_authorisations'] as $table) {
            $this->assertSame($id, DB::table($table)->value('fiscal_year_id'));
        }
        $legacy = FiscalYear::where('is_legacy', true)->value('id');
        $this->assertSame($legacy, DB::table('activity_forms')->where('id', 'unknown')->value('fiscal_year_id'));
        $this->assertSame(3, DB::table('activity_forms')->count());
    }

    public function test_year_filters_lists_counts_and_route_binding(): void
    {
        $this->select('2026/2027');
        $this->assertSame(['current'], ActivityForm::pluck('id')->all());
        $this->assertSame(1, ActivityForm::count());
        $this->assertNull((new ActivityForm)->resolveRouteBinding('11111111-1111-4111-8111-111111111111'));
        $this->select('2025/2026', false);
        $this->assertSame(['11111111-1111-4111-8111-111111111111'], ActivityForm::pluck('id')->all());
    }

    public function test_new_entries_are_stamped_with_selected_year(): void
    {
        $year = $this->select('2026/2027');
        $form = ActivityForm::create(['budget_id' => 'b2', 'status' => 'draft']);
        $this->assertSame($year->id, $form->fiscal_year_id);
    }

    public function test_cross_year_source_is_rejected(): void
    {
        $this->select('2026/2027');
        $this->expectException(ValidationException::class);
        ActivityForm::create(['budget_id' => 'b1', 'status' => 'draft']);
    }

    public function test_historical_year_is_read_only(): void
    {
        $this->select('2025/2026', false);
        $this->expectException(ValidationException::class);
        ActivityForm::findOrFail('11111111-1111-4111-8111-111111111111')->update(['status' => 'approved']);
    }

    public function test_existing_record_cannot_move_years(): void
    {
        $this->select('2026/2027');
        $form = ActivityForm::findOrFail('current');
        $form->fiscal_year_id = 999;
        $this->expectException(ValidationException::class);
        $form->save();
    }

    public function test_budget_label_comes_from_central_setup(): void
    {
        $this->select('2026/2027');
        $budget = DepartmentBudget::create(['fiscal_year' => 'incorrect', 'activity_title' => 'Test']);
        $this->assertSame('2026/2027', $budget->fiscal_year);
    }

    public function test_fiscal_year_setup_rejects_overlapping_dates(): void
    {
        FiscalYear::where('name', '2026/2027')->update(['starts_on' => '2026-07-16', 'ends_on' => '2027-07-15']);
        $request = Illuminate\Http\Request::create('/', 'POST', ['name' => 'Overlap', 'starts_on' => '2027-01-01', 'ends_on' => '2027-12-31']);
        $controller = new App\Http\Controllers\Admin\FiscalYearController;
        $method = new ReflectionMethod($controller, 'validated');
        $this->expectException(ValidationException::class);
        $method->invoke($controller, $request);
    }

    public function test_fiscal_year_setup_accepts_non_overlapping_dates(): void
    {
        FiscalYear::where('name', '2026/2027')->update(['starts_on' => '2026-07-16', 'ends_on' => '2027-07-15']);
        $request = Illuminate\Http\Request::create('/', 'POST', ['name' => '2027/2028', 'starts_on' => '2027-07-16', 'ends_on' => '2028-07-15']);
        $method = new ReflectionMethod(new App\Http\Controllers\Admin\FiscalYearController, 'validated');
        $this->assertSame('2027/2028', $method->invoke(new App\Http\Controllers\Admin\FiscalYearController, $request)['name']);
    }
}
