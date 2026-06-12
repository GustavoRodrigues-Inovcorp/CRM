<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Inertia\Inertia;

class SettingsController extends Controller
{
    public function index()
    {
        /* ─── Logs do utilizador autenticado ─── */
        $logs = ActivityLog::where('user_id', auth()->id())
            ->latest()
            ->paginate(50);

        return Inertia::render('Settings/Index', [
            'logs' => $logs,
        ]);
    }
}