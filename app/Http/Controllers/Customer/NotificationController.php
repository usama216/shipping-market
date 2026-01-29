<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Handles notification management for customers.
 */
class NotificationController extends Controller
{
    /**
     * Mark a specific notification as read.
     */
    public function markAsRead(Request $request, string $id)
    {
        $customer = Auth::guard('customer')->user();

        $notification = $customer->notifications()->find($id);

        if ($notification) {
            $notification->markAsRead();
        }

        return back();
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead(Request $request)
    {
        $customer = Auth::guard('customer')->user();
        $customer->unreadNotifications->markAsRead();

        return back();
    }
}
