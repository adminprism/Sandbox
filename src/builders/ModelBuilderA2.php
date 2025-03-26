<?php
/**
 * ModelBuilderA2 - Implementation of Algorithm 2 for model building
 * 
 * This class extends BasicModelBuilder and implements Algorithm 2 specific logic
 */
// require_once 'BasicModelBuilder.php';
require_once __DIR__ . '/BasicModelBuilder.php';

class ModelBuilderA2 extends BasicModelBuilder {
    /**
     * Get algorithm number implementation
     * @return int
     */
    protected function getAlgorithmNumber(): int {
        return 2;
    }

    /**
     * Fix model with Algorithm 2 specific logic
     */
    public function fixModel($name, $wo_t5 = false) {
        if (!isset($this->state['v'])) {
            return $this->state;
        }

        // Add algorithm specific parameters
        $this->state['param']['algorithm'] = self::ALGORITHM_NUM;
        
        // Calculate G3 parameter
        $this->state['param']['G3'] = $this->getModelTrendType_G3();

        // Add cross point calculation
        $LT = $this->getLT();
        $LCs = $this->getLCs();
        $_CP = $this->linesIntersection($LT, $LCs);
        
        if ($_CP && $_CP['bar'] > $this->state['t4']) {
            $this->state['param']['_cross_point'] = round($_CP['bar'], 3) . " (" . round(abs($_CP['level']), 5) . ")";
        }

        $this->state['param']['_points'] = $this->modelPoints($wo_t5);
        
        return parent::fixModel($name, $wo_t5);
    }

    /**
     * Get next step based on current state for Algorithm 2
     */
    protected function getNextStep(): string {
        switch ($this->state['next_step']) {
            case 'A2_step_1':
                return $this->determineA2Step1Transition();
            case 'A2_step_2':
                return $this->determineA2Step2Transition();
            case 'A2_step_3':
                return $this->determineA2Step3Transition();
            default:
                return 'stop';
        }
    }

    /**
     * Determine transition from A2 step 1
     */
    private function determineA2Step1Transition(): string {
        if (!isset($this->state['t3'])) {
            return 'stop';
        }
        return 'A2_step_2';
    }

    /**
     * Determine transition from A2 step 2
     */
    private function determineA2Step2Transition(): string {
        if (!isset($this->state['t4'])) {
            return 'A2_step_1';
        }
        return 'A2_step_3';
    }

    /**
     * Determine transition from A2 step 3
     */
    private function determineA2Step3Transition(): string {
        if (!isset($this->state['t5'])) {
            return 'A2_step_2';
        }
        return 'A2_step_4';
    }

    /**
     * Algorithm 2 specific validation
     */
    protected function validateModel() {
        if (!parent::validateModel()) {
            return false;
        }

        // Additional A2 specific validation
        $v = $this->state['v'];
        
        // Check T3 position
        if (isset($this->state['t3\''])) {
            if ($this->low($this->state['t3\''], $v) < $this->high($this->state['t2'], $v)) {
                $this->state['param']['EAMP3'] = 'EAM3\'';
            } else {
                $this->state['param']['EAMP3'] = 'EAM3\' out of Base';
            }
        } else {
            $this->state['param']['EAMP3'] = 'EAM3';
        }

        return true;
    }

    /**
     * Calculate Algorithm 2 specific parameters
     */
    protected function calculateModelParameters() {
        $state = parent::calculateModelParameters();

        // Add A2 specific calculations
        $v = $this->state['v'];
        
        // Calculate auxiliary parameters
        if (isset($this->state['t3\'мп'])) {
            if ($this->low($this->state['t3\'мп'], $v) > $this->high($this->state['t2'], $v)) {
                $this->state['param']['auxP3'] = '3\'outofb';
            } else {
                $levelt2 = $this->high($this->state['t2'], $v);
                $levelt2broken = false;
                
                for ($i = $this->state['t3']; $i < $this->state['t3\'мп']; $i++) {
                    if ($this->high($i, $v) > $levelt2) {
                        $levelt2broken = $i;
                        break;
                    }
                }
                
                $this->state['param']['auxP3'] = $levelt2broken ? '3\'aftrb' : '3\'';
            }
        }

        return $this->state;
    }
} 