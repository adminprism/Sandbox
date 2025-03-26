<?php
namespace App\Builders\Lines;

use App\Builders\Lines\Line;

/**
 * Trend line implementation
 */
class TrendLine extends Line {
    /**
     * Build trend line from two points
     */
    public function buildFromPoints(int $bar1, int $bar2, float $level1, float $level2): void {
        $this->startBar = $bar1;
        $this->startLevel = $level1;
        $this->angle = ($level2 - $level1) / ($bar2 - $bar1);
    }

    /**
     * Validate trend line between points
     */
    public function validateBetweenPoints(int $startBar, int $endBar, string $direction = 'low'): bool {
        for ($i = $startBar + 1; $i < $endBar; $i++) {
            if ($this->isTouched($i, $direction)) {
                return false;
            }
        }
        return true;
    }
} 