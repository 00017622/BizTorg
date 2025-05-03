<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;


class NotificationsController extends Controller
{
    public function index(Request $request)
    {
        try {
            $userId = $request->user()->id;

            $notifications = Notification::where('receiver_id', $userId)
                ->orWhere('is_global', true)
                ->where('hasBeenSeen', false)
                ->orderBy('date', 'desc')
                ->get();

            return response()->json([
                'status' => 'success',
                'notifications' => $notifications,
            ], 200);
        } catch (\Exception $e) {
            Log::error("❌ Error fetching notifications: {$e->getMessage()}", [
                'userId' => $request->user()->id,
                'exception' => $e,
            ]);
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch notifications: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function markAsSeen(Request $request) {
        try {

            $userId = $request->user()->id;

            Notification::where('receiver_id', $userId)->update(['hasBeenSeen' => true]);

            return response()->json([
                'status' => 'success',
                'message' => 'All notifications marked as seen',
            ], 200);

        } catch(\Exception $e) {
            Log::error("❌ Error marking notifications as seen: {$e->getMessage()}", [
                'userId' => $request->user()->id,
                'exception' => $e,
            ]);
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to mark notifications as seen: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function markSeenForChat(Request $request) {

        try {


            $userId = $request->user()->id;
            $otherUserId = $request->input('other_user_id');

            if (!$otherUserId) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Other user ID is required',
                ], 400);
            }

            Notification::where('receiver_id', $userId)
                ->where('sender_id', $otherUserId)
                ->where('type', 'message')
                ->where('hasBeenSeen', false)
                ->update(['hasBeenSeen' => true]);

            return response()->json([
                'status' => 'success',
                'message' => 'Notifications marked as seen for this chat',
            ], 200);

        } catch(\Exception $e) {
Log::error("❌ Error marking chat notifications as seen: {$e->getMessage()}", [
                'userId' => $request->user()->id,
                'otherUserId' => $request->input('other_user_id'),
                'exception' => $e,
            ]);
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to mark chat notifications as seen: ' . $e->getMessage(),
            ], 500);
        }
    }
}