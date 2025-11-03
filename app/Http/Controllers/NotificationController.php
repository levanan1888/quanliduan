<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class NotificationController extends Controller
{
    /**
     * Get all notifications for authenticated user
     *
     * @OA\Get(
     *     path="/api/notifications",
     *     tags={"Notifications"},
     *     summary="Get all notifications",
     *     security={{"sanctum": {}}},
     *     @OA\Response(response=200, description="List of notifications"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function index(Request $request)
    {
        $notifications = Notification::where('user_id', $request->user()->id)
            ->with(['relatedTask', 'relatedProject'])
            ->latest('created_at')
            ->paginate(20);

        return response()->json($notifications);
    }

    /**
     * Mark notification as read
     *
     * @OA\Patch(
     *     path="/api/notifications/{notification}/read",
     *     tags={"Notifications"},
     *     summary="Mark notification as read",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(name="notification", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Notification marked as read"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Permission denied")
     * )
     */
    public function markAsRead(Request $request, Notification $notification)
    {
        if ($notification->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'You can only mark your own notifications as read.',
            ], 403);
        }

        $notification->update(['is_read' => true]);

        return response()->json($notification);
    }

    /**
     * Mark all notifications as read
     *
     * @OA\Post(
     *     path="/api/notifications/mark-all-read",
     *     tags={"Notifications"},
     *     summary="Mark all notifications as read",
     *     security={{"sanctum": {}}},
     *     @OA\Response(response=200, description="All notifications marked as read"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function markAllAsRead(Request $request)
    {
        Notification::where('user_id', $request->user()->id)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json([
            'message' => 'All notifications marked as read.',
        ]);
    }

    /**
     * Get unread notification count
     *
     * @OA\Get(
     *     path="/api/notifications/unread-count",
     *     tags={"Notifications"},
     *     summary="Get unread notification count",
     *     security={{"sanctum": {}}},
     *     @OA\Response(response=200, description="Unread count", @OA\JsonContent(
     *         @OA\Property(property="unread_count", type="integer", example=5)
     *     )),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function unreadCount(Request $request)
    {
        $count = Notification::where('user_id', $request->user()->id)
            ->where('is_read', false)
            ->count();

        return response()->json([
            'unread_count' => $count,
        ]);
    }

    /**
     * Delete notification
     *
     * @OA\Delete(
     *     path="/api/notifications/{notification}",
     *     tags={"Notifications"},
     *     summary="Delete notification",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(name="notification", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Notification deleted successfully"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Permission denied")
     * )
     */
    public function destroy(Request $request, Notification $notification)
    {
        if ($notification->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'You can only delete your own notifications.',
            ], 403);
        }

        $notification->delete();

        return response()->json([
            'message' => 'Notification deleted successfully.',
        ]);
    }
}

