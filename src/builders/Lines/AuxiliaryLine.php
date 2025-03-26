<?php
namespace App\Builders\Lines;

/**
 * Auxiliary line implementation
 */
class AuxiliaryLine extends Line {
    /**
     * Build auxiliary line from points
     */
    public function buildFromPoints(int $bar1, int $bar2, float $level1, float $level2): void {
        $this->startBar = $bar1;
        $this->startLevel = $level1;
        $this->angle = ($level2 - $level1) / ($bar2 - $bar1);
    }

    /**
     * Calculate auxiliary parameters
     */
    public function calculateAuxParameters(array $state): array {
        $v = $state['v'];
        
        if (isset($state['t3\'мп'])) {
            if ($this->low($state['t3\'мп'], $v) > $this->high($state['t2'], $v)) {
                return ['auxP3' => '3\'outofb'];
            }
            
            $levelt2 = $this->high($state['t2'], $v);
            $levelt2broken = false;
            
            for ($i = $state['t3']; $i < $state['t3\'мп']; $i++) {
                if ($this->high($i, $v) > $levelt2) {
                    $levelt2broken = $i;
                    break;
                }
            }
            
            return ['auxP3' => $levelt2broken ? '3\'aftrb' : '3\''];
        }

        return [];
    }
} 