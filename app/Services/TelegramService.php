<?php 

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramService {
    protected $botToken;
    protected $chatId;

    public function __construct()
    {
        $this->botToken = env('BOT_TOKEN');
        $this->chatId = env('CHAT_ID');
    }

    public function sendMessage(string $message)
    {
        try {
            $url = "https://api.telegram.org/bot{$this->botToken}/sendMessage";
            $postFields = [
                'chat_id' => $this->chatId,
                'text' => $message,
                'parse_mode' => 'HTML',
            ];
    
            $response = Http::post($url, $postFields);
    
            if ($response->failed()) {
                throw new \Exception("Telegram sendMessage failed: " . $response->body());
            }
    
            return $response->successful();
        } catch (\Exception $e) {
            Log::error("Telegram sendMessage error: " . $e->getMessage());
            return false;
        }
    }
    
    

    public function sendPhoto(string $photoUrl, string $caption)
    {
        try {
            $url = "https://api.telegram.org/bot{$this->botToken}/sendPhoto";
            $postFields = [
                'chat_id' => $this->chatId,
                'photo' => $photoUrl,
                'caption' => $caption,
                'parse_mode' => 'HTML',
            ];

            Log::info("Sending photo to Telegram: " . json_encode($postFields));
    
            $response = Http::asForm($url, $postFields);

            Log::info("Telegram API Response: " . $response->body());
    
            if ($response->failed()) {
                throw new \Exception("Telegram sendPhoto failed: " . $response->body());
            }
    
            return $response->successful();
        } catch (\Exception $e) {
            Log::error("Telegram sendPhoto error: " . $e->getMessage());
            return false;
        }
    }
    

    public function sendMediaGroup(array $media)
{
    try {
        $url = "https://api.telegram.org/bot{$this->botToken}/sendMediaGroup";
        $postFields = [
            'chat_id' => $this->chatId,
            'media' => json_encode($media),
        ];

        Log::info("Telegram API Payload: " . json_encode($postFields));

        $response = Http::post($url, $postFields);

        if ($response->failed()) {
            throw new \Exception("Telegram sendMediaGroup failed: " . $response->body());
        }

        return $response->successful();
    } catch (\Exception $e) {
        Log::error("Telegram sendMediaGroup error: " . $e->getMessage());
        return false;
    }
}


// public function sendLocation(float $latitude, float $longitude)
// {
//     try {
//         $url = "https://api.telegram.org/bot{$this->botToken}/sendLocation";
//         $postFields = [
//             'chat_id' => $this->chatId,
//             'latitude' => $latitude,
//             'longitude' => $longitude,
//         ];

//         $response = Http::post($url, $postFields);

//         if ($response->failed()) {
//             throw new \Exception("Telegram sendLocation failed: " . $response->body());
//         }

//         return $response->successful();
//     } catch (\Exception $e) {
//         Log::error("Telegram sendLocation error: " . $e->getMessage());
//         return false;
//     }
// }
}