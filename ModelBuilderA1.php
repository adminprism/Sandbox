<?php

class ModelBuilderA1 {
    private $state;
    private $res;
    private $modelNextId;
    private $maxBar4Split;
    private $curSplit;
    private $pips;
    private $lastT3fromT1;
    private $splitCnt;
    private $chart;

    public function __construct($initialState, &$res, $modelNextId, $maxBar4Split, $curSplit, $pips) {
        global $Chart;
        
        $this->state = $initialState;
        $this->res = &$res;
        $this->modelNextId = $modelNextId;
        $this->maxBar4Split = $maxBar4Split;
        $this->curSplit = $curSplit;
        $this->pips = $pips;
        $this->lastT3fromT1 = [];
        $this->splitCnt = 0;
        $this->chart = $Chart;
    }

    public function fixModel($name, $wo_t5 = false) {
        static $cnt_fix = 0;
        $cnt_fix++;
        $model = [];

        // Определяем G3
        /* The above PHP code is setting the value of the 'G3' key in the state parameter to the result
        of the getModelTrendType_G3() method. */
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

    private function getModelTrendType_G3() {
        $v = $this->state['v'];
        $t1_level = $this->low($this->state['t1'], $v);
        $t2_level = $this->high($this->state['t2'], $v);
        $t2_broken = false;
        $t1_broken = false;
        $limitBar = $this->state['t1'] - CALC_G3_DEPTH;
        
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

    private function modelPoints($wo_t5 = false) {
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

    private function getLT() {
        $v = $this->state['v'];
        $t3_ = (isset($this->state['t3\''])) ? 't3\'' : 't3';
        return [
            'bar' => $this->state['t1'],
            'level' => $this->low($this->state['t1'], $v),
            'angle' => ($this->low($this->state[$t3_], $v) - $this->low($this->state['t1'], $v)) 
                      / ($this->state[$t3_] - $this->state['t1'])
        ];
    }

    private function getLCs() {
        $v = $this->state['v'];
        $t2_ = (isset($this->state['t2\''])) ? 't2\'' : 't2';
        return [
            'bar' => $this->state[$t2_],
            'level' => $this->high($this->state[$t2_], $v),
            'angle' => ($this->high($this->state['t4'], $v) - $this->high($this->state[$t2_], $v)) 
                      / ($this->state['t4'] - $this->state[$t2_])
        ];
    }

    /**
     * Вычисляет точку пересечения двух линий
     * @param array $line1 Первая линия ['bar' => int, 'level' => float, 'angle' => float]
     * @param array $line2 Вторая линия ['bar' => int, 'level' => float, 'angle' => float]
     * @return array|false Точка пересечения ['bar' => float, 'level' => float] или false если линии не пересекаются
     */
    private function linesIntersection($line1, $line2) {
        // Проверяем параллельность линий
        if (abs($line1['angle'] - $line2['angle']) < 0.000000001) {
            return false; // Линии параллельны
        }

        // Вычисляем точку пересечения
        // y1 = k1*x + b1
        // y2 = k2*x + b2
        // На пересечении y1 = y2:
        // k1*x + b1 = k2*x + b2
        // x = (b2 - b1)/(k1 - k2)
        
        $k1 = $line1['angle'];
        $k2 = $line2['angle'];
        
        // Находим b1 и b2 из уравнения y = kx + b
        // b = y - kx
        $b1 = $line1['level'] - $k1 * $line1['bar'];
        $b2 = $line2['level'] - $k2 * $line2['bar'];
        
        // Вычисляем точку пересечения
        $x = ($b2 - $b1) / ($k1 - $k2);
        $y = $k1 * $x + $b1;
        
        // Проверяем валидность результата
        if (!is_finite($x) || !is_finite($y)) {
            return false;
        }
        
        return [
            'bar' => $x,
            'level' => $y
        ];
    }

    /**
     * Проверяет, пересекает ли цена заданную линию на интервале баров
     * @param array $line Линия для проверки
     * @param int $startBar Начальный бар
     * @param int $endBar Конечный бар
     * @param string $v Направление ('low' или 'high')
     * @return bool|int Номер бара пересечения или false
     */
    private function checkLineIntersection($line, $startBar, $endBar, $v) {
        for ($i = $startBar; $i <= $endBar; $i++) {
            $lineLevel = $this->lineLevel($line, $i);
            if ($v == 'low') {
                if ($this->low($i, $v) < $lineLevel) {
                    return $i;
                }
            } else {
                if ($this->high($i, $v) > $lineLevel) {
                    return $i;
                }
            }
        }
        return false;
    }

    /**
     * Проверяет, касается ли цена заданной линии на интервале баров
     * @param array $line Линия для проверки
     * @param int $startBar Начальный бар
     * @param int $endBar Конечный бар
     * @param string $v Направление ('low' или 'high')
     * @return bool|int Номер бара касания или false
     */
    private function checkLineTouch($line, $startBar, $endBar, $v) {
        for ($i = $startBar; $i <= $endBar; $i++) {
            $lineLevel = $this->lineLevel($line, $i);
            if ($v == 'low') {
                if (abs($this->low($i, $v) - $lineLevel) < 0.000000001) {
                    return $i;
                }
            } else {
                if (abs($this->high($i, $v) - $lineLevel) < 0.000000001) {
                    return $i;
                }
            }
        }
        return false;
    }

    /**
     * Система логирования
     */
    private function myLog($message) {
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

        if (SHOW_LOG_STATISTICS) {
            $key = $caller['file'] . ':' . $caller['line'];
            if (!isset($this->res['FlatLog_Statistics'][$key])) {
                $this->res['FlatLog_Statistics'][$key] = 1;
            } else {
                $this->res['FlatLog_Statistics'][$key]++;
            }
        }

        return $this->state;
    }

    /**
     * Логирование для выбранного бара
     */
    private function myLog_selected_bar($message) {
        if (!isset($this->state['curBar'])) {
            return;
        }

        $this->res['log_selected_bar'][] = [
            'bar' => $this->state['curBar'],
            'message' => $message,
            'time' => microtime(true)
        ];
    }

    /**
     * Логирование начала нового шага алгоритма
     */
    private function myLog_start($step) {
        $curBar = $this->state['curBar'];
        $v = $this->state['v'];
        
        $points = [];
        if (isset($this->state['t2'])) $points[] = 't2';
        if (isset($this->state['t3'])) $points[] = 't3';
        if (isset($this->state['t4'])) $points[] = 't4';
        if (isset($this->state['t5'])) $points[] = 't5';
        if (isset($this->state['conf_t4'])) $points[] = 'conf_t4';

        $pointsStr = implode('-', $points);
        $txt = "*** step $step ($curBar $v) t1" . ($pointsStr ? "-$pointsStr" : "");

        return $this->myLog($txt);
    }

    /**
     * Форматирование записи лога
     */
    private function formatLogEntry($entry) {
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

    /**
     * Очистка состояния модели
     */
    private function clearState($fieldsToKeep = "") {
        $staticFields = explode(',', KEYS_LIST_STATIC);
        $keepFields = array_merge($staticFields, explode(',', $fieldsToKeep));
        
        foreach ($this->state as $key => $value) {
            if (!in_array($key, $keepFields)) {
                unset($this->state[$key]);
            }
        }
        
        return $this->state;
    }

    // Геттеры и сеттеры для доступа к protected полям
    public function getState() {
        return $this->state;
    }

    public function setState($state) {
        $this->state = $state;
    }

    /**
     * Получает значение максимума цены для указанного бара
     */
    private function high($bar, $v, $line = null) {
        if ($v == 'low') {
            return $this->chart[$bar]['high'];
        }
        return -$this->chart[$bar]['low'];
    }

    /**
     * Получает значение минимума цены для указанного бара
     */
    private function low($bar, $v, $line = null) {
        if ($v == 'low') {
            return $this->chart[$bar]['low'];
        }
        return -$this->chart[$bar]['high'];
    }

    /**
     * Вычисляет уровень линии на определенном баре
     */
    private function lineLevel($line, $bar) {
        return $line['level'] + ($bar - $line['bar']) * $line['angle'];
    }

    /**
     * Проверяет, является ли бар экстремумом
     */
    private function is_extremum($bar, $v) {
        if ($v == 'low') {
            return $this->chart[$bar]['low'] < $this->chart[$bar-1]['low'] && 
                   $this->chart[$bar]['low'] < $this->chart[$bar+1]['low'];
        }
        return $this->chart[$bar]['high'] > $this->chart[$bar-1]['high'] && 
               $this->chart[$bar]['high'] > $this->chart[$bar+1]['high'];
    }

    /**
     * Возвращает противоположное направление
     */
    private function not_v($v) {
        return $v == 'low' ? 'high' : 'low';
    }

    /**
     * Проверяет уникальность модели
     */
    private function checkUnique($fields = "") {
        $key = "";
        $fieldsArr = explode(',', $fields);
        
        foreach ($fieldsArr as $field) {
            if (isset($this->state[$field])) {
                $key .= $field . ":" . $this->state[$field] . ";";
            }
        }
        
        if (!isset($this->res['unique_check'])) {
            $this->res['unique_check'] = [];
        }
        
        if (isset($this->res['unique_check'][$key])) {
            return "Дубликат модели найден: " . $key;
        }
        
        $this->res['unique_check'][$key] = 1;
        return false;
    }

    /**
     * Переход к следующей T1
     */
    private function next_T1() {
        if ($this->state['mode'] == 'selected') {
            $this->state = $this->myLog("Завершение ветки - mode=selected = изменение t1 запрещено");
            $this->state['next_step'] = 'stop';
            return $this->state;
        }

        if ($this->state['t1'] < 3) {
            $this->state = $this->myLog("Завершение ветки - дошли до начала графика");
            $this->state['next_step'] = 'stop';
            return $this->state;
        }

        if ($this->state['mode'] == 'last') {
            $v = $this->state['v'];
            foreach ($this->res['Models'] as $models) {
                foreach ($models as $model) {
                    if ($model['v'] == $v) {
                        $this->state = $this->myLog("Завершение ветки - mode=last и уже есть зафиксированные модели");
                        $this->state['next_step'] = 'stop';
                        return $this->state;
                    }
                }
            }
        }

        $this->state['curBar'] = $this->state['t1'] - 1;
        $this->state['next_step'] = 'step_1';
        $this->state = $this->clearState("t1");
        $this->state['param'] = [];
        $this->state['status'] = [];
        
        return $this->state;
    }

    /**
     * Проверяет возможность построения модели
     */
    private function validateModel() {
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

    /**
     * Рассчитывает параметры модели
     */
    private function calculateModelParameters() {
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