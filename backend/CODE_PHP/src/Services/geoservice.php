<?php

namespace Aegis\Services;

class GeoService
{
    private string $baseUrl;
    private string $cacheDir;
    
    public function __construct()
    {
        $this->baseUrl = defined('IPAPI_BASE_URL') ? IPAPI_BASE_URL : 'http://ip-api.com/json';
        $this->cacheDir = __DIR__ . '/../../cache/geo';
        
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }
    
    /**
     * Look up location by IP address
     */
    public function lookupIp(string $ip = ''): array
    {
        // Get user's IP if not provided
        if (empty($ip)) {
            $ip = $this->getUserIp();
        }
        
        // Don't lookup local IPs
        if (in_array($ip, ['127.0.0.1', '::1', 'localhost']) || str_starts_with($ip, '192.168.')) {
            return $this->getDefaultLocation();
        }
        
        // Cache for 24 hours
        $cacheFile = $this->cacheDir . '/' . md5($ip) . '.json';
        if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < 86400) {
            $cached = json_decode(file_get_contents($cacheFile), true);
            $cached['source'] = 'cache';
            return $cached;
        }
        
        $url = "{$this->baseUrl}/{$ip}?fields=status,message,country,countryCode,region,regionName,city,zip,lat,lon,timezone,isp,query";
        
        $response = $this->curlRequest($url);
        
        if (!$response || ($response['status'] ?? '') !== 'success') {
            return $this->getDefaultLocation();
        }
        
        $location = [
            'ip' => $response['query'] ?? $ip,
            'country' => $response['country'] ?? 'Unknown',
            'country_code' => $response['countryCode'] ?? 'XX',
            'region' => $response['regionName'] ?? 'Unknown',
            'city' => $response['city'] ?? 'Unknown',
            'zip' => $response['zip'] ?? '',
            'latitude' => $response['lat'] ?? 0,
            'longitude' => $response['lon'] ?? 0,
            'timezone' => $response['timezone'] ?? 'UTC',
            'isp' => $response['isp'] ?? 'Unknown',
            'timestamp' => date('Y-m-d H:i:s'),
            'source' => 'api'
        ];
        
        // Add faction territory info
        $location['territory'] = $this->getTerritoryInfo($location['country_code']);
        
        file_put_contents($cacheFile, json_encode($location));
        
        return $location;
    }
    
    /**
     * Determine faction territory based on location
     */
    private function getTerritoryInfo(string $countryCode): array
    {
        // Simple territory mapping (for game purposes)
        $heroTerritories = ['PH', 'US', 'JP', 'KR', 'SG', 'AU', 'GB', 'CA'];
        $villainTerritories = ['RU', 'CN', 'KP', 'IR'];
        
        if (in_array($countryCode, $heroTerritories)) {
            return ['faction' => 'hero', 'control' => rand(60, 95)];
        } elseif (in_array($countryCode, $villainTerritories)) {
            return ['faction' => 'villain', 'control' => rand(60, 95)];
        } else {
            return ['faction' => 'contested', 'control' => rand(40, 60)];
        }
    }
    
    /**
     * Get the user's actual IP address
     */
    private function getUserIp(): string
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
        } else {
            return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        }
    }
    
    private function getDefaultLocation(): array
    {
        return [
            'ip' => '127.0.0.1',
            'country' => 'Philippines',
            'country_code' => 'PH',
            'region' => 'Metro Manila',
            'city' => 'Manila',
            'zip' => '1000',
            'latitude' => 14.5995,
            'longitude' => 120.9842,
            'timezone' => 'Asia/Manila',
            'isp' => 'Local',
            'territory' => ['faction' => 'hero', 'control' => 75],
            'timestamp' => date('Y-m-d H:i:s'),
            'source' => 'default (local IP)'
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