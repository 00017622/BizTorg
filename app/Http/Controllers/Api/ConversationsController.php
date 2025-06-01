<?php

namespace App\Http\Controllers\Api;

use App\Events\MessageSent;
use App\Http\Controllers\Controller;
use App\Models\Conversation;
use Illuminate\Http\Request;
use App\Models\Message;
use App\Models\User;
use Carbon\Carbon;
use DateTime;
use Log;

class  ConversationsController extends Controller {
    public function getChats(Request $request)
{
    try {
        $token = $request->bearerToken();

        $authenticatedUser = $request->user();
       
        if (!$authenticatedUser || !($authenticatedUser instanceof \App\Models\User) || !$authenticatedUser->exists || $authenticatedUser->id <= 0) {
           
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthenticated',
                'data' => null,
            ], 401);
        }

        $user = User::findOrFail($authenticatedUser->id);

        $conversations = Conversation::where('user_one_id', $user->id)
            ->orWhere('user_two_id', $user->id)
            ->with([
                'userOne' => function ($query) {
                    $query->select('id', 'name', 'avatar');
                },
                'userOne.profile' => function ($query) {
                    $query->select('id', 'user_id', 'phone');
                },
                'userTwo' => function ($query) {
                    $query->select('id', 'name', 'avatar');
                },
                'userTwo.profile' => function ($query) {
                    $query->select('id', 'user_id', 'phone');
                },
                'messages' => function ($query) {
                    $query->latest()->limit(1);
                },
            ])
            ->get()
            ->map(function ($conversation) use ($user) {
                $otherUser = $conversation->user_one_id == $user->id ? $conversation->userTwo : $conversation->userOne;
                $phoneNumber = $otherUser->profile ? $otherUser->profile->phone : 'Не указан';
                $avatar = $otherUser->avatar ?? '';
                $lastMessage = $conversation->messages->first();
                $lastMessageContent = $lastMessage ? $lastMessage->message : 'Нет сообщений';
            
                $lastMessageDate = '';

                if ($lastMessage) {
                    $date = new DateTime($lastMessage->created_at);
                    $dayNameMap = [
                        'Mon' => 'Пн',
                        'Tue' => 'Вт',
                        'Wed' => 'Ср',
                        'Thu' => 'Чт',
                        'Fri' => 'Пт',
                        'Sat' => 'Сб',
                        'Sun' => 'Вс',
                    ];

                    $englishDayName = $date->format('D');
                    $russianDayName = $dayNameMap[$englishDayName] ?? $englishDayName;
                    $datePart = $date->format('d.m.y');
                    $lastMessageDate = "$russianDayName $datePart";
                }
 
                Log::info('Profile data for user', [
                    'user_id' => $otherUser->id,
                    'profile' => $otherUser->profile ? $otherUser->profile->toArray() : null,
                ]);

            

                $otherUserInfo = User::findOrFail($otherUser->id);

                $shopProfile = null;

                $isShop = $otherUserInfo->isShop;

                $userProfile = $otherUserInfo->profile;

                 $isAlreadySubscriber = false;

                 $hasAlreadyRated = false;



                if ($otherUserInfo->isShop) {
                    $shopProfile = $otherUserInfo->shopProfile;
                    $isAlreadySubscriber = $otherUserInfo->shopProfile->subscribers()->where('user_id', $user->id)->exists();
                    $hasAlreadyRated = $otherUserInfo->shopProfile->raters()->where('user_id', $user->id)->exists();
                }

                return [
                    'id' => $conversation->id,
                    'user_one_id' => $conversation->user_one_id,
                    'user_two_id' => $conversation->user_two_id,
                    'created_at' => $conversation->created_at,
                    'updated_at' => $conversation->updated_at,
                    'user_one' => $conversation->userOne,
                    'user_two' => $conversation->userTwo,
                    'phone_number' => $phoneNumber,
                    'last_message' => $lastMessageContent,
                    'last_message_date' => $lastMessageDate,
                    'avatar' => $avatar,
                    'isShop' => $isShop,
                    'shopProfile' => $shopProfile,
                    'userProfile' => $userProfile,
                    'isAlreadySubscriber' => $isAlreadySubscriber,
                    'hasAlreadyRated' => $hasAlreadyRated,
                ];
            });

        Log::info('SQL Queries executed in getChats', \DB::getQueryLog());
        \DB::disableQueryLog();

        Log::info('Fetched conversations in getChats', ['conversations' => $conversations->toArray()]);



        return response()->json([
            'status' => 'success',
            'message' => 'Conversations fetched successfully',
            'conversations' => $conversations,
        ], 200);

    } catch (\Exception $e) {
        Log::error('Failed to fetch user conversations for user ID ' . ($authenticatedUser->id ?? 'unknown') . ': ' . $e->getMessage(), [
            'exception' => $e,
            'stack_trace' => $e->getTraceAsString(),
        ]);

        return response()->json([
            'status' => 'error',
            'message' => 'Failed to fetch conversations',
            'data' => null,
        ], 500);
    }
}
}