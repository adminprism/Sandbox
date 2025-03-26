<?php
namespace App\Builders\Lines;

use App\Builders\Lines\Line;

/**
 * Tangent line implementation
 */
class TangentLine extends Line {
    /**
     * Build tangent line at point
     */
    public function buildAtPoint(int $bar, float $level, float $angle): void {
        $this->startBar = $bar;
        $this->startLevel = $level;
        $this->angle = $angle;
    }

    /**
     * Find tangent point
     */
    public function findTangentPoint(int $startBar, int $endBar, string $direction = 'low'): ?array {
        $bestBar = null;
        $bestLevel = null;
        $minDistance = PHP_FLOAT_MAX;

        for ($i = $startBar; $i <= $endBar; $i++) {
            $lineLevel = $this->calculateLevel($i);
            $price = $direction === 'low' ? 
                     $this->chart[$i]['low'] : 
                     $this->chart[$i]['high'];
            
            $distance = abs($lineLevel - $price);
            if ($distance < $minDistance) {
                $minDistance = $distance;
                $bestBar = $i;
                $bestLevel = $price;
            }
        }

        return $bestBar ? [
            'bar' => $bestBar,
            'level' => $bestLevel
        ] : null;
    }
} 