<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramService {
    protected $botToken;
    protected $chatId;

    public function __construct()
    {
        $this->botToken = config('services.telegram.bot_token');
        $this->chatId = config('services.telegram.chat_id');
    }

    public function sendMessage(string $message, string $buttonText = '🔗 Подробнее', string $productUrl = '')
    {
        try {
            $url = "https://api.telegram.org/bot{$this->botToken}/sendMessage";
            $postFields = [
                'chat_id' => $this->chatId,
                'text' => $message,
                'parse_mode' => 'HTML',
                'reply_markup' => $productUrl ? json_encode([
                    'inline_keyboard' => [
                        [
                            [
                                'text' => $buttonText,
                                'url' => $productUrl,
                            ],
                        ],
                    ],
                ]) : null,
            ];

            $response = Http::post($url, $postFields);

            if ($response->failed()) {
                throw new \Exception("Telegram sendMessage failed: " . $response->body());
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error("Telegram sendMessage error: " . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }
    
    public function sendPhoto(string $photoUrl, string $caption, string $buttonText = '🔗 Подробнее', string $productUrl = '')
    {
        try {
            $url = "https://api.telegram.org/bot{$this->botToken}/sendPhoto";
            $postFields = [
                'chat_id' => $this->chatId,
                'photo' => $photoUrl,
                'caption' => $caption,
                'parse_mode' => 'HTML',
                'reply_markup' => $productUrl ? json_encode([
                    'inline_keyboard' => [
                        [
                            [
                                'text' => $buttonText,
                                'url' => $productUrl,
                            ],
                        ],
                    ],
                ]) : null,
            ];

            Log::info("Sending photo to Telegram: " . json_encode($postFields));

            $response = Http::post($url, $postFields);

            Log::info("Telegram API Response: " . $response->body());

            if ($response->failed()) {
                throw new \Exception("Telegram sendPhoto failed: " . $response->body());
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error("Telegram sendPhoto error: " . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }
    
    public function sendMediaGroup(array $media, string $buttonText = '🔗 Подробнее', string $productUrl = '')
    {
        try {
            $url = "https://api.telegram.org/bot{$this->botToken}/sendMediaGroup";
            $postFields = [
                'chat_id' => $this->chatId,
                'media' => json_encode($media),
            ];

            $response = Http::post($url, $postFields);

            if ($response->failed()) {
                throw new \Exception("Telegram sendMediaGroup failed: " . $response->body());
            }

            // Send a follow-up message with the button
            if ($productUrl) {
                $this->sendMessage('', $buttonText, $productUrl);
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error("Telegram sendMediaGroup error: " . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }

    public function deleteMessage(string $messageId): array
    {
        try {
            Log::info("Attempting to delete Telegram message with ID: {$messageId}");

            $url = "https://api.telegram.org/bot{$this->botToken}/deleteMessage";
            $postFields = [
                'chat_id' => $this->chatId,
                'message_id' => $messageId,
            ];

            Log::debug("POST request URL: {$url}", [
                'payload' => $postFields,
            ]);

            $response = Http::post($url, $postFields);

            if ($response->failed()) {
                Log::error("Failed to delete Telegram message {$messageId}: " . $response->body(), [
                    'status' => $response->status(),
                    'response' => $response->json(),
                ]);
                throw new \Exception("Telegram message deletion failed: " . $response->body());
            }

            Log::info("Successfully deleted Telegram message {$messageId}", [
                'response' => $response->json(),
            ]);

            return $response->json();
        } catch (\Exception $e) {
            Log::error("Telegram deleteMessage error for message {$messageId}: " . $e->getMessage(), [
                'exception' => $e->getTraceAsString(),
            ]);
            return ['error' => $e->getMessage()];
        }
    }

     public function updateMessage(
    string $messageId,
    string $text,
    string $buttonText = '🔗 Подробнее',
    string $productUrl = '',
): array {
    try {
        Log::info("Attempting to update Telegram message with ID: {$messageId} with text: {$text}");

        // Always include the inline keyboard
        $replyMarkup = [
            'inline_keyboard' => [
                [
                    ['text' => $buttonText, 'url' => $productUrl],
                ],
            ],
        ];

        // First, update the message caption (for messages with photos) or text (for text-only messages)
        $url = "https://api.telegram.org/bot{$this->botToken}/editMessageCaption";
        $params = [
            'chat_id' => $this->chatId,
            'message_id' => $messageId,
            'caption' => $text,
            'parse_mode' => 'HTML',
            'reply_markup' => json_encode($replyMarkup),
        ];

        $response = Http::timeout(30)->post($url, $params);

        // If editMessageCaption fails (e.g., the message is text-only), try editMessageText
        if ($response->failed()) {
            Log::info("editMessageCaption failed, trying editMessageText for message {$messageId}");
            $url = "https://api.telegram.org/bot{$this->botToken}/editMessageText";
            $params = [
                'chat_id' => $this->chatId,
                'message_id' => $messageId,
                'text' => $text,
                'parse_mode' => 'HTML',
                'reply_markup' => json_encode($replyMarkup),
            ];

            $response = Http::timeout(30)->post($url, $params);

            if ($response->failed()) {
                Log::error("Failed to update Telegram message {$messageId}: " . $response->body());
                throw new \Exception("Telegram message update failed: " . $response->body());
            }
        }

        Log::info("Successfully updated Telegram message {$messageId}");
        return $response->json();
    } catch (\Exception $e) {
        Log::error("Telegram updateMessage error for message {$messageId}: " . $e->getMessage(), [
            'exception' => $e->getTraceAsString(),
        ]);
        return ['error' => $e->getMessage()];
    }
}
}