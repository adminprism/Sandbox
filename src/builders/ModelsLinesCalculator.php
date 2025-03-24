<?php
/**
 * ModelsLinesCalculator - Class for handling model lines calculations
 * 
 * This class provides functionality for:
 * - Calculating trend lines and aim lines
 * - Finding line intersections
 * - Validating line breaks and touches
 * - Managing auxiliary lines
 */
class ModelsLinesCalculator {
    /** @var array Chart data */
    private $chart;
    
    /** @var array Current state */
    private $state;
    
    /** @var float Pip size */
    private $pips;

    // Line type constants
    const LINE_TYPE_TREND = 'trend';
    const LINE_TYPE_AIM = 'aim';
    const LINE_TYPE_AUX_TREND = 'aux_trend';
    const LINE_TYPE_AUX_AIM = 'aux_aim';

    /**
     * Constructor
     * 
     * @param array $chart Chart data
     * @param float $pips Pip size
     */
    public function __construct($chart, $pips) {
        $this->chart = $chart;
        $this->pips = $pips;
    }

    /**
     * Sets current state
     * 
     * @param array $state Current state
     */
    public function setState($state) {
        $this->state = $state;
    }

    /**
     * Gets trend line parameters
     * 
     * @return array Line parameters (bar, level, angle)
     */
    public function getTrendLine() {
        $v = $this->state['v'];
        $t3_ = isset($this->state['t3\'']) ? 't3\'' : 't3';
        
        return [
            'bar' => $this->state['t1'],
            'level' => $this->low($this->state['t1'], $v),
            'angle' => ($this->low($this->state[$t3_], $v) - $this->low($this->state['t1'], $v)) 
                      / ($this->state[$t3_] - $this->state['t1'])
        ];
    }

    /**
     * Gets aim line parameters
     * 
     * @return array Line parameters
     */
    public function getAimLine() {
        $v = $this->state['v'];
        $t2_ = isset($this->state['t2\'']) ? 't2\'' : 't2';
        
        return [
            'bar' => $this->state[$t2_],
            'level' => $this->high($this->state[$t2_], $v),
            'angle' => ($this->high($this->state['t4'], $v) - $this->high($this->state[$t2_], $v))
                      / ($this->state['t4'] - $this->state[$t2_])
        ];
    }

    /**
     * Gets auxiliary trend line parameters
     * 
     * @return array Line parameters
     */
    public function getAuxTrendLine() {
        $v = $this->state['v'];
        $t3_ = isset($this->state['t3\'мп']) ? 't3\'мп' : (isset($this->state['t3\'']) ? 't3\'' : 't3');
        
        return [
            'bar' => $this->state[$t3_],
            'level' => $this->low($this->state[$t3_], $v),
            'angle' => ($this->low($this->state['t5'], $v) - $this->low($this->state[$t3_], $v))
                      / ($this->state['t5'] - $this->state[$t3_])
        ];
    }

    /**
     * Calculates line level at specific bar
     * 
     * @param array $line Line parameters
     * @param int $bar Bar number
     * @return float Line level
     */
    public function calculateLineLevel($line, $bar) {
        return $line['level'] + ($bar - $line['bar']) * $line['angle'];
    }

    /**
     * Finds intersection point of two lines
     * 
     * @param array $line1 First line parameters
     * @param array $line2 Second line parameters
     * @return array|false Intersection point or false if parallel
     */
    public function findLinesIntersection($line1, $line2) {
        $price1 = $line1['level'];
        $price2 = $this->calculateLineLevel($line2, $line1['bar']);
        
        if ($price1 == $price2) {
            return ['bar' => $line1['bar'], 'level' => $price1];
        }
        
        if ($line1['angle'] == $line2['angle']) {
            return false; // Lines are parallel
        }
        
        $dy = $line2['angle'] - $line1['angle'];
        $bar = $line1['bar'] + ($price1 - $price2) / $dy;
        
        return [
            'bar' => $bar,
            'level' => $this->calculateLineLevel($line1, $bar)
        ];
    }

    /**
     * Validates if line is broken at specific bar
     * 
     * @param array $line Line parameters
     * @param int $bar Bar number
     * @param string $v Direction ('high'/'low')
     * @return bool True if line is broken
     */
    public function validateLineBreak($line, $bar, $v) {
        return $this->low($bar, $v) <= $this->calculateLineLevel($line, $bar);
    }

    /**
     * Checks if line is touched at specific bar
     * 
     * @param array $line Line parameters
     * @param int $bar Bar number
     * @param string $v Direction
     * @return bool True if line is touched
     */
    public function checkLineTouch($line, $bar, $v) {
        $lineLevel = $this->calculateLineLevel($line, $bar);
        $barLow = $this->low($bar, $v);
        $barHigh = $this->high($bar, $v);
        
        return ($barLow <= $lineLevel && $barHigh >= $lineLevel);
    }

    /**
     * Gets high value for specific bar
     */
    private function high($i, $v) {
        return ($v == 'low') ? $this->chart[$i]['high'] * 1 : $this->chart[$i]['low'] * (-1);
    }

    /**
     * Gets low value for specific bar
     */
    private function low($i, $v) {
        return ($v == 'low') ? $this->chart[$i]['low'] * 1 : $this->chart[$i]['high'] * (-1);
    }
} 