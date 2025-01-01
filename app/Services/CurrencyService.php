<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Symfony\Component\DomCrawler\Crawler;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CurrencyService
{
    public function getDollarRate()
    {
        try {
            $response = Http::timeout(10)->get('https://cbu.uz/ru/');

            if ($response->successful()) {
                $crawler = new Crawler($response->body());
                $usdRate = $crawler->filter('div[data-currency="USD"] .exchange__item_value')->text();

                $usdRate = preg_replace('/[^\d.]/', '', $usdRate);

                Cache::put('usd_rate', $usdRate, now()->addHours(12));

                return trim($usdRate);
            }
        } catch (\Exception $e) {
            Log::error("Failed to fetch USD rate: " . $e->getMessage());
        }
        return Cache::get('usd_rate', 'USD rate not available');
    }
}
