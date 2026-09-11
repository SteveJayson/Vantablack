<?php

namespace Aegis\Services;

class WeatherService
{
    private string $apiKey;
    private string $baseUrl;
    private string $cacheDir;
    
    public function __construct()
    {
        $this->apiKey = defined('OPENWEATHER_API_KEY') ? OPENWEATHER_API_KEY : '';
        $this->baseUrl = defined('OPENWEATHER_BASE_URL') ? OPENWEATHER_BASE_URL : 'https://api.openweathermap.org/data/2.5';
        $this->cacheDir = __DIR__ . '/../../cache/weather';
        
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }
    
    public function getCurrentWeather(string $city = 'Manila', string $units = 'metric'): array
    {
        // If no API key, return mock data
        if (empty($this->apiKey) || $this->apiKey === 'YOUR_API_KEY_HERE') {
            return $this->getMockWeather($city);
        }
        
        // Check cache (5 minutes)
        $cacheKey = md5($city . $units);
        $cacheFile = $this->cacheDir . '/' . $cacheKey . '.json';
        
        if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < 300) {
            $cached = json_decode(file_get_contents($cacheFile), true);
            $cached['source'] = 'cache';
            return $cached;
        }
        
        // Call API
        $url = "{$this->baseUrl}/weather?q={$city}&appid={$this->apiKey}&units={$units}";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200 || !$response) {
            return $this->getMockWeather($city);
        }
        
        $data = json_decode($response, true);
        
        if (!$data || !isset($data['main'])) {
            return $this->getMockWeather($city);
        }
        
        $weather = [
            'city' => $data['name'] ?? $city,
            'country' => $data['sys']['country'] ?? 'PH',
            'temperature' => round($data['main']['temp'] ?? 25, 1),
            'feels_like' => round($data['main']['feels_like'] ?? 25, 1),
            'humidity' => $data['main']['humidity'] ?? 50,
            'pressure' => $data['main']['pressure'] ?? 1013,
            'weather' => $data['weather'][0]['main'] ?? 'Clear',
            'description' => $data['weather'][0]['description'] ?? 'clear sky',
            'icon' => $data['weather'][0]['icon'] ?? '01d',
            'wind_speed' => round($data['wind']['speed'] ?? 0, 1),
            'timestamp' => date('Y-m-d H:i:s'),
            'source' => 'api'
        ];
        
        $weather['energy_impact'] = $this->calculateEnergyImpact($weather);
        
        file_put_contents($cacheFile, json_encode($weather));
        
        return $weather;
    }
    
    private function calculateEnergyImpact(array $weather): array
    {
        $temperature = $weather['temperature'];
        $humidity = $weather['humidity'];
        $weatherType = $weather['weather'];
        
        $drainMultiplier = 1.0;
        $recoveryMultiplier = 1.0;
        $warnings = [];
        
        // Temperature effects
        if ($temperature > 35) {
            $drainMultiplier += 0.3;
            $recoveryMultiplier -= 0.2;
            $warnings[] = '🔥 Extreme heat increases energy drain by 30%';
        } elseif ($temperature > 30) {
            $drainMultiplier += 0.15;
            $warnings[] = '🌡️ Hot weather increases energy drain by 15%';
        } elseif ($temperature < 10) {
            $drainMultiplier += 0.2;
            $warnings[] = '❄️ Cold weather increases energy drain by 20%';
        } elseif ($temperature >= 20 && $temperature <= 25) {
            $recoveryMultiplier += 0.1;
            $warnings[] = '✅ Optimal temperature for energy recovery (+10%)';
        }
        
        // Weather type effects
        switch ($weatherType) {
            case 'Thunderstorm':
                $drainMultiplier += 0.25;
                $warnings[] = '⚡ Thunderstorms boost power but drain energy';
                break;
            case 'Rain':
                $recoveryMultiplier -= 0.15;
                $warnings[] = '🌧️ Rain reduces energy recovery by 15%';
                break;
            case 'Snow':
                $drainMultiplier += 0.2;
                $warnings[] = '🌨️ Snow increases energy drain by 20%';
                break;
            case 'Clear':
                $recoveryMultiplier += 0.05;
                break;
            case 'Fog':
            case 'Mist':
                $drainMultiplier += 0.1;
                $warnings[] = '🌫️ Fog reduces visibility and increases strain';
                break;
        }
        
        // Humidity effects
        if ($humidity > 80) {
            $drainMultiplier += 0.1;
            $warnings[] = '💧 High humidity increases energy drain by 10%';
        }
        
        return [
            'drain_multiplier' => round($drainMultiplier, 2),
            'recovery_multiplier' => round($recoveryMultiplier, 2),
            'warnings' => $warnings,
            'effect_summary' => $this->getEffectSummary($drainMultiplier, $recoveryMultiplier)
        ];
    }
    
    private function getEffectSummary(float $drain, float $recovery): string
    {
        if ($drain > 1.3) return 'SEVERE - Combat not recommended';
        if ($drain > 1.15) return 'HIGH - Increased risk';
        if ($drain > 1.0) return 'MODERATE - Slight impact';
        if ($recovery > 1.1) return 'OPTIMAL - Enhanced performance';
        return 'NORMAL - No significant impact';
    }
    
    public function getForecast(string $city = 'Manila', int $days = 5): array
    {
        if (empty($this->apiKey) || $this->apiKey === 'YOUR_API_KEY_HERE') {
            return $this->getMockForecast($city, $days);
        }
        
        $url = "{$this->baseUrl}/forecast?q={$city}&appid={$this->apiKey}&units=metric&cnt={$days}";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200 || !$response) {
            return $this->getMockForecast($city, $days);
        }
        
        $data = json_decode($response, true);
        
        if (!$data || !isset($data['list'])) {
            return $this->getMockForecast($city, $days);
        }
        
        $forecast = [];
        foreach (array_slice($data['list'], 0, $days) as $item) {
            $forecast[] = [
                'datetime' => $item['dt_txt'] ?? date('Y-m-d H:i:s'),
                'temperature' => round($item['main']['temp'] ?? 25, 1),
                'weather' => $item['weather'][0]['main'] ?? 'Clear',
                'description' => $item['weather'][0]['description'] ?? 'clear',
                'humidity' => $item['main']['humidity'] ?? 50
            ];
        }
        
        return [
            'city' => $data['city']['name'] ?? $city,
            'forecast' => $forecast,
            'source' => 'api'
        ];
    }
    
    private function getMockWeather(string $city): array
    {
        $weathers = ['Clear', 'Clouds', 'Rain', 'Thunderstorm'];
        $weather = $weathers[array_rand($weathers)];
        $temp = rand(20, 35);
        
        $data = [
            'city' => $city,
            'country' => 'PH',
            'temperature' => $temp,
            'feels_like' => $temp + rand(-2, 2),
            'humidity' => rand(40, 90),
            'pressure' => rand(1000, 1020),
            'weather' => $weather,
            'description' => strtolower($weather),
            'icon' => '01d',
            'wind_speed' => rand(0, 20),
            'timestamp' => date('Y-m-d H:i:s'),
            'source' => 'mock (no API key)'
        ];
        
        $data['energy_impact'] = $this->calculateEnergyImpact($data);
        
        return $data;
    }
    
    private function getMockForecast(string $city, int $days): array
    {
        $forecast = [];
        for ($i = 0; $i < $days; $i++) {
            $forecast[] = [
                'datetime' => date('Y-m-d H:i:s', strtotime("+{$i} days")),
                'temperature' => rand(20, 35),
                'weather' => ['Clear', 'Clouds', 'Rain'][rand(0, 2)],
                'description' => 'forecast',
                'humidity' => rand(40, 90)
            ];
        }
        
        return [
            'city' => $city,
            'forecast' => $forecast,
            'source' => 'mock (no API key)'
        ];
    }
}