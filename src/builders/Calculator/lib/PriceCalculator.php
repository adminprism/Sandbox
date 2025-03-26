<?php
namespace App\Builders\Calculator;

class PriceCalculator {
    private $chart;
    private $pips;
    private $cache;

    public function __construct(array $chart, float $pips) {
        $this->chart = $chart;
        $this->pips = $pips;
        $this->cache = new PriceCache();
    }

    public function getHigh(int $bar, string $v): float {
        return $this->cache->get("high_{$bar}_{$v}", function() use ($bar, $v) {
            return $v == 'low' ? 
                $this->chart[$bar]['high'] : 
                -$this->chart[$bar]['low'];
        });
    }

    public function getLow(int $bar, string $v): float {
        return $this->cache->get("low_{$bar}_{$v}", function() use ($bar, $v) {
            return $v == 'low' ? 
                $this->chart[$bar]['low'] : 
                -$this->chart[$bar]['high'];
        });
    }

    public function getCacheStats(): array {
        return $this->cache->getStats();
    }

    public function clearCache(): void {
        $this->cache->clear();
    }
} 