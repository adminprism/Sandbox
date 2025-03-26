<?php
namespace App\Builders\Calculator;

class PriceCache {
    private $memoryCache = [];
    private $persistentCache = [];
    private $cacheStats = [
        'hits' => 0,
        'misses' => 0,
        'memory_hits' => 0,
        'persistent_hits' => 0
    ];
    private $maxMemoryCacheSize = 1000;

    public function get(string $key, callable $calculator) {
        if (isset($this->memoryCache[$key])) {
            $this->cacheStats['hits']++;
            $this->cacheStats['memory_hits']++;
            return $this->memoryCache[$key];
        }

        if (isset($this->persistentCache[$key])) {
            $this->cacheStats['hits']++;
            $this->cacheStats['persistent_hits']++;
            $this->addToMemoryCache($key, $this->persistentCache[$key]);
            return $this->persistentCache[$key];
        }

        $this->cacheStats['misses']++;
        $value = $calculator();
        $this->addToCache($key, $value);
        
        return $value;
    }

    private function addToCache(string $key, $value): void {
        $this->addToMemoryCache($key, $value);
        $this->persistentCache[$key] = $value;
    }

    private function addToMemoryCache(string $key, $value): void {
        if (count($this->memoryCache) >= $this->maxMemoryCacheSize) {
            array_shift($this->memoryCache);
        }
        $this->memoryCache[$key] = $value;
    }

    public function getStats(): array {
        return $this->cacheStats;
    }

    public function clear(): void {
        $this->memoryCache = [];
        $this->persistentCache = [];
    }
} 