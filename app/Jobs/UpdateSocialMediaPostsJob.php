<?php

namespace App\Jobs;

use App\Models\Product;
use App\Models\ShopProfile;
use App\Services\TelegramService;
use App\Services\FacebookService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class UpdateSocialMediaPostsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $productId;
    protected $updatedData;

    public function __construct($productId, array $updatedData)
    {
        $this->productId = $productId;
        $this->updatedData = $updatedData;
    }

    public function handle(TelegramService $telegramService, FacebookService $facebookService)
    {
        try {
            $product = Product::with('region.parent')->findOrFail($this->productId);

            $user = $product->user;

            // Fetch contact info from the user associated with the product
            $contactName = $user->isShop ? $user->shopProfile->contact_name : $product->user->name;
            $contactPhone = $user->isShop ? $user->shopProfile->phone : $product->user->profile->phone;

            $isShop = $user->isShop ?? false; // Assuming a user field for shop status

            $determineShopName = null;

            $shopProfile = ShopProfile::where('user_id', $user->id)->first();
            if ($shopProfile) {
                $determineShopName = $shopProfile->shop_name;
            }

            // Construct messages with proper spacing
            $messageStartTelegram = <<<INFO
📢 <b>Объявление:</b> {$this->updatedData['name']}

📝 <b>Описание:</b> {$this->updatedData['description']}

📍 <b>Регион:</b> {$product->region->parent->name}, {$product->region->name}
INFO;

            $messageStartFacebook = <<<INFO
📢 Объявление: {$this->updatedData['name']}

📝 Описание: {$this->updatedData['description']}

📍 Регион: {$product->region->parent->name}, {$product->region->name}
INFO;

            $telegramEnd = <<<INFO

👤 <b>Контактное лицо:</b> {$contactName}

📞 <b>Номер телефона:</b> {$contactPhone}

🌍 <b>Карта:</b> <a href="https://yandex.ru/maps/?ll={$product->longitude},{$product->latitude}&z=17&l=map&pt={$product->longitude},{$product->latitude},pm2rdm">Местоположение в Yandex Maps</a>
INFO;

            $facebookEnd = <<<INFO

👤 Контактное лицо: {$contactName}

📞 Номер телефона: {$contactPhone}

🌍 Карта: Местоположение в Yandex Maps: https://yandex.ru/maps/?ll={$this->updatedData['longitude']},{$this->updatedData['latitude']}&z=17&l=map&pt={$this->updatedData['longitude']},{$this->updatedData['latitude']},pm2rdm
INFO;

            $shopLineTelegram = $isShop && $determineShopName ? "\n\n🏪 <b>Магазин:</b> {$determineShopName}\n" : '';
            $shopLineFacebook = $isShop && $determineShopName ? "\n\n🏪 Магазин: {$determineShopName}\n" : '';

            $telegramMessage = $messageStartTelegram . $shopLineTelegram . $telegramEnd;
            $facebookMessage = $messageStartFacebook . $shopLineFacebook . $facebookEnd;

            $buttonText = 'Перейти к объявлению ➡️';
            $productUrl = "https://44f7-95-214-211-229.ngrok-free.app/obyavlenie/{$product->slug}";

            // Update Telegram post
            if ($product->telegram_post_id) {
                $telegramResponse = $telegramService->updateMessage(
                    $product->telegram_post_id,
                    $telegramMessage,
                    $buttonText,
                    $productUrl,
                );
                if (isset($telegramResponse['ok']) && $telegramResponse['ok']) {
                    Log::info("Successfully updated Telegram post {$product->telegram_post_id}");
                } else {
                    Log::warning("Failed to update Telegram post {$product->telegram_post_id}: ", $telegramResponse);
                }
            }

            // Update Facebook post
            if ($product->facebook_post_id) {
                $facebookResponse = $facebookService->updatePost(
                    $product->facebook_post_id,
                    $facebookMessage
                );
                if (isset($facebookResponse['id'])) {
                    Log::info("Successfully updated Facebook post {$product->facebook_post_id}");
                } else {
                    Log::warning("Failed to update Facebook post {$product->facebook_post_id}: ", $facebookResponse);
                }
            }
        } catch (\Exception $e) {
            Log::error("Error updating social media posts for product {$this->productId}: " . $e->getMessage());
        }
    }
}