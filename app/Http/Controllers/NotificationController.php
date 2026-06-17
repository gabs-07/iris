<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(): View
    {
        $notifications = Auth::user()->notifications()->latest()->paginate(20);
        return view('notificaciones.index', compact('notifications'));
    }

    public function markRead(string $id): RedirectResponse
    {
        $notification = Auth::user()->notifications()->where('id', $id)->firstOrFail();
        $notification->markAsRead();
        $url = $notification->data['url'] ?? '/dashboard';
        return redirect($url);
    }

    public function markReadBack(string $id): RedirectResponse
    {
        $notification = Auth::user()->notifications()->where('id', $id)->firstOrFail();
        $notification->markAsRead();
        return back()->with('success', 'Notificación marcada como leída.');
    }

    public function markAllRead(): RedirectResponse
    {
        Auth::user()->unreadNotifications->each->markAsRead();
        return back()->with('success', 'Notificaciones marcadas como leídas.');
    }
}
