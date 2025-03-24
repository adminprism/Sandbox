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
    private const ALGORITHM_NUM = 1;

    public function fixModel($name, $wo_t5 = false) {
        static $cnt_fix = 0;
        $cnt_fix++;
        $model = [];

        // Определяем G3
        $this->state['param']['G3'] = $this->getModelTrendType_G3();

        // Добавляем _CROSS_POINT
        $LT = $this->getLT();
        $LCs = $this->getLCs();
        $_CP = $this->linesIntersection($LT, $LCs);
        
        if ($_CP && $_CP['bar'] > $this->state['t4']) {
            $this->state['param']['_cross_point'] = round(" " . $_CP['bar'], 3) . " (" . round(" " . abs($_CP['level']), 5) . ")";
        }

        if ($name) {
            $this->state['status'][$name] = 0;
        }

        $this->state['param']['_points'] = $newModelPoints = $this->modelPoints($wo_t5);
        $this->state = $this->myLog("!!! Фиксируем модель [$name]: $newModelPoints " . ($wo_t5 ? "ФИКСАЦИЯ БЕЗ УЧЕТА Т5!!!" : ""));

        static $field_names = [
            'v', 'alt_old', 'draw_flag', 'split', 'status', 'param', 
            '3', '2', 't1', 't2', 't2\'', 't3-', 't3', 't3\'', 
            't3\'мп', 't3\'мп5\'', 't4', 't5', 't5\''
        ];

        foreach ($field_names as $pv) {
            if (isset($this->state[$pv])) {
                $model[$pv] = $this->state[$pv];
            }
        }

        $model['param']['max_bar'] = $this->maxBar4Split[$this->curSplit];

        $t1 = $this->state['t1'];
        
        if (!isset($this->res['Models'][$t1])) {
            $model['id'] = $this->modelNextId++;
            $this->res['Models'][$t1] = [$model];
        } else {
            $isDublicateFound = false;
            foreach ($this->res['Models'][$t1] as $pk => $pv) {
                if ($pv['param']['_points'] == $newModelPoints) {
                    $isDublicateFound = $pk;
                    break;
                }
            }

            if ($isDublicateFound === false) {
                $model['id'] = $this->modelNextId++;
                $this->res['Models'][$t1][] = $model;
            } else {
                if (($this->state['draw_flag'] ?? false) === true) {
                    $this->state = $this->myLog("Попытка повторной фиксации с draw_flag заблокирована");
                    return $this->state;
                }
                $model['id'] = $this->res['Models'][$t1][$isDublicateFound]['id'];
                $this->res['Models'][$t1][$isDublicateFound] = $model;
            }
        }

        return $this->state;
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
} 