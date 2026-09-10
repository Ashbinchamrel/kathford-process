<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Department;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DepartmentController extends Controller
{

    public function index(): View
    {
        $departments = Department::withCount(['users', 'budgets', 'activityForms'])->orderBy('name')->get();
        $users = \App\Models\User::where('is_active', true)->orderBy('name')->get();
        return view('admin.departments.index', compact('departments', 'users'));
    }

    public function create(): View
    {
        return view('admin.departments.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'code' => ['required', 'string', 'max:20', 'alpha_num', 'uppercase', 'unique:departments,code'],
            'head_user_id' => ['nullable', 'exists:users,id'],
        ]);

        $dept = Department::create([
            'name'      => $request->name,
            'code'      => strtoupper($request->code),
            'head_user_id' => $request->head_user_id,
            'is_active' => true,
        ]);

        AuditLog::record(Auth::user(), 'department.created', $dept, $dept->name);

        return redirect()->route('admin.departments.index')
            ->with('success', "Department \"{$dept->name}\" created.");
    }

    public function edit(Department $department): View
    {
        $users = \App\Models\User::where('is_active', true)->orderBy('name')->get();

        return view('admin.departments.edit', compact('department', 'users'));
    }

    public function update(Request $request, Department $department): RedirectResponse
    {
        $request->validate([
            'name'      => ['required', 'string', 'max:100'],
            'head_user_id' => ['nullable', 'exists:users,id'],
            'is_active' => ['boolean'],
        ]);

        $department->update([
            'name'      => $request->name,
            'head_user_id' => $request->head_user_id,
            'is_active' => $request->boolean('is_active', true),
        ]);

        AuditLog::record(Auth::user(), 'department.updated', $department, $department->name);

        return redirect()->route('admin.departments.index')
            ->with('success', 'Department updated.');
    }

    public function destroy(Department $department): RedirectResponse
    {
        $hasLinkedRecords = $department->users()->exists()
            || $department->budgets()->exists()
            || $department->activityForms()->exists();

        if ($hasLinkedRecords) {
            return back()->withErrors(['error' => 'This department is linked to operational records and cannot be deleted. Set it to inactive instead.']);
        }

        AuditLog::record(Auth::user(), 'department.deleted', $department, $department->name);
        $department->delete();

        return redirect()->route('admin.departments.index')
            ->with('success', 'Department removed.');
    }
}
