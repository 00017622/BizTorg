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
        $this->accessToken = 'EAANaazjLaZCkBOZBR3M4OwJU8sOoHiOqNZBJAwTvW8ZCk20fUEF4IGAINjBuuGY6t2MzbG5Vx086mZCCBBXoNT9u3wZCgAb7yIT4UOVfn3b5IxZCd2fl9qIpjlPBpYc7xPWc9iqZC6ZC9CaNy3SWbXZBmXmKvQpRGakNqK8UQlX2KHGGz3xxelveZBaCAFz';
        $this->instagramAccountId = '17841468384967861';
        $this->defaultImage = 'https://brilliant24.ru/files/cat/template_01.png';
    }
    public function createCarouselPost(string $caption, array $images): array
{
    if (empty($images)) {
        $images = [$this->defaultImage];
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

    // Publish the carousel post
    return $this->publishCarousel($creationIds, $caption);
}


    protected function uploadImage(string $imageUrl): string
{
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
}


protected function publishCarousel(array $creationIds, string $caption): array
{
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
}

protected function createCarouselContainer(array $creationIds, string $caption): string
{
    $url = "https://graph.facebook.com/v17.0/{$this->instagramAccountId}/media";

    $response = Http::post($url, [
        'media_type' => 'CAROUSEL',
        'caption' => $caption,
        'children' => $creationIds, // Array of media creation IDs
        'access_token' => $this->accessToken,
    ]);

    if ($response->failed()) {
        Log::error("Failed to create carousel container: " . $response->body());
        throw new Exception("Carousel container creation failed.");
    }

    return $response->json()['id']; // Return the container creation_id
}


}