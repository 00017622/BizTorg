<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class InstagramService {
    protected $accessToken;
    protected $instagramAccountId;
    protected $defaultImage;

    public function __construct()
    {
        $this->accessToken = 'EAANaazjLaZCkBO39FOoGGBPjlzZAL1oU7HxlaW4bKyKOmsRqzooToRfNIYL9DD3FLMWtSkdZB0RcLaeqVvZA1AFeSjUfPwrbjSsynq4LpgeeTlZBpUkLQWh09xqZCQfPnRbornNy89WenPKbL6mMD2bwHsi6hmuQeqQA41UX5yFxYkYGBkfVZAdnBDMrUdo';
        $this->instagramAccountId = '17841468384967861';
        $this->defaultImage = 'https://coffective.com/wp-content/uploads/2018/06/default-featured-image.png.jpg';
    }

    public function createCarouselPost(string $caption, array $images): array
    {
        try {
            // Ensure there is at least the default image
            if (empty($images)) {
                Log::info("No images provided for Instagram post, using default image.");
                $images = [$this->defaultImage];
            }

            if (count($images) === 1) {
                return $this->publishSingleImage($images[0], $caption);
            }

            $creationIds = [];
            foreach ($images as $image) {
                try {
                    $creationIds[] = $this->uploadImage($image);
                } catch (Exception $e) {
                    Log::error("Failed to upload image: {$image}, Error: " . $e->getMessage());
                }
            }

            if (empty($creationIds)) {
                throw new Exception("Failed to upload any images for Instagram carousel post.");
            }

            return $this->publishCarousel($creationIds, $caption);
        } catch (\Exception $e) {
            Log::error("Instagram createCarouselPost error: " . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }

    protected function publishSingleImage(string $imageUrl, string $caption): array
    {
        try {
            $url = "https://graph.facebook.com/v17.0/{$this->instagramAccountId}/media";

            $response = Http::post($url, [
                'image_url' => $imageUrl,
                'caption' => $caption,
                'access_token' => $this->accessToken,
            ]);

            if ($response->failed()) {
                Log::error("Failed to publish single image: " . $response->body());
                throw new Exception("Single image post creation failed.");
            }

            $creationId = $response->json()['id'];
            return $this->publishMedia($creationId);
        } catch (\Exception $e) {
            Log::error("Instagram publishSingleImage error: " . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }

    protected function publishMedia(string $creationId): array
    {
        try {
            $url = "https://graph.facebook.com/v17.0/{$this->instagramAccountId}/media_publish";

            $response = Http::post($url, [
                'creation_id' => $creationId,
                'access_token' => $this->accessToken,
            ]);

            if ($response->failed()) {
                Log::error("Failed to publish media: " . $response->body());
                throw new Exception("Media publication failed.");
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error("Instagram publishMedia error: " . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }

    protected function uploadImage(string $imageUrl): string
    {
        try {
            $url = "https://graph.facebook.com/v17.0/{$this->instagramAccountId}/media";

            $response = Http::post($url, [
                'image_url' => $imageUrl,
                'is_carousel_item' => true,
                'access_token' => $this->accessToken,
            ]);

            if ($response->failed()) {
                Log::error("Failed to upload image to Instagram: " . $response->body());
                throw new Exception("Image upload failed.");
            }

            return $response->json()['id'];
        } catch (\Exception $e) {
            Log::error("Instagram uploadImage error: " . $e->getMessage());
            throw new Exception("Image upload failed: " . $e->getMessage());
        }
    }

    protected function publishCarousel(array $creationIds, string $caption): array
    {
        try {
            $url = "https://graph.facebook.com/v17.0/{$this->instagramAccountId}/media_publish";

            $response = Http::post($url, [
                'caption' => $caption,
                'creation_id' => $this->createCarouselContainer($creationIds, $caption),
                'access_token' => $this->accessToken,
            ]);

            if ($response->failed()) {
                Log::error("Failed to publish carousel post: " . $response->body());
                throw new Exception("Carousel post creation failed.");
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error("Instagram publishCarousel error: " . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }

    protected function createCarouselContainer(array $creationIds, string $caption): string
    {
        try {
            $url = "https://graph.facebook.com/v17.0/{$this->instagramAccountId}/media";

            $response = Http::post($url, [
                'media_type' => 'CAROUSEL',
                'caption' => $caption,
                'children' => $creationIds,
                'access_token' => $this->accessToken,
            ]);

            if ($response->failed()) {
                Log::error("Failed to create carousel container: " . $response->body());
                throw new Exception("Carousel container creation failed.");
            }

            return $response->json()['id'];
        } catch (\Exception $e) {
            Log::error("Instagram createCarouselContainer error: " . $e->getMessage());
            throw new Exception("Carousel container creation failed: " . $e->getMessage());
        }
    }

 public function deletePost(string $postId): array
    {
        try {
            Log::info("Attempting to update Instagram post with ID: {$postId}");

            $url = "https://graph.facebook.com/{$postId}";
            Log::debug("POST request URL: {$url}");

            $newCaption = "Обьявление было удалено и неактивно";
            $response = Http::timeout(30)->post($url, [
                'caption' => $newCaption,
                'access_token' => $this->accessToken,
                'comment_enabled' => false, 
            ]);

            if ($response->failed()) {
                Log::error("Failed to update Instagram post {$postId}: " . $response->body(), [
                    'status' => $response->status(),
                    'response' => $response->json(),
                ]);
                throw new Exception("Instagram post update failed: " . $response->body());
            }

            Log::info("Successfully updated Instagram post {$postId} with new caption", [
                'response' => $response->json(),
            ]);

            return $response->json();
        } catch (\Exception $e) {
            Log::error("Instagram updatePost error for post {$postId}: " . $e->getMessage(), [
                'exception' => $e->getTraceAsString(),
            ]);
            return ['error' => $e->getMessage()];
        }
    }
}