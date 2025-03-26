<?php
namespace App\Builders\Calculator;

/**
 * Cache manager for price calculations
 */
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

    /**
     * Get cached value or calculate new
     */
    public function get(string $key, callable $calculator) {
        // Try memory cache first
        if (isset($this->memoryCache[$key])) {
            $this->cacheStats['hits']++;
            $this->cacheStats['memory_hits']++;
            return $this->memoryCache[$key];
        }

        // Try persistent cache
        if (isset($this->persistentCache[$key])) {
            $this->cacheStats['hits']++;
            $this->cacheStats['persistent_hits']++;
            // Move to memory cache
            $this->addToMemoryCache($key, $this->persistentCache[$key]);
            return $this->persistentCache[$key];
        }

        // Calculate new value
        $this->cacheStats['misses']++;
        $value = $calculator();
        
        // Cache the value
        $this->addToCache($key, $value);
        
        return $value;
    }

    /**
     * Add value to both caches
     */
    private function addToCache(string $key, $value): void {
        $this->addToMemoryCache($key, $value);
        $this->persistentCache[$key] = $value;
    }

    /**
     * Add value to memory cache with size control
     */
    private function addToMemoryCache(string $key, $value): void {
        if (count($this->memoryCache) >= $this->maxMemoryCacheSize) {
            array_shift($this->memoryCache); // Remove oldest entry
        }
        $this->memoryCache[$key] = $value;
    }

    /**
     * Get cache statistics
     */
    public function getStats(): array {
        return $this->cacheStats;
    }

    /**
     * Clear all caches
     */
    public function clear(): void {
        $this->memoryCache = [];
        $this->persistentCache = [];
    }
} 