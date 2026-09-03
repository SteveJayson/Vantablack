<?php

namespace Aegis\Services;

class FeasibilityEngine
{
    /**
     * Calculate loadout feasibility
     */
    public function calculate(array $combatant, array $loadout): array
    {
        // Calculate base stats
        $baseBioCapacity = $combatant['bioCapacityMax'] ?? 1000;
        $baseRecovery = $combatant['baseRecovery'] ?? 3;
        $baseRisk = $combatant['baseRisk'] ?? 12;
        
        // Sum gear stats
        $totalBioCapacity = 0;
        $totalRecovery = 0;
        $totalRiskModifier = 0;
        
        $slots = ['helmet', 'core', 'dampener', 'gauntlets', 'battery'];
        $equippedGear = [];
        
        foreach ($slots as $slot) {
            if (isset($loadout[$slot]) && $loadout[$slot] !== null) {
                $gear = $loadout[$slot];
                $totalBioCapacity += $gear['bioCapacity'] ?? 0;
                $totalRecovery += $gear['recoveryRate'] ?? 0;
                $totalRiskModifier += $gear['riskModifier'] ?? 0;
                $equippedGear[$slot] = $gear;
            }
        }
        
        // Calculate final values
        $finalBioCapacity = $baseBioCapacity + $totalBioCapacity;
        $finalRecovery = $baseRecovery + $totalRecovery;
        $finalRisk = max(0, min(100, $baseRisk + $totalRiskModifier));
        
        // Calculate energy drain (simplified model)
        $energyDrain = $totalBioCapacity * 0.15;
        $dampeningFactor = $totalRiskModifier * 0.5;
        $effectiveDrain = max(0, $energyDrain - $dampeningFactor);
        
        // Calculate burnout risk
        $burnoutRisk = $finalRisk;
        $isFatal = $effectiveDrain > $finalBioCapacity;
        
        return [
            'isFeasible' => !$isFatal,
            'isFatal' => $isFatal,
            'stats' => [
                'baseBioCapacity' => $baseBioCapacity,
                'totalBioCapacity' => $totalBioCapacity,
                'finalBioCapacity' => $finalBioCapacity,
                'baseRecovery' => $baseRecovery,
                'totalRecovery' => $totalRecovery,
                'finalRecovery' => $finalRecovery,
                'baseRisk' => $baseRisk,
                'totalRiskModifier' => $totalRiskModifier,
                'finalRisk' => $finalRisk,
                'burnoutRisk' => $burnoutRisk,
                'energyDrain' => round($energyDrain, 2),
                'dampeningFactor' => round($dampeningFactor, 2),
                'effectiveDrain' => round($effectiveDrain, 2),
                'capacityRemaining' => round(max(0, $finalBioCapacity - $effectiveDrain), 2)
            ],
            'equippedGear' => $equippedGear,
            'warnings' => $this->getWarnings($burnoutRisk, $isFatal, $effectiveDrain, $finalBioCapacity)
        ];
    }
    
    /**
     * Generate warning messages
     */
    private function getWarnings(float $burnoutRisk, bool $isFatal, float $effectiveDrain, float $capacity): array
    {
        $warnings = [];
        
        if ($isFatal) {
            $warnings[] = '⚠️ FATAL: Energy drain exceeds bio capacity!';
        } elseif ($effectiveDrain > $capacity * 0.8) {
            $warnings[] = '⚠️ HIGH RISK: Energy drain approaching capacity limit';
        }
        
        if ($burnoutRisk >= 80) {
            $warnings[] = '⚠️ CRITICAL: Burnout risk is extremely high';
        } elseif ($burnoutRisk >= 60) {
            $warnings[] = '⚠️ WARNING: Elevated burnout risk detected';
        }
        
        if ($effectiveDrain < $capacity * 0.2) {
            $warnings[] = '✅ LOW RISK: Loadout is well within safe parameters';
        }
        
        return $warnings;
    }
}
