<?php

namespace App\Http\Controllers\Api;


use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Notification;
use Illuminate\Http\Request;
use App\Models\Message;
use App\Models\User;
use Auth;
use Exception;
use Log;


class MessagesController extends Controller {
    public function sendMessage(Request $request) {

        try {
            $validatedData = $request->validate([
                'receiver_id' => 'required|exists:users,id',
                'message' => 'required|string',
            ]);
        
            $sender_id = $request->user()->id;
            $receiver_id = $validatedData['receiver_id'];

            $receiver = User::find($receiver_id);
    
            \Log::info("🔍 Checking conversation between: Sender: $sender_id, Receiver: $receiver_id");
    
        
            $conversation = Conversation::where(function ($query) use ($sender_id, $receiver_id) {
                $query->where('user_one_id', $sender_id)->where('user_two_id', $receiver_id);
            })->orWhere(function ($query) use ($sender_id, $receiver_id) {
                $query->where('user_one_id', $receiver_id)->where('user_two_id', $sender_id);
            })->first();
        
            if (!$conversation) {
                $conversation = Conversation::create([
                    'user_one_id' => $sender_id,
                    'user_two_id' => $receiver_id,
                ]);
            }
        
            $message = Message::create([
                'conversation_id' => $conversation->id,
                'sender_id' => $sender_id,
                'message' => $validatedData['message'],
            ]);
    
            $sender = User::find(Auth::id());
            Notification::create([
                'receiver_id' => $validatedData['receiver_id'],
                'sender_id' => Auth::id(),
                'type' => 'message',
                'content' => "У вас новое сообщение от: {$sender->name}",
                'hasBeenSeen' => false, // Receiver hasn't seen the message yet
                'is_global' => false,
                'reference_id' => $message->id, // Link to the message
                'priority' => 'medium',
                'metadata' => json_encode([
                    'conversation_id' => $conversation->id,
                    'message_preview' => $validatedData['message'],
                    'sender_name' => $sender->name,
                ]),
            ]);
        
            \Log::info("🚀 Sending event MessageSent for Message ID: {$message->id}");
        
            // event(new MessageSent($message, $sender_id, $receiver_id, $conversation->id));
        
            return response()->json([
                'status' => 'success',
                'message' => $message
            ]);
        } catch (Exception $e) {
            Log::error("❌ Error sending message: {$e->getMessage()}", [
                'request' => $request->all(),
                'exception' => $e,
            ]);
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to send message: ' . $e->getMessage(),
            ], 422);
        }
       
    }
    

    public function getMessages(Request $request, $receiver_id) {
        $sender_id = $request->user()->id;

        Log::info("Fetching messages for sender: {$request->user()->id}, receiver: $receiver_id");

        $conversation = Conversation::where(function ($query) use ($sender_id, $receiver_id) {
            $query->where('user_one_id', $sender_id)->where('user_two_id', $receiver_id);
        })->orWhere(function ($query) use ($sender_id, $receiver_id) {
            $query->where('user_one_id', $receiver_id)->where('user_two_id', $sender_id);
        })->first();

        if (!$conversation) {
            return response()->json([
                'success' => false,
                'message'  => 'No conversation found.',
                'messages' => [],
            ]);
        }

        $messages = Message::where('conversation_id', $conversation->id)->orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'message'  => 'Messages fetched successfully.',
            'messages' => $messages,
        ]);
    }
}