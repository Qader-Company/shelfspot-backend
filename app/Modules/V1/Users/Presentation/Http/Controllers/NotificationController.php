<?php

namespace App\Modules\V1\Users\Presentation\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\V1\Users\Presentation\Http\Resources\NotificationResource;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $perPage = min(max((int) $request->integer('per_page', 20), 1), 100);

        $notifications = $request->user()
            ->notifications()
            ->when($request->boolean('unread_only'), fn ($query) => $query->whereNull('read_at'))
            ->latest()
            ->paginate($perPage);

        return ApiResponse::success(
            NotificationResource::collection($notifications)->response()->getData(true),
        );
    }

    public function unreadCount(Request $request)
    {
        return ApiResponse::success([
            'unread_count' => $request->user()->unreadNotifications()->count(),
        ]);
    }

    public function markAsRead(string $id, Request $request)
    {
        $notification = $this->notificationForUser($request, $id);
        $notification->markAsRead();

        return ApiResponse::updated(new NotificationResource($notification->refresh()));
    }

    public function markAllAsRead(Request $request)
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return ApiResponse::updated([
            'unread_count' => 0,
        ]);
    }

    private function notificationForUser(Request $request, string $id): DatabaseNotification
    {
        return $request->user()
            ->notifications()
            ->whereKey($id)
            ->firstOrFail();
    }
}
