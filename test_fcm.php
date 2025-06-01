<?php

require __DIR__ . '/vendor/autoload.php';

use App\Jobs\SendFcmNotification;
use Illuminate\Support\Facades\Log;

$app = require __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

Log::info('Starting FCM test script');

$token = 'eLsTc8ClSEeZc3t-tmer_4:APA91bEPgtmGv_DgQ1dwa50aTgp_Yt9_9L0d2Y__N5TwvVSkb0YT8u7SftBN-HiGwxMAvjr0W8xne0YNISnpmoZwiB7488AqqlahPGdnmh3T6Icm0wE2924';

try {
    // Dispatch to queue
    SendFcmNotification::dispatch(90, 'Test Shop опубликовал новое объявление', 'Test Product - Description', $token, 'https://biztorg.uz/storage/categories/December2024/nFLC7qONERwJaN2oWksq.webp');
    Log::info('FCM notification job dispatched to queue');

    // Optional: Run synchronously for immediate feedback
    // SendFcmNotification::dispatchSync(90, 'Test Shop опубликовал новое объявление', 'Test Product - Description', $token, 'https://biztorg.uz/storage/categories/December2024/nFLC7qONERwJaN2oWksq.webp');
    // Log::info('FCM notification job executed synchronously');
} catch (\Exception $e) {
    Log::error('Failed to dispatch FCM notification job', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
    ]);
}

Log::info('FCM test script completed');