<?php
namespace App\Builders\Lines;

/**
 * Factory for creating different types of lines
 */
class LinesFactory {
    private $chart;
    private $pips;

    public function __construct(array $chart, float $pips) {
        $this->chart = $chart;
        $this->pips = $pips;
    }

    public function createTrendLine(): TrendLine {
        return new TrendLine($this->chart, $this->pips);
    }

    public function createAimLine(): AimLine {
        return new AimLine($this->chart, $this->pips);
    }

    public function createAuxiliaryLine(): AuxiliaryLine {
        return new AuxiliaryLine($this->chart, $this->pips);
    }

    public function createTangentLine(): TangentLine {
        return new TangentLine($this->chart, $this->pips);
    }
} 