<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Currency;
use GuzzleHttp\Client;
use Symfony\Component\DomCrawler\Crawler;

class UpdateCurrencyValues extends Command
{
    protected $signature = 'update:currencies';
    protected $description = 'Scrape currency values from two websites and update the database';

    public function handle()
    {
        $this->info('Fetching USD and RUB from Website 1...');
        $valuesFromSite1 = $this->scrapeFromSite1();

        $this->info('Fetching EUR and JPY from Website 2...');
        $valuesFromSite2 = $this->scrapeFromSite2();

        $this->info('Consolidating and updating values...');
        $finalValues = array_merge($valuesFromSite1, $valuesFromSite2);

        foreach ($finalValues as $name => $value) {
            Currency::updateOrCreate(
                ['name' => $name],
                ['value' => $value]
            );
        }

        $this->info('Currency values updated successfully.');
    }

    /**
     * Scrape USD and RUB from Website 1
     */
    private function scrapeFromSite1()
    {
        $url = 'https://kapitalbank.uz/uz/welcome.php'; // Replace with the actual URL
        $client = new Client(['verify' => false]);
        $response = $client->get($url);

        $html = $response->getBody()->getContents();
        $crawler = new Crawler($html);

        // Scrape USD and RUB values
        return [
            'USD' => $crawler->filter('#usd-rate')->text(),
            'RUB' => $crawler->filter('#rub-rate')->text(),
        ];
    }

    /**
     * Scrape EUR and JPY from Website 2
     */
    private function scrapeFromSite2()
    {
        $url = 'https://example-site2.com/currency-rates'; // Replace with the actual URL
        $client = new Client(['verify' => false]);
        $response = $client->get($url);

        $html = $response->getBody()->getContents();
        $crawler = new Crawler($html);

        // Scrape EUR and JPY values
        return [
            'EUR' => $crawler->filter('.currency-eur')->text(),
            'JPY' => $crawler->filter('.currency-jpy')->text(),
        ];
    }
}
