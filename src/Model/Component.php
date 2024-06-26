<?php

declare(strict_types=1);

namespace ArchAnalyzer\Model;

class Component
{
    use CachingTrait;

    /**
     * Название по умолчанию, если не передано другое
     */
    private const UNDEFINED = '*undefined*';

    /**
     * Название компонента, в который будут сложены используемые примитивы и псевдотипы
     */
    private const PRIMITIVES = '*primitives*';

    /**
     * Название компонента, в который будут сложены элементы относящиеся к глобальному namespace
     */
    private const GLOBAL = '*global*';

    /**
     * @var array<self>
     */
    private static $instances = [];

    /**
     * @var bool
     */
    private $isEnabledForAnalysis = true;

    /**
     * @var string
     */
    private $name;

    /**
     * @var array<Path>
     */
    private $rootPaths;

    /**
     * @var array<Path>
     */
    private $excludedPaths;

    /**
     * @var Restrictions
     */
    private $restrictions;

    /**
     * @var array<UnitOfCode>
     */
    private $unitsOfCode = [];

    /**
     * @param array<Path> $rootPaths
     * @param array<Path> $excludedPaths
     */
    private function __construct(
        string $name,
        array $rootPaths,
        array $excludedPaths = [],
        ?Restrictions $restrictions = null
    ) {
        $this->name = $name;
        $this->rootPaths = $rootPaths;
        $this->excludedPaths = $excludedPaths;
        $this->restrictions = $restrictions ?? new Restrictions();
    }

    /**
     * @param array<Path> $rootPaths
     * @param array<Path> $excludedPaths
     */
    public static function create(
        string $name = self::UNDEFINED,
        array $rootPaths = [],
        array $excludedPaths = [],
        ?Restrictions $restrictions = null
    ): self {
        if (!isset(self::$instances[$name])) {
            self::$instances[$name] = new self(
                $name,
                $rootPaths,
                $excludedPaths,
                $restrictions
            );
        }

        $component = self::$instances[$name];
        foreach ($rootPaths as $rootPath) {
            $component->addRootPath($rootPath);
        }

        foreach ($excludedPaths as $excludedPath) {
            $component->addExcludedPath($excludedPath);
        }

        if ($restrictions) {
            $component->restrictions = $restrictions;
        }

        return $component;
    }

    public static function createByUnitOfCode(UnitOfCode $unitOfCode): self
    {
        if ($unitOfCode->isPrimitive()) {
            return self::create(self::PRIMITIVES);
        }

        if ($unitOfCode->belongToGlobalNamespace()) {
            return self::create(self::GLOBAL);
        }

        $isLocatedInOneOfPaths = static function (UnitOfCode $unitOfCode, Path ...$paths) {
            $trimmedUnitOfCodeName = trim($unitOfCode->name(), '\\');
            foreach ($paths as $path) {
                if ($path->namespace()) {
                    $trimmedNamespace = trim($path->namespace(), '\\');
                    if (stripos($trimmedUnitOfCodeName, $trimmedNamespace) === 0) {
                        return true;
                    }
                }

                if ($unitOfCode->path() !== null
                    && !empty($path->path())
                    && stripos($unitOfCode->path(), $path->path()) === 0
                ) {
                    return true;
                }
            }

            return false;
        };

        foreach (self::$instances as $existingComponent) {
            if ($isLocatedInOneOfPaths($unitOfCode, ...$existingComponent->rootPaths())
                && !$isLocatedInOneOfPaths($unitOfCode, ...$existingComponent->excludedPaths())
            ) {
                return $existingComponent;
            }
        }

        return self::create();
    }

    /**
     * Возвращает все, созданные до текущего момента времени, объекты Component
     *
     * @return array<Component>
     */
    public static function getAll(): array
    {
        return self::$instances;
    }

    /**
     * Выполняет поиск объекта Component по названию (среди всех ранее созданных)
     */
    public static function findByName(string $name): ?Component
    {
        return self::$instances[$name] ?? null;
    }

    /**
     * Проверяет, требуется-ли анализировать содержимое компонента?
     */
    public function isEnabledForAnalysis(): bool
    {
        return $this->isEnabledForAnalysis;
    }

    /**
     * Исключает метод из процесса анализа содержимого
     */
    public function excludeFromAnalyze(): Component
    {
        $this->isEnabledForAnalysis = false;

        return $this;
    }

    /**
     * Проверяет, является-ли переданный путь исключением?
     * Пример:
     *      если excludedPaths: ['/some/excluded/path'],
     *      то для значений $path:
     *          - '/some/excluded/path'
     *          - '/some/excluded/path/'
     *          - '/some/excluded/path/dir1/SomeClass.php'
     *          - '/some/excluded/path/dir2/...'
     *          - '/some/excluded/path/dir3/...'
     *          - и т.д.
     *      метод вернет true
     */
    public function isExcluded(string $path): bool
    {
        foreach ($this->excludedPaths as $excludedPath) {
            if ($excludedPath->isPartOfPath($path)) {
                return true;
            }
        }

        return false;
    }

    public function isUndefined(): bool
    {
        return $this->name === self::UNDEFINED;
    }

    public function isGlobal(): bool
    {
        return $this->name === self::GLOBAL;
    }

    public function isPrimitives(): bool
    {
        return $this->name === self::PRIMITIVES;
    }

    /**
     * Возвращает название компонента
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * Возвращает пути корневых директорий компонента
     *
     * @return array<Path>
     */
    public function rootPaths(): array
    {
        return $this->rootPaths;
    }

    /**
     * Добавляет путь корневой директории компонента
     */
    public function addRootPath(Path $rootPath): self
    {
        if (!in_array($rootPath, $this->rootPaths, true)) {
            $this->rootPaths[] = $rootPath;
        }

        return $this;
    }

    /**
     * Возвращает пути исключения
     *
     * @return array<Path>
     */
    public function excludedPaths(): array
    {
        return $this->excludedPaths;
    }

    /**
     * Добавляет путь исключение
     */
    public function addExcludedPath(Path $excludedPath): self
    {
        if (!in_array($excludedPath, $this->excludedPaths, true)) {
            $this->excludedPaths[] = $excludedPath;
        }

        return $this;
    }

    /**
     * Проверяет, разрешена ли текущему компоненту зависимость от переданного?
     */
    public function isDependencyAllowed(Component $dependency): bool
    {
        $key = 'isDependencyAllowed' . $dependency->name();

        return $this->execWithCache($key, function () use ($dependency) {
            return $this->restrictions->isDependencyAllowed($dependency, $this);
        });
    }

    /**
     * Проверяет, существует ли зависимость в конфиге разрешенного состояния
     */
    public function isDependencyInAllowedState(Component $dependency): bool
    {
        $key = 'isDependencyInAllowedState' . $dependency->name();

        return $this->execWithCache($key, function () use ($dependency) {
            return $this->restrictions->isComponentDependencyInAllowedState($dependency, $this);
        });
    }

    public function restrictions(): Restrictions
    {
        return $this->restrictions;
    }

    /**
     * Возвращает список элементов компонента
     *
     * @return array<UnitOfCode>
     */
    public function unitsOfCode(): array
    {
        return $this->unitsOfCode;
    }

    /**
     * Добавляет элемент компонента
     */
    public function addUnitOfCode(UnitOfCode $unitOfCode): self
    {
        $this->unitsOfCode[spl_object_hash($unitOfCode)] = $unitOfCode;

        return $this;
    }

    /**
     * Удаляет элемент компонента
     */
    public function removeUnitOfCode(UnitOfCode $unitOfCode): self
    {
        unset($this->unitsOfCode[spl_object_hash($unitOfCode)]);

        return $this;
    }

    /**
     * Возвращает список компонентов, которые зависят от этого компонента.
     *
     * @return array<Component>
     */
    public function getDependentComponents(): array
    {
        $uniqueDependentComponents = [];
        foreach ($this->unitsOfCode as $unitOfCode) {
            foreach ($unitOfCode->inputDependencies() as $dependentUnitOfCode) {
                if (!$dependentUnitOfCode->belongToComponent($this)) {
                    $component = $dependentUnitOfCode->component();
                    $uniqueDependentComponents[spl_object_hash($component)] = $component;
                }
            }
        }

        return array_values($uniqueDependentComponents);
    }

    /**
     * Возвращает список компонентов, от которых зависит этот компонент.
     *
     * @return array<Component>
     */
    public function getDependencyComponents(): array
    {
        return $this->execWithCache('getDependencyComponents', function () {
            $uniqueDependencyComponents = [];
            foreach ($this->unitsOfCode as $unitOfCode) {
                foreach ($unitOfCode->outputDependencies() as $dependency) {
                    if (!$dependency->belongToComponent($this)
                        && !$dependency->belongToGlobalNamespace()
                        && !$dependency->isPrimitive()
                    ) {
                        $component = $dependency->component();
                        $uniqueDependencyComponents[spl_object_hash($component)] = $component;
                    }
                }
            }

            return array_values($uniqueDependencyComponents);
        });
    }

    /**
     * Возвращает список элементов этого компонента, которые зависят от элементов полученного компонента.
     *
     * @return array<UnitOfCode>
     */
    public function getDependentUnitsOfCode(Component $dependencyComponent): array
    {
        $key = 'getDependentUnitsOfCode' . $dependencyComponent->name();

        return $this->execWithCache($key, function () use ($dependencyComponent) {
            $uniqueDependentUnitsOfCode = [];
            foreach ($this->unitsOfCode as $unitOfCode) {
                foreach ($unitOfCode->outputDependencies() as $dependency) {
                    if ($dependency->belongToComponent($dependencyComponent)) {
                        $uniqueDependentUnitsOfCode[spl_object_hash($unitOfCode)] = $unitOfCode;
                    }
                }
            }

            return array_values($uniqueDependentUnitsOfCode);
        });
    }

    /**
     * Возвращает список элементов полученного компонента, от которых зависят элементы этого компонента.
     *
     * @return array<UnitOfCode>
     */
    public function getDependencyUnitsOfCode(Component $dependencyComponent): array
    {
        $key = 'getDependencyUnitsOfCode' . $dependencyComponent->name();

        return $this->execWithCache($key, function () use ($dependencyComponent) {
            $uniqueDependencyUnitsOfCode = [];
            foreach ($this->unitsOfCode as $unitOfCode) {
                foreach ($unitOfCode->outputDependencies() as $dependency) {
                    if ($dependency->belongToComponent($dependencyComponent)) {
                        $uniqueDependencyUnitsOfCode[spl_object_hash($dependency)] = $dependency;
                    }
                }
            }

            return array_values($uniqueDependencyUnitsOfCode);
        });
    }

    /**
     * Возвращает компоненты, от которых текущий зависеть не должен, но зависит
     *
     * @return array<Component>
     */
    public function getIllegalDependencyComponents(): array
    {
        return $this->restrictions->getIllegalDependencyComponents($this);
    }

    /**
     * Возвращает элементы других компонентов, от которых текущий зависеть не должен, но зависит
     *
     * @param bool $onlyFromAllowedComponents Если false, метод вернёт все запрещенные для взаимодействия элементы-зависимости,
     * т.е. элементы запрещенных для взаимодействия компонентов и приватные элементы разрешенных для взаимодействия компонентов.
     * Если true, метод вернет только запрещенные элементы-зависимости из разрешенных для взаимодействия компонентов,
     * т.е. только приватные элементы разрешенных для взаимодействия компонентов.
     *
     * @return array<UnitOfCode>
     */
    public function getIllegalDependencyUnitsOfCode(bool $onlyFromAllowedComponents = false): array
    {
        return $this->restrictions->getIllegalDependencyUnitsOfCode($this, $onlyFromAllowedComponents);
    }

    /**
     * Возвращает найденные циклические зависимости компонентов
     *
     * @param array<Component> $path Оставь пустым (используется в рекурсии)
     * @param array<array<Component>> $result Оставь пустым (используется в рекурсии)
     *
     * @return array<array<Component>>
     */
    public function getCyclicDependencies(array $path = [], array $result = []): array
    {
        $path[] = $this;
        foreach ($this->getDependencyComponents() as $dependencyComponent) {
            if (in_array($dependencyComponent, $path, true)) {
                if (isset($path[0]) && $path[0] === $dependencyComponent) {
                    $result[] = array_merge($path, [$dependencyComponent]);
                }
            } else {
                $result = $dependencyComponent->getCyclicDependencies($path, $result);
            }
        }

        return $result;
    }

    /**
     * Рассчитывает абстрактность компонента <br>
     * A = Na ÷ Nc <br>
     * Где Na - число абстрактных элементов компонента, а Nc - общее число элементов компонента
     *
     * @return float 0..1 (0 - полное отсутствие абстрактных элементов в компоненте, 1 - все элементы компонента абстрактны)
     */
    public function calculateAbstractnessRate(): float
    {
        $numOfConcrete = 0;
        $numOfAbstract = 0;

        foreach ($this->unitsOfCode as $unitOfCode) {
            $isAbstract = $unitOfCode->isAbstract();

            if ($isAbstract === true) {
                $numOfAbstract++;
            } elseif ($isAbstract === false) {
                $numOfConcrete++;
            }
        }

        $total = $numOfAbstract + $numOfConcrete;
        if ($total === 0) {
            return 0;
        }

        return round($numOfAbstract / $total, 3);
    }

    /**
     * Рассчитывает неустойчивость компонента <br>
     *
     * I = Fan-out ÷ (Fan-in + Fan-out) <br>
     * Где Fan-in - количество входящих зависимостей (классов вне данного компонента, которые зависят от классов внутри
     * компонента), а Fan-out - количество исходящих зависимостей (классов внутри данного компонента, зависящих от
     * классов за его пределами)
     *
     * @return float 0..1 (0 - компонент максимально устойчив, 1 - компонент максимально неустойчив)
     */
    public function calculateInstabilityRate(): float
    {
        return $this->execWithCache('calculateInstabilityRate', function () {
            $uniqueInputExternalDependencies = [];
            $uniqueOutputExternalDependencies = [];

            foreach ($this->unitsOfCode as $unitOfCode) {
                foreach ($unitOfCode->inputDependencies() as $dependency) {
                    if (!$dependency->belongToComponent($this)) {
                        $uniqueInputExternalDependencies[$dependency->name()] = true;
                    }
                }

                foreach ($unitOfCode->outputDependencies() as $dependency) {
                    if ($dependency->belongToComponent($this)
                        || $dependency->belongToGlobalNamespace()
                        || $dependency->isPrimitive()
                    ) {
                        continue;
                    }

                    $uniqueOutputExternalDependencies[$dependency->name()] = true;
                }
            }

            $numOfUniqueInputExternalDependencies = count($uniqueInputExternalDependencies);
            $numOfUniqueOutputExternalDependencies = count($uniqueOutputExternalDependencies);
            $totalUniqueExternalDependencies = $numOfUniqueInputExternalDependencies + $numOfUniqueOutputExternalDependencies;

            return $totalUniqueExternalDependencies ?
                round($numOfUniqueOutputExternalDependencies / $totalUniqueExternalDependencies, 3)
                : 0;
        });
    }

    /**
     * Рассчитывает расстояние до главной последовательности на графике A/I <br>
     *
     * D = |A+I–1| <br>
     * Где A - метрика абстрактности компонента, а I - метрика неустойчивости компонента
     *
     * @see calculateAbstractnessRate
     * @see calculateInstabilityRate
     */
    public function calculateDistanceRate(): float
    {
        return abs($this->calculateAbstractnessRate() + $this->calculateInstabilityRate() - 1);
    }

    /**
     * Рассчитывает превышение метрикой D максимально допустимого значения (задаваемого в конфиге max_allowable_distance)
     */
    public function calculateDistanceRateOverage(): float
    {
        return $this->restrictions->calculateDistanceRateOverage($this);
    }

    /**
     * Рассчитывает примитивность компонента
     */
    public function calculatePrimitivenessRate(): float
    {
        $sumPrimitivenessRates = 0;
        $numOfUnitOfCode = count($this->unitsOfCode);
        foreach ($this->unitsOfCode as $unitOfCode) {
            $sumPrimitivenessRates += $unitOfCode->calculatePrimitivenessRate();
        }

        if (!$numOfUnitOfCode) {
            return 0;
        }

        return round($sumPrimitivenessRates/$numOfUnitOfCode, 3);
    }

    /**
     * @param array<Path> $allowedPaths
     */
    public function filterByPaths(array $allowedPaths): void
    {
        if (empty($allowedPaths)) {
            return;
        }

        foreach ($this->unitsOfCode as $index => $unitOfCode) {
            $isAllowed = false;
            foreach ($allowedPaths as $allowedPath) {
                if ($allowedPath->isPartOfPath($unitOfCode->path())) {
                    $isAllowed = true;
                    break;
                }
            }

            if (!$isAllowed) {
                unset($this->unitsOfCode[$index]);
                continue;
            }

            $unitOfCode->filterDependenciesByPaths($allowedPaths, $this->restrictions->isAllowedStateEnabled());
            if (!$unitOfCode->inputDependencies() && !$unitOfCode->outputDependencies()) {
                unset($this->unitsOfCode[$index]);
            }
        }
    }
}
