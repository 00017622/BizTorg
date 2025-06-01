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

class PostToSocialMediaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $product;
    protected $contactName;
    protected $contactPhone;
    protected $images;
    protected $isShop;
    protected $shopName;

    public function __construct($product, $contactName, $contactPhone, $images, $isShop, $shopName = null)
    {
        $this->product = $product;
        $this->contactName = $contactName;
        $this->contactPhone = $contactPhone;
        $this->images = $images;
        $this->isShop = $isShop;
        $this->shopName = $shopName;
    }

    public function handle(
        TelegramService $telegramService,
        FacebookService $facebookService,
        InstagramService $instagramService
    ) {
        try {
            $messageStartTelegram = <<<INFO
📢 <b>Объявление:</b> {$this->product->name}

📝 <b>Описание:</b> {$this->product->description}

📍 <b>Регион:</b> {$this->product->region->parent->name}, {$this->product->region->name}

INFO;

            $messageStartFacebookInstagram = <<<INFO
📢 Объявление: {$this->product->name}

📝 Описание: {$this->product->description}

📍 Регион: {$this->product->region->parent->name}, {$this->product->region->name}

INFO;

            $telegramEnd = <<<INFO

👤 <b>Контактное лицо:</b> {$this->contactName};

📞 <b>Номер телефона:</b> {$this->contactPhone}

🌍 <b>Карта:</b> <a href="https://yandex.ru/maps/?ll={$this->product->longitude},{$this->product->latitude}&z=17&l=map&pt={$this->product->longitude},{$this->product->latitude},pm2rdm">Местоположение в Yandex Maps</a>
INFO;

            $facebookEnd = <<<INFO

👤 Контактное лицо: {$this->contactName}

📞 Номер телефона: {$this->contactPhone}

🌍 Карта: Местоположение в Yandex Maps: https://yandex.ru/maps/?ll={$this->product->longitude},{$this->product->latitude}&z=17&l=map&pt={$this->product->longitude},{$this->product->latitude},pm2rdm
INFO;

            $instagramEnd = "

👤 Контактное лицо: {$this->contactName}

📞 Номер телефона: {$this->contactPhone}

🌍 Карта: Местоположение в Yandex Maps: https://yandex.ru/maps/?ll={$this->product->longitude},{$this->product->latitude}&z=17&l=map&pt={$this->product->longitude},{$this->product->latitude},pm2rdm
";

            $shopLineTelegram = $this->isShop && $this->shopName ? "\n🏪 <b>Магазин:</b> {$this->shopName}" : '';
            $shopLineFacebook = $this->isShop && $this->shopName ? "\n🏪 Магазин: {$this->shopName}" : '';
            $shopLineInstagram = $this->isShop && $this->shopName ? "\n🏪 Магазин: {$this->shopName}" : '';

            $productInfo = $messageStartTelegram . $shopLineTelegram . $telegramEnd;
            $facebookProductInfo = $messageStartFacebookInstagram . $shopLineFacebook . $facebookEnd;
            $instaMessage = $messageStartFacebookInstagram . $shopLineInstagram . $instagramEnd;

            $buttonText = 'Перейти к объявлению ➡️';
            $productUrl = "https://biztorg.uz/obyavlenie/{$this->product->slug}";
            $locationUrl = "https://yandex.ru/maps/?ll={$this->product->longitude},{$this->product->latitude}&z=17&l=map&pt={$this->product->longitude},{$this->product->latitude},pm2rdm";
            $contactPhone = $this->contactPhone;

            // Telegram Post
            if (count($this->images) > 1) {
                $media = [];
                foreach ($this->images as $index => $image) {
                    $mediaItem = [
                        'type' => 'photo',
                        'media' => $image,
                        'parse_mode' => 'HTML',
                    ];
                    if ($index === 0) {
                        $mediaItem['caption'] = $productInfo;
                    }
                    $media[] = $mediaItem;
                }
                $telegramResponse = $telegramService->sendMediaGroup($media, $buttonText, $productUrl);
                Log::debug('Telegram response before update: ', $telegramResponse);
                if (isset($telegramResponse['result']['message_id'])) {
                    $telegramPostId = $telegramResponse['result']['message_id'];
                    Log::debug("Attempting to update product {$this->product->id} with telegram_post_id: {$telegramPostId}");
                    $updateResult = $this->product->update(['telegram_post_id' => $telegramPostId]);
                    Log::debug("Update result for telegram_post_id: ", ['success' => $updateResult, 'product' => $this->product->fresh()->toArray()]);
                } else {
                    Log::warning('Telegram post ID not found in response: ', $telegramResponse);
                }
            } elseif (count($this->images) === 1) {
                $telegramResponse = $telegramService->sendPhoto($this->images[0], $productInfo, $buttonText, $productUrl);
                Log::debug('Telegram response before update: ', $telegramResponse);
                if (isset($telegramResponse['result']['message_id'])) {
                    $telegramPostId = $telegramResponse['result']['message_id'];
                    Log::debug("Attempting to update product {$this->product->id} with telegram_post_id: {$telegramPostId}");
                    $updateResult = $this->product->update(['telegram_post_id' => $telegramPostId]);
                    Log::debug("Update result for telegram_post_id: ", ['success' => $updateResult, 'product' => $this->product->fresh()->toArray()]);
                } else {
                    Log::warning('Telegram post ID not found in response: ', $telegramResponse);
                }
            } else {
                $telegramResponse = $telegramService->sendMessage($productInfo, $buttonText, $productUrl);
                Log::debug('Telegram response before update: ', $telegramResponse);
                if (isset($telegramResponse['result']['message_id'])) {
                    $telegramPostId = $telegramResponse['result']['message_id'];
                    Log::debug("Attempting to update product {$this->product->id} with telegram_post_id: {$telegramPostId}");
                    $updateResult = $this->product->update(['telegram_post_id' => $telegramPostId]);
                    Log::debug("Update result for telegram_post_id: ", ['success' => $updateResult, 'product' => $this->product->fresh()->toArray()]);
                } else {
                    Log::warning('Telegram post ID not found in response: ', $telegramResponse);
                }
            }

            // Facebook Post
            $imagesForFacebook = array_map(function ($image) {
                return ['id' => null, 'image_url' => $image];
            }, $this->images);
            $facebookResponse = $facebookService->createPost($facebookProductInfo, $imagesForFacebook);
            Log::debug('Facebook response before update: ', $facebookResponse);
            if (isset($facebookResponse['id'])) {
                // Extract the post_id from the composite ID (page_id_post_id)
                $facebookPostId = explode('_', $facebookResponse['id'])[1] ?? $facebookResponse['id'];
                Log::debug("Attempting to update product {$this->product->id} with facebook_post_id: {$facebookPostId}");
                $updateResult = $this->product->update(['facebook_post_id' => $facebookPostId]);
                Log::debug("Update result for facebook_post_id: ", ['success' => $updateResult, 'product' => $this->product->fresh()->toArray()]);
            } else {
                Log::warning('Facebook post ID not found in response: ', $facebookResponse);
            }

            // Instagram Post with Retry Logic
            $maxAttempts = 3;
            $attempt = 1;
            $instagramResponse = null;

            while ($attempt <= $maxAttempts) {
                try {
                    $instagramResponse = $instagramService->createCarouselPost($instaMessage, $this->images);
                    break; // Success, exit the loop
                } catch (\Exception $e) {
                    Log::warning("Instagram post attempt {$attempt} failed: " . $e->getMessage());
                    if ($attempt === $maxAttempts) {
                        Log::error("Failed to post to Instagram after {$maxAttempts} attempts: " . $e->getMessage());
                        $instagramResponse = ['error' => $e->getMessage()];
                        break;
                    }
                    sleep(2); // Wait 2 seconds before retrying
                    $attempt++;
                }
            }

            Log::debug('Instagram response before update: ', $instagramResponse);
            if (isset($instagramResponse['id'])) {
                $instaPostId = $instagramResponse['id'];
                Log::debug("Attempting to update product {$this->product->id} with insta_post_id: {$instaPostId}");
                $updateResult = $this->product->update(['insta_post_id' => $instaPostId]);
                Log::debug("Update result for insta_post_id: ", ['success' => $updateResult, 'product' => $this->product->fresh()->toArray()]);
            } else {
                Log::warning('Instagram post ID not found in response: ', $instagramResponse);
            }
        } catch (\Exception $e) {
            Log::error("Failed to post to social media: " . $e->getMessage());
        }
    }
}