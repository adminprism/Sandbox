<?php
namespace Tests\Lines;

use PHPUnit\Framework\TestCase;
use App\Builders\Lines\TrendLine;
use App\Builders\Lines\AimLine;

class LineTest extends TestCase {
    private $chart;
    private $pips;

    protected function setUp(): void {
        $this->chart = [
            ['high' => 1.2000, 'low' => 1.1900],
            ['high' => 1.2100, 'low' => 1.1950],
            ['high' => 1.2050, 'low' => 1.1850]
        ];
        $this->pips = 0.0001;
    }

    public function testTrendLineCalculations(): void {
        $line = new TrendLine($this->chart, $this->pips);
        $line->buildFromPoints(0, 2, 1.1900, 1.1850);
        
        // Test level calculation
        $level1 = $line->calculateLevel(1);
        $level2 = $line->calculateLevel(1);
        
        $this->assertEquals($level1, $level2);
        $this->assertEquals(1.1875, $level1, '', 0.0001);
    }

    public function testAimLineCalculations(): void {
        $line = new AimLine($this->chart, $this->pips);
        $line->buildFromPoints(0, 2, 1.2000, 1.2050);
        
        $break = $line->findBreak(0, 2);
        $this->assertNotNull($break);
        $this->assertEquals(1, $break);
    }

    public function testLineIntersection(): void
    {
        $trendLine = new TrendLine($this->chart, $this->pips);
        $aimLine = new AimLine($this->chart, $this->pips);

        $trendLine->buildFromPoints(0, 2, 1.1900, 1.1850);
        $aimLine->buildFromPoints(0, 2, 1.2000, 1.2050);

        $intersection = $trendLine->findIntersection($aimLine);
        
        $this->assertNotNull($intersection);
        $this->assertArrayHasKey('bar', $intersection);
        $this->assertArrayHasKey('level', $intersection);
    }
} 