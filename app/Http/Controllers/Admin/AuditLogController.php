<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AuditLogController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('search', ''));

        return Inertia::render('Admin/AuditLogs', [
            'logs' => AuditLog::with('user')
                ->when($search, fn ($q) => $q->where(fn ($w) => $w->where('action', 'like', "%{$search}%")->orWhere('description', 'like', "%{$search}%")))
                ->latest('id')->paginate(25)->withQueryString()
                ->through(fn ($l) => [
                    'id' => $l->id, 'user' => $l->user?->name ?? 'System', 'action' => $l->action, 'description' => $l->description,
                    'ip' => $l->ip_address, 'at' => $l->created_at->format('M j, Y g:i A'),
                ]),
            'filters' => ['search' => $search],
        ]);
    }
}
