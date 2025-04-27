<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;
    public $sender_id;
    public $receiver_id;
    public $conversation_id;

    public function __construct($message, $sender_id, $receiver_id, $conversation_id)
    {
        \Log::info("MessageSent event fired: Message ID {$message->id}, Sender: $sender_id, Receiver: $receiver_id");
        $this->message = $message;
        $this->sender_id = $sender_id;
        $this->receiver_id = $receiver_id;
        $this->conversation_id = $conversation_id;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn()
    {

     

        $ids = [$this->sender_id, $this->receiver_id];

        sort($ids);

        return [
            new Channel('conversation.' . $ids[0] . '.' . $ids[1]),
        ];
    }

    public function broadcastAs()
{
    return 'MessageSent';
}

    public function broadcastWith() {
        \Log::info("📡 Broadcasting Message: " . json_encode([
            'id'         => $this->message->id,
            'sender_id'  => $this->sender_id,
            'message'    => $this->message->message,
            'created_at' => $this->message->created_at->toDateTimeString(),
        ]));
        return [
            'id'             => $this->message->id,
            'conversationId' => $this->conversation_id,
            'sender_id'      => $this->sender_id,
            'message'        => $this->message->message,
            'created_at'     => $this->message->created_at->toDateTimeString(),
        ];
    }
}
