<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FacebookService {

    protected $pageAccessToken;
    protected $pageId;

    public function __construct()
    {
        $this->pageAccessToken = 'EAANaazjLaZCkBO39FOoGGBPjlzZAL1oU7HxlaW4bKyKOmsRqzooToRfNIYL9DD3FLMWtSkdZB0RcLaeqVvZA1AFeSjUfPwrbjSsynq4LpgeeTlZBpUkLQWh09xqZCQfPnRbornNy89WenPKbL6mMD2bwHsi6hmuQeqQA41UX5yFxYkYGBkfVZAdnBDMrUdo';
        $this->pageId = '511524108707522';
    
        if (empty($this->pageAccessToken) || empty($this->pageId)) {
            throw new Exception("Facebook configuration is missing: Page Access Token or Page ID.");
        }
    }

    public function createPost(string $message, array $photos = null) {
        try {
            $url = "https://graph.facebook.com/{$this->pageId}/feed";

            if (empty($photos)) {
                $defaultImageUrl = 'https://coffective.com/wp-content/uploads/2018/06/default-featured-image.png.jpg';
                $defaultImageSent = $this->uploadImages($defaultImageUrl);

                if (!$defaultImageSent || isset($defaultImageSent['error'])) {
                    throw new Exception("Failed to upload the default image.");
                }

                $response = Http::post($url, [
                    'message' => $message,
                    'access_token' => $this->pageAccessToken,
                    'attached_media' => json_encode([['media_fbid' => $defaultImageSent['id']]])
                ]);

                return $response->successful() ? $response->json() : ['error' => 'Failed to create post: ' . $response->body()];
            }

            $photoIds = [];

            foreach ($photos as $photo) {
                $imageSent = $this->uploadImages($photo['image_url']);
                if (!$imageSent || isset($imageSent['error'])) {
                    Log::error("Failed to upload image: {$photo['image_url']}");
                    throw new Exception("Image upload failed for URL: {$photo['image_url']}");
                }
                $photoIds[] = $imageSent['id'];
            }

            $response = Http::post($url, [
                'message' => $message,
                'attached_media' => json_encode(
                    array_map(fn ($id) => ['media_fbid' => $id], $photoIds)
                ),
                'access_token' => $this->pageAccessToken,
            ]);

            return $response->successful() ? $response->json() : ['error' => 'Failed to create post: ' . $response->body()];
        } catch (\Exception $e) {
            Log::error("Facebook createPost error: " . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }

    protected function uploadImages(string $photoUrl) {
        try {
            $url = "https://graph.facebook.com/{$this->pageId}/photos";
            $response = Http::post($url, [
                'url' => $photoUrl,
                'access_token' => $this->pageAccessToken,
                'published' => false,
            ]);

            Log::info("Facebook upload response: " . $response->body());

            return $response->successful() ? $response->json() : ['error' => 'Failed to upload image: ' . $response->body()];
        } catch (\Exception $e) {
            Log::error("Facebook uploadImages error: " . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }

    public function deletePost(string $postId): array
    {
        try {
            Log::info("Attempting to delete Facebook post with ID: {$postId}");

            $url = "https://graph.facebook.com/{$this->pageId}_{$postId}";
            Log::debug("DELETE request URL: {$url}");

            $response = Http::timeout(30)->delete($url, [
                'access_token' => $this->pageAccessToken,
            ]);

            if ($response->failed()) {
                Log::error("Failed to delete Facebook post {$postId}: " . $response->body(), [
                    'status' => $response->status(),
                    'response' => $response->json(),
                ]);
                throw new Exception("Facebook post deletion failed: " . $response->body());
            }

            Log::info("Successfully deleted Facebook post {$postId}", [
                'response' => $response->json(),
            ]);

            return $response->json();
        } catch (\Exception $e) {
            Log::error("Facebook deletePost error for post {$postId}: " . $e->getMessage(), [
                'exception' => $e->getTraceAsString(),
            ]);
            return ['error' => $e->getMessage()];
        }
    }

    public function updatePost(string $postId, string $message): array
    {
        try {
            Log::info("Attempting to update Facebook post with ID: {$postId}");

            $url = "https://graph.facebook.com/{$this->pageId}_{$postId}";
            Log::debug("POST request URL: {$url}");

            $response = Http::timeout(30)->post($url, [
                'message' => $message,
                'access_token' => $this->pageAccessToken,
            ]);

            if ($response->failed()) {
                Log::error("Failed to update Facebook post {$postId}: " . $response->body(), [
                    'status' => $response->status(),
                    'response' => $response->json(),
                ]);
                throw new Exception("Facebook post update failed: " . $response->body());
            }

            Log::info("Successfully updated Facebook post {$postId} with new message", [
                'response' => $response->json(),
            ]);

            return $response->json();
        } catch (\Exception $e) {
            Log::error("Facebook updatePost error for post {$postId}: " . $e->getMessage(), [
                'exception' => $e->getTraceAsString(),
            ]);
            return ['error' => $e->getMessage()];
        }
    }
}