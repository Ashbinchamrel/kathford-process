<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FiscalYear;
use App\Models\Setting;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class FiscalYearController extends Controller
{
    public function store(Request $request)
    {
        $data = $this->validated($request);
        DB::transaction(function () use ($data, $request) {
            $year = FiscalYear::create($data);
            AuditLog::record($request->user(), 'fiscal_year.created', $year, 'Fiscal year '.$year->name.' created');
        });
        return back()->with('success', 'Fiscal year created. Set it as active when you are ready to use it.');
    }

    public function update(Request $request, FiscalYear $fiscalYear)
    {
        abort_if($fiscalYear->is_legacy, 403);
        $data = $this->validated($request, $fiscalYear);
        DB::transaction(function () use ($fiscalYear, $data, $request) {
            $fiscalYear->update($data);
            DB::table('department_budgets')->where('fiscal_year_id', $fiscalYear->id)->update(['fiscal_year' => $fiscalYear->name]);
            AuditLog::record($request->user(), 'fiscal_year.updated', $fiscalYear, 'Fiscal year dates and label updated');
        });
        return back()->with('success', 'Fiscal year setup updated. Existing records remain in this year.');
    }

    public function activate(Request $request, FiscalYear $fiscalYear)
    {
        abort_if($fiscalYear->is_legacy, 403);
        if (!$fiscalYear->starts_on || !$fiscalYear->ends_on) {
            throw ValidationException::withMessages(['fiscal_year' => 'Save the start and end dates before activating this fiscal year.']);
        }
        DB::transaction(function () use ($fiscalYear, $request) {
            Setting::set('active_fiscal_year_id', $fiscalYear->id, 'integer', 'company');
            AuditLog::record($request->user(), 'fiscal_year.activated', $fiscalYear, 'Active organisation fiscal year changed to '.$fiscalYear->name);
        });
        $request->session()->forget('fiscal_year_id');
        return back()->with('success', $fiscalYear->name.' is now the active fiscal year. Other years are available for viewing.');
    }

    public function select(Request $request)
    {
        abort_unless($request->user()->isSuperAdmin(), 403);
        $data = $request->validate(['fiscal_year_id' => ['required', 'exists:fiscal_years,id']]);
        if ((int) $data['fiscal_year_id'] === (int) Setting::get('active_fiscal_year_id')) {
            $request->session()->forget('fiscal_year_id');
        } else {
            $request->session()->put('fiscal_year_id', (int) $data['fiscal_year_id']);
        }
        // A dashboard redirect prevents stale record IDs/filters from the previous year.
        return redirect()->route('dashboard')->with('success', 'Working fiscal year changed.');
    }

    private function validated(Request $request, ?FiscalYear $year = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:30', Rule::unique('fiscal_years')->ignore($year?->id)],
            'starts_on' => ['required', 'date_format:Y-m-d'],
            'ends_on' => ['required', 'date_format:Y-m-d', 'after_or_equal:starts_on'],
        ]);
        if (FiscalYear::where('is_legacy', false)->when($year, fn ($q) => $q->whereKeyNot($year->id))
            ->where('starts_on', '<=', $data['ends_on'])->where('ends_on', '>=', $data['starts_on'])->exists()) {
            throw ValidationException::withMessages(['starts_on' => 'Fiscal year dates must not overlap another configured year.']);
        }
        return $data;
    }
}
