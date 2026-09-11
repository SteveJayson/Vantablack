<?php

namespace Aegis\Services;

class CurrencyService
{
    private string $apiKey;
    private string $baseUrl;
    private string $cacheDir;
    
    public function __construct()
    {
        $this->apiKey = defined('EXCHANGERATE_API_KEY') ? EXCHANGERATE_API_KEY : '';
        $this->baseUrl = defined('EXCHANGERATE_BASE_URL') ? EXCHANGERATE_BASE_URL : 'https://v6.exchangerate-api.com/v6';
        $this->cacheDir = __DIR__ . '/../../cache/currency';
        
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }
    
    /**
     * Get exchange rates for a base currency
     */
    public function getRates(string $base = 'PHP'): array
    {
        if (empty($this->apiKey) || $this->apiKey === 'YOUR_EXCHANGERATE_API_KEY_HERE') {
            return $this->getMockRates($base);
        }
        
        // Cache for 1 hour
        $cacheKey = md5($base);
        $cacheFile = $this->cacheDir . '/' . $cacheKey . '.json';
        
        if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < 3600) {
            $cached = json_decode(file_get_contents($cacheFile), true);
            $cached['source'] = 'cache';
            return $cached;
        }
        
        $url = "{$this->baseUrl}/{$this->apiKey}/latest/{$base}";
        
        $response = $this->curlRequest($url);
        
        if (!$response || !isset($response['conversion_rates'])) {
            return $this->getMockRates($base);
        }
        
        $rates = [
            'base' => $base,
            'rates' => $response['conversion_rates'],
            'updated_at' => $response['time_last_update_utc'] ?? date('Y-m-d H:i:s'),
            'source' => 'api'
        ];
        
        file_put_contents($cacheFile, json_encode($rates));
        
        return $rates;
    }
    
    /**
     * Convert amount from one currency to another
     */
    public function convert(float $amount, string $from = 'PHP', string $to = 'USD'): array
    {
        $rates = $this->getRates($from);
        
        if (!isset($rates['rates'][$to])) {
            return [
                'success' => false,
                'error' => "Currency {$to} not supported"
            ];
        }
        
        $rate = $rates['rates'][$to];
        $converted = round($amount * $rate, 2);
        
        return [
            'success' => true,
            'from' => [
                'currency' => $from,
                'amount' => $amount
            ],
            'to' => [
                'currency' => $to,
                'amount' => $converted
            ],
            'rate' => $rate,
            'updated_at' => $rates['updated_at'] ?? date('Y-m-d H:i:s')
        ];
    }
    
    /**
     * Get gear price in multiple currencies
     */
    public function getGearPriceInCurrencies(int $credits): array
    {
        $currencies = ['USD', 'EUR', 'JPY', 'GBP', 'KRW', 'SGD'];
        $rates = $this->getRates('PHP');
        $prices = [];
        
        foreach ($currencies as $currency) {
            if (isset($rates['rates'][$currency])) {
                $prices[$currency] = round($credits * $rates['rates'][$currency], 2);
            }
        }
        
        return [
            'base_credits' => $credits,
            'conversions' => $prices,
            'updated_at' => $rates['updated_at'] ?? date('Y-m-d H:i:s')
        ];
    }
    
    /**
     * Mock rates when no API key
     */
    private function getMockRates(string $base): array
    {
        return [
            'base' => $base,
            'rates' => [
                'PHP' => 1.0,
                'USD' => 0.018,
                'EUR' => 0.016,
                'JPY' => 2.65,
                'GBP' => 0.014,
                'KRW' => 23.5,
                'SGD' => 0.024,
                'AUD' => 0.027,
                'CAD' => 0.024,
                'CNY' => 0.13
            ],
            'updated_at' => date('Y-m-d H:i:s'),
            'source' => 'mock (no API key)'
        ];
    }
    
    private function curlRequest(string $url): ?array
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200 || !$response) {
            return null;
        }
        
        return json_decode($response, true);
    }
}