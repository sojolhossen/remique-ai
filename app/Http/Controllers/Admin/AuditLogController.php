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
        $search = trim($request->get('search', ''));
        $action = $request->get('action', '');

        $query = AuditLog::with('user');

        if (! empty($action)) {
            $query->where('action', $action);
        }

        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('action', 'like', "%{$search}%")
                  ->orWhere('reason', 'like', "%{$search}%")
                  ->orWhere('target_type', 'like', "%{$search}%")
                  ->orWhere('ip_address', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('name', 'like', "%{$search}%")
                         ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        $logs = $query->orderByDesc('created_at')->paginate(25)->withQueryString();

        $distinctActions = AuditLog::select('action')->distinct()->orderBy('action')->pluck('action');

        return view('admin.audit.index', compact('logs', 'distinctActions', 'search', 'action'));
    }
}
