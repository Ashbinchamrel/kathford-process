<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\DepartmentBudget;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class BudgetController extends Controller
{
    public function index(Request $request): View
    {
        $this->ensureBudgetManager();

        $query = DepartmentBudget::with('department')->orderByDesc('fiscal_year')->orderBy('activity_title');

        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }
        if ($request->filled('fiscal_year')) {
            $query->where('fiscal_year', $request->fiscal_year);
        }
        if ($request->filled('search')) {
            $search = $request->string('search')->trim();
            $query->where(function ($q) use ($search) {
                $q->where('activity_title', 'like', "%{$search}%")
                    ->orWhereHas('department', fn ($department) => $department->where('name', 'like', "%{$search}%"));
            });
        }

        $budgets = $query->get();
        $budgetRows = $budgets->map(fn (DepartmentBudget $budget) => [
            'budget' => $budget,
            'reserved' => $budget->reservedAmount(),
            'remaining' => $budget->remainingAmount(),
        ]);

        return view('budgets.index', [
            'budgetRows' => $budgetRows,
            'departments' => Department::active()->orderBy('name')->get(),
            'fiscalYears' => DepartmentBudget::query()->select('fiscal_year')->distinct()->orderByDesc('fiscal_year')->pluck('fiscal_year'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensureBudgetManager();
        $data = $this->validatedBudget($request);
        $data['created_by'] = Auth::id();
        DepartmentBudget::create($data);

        return back()->with('success', 'Department budget activity created. It is now available when creating activity forms.');
    }

    public function update(Request $request, DepartmentBudget $budget): RedirectResponse
    {
        $this->ensureBudgetManager();
        $this->assertNotPlanningBudget($budget->id);
        $data = $this->validatedBudget($request, $budget->id);
        $budget->update($data);

        return back()->with('success', 'Department budget updated.');
    }

    public function upload(Request $request): RedirectResponse
    {
        $this->ensureBudgetManager();
        $request->validate(['budget_file' => ['required', 'file', 'mimes:csv,txt', 'max:5120']]);

        $handle = fopen($request->file('budget_file')->getRealPath(), 'r');
        $headers = fgetcsv($handle);
        $headers = array_map(fn ($header) => Str::of((string) $header)->replace("\xEF\xBB\xBF", '')->lower()->trim()->replace(' ', '_')->toString(), $headers ?: []);
        $required = ['department', 'fiscal_year', 'title'];

        if (array_diff($required, $headers)) {
            fclose($handle);
            return back()->withErrors(['budget_file' => 'CSV must contain these headings: department, fiscal_year, title. Allocation, notes, and active are optional.']);
        }

        $created = 0;
        $updated = 0;
        $rowNumber = 1;
        try {
            DB::transaction(function () use ($handle, $headers, &$created, &$updated, &$rowNumber) {
                while (($row = fgetcsv($handle)) !== false) {
                    $rowNumber++;
                    if (!array_filter($row, fn ($value) => trim((string) $value) !== '')) continue;
                    $record = array_combine($headers, array_pad($row, count($headers), null));
                    $department = Department::active()->where('name', trim((string) ($record['department'] ?? '')))->first();
                    $allocation = str_replace([',', 'Rs', 'NPR', ' '], '', (string) ($record['allocation'] ?? ''));
                    $hasAllocation = $allocation !== '';
                    $allocation = $hasAllocation ? $allocation : '0';

                    if (!$department || blank($record['fiscal_year'] ?? null) || blank($record['title'] ?? null) || !is_numeric($allocation) || (float) $allocation < 0) {
                        throw new \RuntimeException("Invalid budget CSV data on row {$rowNumber}. Use an active department name, fiscal year, title, and, if supplied, a non-negative allocation.");
                    }

                    if (trim((string) $record['fiscal_year']) !== app(\App\Support\FiscalYearContext::class)->year?->name) {
                        throw new \RuntimeException("Row {$rowNumber}: fiscal_year must match the selected active fiscal year.");
                    }

                    $budget = DepartmentBudget::withTrashed()->firstOrNew([
                        'department_id' => $department->id,
                        'fiscal_year' => trim((string) $record['fiscal_year']),
                        'activity_title' => trim((string) $record['title']),
                    ]);
                    if ($budget->exists) $this->assertNotPlanningBudget($budget->id);
                    // An omitted allocation creates a title-only budget activity. It does not
                    // erase an existing allocation when a CSV is used just to maintain titles.
                    if ($hasAllocation || ! $budget->exists) {
                        $budget->allocated_amount = $allocation;
                    }
                    $budget->notes = $record['notes'] ?? null;
                    $budget->is_active = !isset($record['active']) || !in_array(strtolower(trim((string) $record['active'])), ['0', 'false', 'no', 'inactive'], true);
                    $budget->created_by ??= Auth::id();
                    if ($budget->trashed()) $budget->restore();
                    $budget->exists ? $updated++ : $created++;
                    $budget->save();
                }
            });
        } catch (\Throwable $exception) {
            fclose($handle);
            return back()->withErrors(['budget_file' => $exception->getMessage()]);
        }
        fclose($handle);

        return back()->with('success', "Budget upload complete: {$created} created, {$updated} updated.");
    }

    private function validatedBudget(Request $request, ?string $ignoreId = null): array
    {
        $rules = [
            'department_id' => ['required', 'exists:departments,id'],
            'fiscal_year' => ['required', \Illuminate\Validation\Rule::in([app(\App\Support\FiscalYearContext::class)->year?->name])],
            'activity_title' => ['required', 'string', 'max:200'],
            'allocated_amount' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['nullable', 'boolean'],
        ];
        $data = $request->validate($rules);
        $data['allocated_amount'] = $data['allocated_amount'] ?? 0;
        $data['is_active'] = $request->boolean('is_active', true);

        $duplicate = DepartmentBudget::withTrashed()
            ->where('department_id', $data['department_id'])
            ->where('fiscal_year', $data['fiscal_year'])
            ->where('activity_title', $data['activity_title'])
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->exists();
        if ($duplicate) {
            throw ValidationException::withMessages([
                'activity_title' => 'This department already has the same activity title in that fiscal year.',
            ]);
        }

        return $data;
    }

    private function assertNotPlanningBudget(string $id): void
    {
        if (\Illuminate\Support\Facades\Schema::hasTable('planning_budget_publications') && DB::table('planning_budget_publications')->where('budget_id', $id)->exists()) {
            throw ValidationException::withMessages(['budget' => 'This allocation was approved through Planning and cannot be overwritten here.']);
        }
    }

    private function ensureBudgetManager(): void
    {
        abort_unless(
            Auth::user()->isSuperAdmin()
                || Auth::user()->can('budgets.view')
                || Auth::user()->can('budgets.manage'),
            403
        );
    }
}
