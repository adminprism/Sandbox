# План создания класса ModelsLinesCalculator

## 1. Структура класса

```php
class ModelsLinesCalculator {
    private $chart;
    private $state;
    private $pips;
    
    // Константы для типов линий
    const LINE_TYPE_TREND = 'trend';
    const LINE_TYPE_AIM = 'aim'; 
    const LINE_TYPE_AUX_TREND = 'aux_trend';
}
```

## 2. Основные методы

### 2.1 Конструктор и инициализация
```php
public function __construct($chart, $pips) {
    $this->chart = $chart;
    $this->pips = $pips;
}

public function setState($state) {
    $this->state = $state;
}
```

### 2.2 Методы для работы с линиями

#### Получение линий:
- `getTrendLine()` - линия тренда
- `getAimLine()` - линия целей
- `getAuxTrendLine()` - вспомогательная линия тренда
- `getAuxAimLine()` - вспомогательная линия целей

#### Расчет параметров линий:
- `calculateLineLevel()` - уровень линии на определенном баре
- `findLinesIntersection()` - поиск точки пересечения линий
- `validateLineBreak()` - проверка пробоя линии

### 2.3 Вспомогательные методы
- `getLineAngle()` - расчет угла наклона линии
- `getLineParameters()` - получение параметров линии
- `checkLineTouch()` - проверка касания линии

## 3. Этапы реализации

### Этап 1: Базовая структура
1. Создание класса с основными свойствами
2. Реализация конструктора и setState
3. Добавление констант для типов линий

### Этап 2: Методы получения линий
1. Реализация getTrendLine()
2. Реализация getAimLine()
3. Реализация вспомогательных линий

### Этап 3: Расчетные методы
1. Реализация calculateLineLevel()
2. Реализация findLinesIntersection()
3. Реализация validateLineBreak()

### Этап 4: Вспомогательные функции
1. Реализация getLineAngle()
2. Реализация getLineParameters()
3. Реализация checkLineTouch()

## 4. Пример использования

```php
// Создание калькулятора
$calculator = new ModelsLinesCalculator($chart, $pips);

// Установка состояния
$calculator->setState($state);

// Получение линии тренда
$trendLine = $calculator->getTrendLine();

// Проверка пересечения линий
$intersection = $calculator->findLinesIntersection(
    $calculator->getTrendLine(),
    $calculator->getAimLine()
);

// Проверка пробоя
$isBreak = $calculator->validateLineBreak($trendLine, $bar);
```

## 5. Интеграция

### 5.1 В BasicModelBuilder:
```php
protected $linesCalculator;

public function __construct($state, &$res, $modelNextId, $maxBar4Split, $curSplit, $pips) {
    parent::__construct($state, $res, $modelNextId, $maxBar4Split, $curSplit, $pips);
    $this->linesCalculator = new ModelsLinesCalculator($this->chart, $this->pips);
}
```

### 5.2 В ModelBuilderA1/A2:
```php
protected function calculateModelParameters() {
    $this->linesCalculator->setState($this->state);
    $trendLine = $this->linesCalculator->getTrendLine();
    // ... дальнейшие расчеты
}
```

## 6. Преимущества выделения в отдельный класс

1. Улучшение организации кода
2. Централизация логики работы с линиями
3. Упрощение тестирования
4. Возможность повторного использования
5. Упрощение поддержки и модификации

## 7. Дальнейшее развитие

1. Добавление новых типов линий
2. Расширение функциональности расчетов
3. Добавление валидации параметров
4. Оптимизация производительности
5. Добавление кэширования результатов 