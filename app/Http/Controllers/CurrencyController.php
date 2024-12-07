<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Http;
use Symfony\Component\DomCrawler\Crawler;

class CurrencyController extends Controller
{
    public function getDollarRate() {
        $response = Http::get('https://cbu.uz/ru/');

        if ($response->successful()) {
            $crawler = new Crawler($response->body());

            $usdRate = $crawler->filter('div[data-currency="USD"] .exchange_item_value')->text();

            $usdRate = preg_replace('/\D+/', '', $usdRate);

            return trim($usdRate);
        }

        return null;

    }
}
