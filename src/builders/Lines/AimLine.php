<?php
namespace App\Builders\Lines;

use App\Builders\Lines\Line;

/**
 * Aim line implementation
 */
class AimLine extends Line {
    /**
     * Build aim line from two points
     */
    public function buildFromPoints(int $bar1, int $bar2, float $level1, float $level2): void {
        $this->startBar = $bar1;
        $this->startLevel = $level1;
        $this->angle = ($level2 - $level1) / ($bar2 - $bar1);
    }

    /**
     * Find aim line break
     */
    public function findBreak(int $startBar, int $endBar, string $direction = 'high'): ?int {
        for ($i = $startBar; $i <= $endBar; $i++) {
            if ($this->isTouched($i, $direction)) {
                return $i;
            }
        }
        return null;
    }
} 