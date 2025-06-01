<?php

namespace App\Jobs;

use App\Services\TelegramService;
use App\Services\FacebookService;
use App\Services\InstagramService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RemoveFromSocialMediaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $telegramPostId;
    protected $facebookPostId;
    protected $instaPostId;

    public function __construct(?string $telegramPostId, ?string $facebookPostId, ?string $instaPostId)
    {
        $this->telegramPostId = $telegramPostId;
        $this->facebookPostId = $facebookPostId;
        $this->instaPostId = $instaPostId;
    }

    public function handle(
        TelegramService $telegramService,
        FacebookService $facebookService,
        InstagramService $instagramService
    ) {
        // Telegram Deletion
        if ($this->telegramPostId) {
            try {
                $telegramResponse = $telegramService->deleteMessage($this->telegramPostId);
                Log::debug('Telegram delete response: ', $telegramResponse);
                if (isset($telegramResponse['error'])) {
                    Log::warning('Telegram delete failed for message ' . $this->telegramPostId . ': ', $telegramResponse);
                } else {
                    Log::info("Successfully deleted Telegram message {$this->telegramPostId}");
                }
            } catch (\Exception $e) {
                Log::error("Failed to delete Telegram message {$this->telegramPostId}: " . $e->getMessage());
            }
        }

        // Facebook Deletion
        if ($this->facebookPostId) {
            try {
                $facebookResponse = $facebookService->deletePost($this->facebookPostId);
                Log::debug('Facebook delete response: ', $facebookResponse);
                if (isset($facebookResponse['error'])) {
                    Log::warning('Facebook delete failed for post ' . $this->facebookPostId . ': ', $facebookResponse);
                } else {
                    Log::info("Successfully deleted Facebook post {$this->facebookPostId}");
                }
            } catch (\Exception $e) {
                Log::error("Failed to delete Facebook post {$this->facebookPostId}: " . $e->getMessage());
            }
        }

        // Instagram Deletion
        if ($this->instaPostId) {
            try {
                $instagramResponse = $instagramService->deletePost($this->instaPostId);
                Log::debug('Instagram delete response: ', $instagramResponse);
                if (isset($instagramResponse['status']) && $instagramResponse['status'] === 'already_deleted') {
                    Log::info("Instagram post {$this->instaPostId} already deleted or inaccessible.");
                } elseif (isset($instagramResponse['error'])) {
                    Log::warning('Instagram delete failed for post ' . $this->instaPostId . ': ', $instagramResponse);
                } else {
                    Log::info("Successfully deleted Instagram post {$this->instaPostId}");
                }
            } catch (\Exception $e) {
                Log::error("Failed to delete Instagram post {$this->instaPostId}: " . $e->getMessage());
            }
        }

        // Note: The controller should clear the social media IDs (telegram_post_id, facebook_post_id, insta_post_id)
        // from the Product model after this job completes successfully.
    }
}