<?php
/**
 * StateManager - Enhanced class for handling model building state management
 * 
 * This class centralizes state management logic from build_models_A1.php 
 * and build_models_A2.php, providing a clean interface for state operations.
 */
class StateManager {
    private $state;
    private $res;
    private $lastT3fromT1 = [];
    private $splitCnt = 1;
    private $modelNextId;
    private $maxBar4Split;
    private $curSplit;
    private $chart;
    private $pips;

    // Constants moved from build_models_common.php
    private const KEYS_LIST_STATIC = "v,mode,curBar,next_step,cnt,flat_log,status,param,split";
    private const BAR_T3_50 = 50;
    private const BAR_T3_150 = 150;
    private const REQUIRED_POINTS = ['t1', 't2', 't3', 't4'];

    public function __construct(&$res, $modelNextId, $maxBar4Split, $curSplit, $chart, $pips) {
        $this->res = &$res;
        $this->modelNextId = $modelNextId;
        $this->maxBar4Split = $maxBar4Split;
        $this->curSplit = $curSplit;
        $this->chart = $chart;
        $this->pips = $pips;
    }

    /**
     * Initialize new state
     */
    public function initializeState(array $params): array {
        $state = [
            'status' => [],
            'split' => 0,
            'mode' => $params['mode'] ?? 'all',
            'curBar' => 0,
            'next_step' => 'step_1',
            'cnt' => 0,
            'flat_log' => [],
            'param' => []
        ];

        if (isset($params['log']) && $params['log'] == 0) {
            unset($state['flat_log']);
        }

        return $state;
    }

    /**
     * Clear state keeping only specified keys
     */
    public function clearState(array $state, string $keys): array {
        $keyList = $keys . "," . self::KEYS_LIST_STATIC;
        $keyArray = explode(",", $keyList);
        
        $toUnset = [];
        foreach ($state as $key => $value) {
            if (!in_array($key, $keyArray)) {
                $toUnset[] = $key;
            }
        }

        foreach ($toUnset as $key) {
            unset($state[$key]);
        }

        if (!isset($state['status'])) {
            $state['status'] = [];
        }
        if (!isset($state['param'])) {
            $state['param'] = [];
        }

        return $state;
    }

    /**
     * Check state uniqueness
     */
    public function checkUnique(array $state, string $keyList): ?string {
        $keys = explode(",", $keyList);
        $ind = $state['next_step'] . ':';
        
        foreach ($keys as $key) {
            $ind .= ' ' . $key . '=' . ($state[$key] ?? 'null');
        }

        if (!isset($this->res['info']['checkUnique'][$ind])) {
            $this->res['info']['checkUnique'][$ind] = 1;
            return null;
        }

        $this->res['info']['checkUnique'][$ind]++;
        return "Uniqueness error: " . $ind . " split= " . $state['split'] . 
               " came from " . $this->res['info']['last_function_ok'];
    }

    /**
     * Create new split state
     */
    public function createSplitState(array $baseState, array $overrides = []): array {
        $newState = $baseState;
        $newState['split'] = $this->splitCnt++;
        $newState['status'] = [];
        $newState['param'] = [];
        
        if (isset($newState['flat_log'])) {
            $newState['flat_log'] = [];
        }

        foreach ($overrides as $key => $value) {
            $newState[$key] = $value;
        }

        return $newState;
    }

    /**
     * Update T3 tracking
     */
    public function updateT3Tracking(int $t1, int $t3): void {
        if (!isset($this->lastT3fromT1[$t1]) || $this->lastT3fromT1[$t1] < $t3) {
            $this->lastT3fromT1[$t1] = $t3;
        }
    }

    /**
     * Get next T1 state
     */
    public function getNextT1State(array $state): array {
        if ($state['mode'] == 'selected') {
            $state['next_step'] = 'stop';
            return $state;
        }

        if ($state['t1'] < 3) {
            $state['next_step'] = 'stop';
            return $state;
        }

        if ($state['mode'] == 'last') {
            $v = $state['v'];
            foreach ($this->res['Models'] as $models) {
                foreach ($models as $model) {
                    if ($model['v'] == $v) {
                        $state['next_step'] = 'stop';
                        return $state;
                    }
                }
            }
        }

        $state['curBar'] = $state['t1'] - 1;
        $state['next_step'] = 'step_1';
        $state = $this->clearState($state, "t1");
        $state['param'] = [];
        $state['status'] = [];

        return $state;
    }

    /**
     * Log state changes
     */
    public function logState(array $state, string $message): array {
        if (!isset($state['flat_log'])) {
            return $state;
        }

        $state['flat_log'][] = '[' . $state['split'] . '] ' . $message;

        if (defined('SHOW_LOG_STATISTICS') && SHOW_LOG_STATISTICS) {
            $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
            $caller = $backtrace[1];
            $key = $caller['file'] . ':' . $caller['line'];
            
            if (!isset($this->res['FlatLog_Statistics'][$key])) {
                $this->res['FlatLog_Statistics'][$key] = 1;
            } else {
                $this->res['FlatLog_Statistics'][$key]++;
            }
        }

        return $state;
    }

    /**
     * Get current split count
     */
    public function getSplitCount(): int {
        return $this->splitCnt;
    }

    /**
     * Get last T3 for given T1
     */
    public function getLastT3(int $t1): ?int {
        return $this->lastT3fromT1[$t1] ?? null;
    }

    /**
     * Validate model state
     */
    public function validateModelState(array $state): bool {
        // Check required points
        foreach (self::REQUIRED_POINTS as $point) {
            if (!isset($state[$point])) {
                return false;
            }
        }

        // Check point sequence
        if ($state['t1'] >= $state['t2'] ||
            $state['t2'] >= $state['t3'] ||
            $state['t3'] >= $state['t4']) {
            return false;
        }

        return true;
    }

    /**
     * Handle model fixing
     */
    public function fixModel(array $state, string $name, bool $wo_t5 = false): array {
        $model = $this->createModelFromState($state);
        $t1 = $state['t1'];
        
        if (!isset($this->res['Models'][$t1])) {
            $model['id'] = $this->modelNextId++;
            $this->res['Models'][$t1] = [$model];
            return $state;
        }

        // Check for duplicates
        $newModelPoints = $state['param']['_points'];
        foreach ($this->res['Models'][$t1] as $pk => $pv) {
            if ($pv['param']['_points'] == $newModelPoints) {
                if (($state['draw_flag'] ?? false) === true) {
                    return $this->logState($state, "Duplicate model fixing with draw_flag blocked");
                }
                $model['id'] = $this->res['Models'][$t1][$pk]['id'];
                $this->res['Models'][$t1][$pk] = $model;
                return $state;
            }
        }

        // Add new model
        $model['id'] = $this->modelNextId++;
        $this->res['Models'][$t1][] = $model;
        return $state;
    }

    /**
     * Create model from state
     */
    private function createModelFromState(array $state): array {
        static $field_names = [
            'v', 'alt_old', 'draw_flag', 'split', 'status', 'param',
            '3', '2', 't1', 't2', 't2\'', 't3-', 't3', 't3\'',
            't3\'мп', 't3\'мп5\'', 't4', 't5', 't5\''
        ];

        $model = [];
        foreach ($field_names as $field) {
            if (isset($state[$field])) {
                $model[$field] = $state[$field];
            }
        }

        $model['param']['max_bar'] = $this->maxBar4Split[$this->curSplit];
        return $model;
    }

    /**
     * Calculate model parameters
     */
    public function calculateModelParameters(array $state): array {
        $v = $state['v'];
        $K = ($v == 'low') ? 1 : -1;

        // Calculate height
        $state['param']['height'] = round(abs(
            $this->getHigh($state['t2'], $v) -
            $this->getLow($state['t3'], $v)
        ) / $this->pips, 1);

        // Calculate width
        $state['param']['width'] = $state['t4'] - $state['t1'];

        // Calculate angles
        $state['param']['LT_angle'] = $this->calculateAngle(
            $state['t1'],
            $state['t3'],
            $this->getLow($state['t1'], $v),
            $this->getLow($state['t3'], $v)
        );

        $state['param']['LC_angle'] = $this->calculateAngle(
            $state['t2'],
            $state['t4'],
            $this->getHigh($state['t2'], $v),
            $this->getHigh($state['t4'], $v)
        );

        return $state;
    }

    /**
     * Calculate angle between points
     */
    private function calculateAngle(int $x1, int $x2, float $y1, float $y2): float {
        return round(
            atan2($y2 - $y1, $x2 - $x1) * 180 / M_PI,
            2
        );
    }

    /**
     * Get high value
     */
    private function getHigh(int $bar, string $v): float {
        return $v == 'low' ? 
            $this->chart[$bar]['high'] : 
            -$this->chart[$bar]['low'];
    }

    /**
     * Get low value
     */
    private function getLow(int $bar, string $v): float {
        return $v == 'low' ? 
            $this->chart[$bar]['low'] : 
            -$this->chart[$bar]['high'];
    }

    /**
     * Add algorithm-specific state handling methods
     */
    public function handleAlgorithmState(array $state, int $algorithmNum): array {
        switch ($algorithmNum) {
            case 1:
                return $this->handleAlgorithm1State($state);
            case 2:
                return $this->handleAlgorithm2State($state);
            default:
                throw new \InvalidArgumentException("Unknown algorithm number: $algorithmNum");
        }
    }

    /**
     * Handle Algorithm 1 specific state logic
     */
    private function handleAlgorithm1State(array $state): array {
        if (!isset($state['next_step'])) {
            return $state;
        }

        switch ($state['next_step']) {
            case 'step_1':
                $state = $this->handleStep1($state);
                break;
            case 'step_2':
                $state = $this->handleStep2($state);
                break;
            case 'step_3':
                $state = $this->handleStep3($state);
                break;
            // Add other steps as needed
        }

        return $state;
    }

    /**
     * Handle Algorithm 2 specific state logic
     */
    private function handleAlgorithm2State(array $state): array {
        if (!isset($state['next_step'])) {
            return $state;
        }

        switch ($state['next_step']) {
            case 'A2_step_1':
                $state = $this->handleA2Step1($state);
                break;
            case 'A2_step_2':
                $state = $this->handleA2Step2($state);
                break;
            case 'A2_step_3':
                $state = $this->handleA2Step3($state);
                break;
            case 'A2_step_4':
                $state = $this->handleA2Step4($state);
                break;
            case 'stop':
                break;
            default:
                $state = $this->logState($state, "Unknown step {$state['next_step']} for Algorithm 2");
                $state['next_step'] = 'stop';
        }

        return $state;
    }

    /**
     * Handle model state transitions
     */
    public function handleStateTransition(array $state, string $nextStep, array $params = []): array {
        $state['next_step'] = $nextStep;
        
        foreach ($params as $key => $value) {
            $state[$key] = $value;
        }

        return $this->logState($state, "State transition to $nextStep");
    }

    /**
     * Add state validation methods
     */
    public function validateStateTransition(array $state, string $fromStep, string $toStep): bool {
        // Validate allowed transitions
        $allowedTransitions = [
            'step_1' => ['step_2', 'stop'],
            'step_2' => ['step_3', 'step_1'],
            'step_3' => ['step_4', 'step_2'],
            // Add other valid transitions
        ];

        if (!isset($allowedTransitions[$fromStep])) {
            return false;
        }

        return in_array($toStep, $allowedTransitions[$fromStep]);
    }

    /**
     * Add Algorithm 2 specific state handling methods
     */
    private function handleA2Step1(array $state): array {
        // Point 1 of Algorithm 2: Search for point 3 and initial point of previous trend
        if (!isset($state['t3'])) {
            $state = $this->searchForT3($state);
        }
        
        if (isset($state['t3'])) {
            $state['next_step'] = 'A2_step_2';
        }
        
        return $state;
    }

    private function handleA2Step2(array $state): array {
        // Point 2: Search for points 4 and 5
        if (!isset($state['t4'])) {
            $state = $this->searchForT4($state);
        }
        
        if (isset($state['t4'])) {
            if (!isset($state['t5'])) {
                $state = $this->searchForT5($state);
            }
            
            if (isset($state['t5'])) {
                $state['next_step'] = 'A2_step_3';
            }
        }
        
        return $state;
    }

    private function handleA2Step3(array $state): array {
        // Point 3: Build trend line and validate
        $state = $this->buildTrendLine($state);
        
        if ($this->validateTrendLine($state)) {
            $state['next_step'] = 'A2_step_4';
        } else {
            $state['next_step'] = 'A2_step_2';
        }
        
        return $state;
    }

    private function handleA2Step4(array $state): array {
        // Point 4: Build trend line and validate
        $state = $this->buildTrendLine($state);
        
        if ($this->validateTrendLine($state)) {
            $state['next_step'] = 'stop';
        } else {
            $state['next_step'] = 'A2_step_2';
        }
        
        return $state;
    }

    /**
     * Search for T3 in Algorithm 2
     */
    private function searchForT3(array $state): array {
        $v = $state['v'];
        $curBar = $state['curBar'];

        while ($curBar > 3) {
            if ($this->isExtremum($curBar, $v)) {
                // Validate potential T3
                if ($this->validatePotentialT3($state, $curBar)) {
                    $state['t3'] = $curBar;
                    $state = $this->logState($state, "Found T3 at bar $curBar");
                    break;
                }
            }
            $curBar--;
        }

        $state['curBar'] = $curBar;
        return $state;
    }

    /**
     * Search for T4 in Algorithm 2
     */
    private function searchForT4(array $state): array {
        $v = $state['v'];
        $curBar = $state['curBar'];

        while ($curBar > $state['t3']) {
            if ($this->isExtremum($curBar, not_v($v))) {
                if ($this->validatePotentialT4($state, $curBar)) {
                    $state['t4'] = $curBar;
                    $state = $this->logState($state, "Found T4 at bar $curBar");
                    break;
                }
            }
            $curBar--;
        }

        $state['curBar'] = $curBar;
        return $state;
    }

    /**
     * Search for T5 in Algorithm 2
     */
    private function searchForT5(array $state): array {
        $v = $state['v'];
        $curBar = $state['curBar'];

        while ($curBar > $state['t4']) {
            if ($this->isExtremum($curBar, $v)) {
                if ($this->validatePotentialT5($state, $curBar)) {
                    $state['t5'] = $curBar;
                    $state = $this->logState($state, "Found T5 at bar $curBar");
                    break;
                }
            }
            $curBar--;
        }

        $state['curBar'] = $curBar;
        return $state;
    }

    /**
     * Validate potential T3 for Algorithm 2
     */
    private function validatePotentialT3(array $state, int $bar): bool {
        $v = $state['v'];
        
        // Check if T3 level is appropriate
        if ($v == 'low') {
            return $this->low($bar, $v) < $this->low($state['t1'], $v);
        }
        return $this->high($bar, $v) > $this->high($state['t1'], $v);
    }

    /**
     * Validate potential T4 for Algorithm 2
     */
    private function validatePotentialT4(array $state, int $bar): bool {
        $v = $state['v'];
        
        // Check if T4 level is appropriate
        if ($v == 'low') {
            return $this->high($bar, $v) > $this->high($state['t3'], $v);
        }
        return $this->low($bar, $v) < $this->low($state['t3'], $v);
    }

    /**
     * Validate potential T5 for Algorithm 2
     */
    private function validatePotentialT5(array $state, int $bar): bool {
        $v = $state['v'];
        
        // Check if T5 level is appropriate
        if ($v == 'low') {
            return $this->low($bar, $v) < $this->low($state['t4'], $v);
        }
        return $this->high($bar, $v) > $this->high($state['t4'], $v);
    }

    /**
     * Build trend line
     */
    private function buildTrendLine(array $state): array {
        $v = $state['v'];
        
        // Calculate trend line parameters
        $t3Point = isset($state['t3\'']) ? 't3\'' : 't3';
        
        $trendLine = [
            'bar' => $state['t1'],
            'level' => $this->low($state['t1'], $v),
            'angle' => ($this->low($state[$t3Point], $v) - $this->low($state['t1'], $v)) / 
                      ($state[$t3Point] - $state['t1'])
        ];

        $state['param']['trendLine'] = $trendLine;
        $state = $this->logState($state, "Built trend line with angle {$trendLine['angle']}");

        return $state;
    }

    /**
     * Validate trend line
     */
    private function validateTrendLine(array $state, bool $isAlgorithm2 = false): bool {
        $v = $state['v'];
        
        // Check basic position requirements
        if ($state['t3'] <= $state['t2'] || $state['t2'] <= $state['t1']) {
            return false;
        }

        // Calculate trend line parameters
        $trendLine = [
            'bar' => $state['t1'],
            'level' => $this->low($state['t1'], $v),
            'angle' => ($this->low($state['t3'], $v) - $this->low($state['t1'], $v)) / 
                      ($state['t3'] - $state['t1'])
        ];

        // Algorithm 2 specific validation
        if ($isAlgorithm2) {
            return $this->validateAlgorithm2TrendLine($state, $trendLine);
        }

        // Check if price crosses trend line between T1 and T3
        for ($i = $state['t1'] + 1; $i < $state['t3']; $i++) {
            $lineLevel = $this->calculateLineLevel($trendLine, $i);
            if ($this->low($i, $v) <= $lineLevel) {
                return false;
            }
        }

        return true;
    }

    /**
     * Algorithm 2 specific trend line validation
     */
    private function validateAlgorithm2TrendLine(array $state, array $trendLine): bool {
        $v = $state['v'];
        
        // Add Algorithm 2 specific validation logic here
        // For example, checking additional conditions for T3' and T5
        if (isset($state['t3\''])) {
            $t3Level = $this->low($state['t3\''], $v);
        } else {
            $t3Level = $this->low($state['t3'], $v);
        }

        // Validate trend line angle
        $angle = ($t3Level - $this->low($state['t1'], $v)) / 
                ($state['t3'] - $state['t1']);

        if (abs($angle) < 0.0001) {
            return false;
        }

        return true;
    }

    /**
     * Calculate line level at specific bar
     */
    private function calculateLineLevel(array $line, int $bar): float {
        return $line['level'] + ($bar - $line['bar']) * $line['angle'];
    }

    /**
     * Handle Step 1 - Point 1 search and confirming extremum
     */
    private function handleStep1(array $state): array {
        $v = $state['v'];
        
        if (!isset($state['t1'])) {
            // Search for extremum
            if ($this->isExtremum($state['curBar'], $v)) {
                $state['t1'] = $state['curBar'];
                $state = $this->logState($state, "Found T1 at bar {$state['curBar']}");
                $state['next_step'] = 'step_2';
            } else {
                $state['curBar']--;
                if ($state['curBar'] < 3) {
                    $state['next_step'] = 'stop';
                }
            }
        }

        return $state;
    }

    /**
     * Handle Step 2 - Point 2 search and validation
     */
    private function handleStep2(array $state): array {
        $v = $state['v'];
        
        if (!isset($state['t2'])) {
            // Search for T2
            if ($this->isExtremum($state['curBar'], not_v($v))) {
                $state['t2'] = $state['curBar'];
                $state = $this->logState($state, "Found T2 at bar {$state['curBar']}");
                
                // Validate T2 position
                if ($this->validateT2Position($state)) {
                    $state['next_step'] = 'step_3';
                } else {
                    unset($state['t2']);
                    $state['curBar']--;
                }
            } else {
                $state['curBar']--;
            }
        }

        return $state;
    }

    /**
     * Handle Step 3 - Point 3 search and trend line validation
     */
    private function handleStep3(array $state): array {
        $v = $state['v'];
        
        if (!isset($state['t3'])) {
            // Search for T3
            if ($this->isExtremum($state['curBar'], $v)) {
                $state['t3'] = $state['curBar'];
                $state = $this->logState($state, "Found T3 at bar {$state['curBar']}");
                
                // Validate trend line
                if ($this->validateTrendLine($state)) {
                    $state['next_step'] = 'step_4';
                } else {
                    unset($state['t3']);
                    $state['curBar']--;
                }
            } else {
                $state['curBar']--;
            }
        }

        return $state;
    }

    /**
     * Check if bar is extremum
     */
    private function isExtremum(int $bar, string $type): bool {
        if ($bar <= 0 || $bar >= count($this->chart) - 1) {
            return false;
        }

        if ($type == 'low') {
            return $this->chart[$bar]['low'] < $this->chart[$bar-1]['low'] && 
                   $this->chart[$bar]['low'] < $this->chart[$bar+1]['low'];
        }
        
        return $this->chart[$bar]['high'] > $this->chart[$bar-1]['high'] && 
               $this->chart[$bar]['high'] > $this->chart[$bar+1]['high'];
    }

    /**
     * Validate T2 position relative to T1
     */
    private function validateT2Position(array $state): bool {
        $v = $state['v'];
        
        if ($v == 'low') {
            return $this->high($state['t2'], $v) > $this->high($state['t1'], $v);
        }
        
        return $this->low($state['t2'], $v) < $this->low($state['t1'], $v);
    }

    /**
     * Get high value for bar
     */
    private function high(int $bar, string $v): float {
        return $v == 'low' ? 
            $this->chart[$bar]['high'] : 
            -$this->chart[$bar]['low'];
    }

    /**
     * Get low value for bar
     */
    private function low(int $bar, string $v): float {
        return $v == 'low' ? 
            $this->chart[$bar]['low'] : 
            -$this->chart[$bar]['high'];
    }

    /**
     * Get opposite direction
     */
    private function not_v(string $v): string {
        return $v == 'low' ? 'high' : 'low';
    }
} 