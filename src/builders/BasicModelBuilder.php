<?php
/**
 * BasicModelBuilder - Base class for model building algorithms
 * 
 * This class provides common functionality for all model builders including:
 * - Chart data analysis
 * - Line calculations
 * - Model validation
 * - Logging functionality
 */
require_once __DIR__ . '/ModelsLinesCalculator.php';
require_once __DIR__ . '/StateManager.php';

abstract class BasicModelBuilder {
    protected $state;
    protected $res;
    protected $modelNextId;
    protected $maxBar4Split;
    protected $curSplit;
    protected $pips;
    protected $chart;
    protected $linesCalculator;
    protected $stateManager;

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
        
        $this->linesCalculator = new ModelsLinesCalculator($this->chart, $this->pips);
        $this->linesCalculator->setState($this->state);
        
        $this->stateManager = new StateManager(
            $res,
            $modelNextId,
            $maxBar4Split, 
            $curSplit,
            $Chart,
            $pips
        );
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
        return $this->linesCalculator->getTrendLine();
    }

    protected function getLCs() {
        return $this->linesCalculator->getAimLine();
    }

    protected function linesIntersection($line1, $line2) {
        return $this->linesCalculator->findLinesIntersection($line1, $line2);
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
        if (!$this->stateManager->validateModelState($this->state)) {
            return $this->state;
        }

        $this->state = $this->stateManager->calculateModelParameters($this->state);
        $this->state = $this->stateManager->fixModel($this->state, $name, $wo_t5);
        
        return $this->state;
    }

    protected function validateModel() {
        return true;
    }

    protected function calculateModelParameters() {
        return $this->state;
    }

    protected function lineLevel($line, $bar) {
        return $this->linesCalculator->calculateLineLevel($line, $bar);
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
        $this->state = $this->stateManager->logState($this->state, $message);
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

    protected function setState($state) {
        $this->state = $state;
        $this->linesCalculator->setState($state);
    }

    protected function clearState($state, $keys) {
        return $this->stateManager->clearState($state, $keys);
    }

    protected function checkUnique($state, $keys) {
        return $this->stateManager->checkUnique($state, $keys); 
    }

    /**
     * Handle state transitions
     */
    protected function transitionState(string $nextStep, array $params = []): void {
        $this->state = $this->stateManager->handleStateTransition(
            $this->state,
            $nextStep,
            $params
        );
    }

    /**
     * Get algorithm number
     * @return int
     */
    abstract protected function getAlgorithmNumber(): int;

    /**
     * Process current state
     */
    public function processState(): array {
        if (!isset($this->state['next_step'])) {
            return $this->state;
        }

        // Validate state transition
        if (!$this->stateManager->validateStateTransition(
            $this->state,
            $this->state['next_step'],
            $this->getNextStep()
        )) {
            $this->myLog("Invalid state transition attempted");
            return $this->state;
        }

        // Process algorithm-specific state
        $this->state = $this->stateManager->handleAlgorithmState(
            $this->state,
            $this->getAlgorithmNumber()
        );

        return $this->state;
    }

    /**
     * Get next step based on current state
     */
    protected function getNextStep(): string {
        // Override in specific implementations
        return 'stop';
    }
} 