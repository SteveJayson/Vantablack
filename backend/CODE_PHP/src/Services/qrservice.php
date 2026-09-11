<?php

namespace Aegis\Services;

class QrService
{
    private string $baseUrl;
    
    public function __construct()
    {
        $this->baseUrl = defined('QRSERVER_BASE_URL') ? QRSERVER_BASE_URL : 'https://api.qrserver.com/v1';
    }
    
    /**
     * Generate QR code URL for any data
     */
    public function generateQrUrl(string $data, int $size = 200): string
    {
        $encodedData = urlencode($data);
        return "{$this->baseUrl}/create-qr-code/?size={$size}x{$size}&data={$encodedData}";
    }
    
    /**
     * Generate QR code for a gear item
     */
    public function generateGearQr(array $gear): array
    {
        $qrData = json_encode([
            'type' => 'gear',
            'id' => $gear['id'],
            'name' => $gear['name'],
            'slot' => $gear['slot'],
            'price' => $gear['price'],
            'source' => $gear['source']
        ]);
        
        return [
            'gear_id' => $gear['id'],
            'gear_name' => $gear['name'],
            'qr_url' => $this->generateQrUrl($qrData, 300),
            'qr_data' => $qrData,
            'generated_at' => date('Y-m-d H:i:s')
        ];
    }
    
    /**
     * Generate QR code for a combatant
     */
    public function generateCombatantQr(array $combatant): array
    {
        $qrData = json_encode([
            'type' => 'combatant',
            'id' => $combatant['id'],
            'name' => $combatant['name'],
            'role' => $combatant['role'] ?? 'civilian',
            'faction' => $combatant['faction'],
            'credits' => $combatant['credits']
        ]);
        
        return [
            'combatant_id' => $combatant['id'],
            'combatant_name' => $combatant['name'],
            'qr_url' => $this->generateQrUrl($qrData, 300),
            'qr_data' => $qrData,
            'generated_at' => date('Y-m-d H:i:s')
        ];
    }
}