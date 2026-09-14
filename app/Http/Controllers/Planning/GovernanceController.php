<?php

namespace App\Http\Controllers\Planning;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Department;
use App\Models\Planning\Document;
use App\Models\Planning\Item;
use App\Models\Planning\Period;
use App\Models\Planning\Task;
use App\Models\Setting;
use App\Services\Planning\Access;
use App\Services\Planning\Governance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class GovernanceController extends Controller
{
    public function save(Request $r)
    {
        abort_unless($r->user()->can('planning.setup'), 403);
        $data = $r->validate([
            'board' => 'prohibited', 'cmt' => 'prohibited', 'heads' => 'prohibited',
            'chains' => 'required|array', 'chains.*' => ['nullable', Rule::exists('approval_chains', 'id')->where('is_active', true)],
        ]);
        $chains = array_intersect_key($data['chains'], Access::TYPES);
        Setting::set('planning_governance', ['chains' => $chains], 'json', 'planning');
        AuditLog::record($r->user(), 'planning.approval_defaults.updated', null, 'Updated Planning approval defaults.');

        return back()->with('success', 'Planning approval defaults saved.');
    }

    public function dashboard(Request $r, string $level)
    {
        $levels = Governance::dashboardLevels($r->user());
        abort_unless(isset($levels[$level]), 403);
        if ($level !== 'department') {
            abort_unless(Governance::member($r->user(), $level), 403);
        }
        $departments = Department::active()->where('head_user_id', $r->user()->id)->get();
        $departmentId = $r->input('department_id', $departments->first()?->id);
        if ($level === 'department') {
            abort_unless($departmentId && $departments->contains('id', $departmentId), 403, 'This dashboard is available to the designated department head.');
        }
        $type = $level === 'board' ? 'cmt_goals' : 'goals';
        $documents = Document::where('type', $type)->where('status', 'approved');
        if ($level === 'department') {
            $documents->where('department_id', $departmentId);
        }
        if ($r->filled('period_id')) {
            $documents->where('period_id', $r->period_id);
        }
        $ids = (clone $documents)->pluck('id');
        $goals = Item::with(['document.department', 'document.period', 'owner'])->whereIn('document_id', $ids);
        $summary = ['documents' => $ids->count(), 'goals' => (clone $goals)->count(), 'reported' => (clone $goals)->whereNotNull('actual')->count(), 'overdue' => (clone $goals)->where('due_on', '<', today())->where(fn ($q) => $q->whereNull('actual')->orWhereColumn('actual', '<', 'target'))->count()];
        $tasks = null;
        if ($level === 'department') {
            $tasks = Task::where('is_private', false)->where('department_id', $departmentId)->select('status', DB::raw('count(*) as total'))->groupBy('status')->pluck('total', 'status');
        }
        $periods = Period::orderByDesc('starts_on')->get();

        return view('planning.governance-dashboard', ['dashboardLevels' => $levels, 'level' => $level, 'summary' => $summary, 'goals' => $goals->orderBy('due_on')->paginate(15)->withQueryString(), 'departments' => $departments, 'departmentId' => $departmentId, 'periods' => $periods, 'tasks' => $tasks]);
    }
}
