<?php

class BasicModelBuilder {
    protected $state;
    protected $res;
    protected $modelNextId;
    protected $maxBar4Split;
    protected $curSplit;
    protected $pips;
    protected $chart;

    // Общие константы
    protected const KEYS_LIST_STATIC = "v,mode,curBar,next_step,cnt,flat_log,status,param,split";
    protected const BAR_50 = 50;
    protected const BAR_T3_50 = 50;
    protected const BAR_T3_150 = 150;
    protected const PAR246aux = 0.5;
    protected const CALC_G3_DEPTH = 150;

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

    // Базовые методы для работы с графиком
    protected function high($bar, $v) {
        return $v == 'low' ? 
            $this->chart[$bar]['high'] : 
            -$this->chart[$bar]['low'];
    }

    protected function low($bar, $v) {
        return $v == 'low' ? 
            $this->chart[$bar]['low'] : 
            -$this->chart[$bar]['high'];
    }

    // Методы для работы с линиями
    protected function getLT() {
        $v = $this->state['v'];
        $t3_ = (isset($this->state['t3\''])) ? 't3\'' : 't3';
        return [
            'bar' => $this->state['t1'],
            'level' => $this->low($this->state['t1'], $v),
            'angle' => ($this->low($this->state[$t3_], $v) - $this->low($this->state['t1'], $v)) 
                      / ($this->state[$t3_] - $this->state['t1'])
        ];
    }

    protected function getLCs() {
        $v = $this->state['v'];
        $t2_ = (isset($this->state['t2\''])) ? 't2\'' : 't2';
        return [
            'bar' => $this->state[$t2_],
            'level' => $this->high($this->state[$t2_], $v),
            'angle' => ($this->high($this->state['t4'], $v) - $this->high($this->state[$t2_], $v)) 
                      / ($this->state['t4'] - $this->state[$t2_])
        ];
    }

    protected function linesIntersection($line1, $line2) {
        if (abs($line1['angle'] - $line2['angle']) < 0.000000001) {
            return false;
        }

        $k1 = $line1['angle'];
        $k2 = $line2['angle'];
        $b1 = $line1['level'] - $k1 * $line1['bar'];
        $b2 = $line2['level'] - $k2 * $line2['bar'];
        
        $x = ($b2 - $b1) / ($k1 - $k2);
        $y = $k1 * $x + $b1;
        
        if (!is_finite($x) || !is_finite($y)) {
            return false;
        }
        
        return [
            'bar' => $x,
            'level' => $y
        ];
    }

    protected function getModelTrendType_G3() {
        $v = $this->state['v'];
        $t1_level = $this->low($this->state['t1'], $v);
        $t2_level = $this->high($this->state['t2'], $v);
        $t2_broken = false;
        $t1_broken = false;
        $limitBar = $this->state['t1'] - self::CALC_G3_DEPTH;
        
        if ($limitBar < 0) {
            $limitBar = 0;
        }

        for ($i = $this->state['t1'] - 1; $i >= $limitBar; $i--) {
            if ($this->high($i, $v) > $t2_level) {
                $t2_broken = $i;
                break;
            }
            if ($this->low($i, $v) < $t1_level) {
                $t1_broken = $i;
                break;
            }
        }

        if ($t1_broken === false && $t2_broken === false) {
            return "NoData";
        }

        if ($t2_broken === false || $t1_broken > $t2_broken) {
            return "HTModel";
        }

        return "BTModel";
    }

    protected function updateLogStatistics($caller) {
        $key = $caller['file'] . ':' . $caller['line'];
        if (!isset($this->res['FlatLog_Statistics'][$key])) {
            $this->res['FlatLog_Statistics'][$key] = 1;
        } else {
            $this->res['FlatLog_Statistics'][$key]++;
        }
    }

    // Базовая реализация методов, которые могут быть переопределены
    protected function fixModel($name, $wo_t5 = false) {
        if (!$this->validateModel()) {
            return $this->state;
        }

        $this->calculateModelParameters();
        return $this->state;
    }

    protected function validateModel() {
        return true;
    }

    protected function calculateModelParameters() {
        return $this->state;
    }

    protected function lineLevel($line, $bar) {
        return $line['level'] + ($bar - $line['bar']) * $line['angle'];
    }

    protected function is_extremum($bar, $v) {
        if ($bar <= 0 || $bar >= count($this->chart) - 1) {
            return false;
        }

        if ($v == 'low') {
            return $this->chart[$bar]['low'] < $this->chart[$bar-1]['low'] && 
                   $this->chart[$bar]['low'] < $this->chart[$bar+1]['low'];
        }
        return $this->chart[$bar]['high'] > $this->chart[$bar-1]['high'] && 
               $this->chart[$bar]['high'] > $this->chart[$bar+1]['high'];
    }

    protected function not_v($v) {
        return $v == 'low' ? 'high' : 'low';
    }

    // Добавляем методы для логирования в BasicModelBuilder

    protected function myLog($message) {
        if (!isset($this->state['flat_log'])) {
            return $this->state;
        }

        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $caller = $backtrace[1];
        
        $logEntry = [
            'time' => microtime(true),
            'message' => $message,
            'function' => $caller['function'] ?? 'unknown',
            'line' => $caller['line'] ?? 0,
            'state' => [
                'curBar' => $this->state['curBar'] ?? null,
                'next_step' => $this->state['next_step'] ?? null,
                'v' => $this->state['v'] ?? null
            ]
        ];

        $this->state['flat_log'][] = $this->formatLogEntry($logEntry);

        if (defined('SHOW_LOG_STATISTICS') && SHOW_LOG_STATISTICS) {
            $this->updateLogStatistics($caller);
        }

        return $this->state;
    }

    protected function formatLogEntry($entry) {
        $timeStr = date('Y-m-d H:i:s', (int)$entry['time']);
        $msec = sprintf(".%03d", ($entry['time'] - floor($entry['time'])) * 1000);
        
        return sprintf(
            "[%s%s] %s (bar:%s, next:%s, v:%s) - %s:%d",
            $timeStr,
            $msec,
            $entry['message'],
            $entry['state']['curBar'],
            $entry['state']['next_step'],
            $entry['state']['v'],
            $entry['function'],
            $entry['line']
        );
    }

    protected function modelPoints($wo_t5 = false) {
        static $point_names = ['t1', 't2', 't3', 't4', 't5'];
        $points = "";

        foreach ($point_names as $pv) {
            if (!$wo_t5 || $pv !== 't5') {
                if (isset($this->state[$pv])) {
                    $points .= $pv . ":" . $this->state[$pv] . " ";
                }
            }
        }

        return trim($points);
    }
} 