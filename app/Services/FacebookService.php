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
        $this->pageAccessToken = 'EAANaazjLaZCkBO5f06DTwJGFJzZCcPgaQKq1ZCIKfQbZBGjejQgYCk77C0npq3rWdm46g8xjax1VkKzdHp2QYaAFntt6pro65z5V024HSTQfCoeQu5VvPYpjXmzaM9FUQpehIYB2gMunZCWMLRoRCe1W29ZB5YLYx5A1OJhEb5tDHgJ4pcFnmeZA0L5ivb0u1rs';
        $this->pageId = '511524108707522';
    
        if (empty($this->pageAccessToken) || empty($this->pageId)) {
            throw new Exception("Facebook configuration is missing: Page Access Token or Page ID.");
        }
    }

    public function createPost(string $message, array $photos=null) {
        $url = "https://graph.facebook.com/{$this->pageId}/feed";

        if (empty($photos)) {
           $defaultImageUrl = 'https://brilliant24.ru/files/cat/template_01.png';
            $defaultImageSent = $this->uploadImages($defaultImageUrl);

            if (!$defaultImageSent) {
                throw new Exception("Failed to upload the default image.");
            }

           $response = Http::post($url, [
                'message' => $message,
                'access_token' => $this->pageAccessToken,
                'attached_media' => json_encode([['media_fbid' => $defaultImageSent['id']]])
            ]);

            return $response->successful() ? $response->json() : false;
        }

        $photoIds = [];

        foreach($photos as $photo) {
            $imageSent = $this->uploadImages($photo['image_url']);
            if ($imageSent) {
                $photoIds[] = $imageSent['id'];
            } else {
                Log::error("Failed to upload image: {$photo['image_url']}");
                throw new Exception("Image upload failed for URL: {$photo['image_url']}");
            }
        }

        $response = Http::post($url, [
            'message' => $message,
            'attached_media' => json_encode(
                array_map(fn ($id) => ['media_fbid' => $id], $photoIds)
            ),
            'access_token' => $this->pageAccessToken,
        ]);

        return $response->successful() ? $response->json() : false;
    }

    protected function uploadImages(string $photoUrl) {
        $url = "https://graph.facebook.com/{$this->pageId}/photos";
        $response = Http::post($url, [
            'url' => $photoUrl ,
            'access_token' => $this->pageAccessToken,
            'published' => false,
        ]);

        Log::info("Facebook upload response: " . $response->body());

        return $response->successful() ? $response->json() : false;
    }
}