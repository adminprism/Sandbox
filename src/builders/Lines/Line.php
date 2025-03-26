<?php
namespace App\Builders\Lines;

use App\Builders\Calculator\PriceCalculator;

/**
 * Base abstract class for all chart lines
 */
abstract class Line {
    protected $startBar;
    protected $startLevel;
    protected $angle;
    protected $chart;
    protected $pips;
    protected $calculator;

    public function __construct(array $chart, float $pips) {
        $this->chart = $chart;
        $this->pips = $pips;
        $this->calculator = new PriceCalculator($chart, $pips);
    }

    /**
     * Calculate line level at specific bar
     */
    public function calculateLevel(int $bar): float {
        return $this->startLevel + ($bar - $this->startBar) * $this->angle;
    }

    /**
     * Find intersection with another line
     */
    public function findIntersection(Line $otherLine): ?array {
        if ($this->angle == $otherLine->angle) {
            return null; // Parallel lines
        }

        $price1 = $this->startLevel;
        $price2 = $otherLine->calculateLevel($this->startBar);

        if ($price1 == $price2) {
            return [
                'bar' => $this->startBar,
                'level' => $price1
            ];
        }

        $dy = $otherLine->angle - $this->angle;
        $bar = $this->startBar + ($price1 - $price2) / $dy;

        return [
            'bar' => $bar,
            'level' => $this->calculateLevel($bar)
        ];
    }

    /**
     * Check if price touches line at bar
     */
    public function isTouched(int $bar, string $direction = 'low'): bool {
        $lineLevel = $this->calculateLevel($bar);
        
        if ($direction === 'low') {
            return $this->chart[$bar]['low'] <= $lineLevel;
        }
        return $this->chart[$bar]['high'] >= $lineLevel;
    }

    /**
     * Set line parameters
     */
    public function setParameters(int $startBar, float $startLevel, float $angle): void {
        $this->startBar = $startBar;
        $this->startLevel = $startLevel;
        $this->angle = $angle;
    }

    /**
     * Get high value for bar
     */
    protected function high(int $bar, string $v): float {
        return $this->calculator->getHigh($bar, $v);
    }

    /**
     * Get low value for bar
     */
    protected function low(int $bar, string $v): float {
        return $this->calculator->getLow($bar, $v);
    }

    protected function calculateLineLevel(float $startLevel, int $startBar, float $angle, int $targetBar): float {
        return $this->calculator->calculateLineLevel($startLevel, $startBar, $angle, $targetBar);
    }

    protected function isExtremum(int $bar, string $type): bool {
        return $this->calculator->isExtremum($bar, $type);
    }
} 