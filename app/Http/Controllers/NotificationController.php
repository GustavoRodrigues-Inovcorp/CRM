<?php

namespace App\Http\Controllers;

use App\Models\CrmNotification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /* ─── Lista notificações não lidas ─── */
    public function index()
    {
        $notifications = CrmNotification::where('user_id', auth()->id())
            ->latest()
            ->take(20)
            ->get();

        $unreadCount = CrmNotification::where('user_id', auth()->id())
            ->where('read', false)
            ->count();

        return response()->json([
            'notifications' => $notifications,
            'unread_count'  => $unreadCount,
        ]);
    }

    /* ─── Marca uma notificação como lida ─── */
    public function markRead(CrmNotification $notification)
    {
        abort_if($notification->user_id !== auth()->id(), 403);
        $notification->update(['read' => true]);
        return response()->json(['success' => true]);
    }

    /* ─── Marca todas como lidas ─── */
    public function markAllRead()
    {
        CrmNotification::where('user_id', auth()->id())
            ->where('read', false)
            ->update(['read' => true]);

        return response()->json(['success' => true]);
    }
}