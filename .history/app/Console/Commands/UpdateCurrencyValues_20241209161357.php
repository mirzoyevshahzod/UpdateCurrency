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
        $this->info('Starting to scrape currency values...');

        try {
            // Scrape USD and RUB from Website 1 (Kapitalbank)
            $this->info('Fetching USD and RUB from Website 1 (Kapitalbank)...');
            $valuesFromSite1 = $this->scrapeFromKapitalbank();

            // Scrape EUR and JPY from Website 2
            $this->info('Fetching EUR and JPY from Website 2...');
            $valuesFromSite2 = $this->scrapeFromSite2();

            // Consolidate results
            $this->info('Consolidating and updating values...');
            $finalValues = array_merge($valuesFromSite1, $valuesFromSite2);

            // Update the database
            foreach ($finalValues as $name => $value) {
                Currency::updateOrCreate(
                    ['name' => $name],
                    ['value' => $value]
                );
            }

            $this->info('Currency values updated successfully.');
        } catch (\Exception $e) {
            $this->error('An error occurred: ' . $e->getMessage());
        }
    }

    /**
     * Scrape USD and RUB from Kapitalbank
     *
     * @return array
     */
    private function scrapeFromKapitalbank()
    {
        $url = 'https://kapitalbank.uz/uz/welcome.php'; // Actual URL of Kapitalbank
        $client = new Client(['verify' => false]);

        try {
            $response = $client->get($url);
            $html = $response->getBody()->getContents();
            $crawler = new Crawler($html);

            // Extract USD and RUB
            return [
                'USD' => $crawler->filter('.kapitalbank_currency_tablo_rate_box:contains("USD") .kapitalbank_currency_tablo_type_value')->text(),
                'RUB' => $crawler->filter('.kapitalbank_currency_tablo_rate_box:contains("RUB") .kapitalbank_currency_tablo_type_value')->text(),
            ];
        } catch (\Exception $e) {
            $this->error('Error fetching data from Kapitalbank: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Scrape EUR and JPY from another website
     *
     * @return array
     */
    private function scrapeFromSite2()
    {
        $url = 'https://example-site2.com/currency-rates'; // Replace with the actual URL
        $client = new Client(['verify' => false]);

        try {
            $response = $client->get($url);
            $html = $response->getBody()->getContents();
            $crawler = new Crawler($html);

            // Extract EUR and JPY
            return [
                'EUR' => $crawler->filter('.currency-eur')->text(),
                'JPY' => $crawler->filter('.currency-jpy')->text(),
            ];
        } catch (\Exception $e) {
            $this->error('Error fetching data from Site 2: ' . $e->getMessage());
            return [];
        }
    }
}
