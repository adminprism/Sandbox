# Документация по структуре проекта Model Builders

## Структура директорий 
src/builders/
├── BasicModelBuilder.php
├── ModelBuilderA1.php
├── ModelBuilderA2.php
├── ModelsLinesCalculator.php
└── build_models_common.php
## Описание файлов

### BasicModelBuilder.php
Базовый абстрактный класс для построения моделей. Содержит общую функциональность для всех типов моделей.

#### Основные методы:
- `high()` / `low()` - получение максимальных/минимальных значений для бара
- `lineLevel()` - расчет уровня линии на определенном баре
- `linesIntersection()` - поиск точки пересечения двух линий
- `is_extremum()` - проверка бара на экстремум
- `myLog()` - логирование операций
- `validateModel()` - базовая валидация модели
- `calculateModelParameters()` - расчет базовых параметров модели

### ModelBuilderA1.php
Реализация построителя моделей для алгоритма 1.

#### Ключевые особенности:
- Наследуется от BasicModelBuilder
- Константа `ALGORITHM_NUM = 1`
- Специфическая реализация методов:
  - `fixModel()` - фиксация модели с учетом особенностей алгоритма 1
  - `validateModel()` - проверка последовательности точек и уровней
  - `calculateModelParameters()` - расчет высоты, ширины и углов модели

### ModelBuilderA2.php
Реализация построителя моделей для алгоритма 2.

#### Ключевые особенности:
- Наследуется от BasicModelBuilder
- Константа `ALGORITHM_NUM = 2`
- Упрощенная версия ModelBuilderA1
- Сохраняет основную логику алгоритма без изменений

### ModelsLinesCalculator.php
Handles all line-related calculations for models:
- Calculates trend lines and aim lines
- Finds line intersections
- Validates line breaks and touches
- Manages auxiliary lines

Key features:
```php
// Initialize calculator
$calculator = new ModelsLinesCalculator($chart, $pips);

// Get trend line parameters
$trendLine = $calculator->getTrendLine();

// Calculate line intersections
$intersection = $calculator->findLinesIntersection($line1, $line2);

// Validate line breaks
$isBreak = $calculator->validateLineBreak($line, $bar, $direction);
```

## Line Types
ModelsLinesCalculator supports:
- Trend lines (LINE_TYPE_TREND)
- Aim lines (LINE_TYPE_AIM)
- Auxiliary trend lines (LINE_TYPE_AUX_TREND)
- Auxiliary aim lines (LINE_TYPE_AUX_AIM)

### build_models_common.php
Содержит общие функции и константы, используемые всеми построителями моделей.

#### Основные компоненты:
- Константы для настройки алгоритмов
- Общие вспомогательные функции
- Функции для работы с графиками
- Обработка логов и статистики

## Взаимодействие компонентов

1. **Инициализация**:
   ```php
   require_once __DIR__ . "/src/builders/build_models_common.php";
   require_once __DIR__ . "/src/builders/ModelBuilderA1.php";
   ```

2. **Создание экземпляра**:
   ```php
   $modelBuilder = new ModelBuilderA1($state, $res, $modelNextId, $maxBar4Split, $curSplit, $pips);
   ```

3. **Использование**:
   ```php
   $state = $modelBuilder->fixModel($name, $wo_t5);
   ```

## Основные параметры

### State
Основной объект состояния, содержащий:
- `v` - направление (low/high)
- `mode` - режим работы
- `curBar` - текущий бар
- `next_step` - следующий шаг
- `status` - статусы модели
- `param` - параметры модели

### Точки модели
- `t1` - первая точка
- `t2` - вторая точка
- `t3` - третья точка
- `t4` - четвертая точка
- `t5` - пятая точка (опционально)

## Логирование

Система логирования включает:
- Общий лог (`$res["log"]`)
- Лог выбранных баров (`$res["log_selected_bar"]`)
- Статистику вызовов (`$res["FlatLog_Statistics"]`)

## Константы

```php
ALGORITHM_NUM     // Номер алгоритма
BAR_50           // Глубина поиска пересечения
BAR_T3_50        // Количество баров для поиска t3
BAR_T3_150       // Глубина поиска точки t3
PAR246aux        // Минимальное соотношение t2-t4 к t4-t6
CALC_G3_DEPTH    // Глубина поиска при определении G3
```

## Типы моделей

- AM (ЧМП) - Чистая Модель Притяжения
- DBM (МДР) - Модель Длительного Развития
- EM (МР) - Модель Развития
- AM/DBM (ЧМП/МДР) - Гибридная модель
- EM/DBM (МР/МДР) - Гибридная модель

## Рекомендации по расширению

1. Для добавления нового алгоритма:
   - Создать новый класс, наследующий BasicModelBuilder
   - Реализовать специфические методы
   - Добавить необходимые константы

2. Для модификации существующих алгоритмов:
   - Изменить соответствующие методы в ModelBuilderA1/A2
   - Обновить константы в build_models_common.php

3. Для добавления новой функциональности:
   - Добавить методы в BasicModelBuilder
   - При необходимости переопределить в дочерних классах 

   
### Working with Lines
```php
// Get trend line
$trendLine = $builder->getLinesCalculator()->getTrendLine();

// Calculate line intersection
$intersection = $builder->getLinesCalculator()->findLinesIntersection($trendLine, $aimLine);

// Check line break
$isBreak = $builder->getLinesCalculator()->validateLineBreak($line, $bar, 'low');
```

## Extending Functionality

### Adding New Algorithms
1. Create new class extending BasicModelBuilder
2. Implement required methods
3. Add specific line calculations if needed

### Adding New Line Types
1. Add new constants to ModelsLinesCalculator
2. Implement calculation methods
3. Add validation methods if required

## Best Practices
- Always use ModelsLinesCalculator for line-related calculations
- Maintain proper state management
- Follow logging conventions
- Use provided constants instead of hard-coded values

## Dependencies
- PHP 7.0 or higher
- Proper file structure in src/builders/
- Correct path configuration

## Testing
- Validate line calculations
- Check model building results
- Verify state management
- Test line intersections and breaks

## Maintenance
Regular checks for:
- Line calculation accuracy
- Model validation
- State consistency
- Logging functionality

For more detailed information about specific components or usage examples, please refer to the individual class documentation.