<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {

        $user = $request->user();
        $notifications = $user->notifications()->latest()->paginate(20);

        return view('public.notifications.index', compact('notifications'));
    }

    public function markRead(Request $request, string $notificationId): RedirectResponse
    {

        $user = $request->user();
        $notification = $user->notifications()->whereKey($notificationId)->firstOrFail();
        $notification->markAsRead();

        $actionUrl = $notification->data['action_url'] ?? null;

        if (is_string($actionUrl) && $actionUrl !== '') {
            return redirect()->to($actionUrl);
        }

        return back()->with('status', __('Notification marked as read.'));
    }

    public function markAllRead(Request $request): RedirectResponse
    {

        $user = $request->user();
        $user->unreadNotifications->markAsRead();

        return back()->with('status', __('All notifications marked as read.'));
    }
}
