<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\CurrencyService;
use Illuminate\Support\Facades\Cache;

class UpdateUsdRate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'usd-rate:update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch and update the USD exchange rate every 12 or 24 hours';

    /**
     * Execute the console command.
     */
    protected $currencyService;

    public function __construct(CurrencyService $currencyService) {
        parent::__construct();
        $this->currencyService = $currencyService;
    }

    public function handle()
    {
        $usdRate = $this->currencyService->getDollarRate();

        if ($usdRate) {

            Cache::put('usd_rate', $usdRate, now()->addHours(12));
            $this->info("USD rate updated successfully: $usdRate");
        } else{ 
            $this->error("Failed to fetch USD rate.");
        }
    }
}
