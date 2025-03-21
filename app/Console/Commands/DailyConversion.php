<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class DailyConversion extends Command
{
    protected $signature = 'conversion:daily';
    protected $description = 'Fetch USD->EUR rate and update websites table daily';

    public function handle()
    {
        // 1) Fetch the latest USD->EUR from an external API
        $url = 'https://api.exchangerate.host/latest?base=USD&symbols=EUR';
        $response = Http::get($url)->json(); // Using Laravel's Http client

        if (!isset($response['rates']['EUR'])) {
            $this->error('Error: Unexpected API response');
            return 1; // Error code
        }

        $todayRate = $response['rates']['EUR'];
        $this->info("Today's Rate: $todayRate");

        // 2) Store in app_settings
        DB::table('app_settings')
            ->updateOrInsert(
                ['setting_name' => 'usd_eur_rate'],
                ['setting_value' => $todayRate]
            );

        // 3) Re-scale all USD rows
        DB::update("
            UPDATE websites w
            JOIN websites_conversion_log c ON w.id = c.website_id
            SET
                w.publisher_price       = w.publisher_price       * (? / c.last_used_rate),
                w.link_insertion_price  = w.link_insertion_price  * (? / c.last_used_rate),
                w.no_follow_price       = w.no_follow_price       * (? / c.last_used_rate),
                w.special_topic_price   = w.special_topic_price   * (? / c.last_used_rate),
                w.profit               = w.profit               * (? / c.last_used_rate),
                w.automatic_evaluation = w.automatic_evaluation * (? / c.last_used_rate),
                c.last_used_rate       = ?
            WHERE w.currency = 'USD'
        ", [$todayRate, $todayRate, $todayRate, $todayRate, $todayRate]);

        $this->info('Daily conversion complete!');
        return 0; // Success code
    }
}
