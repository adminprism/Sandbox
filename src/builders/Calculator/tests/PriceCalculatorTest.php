<?php
namespace Tests\Calculator;

use PHPUnit\Framework\TestCase;
use App\Builders\Calculator\PriceCalculator;

class PriceCalculatorTest extends TestCase {
    private $calculator;
    private $chart;

    protected function setUp(): void {
        $this->chart = [
            ['high' => 1.2000, 'low' => 1.1900],
            ['high' => 1.2100, 'low' => 1.1950],
            ['high' => 1.2050, 'low' => 1.1850]
        ];
        $this->calculator = new PriceCalculator($this->chart, 0.0001);
    }

    public function testHighValueCalculation(): void {
        $high = $this->calculator->getHigh(1, 'low');
        $this->assertEquals(1.2100, $high);
    }

    public function testLowValueCalculation(): void {
        $low = $this->calculator->getLow(1, 'low');
        $this->assertEquals(1.1950, $low);
    }

    public function testCaching(): void {
        $high1 = $this->calculator->getHigh(1, 'low');
        $high2 = $this->calculator->getHigh(1, 'low');
        
        $stats = $this->calculator->getCacheStats();
        
        $this->assertEquals($high1, $high2);
        $this->assertEquals(1, $stats['misses']);
        $this->assertEquals(1, $stats['hits']);
    }

    public function testCacheClear(): void {
        $this->calculator->getHigh(1, 'low');
        $this->calculator->clearCache();
        $this->calculator->getHigh(1, 'low');
        
        $stats = $this->calculator->getCacheStats();
        $this->assertEquals(2, $stats['misses']);
    }
} 