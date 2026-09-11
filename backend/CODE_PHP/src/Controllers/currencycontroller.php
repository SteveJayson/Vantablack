<?php

namespace Aegis\Controllers;

use Aegis\Config\Database;
use Aegis\Services\CurrencyService;
use PDO;

class CurrencyController
{
    private PDO $db;
    private CurrencyService $currency;
    
    public function __construct()
    {
        $this->db = Database::getConnection();
        $this->currency = new CurrencyService();
    }
    
    /**
     * GET /api/currency/rates
     */
    public function getRates($request, $response, $args)
    {
        try {
            $base = $request->getQueryParam('base', 'PHP');
            $rates = $this->currency->getRates($base);
            
            return $this->jsonResponse($response, 200, true, 'Exchange rates retrieved', $rates);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, 500, false, 'Server error: ' . $e->getMessage());
        }
    }
    
    /**
     * GET /api/currency/convert
     */
    public function convert($request, $response, $args)
    {
        try {
            $amount = (float)$request->getQueryParam('amount', 100);
            $from = $request->getQueryParam('from', 'PHP');
            $to = $request->getQueryParam('to', 'USD');
            
            $result = $this->currency->convert($amount, $from, $to);
            
            if (!$result['success']) {
                return $this->jsonResponse($response, 400, false, $result['error'] ?? 'Conversion failed');
            }
            
            return $this->jsonResponse($response, 200, true, 'Currency converted', $result);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, 500, false, 'Server error: ' . $e->getMessage());
        }
    }
    
    /**
     * GET /api/currency/gear-price/{id}
     */
    public function getGearPriceInCurrency($request, $response, $args)
    {
        try {
            $gearId = $args['id'] ?? null;
            
            if (!$gearId) {
                return $this->jsonResponse($response, 400, false, 'Gear ID required');
            }
            
            $stmt = $this->db->prepare("SELECT id, name, price FROM gear_items WHERE id = ?");
            $stmt->execute([$gearId]);
            $gear = $stmt->fetch();
            
            if (!$gear) {
                return $this->jsonResponse($response, 404, false, 'Gear not found');
            }
            
            $conversions = $this->currency->getGearPriceInCurrencies((int)$gear['price']);
            
            return $this->jsonResponse($response, 200, true, 'Gear price converted', [
                'gear' => [
                    'id' => $gear['id'],
                    'name' => $gear['name'],
                    'price_credits' => (int)$gear['price']
                ],
                'conversions' => $conversions['conversions'],
                'updated_at' => $conversions['updated_at']
            ]);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, 500, false, 'Server error: ' . $e->getMessage());
        }
    }
    
    private function jsonResponse($response, int $status, bool $success, string $message, array $data = [])
    {
        $payload = ['status' => $status, 'success' => $success, 'message' => $message, 'data' => $data];
        $response->getBody()->write(json_encode($payload));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($status);
    }
}