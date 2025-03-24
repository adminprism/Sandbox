<?php
/**
 * ModelBuilderA2 - Implementation of Algorithm 2 for model building
 * 
 * Simplified version of ModelBuilderA1 that maintains core algorithm logic
 * while reducing complexity
 */
// require_once 'BasicModelBuilder.php';
require_once __DIR__ . '/BasicModelBuilder.php';

class ModelBuilderA2 extends BasicModelBuilder {
    private const ALGORITHM_NUM = 2;

    public function fixModel($name, $wo_t5 = false) {
        if (!isset($this->state['v'])) {
            return $this->state;
        }

        if (!isset($this->res['Models'][$this->modelNextId])) {
            $this->res['Models'][$this->modelNextId] = [];
        }

        // Создаем новую модель
        $newModel = [
            'v' => $this->state['v'],
            'param' => $this->state['param'] ?? [],
            'status' => $this->state['status'] ?? []
        ];

        // Добавляем имя модели в статус
        $newModel['status'][$name] = 0;

        // Добавляем модель в результаты
        $this->res['Models'][$this->modelNextId][] = $newModel;
        
        return parent::fixModel($name, $wo_t5);
    }

    protected function validateModel() {
        // Специфичная валидация для алгоритма 2
        if (!isset($this->state['v'])) {
            return false;
        }
        
        return parent::validateModel();
    }

    protected function calculateModelParameters() {
        // Специфичные расчеты для алгоритма 2
        if (isset($this->state['param'])) {
            $this->state['param']['algorithm'] = self::ALGORITHM_NUM;
        }
        
        return parent::calculateModelParameters();
    }
} 