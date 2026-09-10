<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{

    public function index(Request $request): View
    {
        $query = AuditLog::with('user')->latest('logged_at');

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('action', 'like', "%{$request->search}%")
                  ->orWhere('model_label', 'like', "%{$request->search}%");
            });
        }
        if ($request->action) {
            $query->where('action', 'like', "%{$request->action}%");
        }
        if ($request->user_id) {
            $query->where('user_id', $request->user_id);
        }
        if ($request->date_from) {
            $query->where('logged_at', '>=', $request->date_from);
        }
        if ($request->date_to) {
            $query->where('logged_at', '<=', $request->date_to . ' 23:59:59');
        }

        $logs = $query->paginate(50)->withQueryString();

        return view('admin.audit-logs.index', compact('logs'));
    }

    public function show(AuditLog $auditLog): View
    {
        $auditLog->load('user');
        return view('admin.audit-logs.show', compact('auditLog'));
    }
}
