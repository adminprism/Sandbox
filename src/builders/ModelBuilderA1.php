<?php
/**
 * ModelBuilderA1 - Implementation of Algorithm 1 for model building
 * 
 * This class extends the BasicModelBuilder class and implements the specific
 * logic for Algorithm 1. It includes:
 * - Model fixing
 * - Model validation
 * - Parameter calculation
 */
// require_once 'BasicModelBuilder.php';
require_once __DIR__ . '/BasicModelBuilder.php';

class ModelBuilderA1 extends BasicModelBuilder {
    /**
     * Get algorithm number implementation
     * @return int
     */
    protected function getAlgorithmNumber(): int {
        return 1;
    }

    public function fixModel($name, $wo_t5 = false) {
        // Определяем G3
        $this->state['param']['G3'] = $this->getModelTrendType_G3();

        // Добавляем _CROSS_POINT
        $LT = $this->getLT();
        $LCs = $this->getLCs();
        $_CP = $this->linesIntersection($LT, $LCs);
        
        if ($_CP && $_CP['bar'] > $this->state['t4']) {
            $this->state['param']['_cross_point'] = round(" " . $_CP['bar'], 3) . " (" . round(" " . abs($_CP['level']), 5) . ")";
        }

        $this->state['param']['_points'] = $this->modelPoints($wo_t5);
        
        return parent::fixModel($name, $wo_t5);
    }

    protected function validateModel() {
        // Проверка наличия обязательных точек
        $requiredPoints = ['t1', 't2', 't3', 't4'];
        foreach ($requiredPoints as $point) {
            if (!isset($this->state[$point])) {
                return false;
            }
        }

        // Проверка последовательности точек
        if ($this->state['t1'] >= $this->state['t2'] ||
            $this->state['t2'] >= $this->state['t3'] ||
            $this->state['t3'] >= $this->state['t4']) {
            return false;
        }

        // Проверка уровней
        $v = $this->state['v'];
        if ($v == 'low') {
            if ($this->low($this->state['t1'], $v) <= $this->low($this->state['t3'], $v) ||
                $this->high($this->state['t2'], $v) <= $this->high($this->state['t4'], $v)) {
                return false;
            }
        } else {
            if ($this->high($this->state['t1'], $v) >= $this->high($this->state['t3'], $v) ||
                $this->low($this->state['t2'], $v) >= $this->low($this->state['t4'], $v)) {
                return false;
            }
        }

        return true;
    }

    protected function calculateModelParameters() {
        $v = $this->state['v'];
        $K = ($v == 'low') ? 1 : -1;

        // Расчет базовых параметров
        $this->state['param']['height'] = round(abs(
            $this->high($this->state['t2'], $v) - 
            $this->low($this->state['t3'], $v)
        ) / $this->pips, 1);

        $this->state['param']['width'] = $this->state['t4'] - $this->state['t1'];

        // Расчет углов
        $this->state['param']['LT_angle'] = round(
            atan2(
                $this->low($this->state['t3'], $v) - $this->low($this->state['t1'], $v),
                $this->state['t3'] - $this->state['t1']
            ) * 180 / M_PI,
            2
        );

        $this->state['param']['LC_angle'] = round(
            atan2(
                $this->high($this->state['t4'], $v) - $this->high($this->state['t2'], $v),
                $this->state['t4'] - $this->state['t2']
            ) * 180 / M_PI,
            2
        );

        return $this->state;
    }

    protected function getNextT1State() {
        return $this->stateManager->getNextT1State($this->state);
    }

    /**
     * Get next step based on current state
     */
    protected function getNextStep(): string {
        switch ($this->state['next_step']) {
            case 'step_1':
                return $this->determineStep1Transition();
            case 'step_2':
                return $this->determineStep2Transition();
            case 'step_3':
                return $this->determineStep3Transition();
            default:
                return 'stop';
        }
    }

    /**
     * Determine transition from step 1
     */
    private function determineStep1Transition(): string {
        if (!isset($this->state['t1'])) {
            return 'stop';
        }
        return 'step_2';
    }

    /**
     * Determine transition from step 2
     */
    private function determineStep2Transition(): string {
        if (!isset($this->state['t2'])) {
            return 'step_1';
        }
        return 'step_3';
    }

    /**
     * Determine transition from step 3
     */
    private function determineStep3Transition(): string {
        if (!isset($this->state['t3'])) {
            return 'step_2';
        }
        return 'step_4';
    }
} 