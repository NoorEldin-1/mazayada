<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\Api\V1\NotificationResource;
use App\Models\UserNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Notifications
 *
 * The citizen's in-app notification inbox.
 */
class NotificationController extends ApiController
{
    /**
     * List notifications
     *
     * Paginated, newest first. The unread total is in meta.unread_count.
     */
    public function index(Request $request): JsonResponse
    {
        $notifications = $request->user()->userNotifications()
            ->latest('created_at')
            ->paginate(20);

        return $this->paginated($notifications, NotificationResource::class, null, [
            'unread_count' => $request->user()->unreadNotificationsCount(),
        ]);
    }

    /**
     * Unread count
     */
    public function unreadCount(Request $request): JsonResponse
    {
        return $this->ok(['unread_count' => $request->user()->unreadNotificationsCount()]);
    }

    /**
     * Mark one as read
     */
    public function markRead(Request $request, UserNotification $notification): JsonResponse
    {
        abort_unless($notification->user_id === $request->user()->id, 403);

        $notification->update(['is_read' => true]);

        return $this->ok(null, __('notifications.flash_marked_read'));
    }

    /**
     * Mark all as read
     */
    public function markAllRead(Request $request): JsonResponse
    {
        $request->user()->userNotifications()
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return $this->ok(null, __('notifications.flash_all_marked_read'));
    }
}
