<?php
namespace App\Builders\Calculator;

/**
 * Class for optimizing common price calculations
 */
class PriceCalculator {
    private $chart;
    private $pips;
    private $cache;

    public function __construct(array $chart, float $pips) {
        $this->chart = $chart;
        $this->pips = $pips;
        $this->cache = new PriceCache();
    }

    /**
     * Get high value with caching
     */
    public function getHigh(int $bar, string $v): float {
        return $this->cache->get("high_{$bar}_{$v}", function() use ($bar, $v) {
            return $v == 'low' ? 
                $this->chart[$bar]['high'] : 
                -$this->chart[$bar]['low'];
        });
    }

    /**
     * Get low value with caching
     */
    public function getLow(int $bar, string $v): float {
        return $this->cache->get("low_{$bar}_{$v}", function() use ($bar, $v) {
            return $v == 'low' ? 
                $this->chart[$bar]['low'] : 
                -$this->chart[$bar]['high'];
        });
    }

    /**
     * Calculate line angle
     */
    public function calculateAngle(float $level1, float $level2, int $bar1, int $bar2): float {
        return ($level2 - $level1) / ($bar2 - $bar1);
    }

    /**
     * Calculate and cache line level
     */
    public function calculateLineLevel(float $startLevel, int $startBar, float $angle, int $targetBar): float {
        $cacheKey = "line_level_{$startLevel}_{$startBar}_{$angle}_{$targetBar}";
        
        return $this->cache->get($cacheKey, function() use ($startLevel, $startBar, $angle, $targetBar) {
            return $startLevel + ($targetBar - $startBar) * $angle;
        });
    }

    /**
     * Find lines intersection
     */
    public function findIntersection(array $line1, array $line2): ?array {
        if ($line1['angle'] == $line2['angle']) {
            return null; // Parallel lines
        }

        $dy = $line2['angle'] - $line1['angle'];
        $bar = $line1['bar'] + ($line1['level'] - $line2['level']) / $dy;

        return [
            'bar' => $bar,
            'level' => $this->calculateLineLevel(
                $line1['level'], 
                $line1['bar'], 
                $line1['angle'], 
                $bar
            )
        ];
    }

    /**
     * Find extremum with caching
     */
    public function isExtremum(int $bar, string $type): bool {
        $cacheKey = "extremum_{$bar}_{$type}";
        
        return $this->cache->get($cacheKey, function() use ($bar, $type) {
            if ($bar <= 0 || $bar >= count($this->chart) - 1) {
                return false;
            }

            if ($type == 'low') {
                return $this->chart[$bar]['low'] < $this->chart[$bar-1]['low'] && 
                       $this->chart[$bar]['low'] < $this->chart[$bar+1]['low'];
            }
            
            return $this->chart[$bar]['high'] > $this->chart[$bar-1]['high'] && 
                   $this->chart[$bar]['high'] > $this->chart[$bar+1]['high'];
        });
    }

    /**
     * Calculate distance between points
     */
    public function calculateDistance(array $point1, array $point2): array {
        return [
            'price' => abs($point2['level'] - $point1['level']),
            'time' => abs($point2['bar'] - $point1['bar'])
        ];
    }

    /**
     * Get cache statistics
     */
    public function getCacheStats(): array {
        return $this->cache->getStats();
    }

    /**
     * Clear calculation cache
     */
    public function clearCache(): void {
        $this->cache->clear();
    }
} 