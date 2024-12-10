<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Currency;
use GuzzleHttp\Client;
use Symfony\Component\DomCrawler\Crawler;

class UpdateCurrencyValues extends Command
{
    protected $signature = 'update:currencies';
    protected $description = 'Scrape currency values from two websites and update rows in the database';

    public function handle()
    {
        $this->info('Starting to scrape currency values...');

        try {
            // Scrape USD and RUB from Website 1 (Kapitalbank)
            $this->info('Fetching USD and RUB from Website 1 (Kapitalbank)...');
            $valuesFromSite1 = $this->scrapeFromKapitalbank();

            foreach ($valuesFromSite1 as $name => $value) {
                $this->info("$name: $value");
            }

            // Scrape USD, EUR, and RUB from Website 2 (CBU)
            $this->info('Fetching USD, EUR, and RUB from Website 2 (CBU)...');
            $valuesFromSite2 = $this->scrapeFromCBU();

            foreach ($valuesFromSite2 as $name => $value) {
                $this->info("$name: $value");
            }

            // Combine results from both websites
            $this->info('Updating values in the database...');
            $allCurrencies = [
                ['name' => 'USD', 'value' => $valuesFromSite1['USD'] ?? null],
                ['name' => 'RUB', 'value' => $valuesFromSite1['RUB'] ?? null],
                ['name' => 'EUR', 'value' => $valuesFromSite1['EUR'] ?? null],
                ['name' => 'CBU-USD', 'value' => $valuesFromSite2['USD'] ?? null],
                ['name' => 'CBU-RUB', 'value' => $valuesFromSite2['RUB'] ?? null],
                ['name' => 'CBU-EUR', 'value' => $valuesFromSite2['EUR'] ?? null],
            ];

            // Add or update rows in the database
            foreach ($allCurrencies as $currency) {
                if ($currency['value'] !== null) {
                    Currency::updateOrCreate(
                        ['date' => now()->toDateString(), 'name' => $currency['name']], // Match on date and name
                        ['value' => $currency['value'], 'updated_at' => now()] // Update or insert value
                    )->touch();
                }
            }

            $this->info('Currency values updated successfully.');
        } catch (\Exception $e) {
            $this->error('An error occurred: ' . $e->getMessage());
        }
    }

    private function scrapeFromKapitalbank()
    {
        $url = 'https://kapitalbank.uz/uz/welcome.php'; // Replace with the actual URL of Kapitalbank
        $client = new Client(['verify' => false]);

        try {
            $response = $client->get($url);
            $html = $response->getBody()->getContents();
            $crawler = new Crawler($html);

            $currencies = [];
            $crawler->filter('div.kapitalbank_currency_tablo_rate_box')->each(function (Crawler $node) use (&$currencies) {
                $currencyName = $node->filter('div.kapitalbank_currency_tablo_type_box')->text();
                $currencyValue = $node->filter('div.kapitalbank_currency_tablo_type_value')->text();

                if (in_array($currencyName, ['USD', 'RUB', 'EUR'])) {
                    $currencies[$currencyName] = $currencyValue;
                }
            });

            return $currencies;
        } catch (\Exception $e) {
            $this->error('Error fetching data from Kapitalbank: ' . $e->getMessage());
            return [];
        }
    }

    private function scrapeFromCBU()
    {
        $url = 'https://cbu.uz/oz/arkhiv-kursov-valyut/json/'; // Actual URL of the Central Bank of Uzbekistan
        $client = new Client(['verify' => false]);

        try {
            $response = $client->get($url);
            $data = json_decode($response->getBody()->getContents(), true);

            if (!$data) {
                $this->error('Failed to fetch or parse JSON data from CBU.');
                return [];
            }

            // Extract USD, EUR, and RUB
            $currencies = [];
            foreach ($data as $currency) {
                if (in_array($currency['Ccy'], ['USD', 'EUR', 'RUB'])) {
                    $currencies[$currency['Ccy']] = $currency['Rate'];
                }
            }

            return $currencies;
        } catch (\Exception $e) {
            $this->error('Error fetching data from CBU: ' . $e->getMessage());
            return [];
        }
    }
}
