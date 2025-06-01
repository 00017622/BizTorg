<?php

namespace App\Jobs;

use App\Models\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Laravel\Firebase\Facades\Firebase;

class SendFcmNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $productId;
    private $notificationTitle;
    private $notificationBody;
    private $fcmToken;
    private $productImageUrl;
    private $senderId;
    private $shopName;
    private $productName;
    private $productDescription;
    private $subscriberId;
    private $shopImage;

    /**
     * Create a new job instance.
     *
     * @param int $productId The ID of the product
     * @param string $notificationTitle The title of the notification
     * @param string $notificationBody The body of the notification
     * @param string $fcmToken The FCM token of the recipient
     * @param string|null $productImageUrl The URL of the product image (optional)
     */
    public function __construct(int $productId, string $notificationTitle, string $notificationBody,
     string $fcmToken, ?string $productImageUrl = null, int $senderId, string $shopName,
      string $productName, string $productDescription, int $subscriberId, string $shopImage)
    {
        $this->productId = $productId;
        $this->notificationTitle = $notificationTitle;
        $this->notificationBody = $notificationBody;
        $this->fcmToken = $fcmToken;
        $this->productImageUrl = $productImageUrl;
        $this->senderId = $senderId;
        $this->shopName = $shopName;
        $this->productName = $productName;
        $this->productDescription = $productDescription;
        $this->subscriberId = $subscriberId;
        $this->shopImage = $shopImage;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        \Log::debug('Starting SendFcmNotification job', [
            'productId' => $this->productId,
            'title' => $this->notificationTitle,
            'body' => $this->notificationBody,
            'fcmToken' => substr($this->fcmToken, 0, 10) . '...',
            'imageUrl' => $this->productImageUrl,
        ]);

        if (empty($this->fcmToken)) {
            \Log::error('FCM token is empty');
            return;
        }

        try {
            $messaging = Firebase::messaging();
            \Log::debug('Firebase messaging instance created');

            $messageId = 'info_' . time();
            $data = [
                'title' => $this->notificationTitle,
                'type' => 'product-info',
                'body' => $this->notificationBody,
                'messageId' => $messageId,
                'imageUrl' => $this->productImageUrl ?? '',
                'productId' => (string) $this->productId,
            ];

            \Log::debug('FCM message data prepared', $data);

            $message = CloudMessage::new()
                ->toToken($this->fcmToken)
                ->withData($data);

            \Log::debug('FCM message object created', [
                'token' => substr($this->fcmToken, 0, 10) . '...',
                'data' => $data,
            ]);

            $result = $messaging->send($message);
            \Log::info('FCM notification sent successfully', [
                'messageId' => $messageId,
                'data' => $data,
                'result' => $result,
            ]);

           Notification::create([
                    'receiver_id' => $this->subscriberId,
                    'sender_id' => $this->senderId,
                    'type' => 'product-ad',
                    'content' => $this->notificationBody,
                    'hasBeenSeen' => false,
                    'is_global' => false,
                    'reference_id' => $messageId,
                    'priority' => 'medium',
                    'metadata' => json_encode([
                        'product_name' => $this->productName,
                        'product_description' => $this->productDescription,
                        'product_id' => $this->productId,
                        'product_image_url' => $this->productImageUrl ?? '',
                        'shop_title' => $this->shopName,
                        'shop_image' => $this->shopImage,
                    ]),
                ]);

                \Log::info('Notification record created for subscriber ID: ' . $this->subscriberId);

        } catch (\Kreait\Firebase\Exception\Messaging\InvalidArgument $e) {
            \Log::error('FCM configuration error', [
                'error' => $e->getMessage(),
                'token' => substr($this->fcmToken, 0, 10) . '...',
            ]);
        } catch (\Kreait\Firebase\Exception\Messaging\NotFound $e) {
            \Log::error('FCM token not found or invalid', [
                'error' => $e->getMessage(),
                'token' => substr($this->fcmToken, 0, 10) . '...',
            ]);
        } catch (\Exception $e) {
            \Log::error('FCM notification failed', [
                'error' => $e->getMessage(),
                'token' => substr($this->fcmToken, 0, 10) . '...',
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}