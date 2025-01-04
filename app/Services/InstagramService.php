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
        $this->accessToken = 'EAANaazjLaZCkBO8V5eycUdZB74fD8F0tREljpqlhPQZBF7zA7EpjUsoB8WfSEJDfgXHfWt7DiSpynNAYsZB6xouFl3yz4CNmIANXgltcO75aJOm4rsjSJ8nVrDwZBg2EnEvtAXoPDHaNyGy4D1k5wPnD6yw43FTZA0EJPclllzLL53dugOKlDQrZA2U';
        $this->instagramAccountId = '17841468384967861';
        $this->defaultImage = 'https://brilliant24.ru/files/cat/template_01.png';
    }
    public function createCarouselPost(string $caption, array $images): array
{
    if (empty($images)) {
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
}

    protected function publishSingleImage(string $imageUrl, string $caption) {
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
    }

    protected function publishMedia(string $creationId): array
{
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
        'children' => $creationIds,
        'access_token' => $this->accessToken,
    ]);

    if ($response->failed()) {
        Log::error("Failed to create carousel container: " . $response->body());
        throw new Exception("Carousel container creation failed.");
    }

    return $response->json()['id'];
}


}