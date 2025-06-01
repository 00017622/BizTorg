<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;


class NotificationsController extends Controller
{

    public function index(Request $request)
{
    try {
        $userId = $request->user()->id;

        $notifications = Notification::where(function ($query) use ($userId) {
                $query->where('receiver_id', $userId)
                      ->orWhere('is_global', true);
            })
            ->where('hasBeenSeen', false)
            ->orderBy('date', 'desc')
            ->with(['sender' => function ($query) {
                $query->select('id', 'isShop', 'name')
                      ->with(['profile', 'shopProfile']); // Eager-load profile and shopProfile
            }])
            ->get()
            ->map(function ($notification) use ($userId) {
                // Decode existing metadata
                $metadata = json_decode($notification->metadata, true) ?? [];

                // Initialize custom fields
                $isShop = false;
                $senderName = $metadata['sender_name'] ?? 'Unknown';
                $shopProfile = null;
                $userProfile = null;
                $isAlreadySubscriber = false;
                $hasAlreadyRated = false;

                if ($notification->sender) {
                    $isShop = $notification->sender->isShop;
                    $senderName = $isShop
                        ? ($notification->sender->shopProfile->shop_name ?? $senderName)
                        : ($notification->sender->name ?? $senderName);
                    $shopProfile = $notification->sender->shopProfile;
                    $userProfile = $notification->sender->profile;
                    if ($isShop && $shopProfile) {
                        $isAlreadySubscriber = $shopProfile->subscribers()
                            ->where('user_id', $userId)
                            ->exists();
                        $hasAlreadyRated = $shopProfile->raters()
                            ->where('user_id', $userId)
                            ->exists();
                    }
                }

                // Update metadata with only necessary fields
                $metadata['sender_name'] = $senderName;

                // Convert notification to array and merge with custom fields
                return array_merge($notification->toArray(), [
                    'metadata' => json_encode($metadata),
                    'isShop' => $isShop,
                    'sender_name' => $senderName,
                    'shopProfile' => $shopProfile,
                    'userProfile' => $userProfile,
                    'isAlreadySubscriber' => $isAlreadySubscriber,
                    'hasAlreadyRated' => $hasAlreadyRated,
                    // Add more custom fields here if needed
                ]);
            });

        Log::info("Fetched notifications for user $userId", [
            'count' => $notifications->count(),
            'notification_ids' => $notifications->pluck('id')->toArray(),
        ]);

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