<?php
require_once __DIR__ . '/../src/builders/StateManager.php';

class StateManagerTest {
    private $stateManager;
    private $res = [];
    private $chart = [];
    
    public function __construct() {
        $this->stateManager = new StateManager(
            $this->res,
            1, // modelNextId
            [0 => 100], // maxBar4Split
            0, // curSplit
            $this->chart,
            0.00001 // pips
        );
    }

    public function testStateTransitions() {
        // Test initial state
        $state = $this->stateManager->initializeState(['mode' => 'all']);
        assert($state['next_step'] === 'step_1', "Initial step should be step_1");

        // Test state transition
        $state = $this->stateManager->handleStateTransition($state, 'step_2', ['t1' => 10]);
        assert($state['next_step'] === 'step_2', "State should transition to step_2");
        assert($state['t1'] === 10, "State should contain new parameters");

        // Test validation
        $isValid = $this->stateManager->validateStateTransition($state, 'step_1', 'step_2');
        assert($isValid === true, "Transition should be valid");

        echo "All tests passed!\n";
    }

    public function testAlgorithm2Transitions() {
        // Test initial A2 state
        $state = $this->stateManager->initializeState(['mode' => 'all']);
        $state['next_step'] = 'A2_step_1';
        assert($state['next_step'] === 'A2_step_1', "Initial A2 step should be A2_step_1");

        // Test A2 state transition
        $state = $this->stateManager->handleStateTransition($state, 'A2_step_2', ['t3' => 10]);
        assert($state['next_step'] === 'A2_step_2', "State should transition to A2_step_2");
        assert($state['t3'] === 10, "State should contain T3 parameter");

        // Test A2 validation
        $isValid = $this->stateManager->validateStateTransition($state, 'A2_step_1', 'A2_step_2');
        assert($isValid === true, "A2 transition should be valid");

        echo "Algorithm 2 tests passed!\n";
    }

    public function runAllTests() {
        $this->testStateTransitions();
        $this->testAlgorithm2Transitions();
    }
}

// Run tests
$test = new StateManagerTest();
$test->runAllTests(); 