<?php

class ModelBuilderA2 {
    private $state;
    private $res;
    private $modelNextId;
    private $maxBar4Split;
    private $curSplit;
    private $pips;
    private $chart;

    // Constants specific to Algorithm 2
    private const ALGORITHM_NUM = 2;
    private const BAR_50 = 50;
    private const BAR_T3_50 = 50;
    private const BAR_T3_150 = 150;
    private const PAR246aux = 0.5;
    private const CALC_G3_DEPTH = 150;
    private const KEYS_LIST_STATIC = "v,mode,curBar,next_step,cnt,flat_log,status,param,split";

    /**
     * Initialize the ModelBuilder with required parameters
     */
    public function __construct($initialState, &$res, $modelNextId, $maxBar4Split, $curSplit, $pips) {
        global $Chart;
        
        $this->state = $initialState;
        $this->res = &$res;
        $this->modelNextId = $modelNextId;
        $this->maxBar4Split = $maxBar4Split; 
        $this->curSplit = $curSplit;
        $this->pips = $pips;
        $this->chart = $Chart;
    }

    /**
     * Fix model and add it to results collection
     */
    public function fixModel($name, $wo_t5 = false) {
        if (!isset($this->state['v'])) {
            return $this->state;
        }

        if (!isset($this->res['Models'][$this->modelNextId])) {
            $this->res['Models'][$this->modelNextId] = [];
        }

        // Create new model entry
        $newModel = [
            'v' => $this->state['v']
        ];

        // Copy parameters and status
        if (isset($this->state['param'])) {
            $newModel['param'] = $this->state['param'];
        }
        if (isset($this->state['status'])) {
            $newModel['status'] = $this->state['status'];
        }

        // Add model name to status
        if (!isset($newModel['status'])) {
            $newModel['status'] = [];
        }
        $newModel['status'][$name] = 0;

        // Add model to results
        $this->res['Models'][$this->modelNextId][] = $newModel;
        
        return $this->state;
    }

    /**
     * Log message with state information
     */
    public function myLog($state, $message) {
        if (isset($state['flat_log'])) {
            $state['flat_log'][] = $this->formatLogEntry($message);
        }
        return $state;
    }

    /**
     * Format log entry with additional information
     */
    public function formatLogEntry($message) {
        $splitInfo = isset($this->state['split']) ? "[{$this->state['split']}] " : "";
        return $splitInfo . $message;
    }

    /**
     * Log start of new algorithm step
     */
    public function myLog_start($state, $step) {
        $curBar = $state['curBar'];
        $v = $state['v'];
        $txt = "*** step $step ($curBar $v) t1";
        $tArr = [];

        // Add existing points to log
        $points = ['t2', 't3', 't4', 't5', 'conf_t4'];
        foreach ($points as $point) {
            if (isset($state[$point])) {
                $tArr[] = $point;
            }
        }

        // Build log message
        foreach ($tArr as $point) {
            $txt .= "-" . $point;
        }

        $s = $state['t1'];
        foreach ($tArr as $point) {
            $s .= "-" . $state[$point];
        }

        return $this->myLog($state, $txt . ": " . $s);
    }

    /**
     * Clear specified keys from state
     */
    public function clearState($state, $keys) {
        $keysToKeep = explode(",", self::KEYS_LIST_STATIC);
        $keysToClear = explode(",", $keys);

        foreach ($keysToClear as $key) {
            if (!in_array($key, $keysToKeep) && isset($state[$key])) {
                unset($state[$key]);
            }
        }

        return $state;
    }

    /**
     * Check if state combination already exists
     */
    public function checkUnique($state, $keys) {
        // Implementation
        return false;
    }

    /**
     * Get high value for bar
     */
    public function high($bar, $v, $line = null) {
        return $v == 'low' ? 
            $this->chart[$bar]['high'] : 
            -$this->chart[$bar]['low'];
    }

    /**
     * Get low value for bar
     */
    public function low($bar, $v) {
        return $v == 'low' ? 
            $this->chart[$bar]['low'] : 
            -$this->chart[$bar]['high'];
    }

    /**
     * Calculate line level at specific bar
     */
    public function lineLevel($line, $bar) {
        return $line['level'] + ($bar - $line['bar']) * $line['angle'];
    }

    /**
     * Check if bar is extremum
     */
    public function is_extremum($bar, $v) {
        if ($bar <= 0 || $bar >= count($this->chart) - 1) {
            return false;
        }

        $prev = $this->chart[$bar - 1];
        $curr = $this->chart[$bar];
        $next = $this->chart[$bar + 1];

        if ($v == 'low') {
            return $curr['low'] < $prev['low'] && $curr['low'] < $next['low'];
        } else {
            return $curr['high'] > $prev['high'] && $curr['high'] > $next['high'];
        }
    }

    /**
     * Get opposite direction
     */
    public function not_v($v) {
        return $v == 'low' ? 'high' : 'low';
    }

    /**
     * Calculate intersection point of two lines
     */
    public function linesIntersection($line1, $line2) {
        if ($line1['angle'] == $line2['angle']) {
            return false;
        }

        $x = ($line2['level'] - $line1['level'] + $line1['angle'] * $line1['bar'] - $line2['angle'] * $line2['bar']) / 
             ($line1['angle'] - $line2['angle']);

        return [
            'bar' => $x,
            'level' => $line1['level'] + ($x - $line1['bar']) * $line1['angle']
        ];
    }

    /**
     * Get line trend
     */
    public function getLT($state) {
        $v = $state['v'];
        $t3_ = (isset($state['t3\''])) ? 't3\'' : 't3';
        return [
            'bar' => $state['t1'],
            'level' => $this->low($state['t1'], $v),
            'angle' => ($this->low($state[$t3_], $v) - $this->low($state['t1'], $v)) / ($state[$t3_] - $state['t1'])
        ];
    }

    /**
     * Get line targets
     */
    public function getLC($state) {
        $v = $state['v'];
        return [
            'bar' => $state['t2'],
            'level' => $this->high($state['t2'], $v),
            'angle' => ($this->high($state['t4'], $v) - $this->high($state['t2'], $v)) / ($state['t4'] - $state['t2'])
        ];
    }
} 