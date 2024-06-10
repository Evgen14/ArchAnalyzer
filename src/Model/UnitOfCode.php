<?php

declare(strict_types=1);

namespace ArchAnalyzer\Model;

use ArchAnalyzer\Model\Type\Type;
use ArchAnalyzer\Model\Type\TypeClass;
use ArchAnalyzer\Model\Type\TypeInterface;
use ArchAnalyzer\Model\Type\TypePrimitive;
use ArchAnalyzer\Model\Type\TypeTrait;
use ArchAnalyzer\Model\Type\TypeUndefined;
use ArchAnalyzer\Service\Helper\PathHelper;

class UnitOfCode
{
    use CachingTrait;

    /**
     * @var array<self>
     */
    private static $instances = [];

    /**
     * @var string
     */
    private $name;

    /**
     * @var string|null
     */
    private $path;

    /**
     * @var Type
     */
    private $type;

    /**
     * @var array<UnitOfCode>
     */
    private $inputDependencies = [];

    /**
     * @var array<UnitOfCode>
     */
    private $outputDependencies = [];

    /**
     * @var Component
     */
    private $component;

    private function __construct(string $name, Type $type, ?string $path = null, ?Component $component = null)
    {
        $this->name = $name;
        $this->type = $type;
        $this->path = $path;
        $this->component = ($component ?? Component::createByUnitOfCode($this))->addUnitOfCode($this);
    }

    public static function create(string $fullName, ?Component $component = null, ?string $path = null): self
    {
        // Приведение названий к единому виду, без обратного слэша в начале
        $fullName = trim($fullName, '\\');

        $unitOfCode = self::$instances[$fullName] ?? null;
        if (!$unitOfCode) {
            switch (true) {
                case TypeInterface::isThisType($fullName):
                    $type = TypeInterface::getInstance();
                    $path = $path ?? PathHelper::detectPath($fullName);
                    break;

                case TypeClass::isThisType($fullName):
                    try {
                        assert(class_exists($fullName, false)
                            || trait_exists($fullName, false)
                            || interface_exists($fullName, false));
                        $reflection = new \ReflectionClass($fullName);
                        $isAbstract = $reflection->isAbstract();
                    } catch (\ReflectionException $e) {
                        $isAbstract = false;
                    }

                    $type = TypeClass::getInstance($isAbstract);
                    $path = $path ?? PathHelper::detectPath($fullName);
                    break;

                case TypeTrait::isThisType($fullName):
                    $type = TypeTrait::getInstance();
                    $path = $path ?? PathHelper::detectPath($fullName);
                    break;

                case TypePrimitive::isThisType($fullName):
                    $type = TypePrimitive::getInstance();
                    break;

                default:
                    $type = TypeUndefined::getInstance();
            }

            $unitOfCode = new UnitOfCode($fullName, $type, $path, $component);
            self::$instances[$fullName] = $unitOfCode;
        }

        if ($component) {
            $unitOfCode->setComponent($component);
        }

        if ($path) {
            $unitOfCode->path = $path;
        }

        return $unitOfCode;
    }

    /**
     * Возвращает название элемента
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * Возвращает путь к элементу
     */
    public function path(): ?string
    {
        return $this->path;
    }

    /**
     * Устанавливает принадлежность элемента к переданному компоненту
     */
    private function setComponent(Component $component): self
    {
        if ($this->component !== $component) {
            $this->component->removeUnitOfCode($this);
            $this->component = $component->addUnitOfCode($this);
        }

        return $this;
    }

    /**
     * Возвращает компонент, которому элемент принадлежит
     */
    public function component(): Component
    {
        return $this->component;
    }

    /**
     * Проверяет, располагается-ли элемент в глобальном namespace?
     */
    public function belongToGlobalNamespace(): bool
    {
        return count(array_filter(explode('\\', $this->name))) === 1;
    }

    /**
     * Проверяет принадлежность элемента переданному компоненту
     */
    public function belongToComponent(Component $component): bool
    {
        return $this->component === $component;
    }

    /**
     * Проверяет, является ли элемент доступным для взаимодействия извне компонента, к которому он принадлежит
     */
    public function isAccessibleFromOutside(): bool
    {
        return $this->execWithCache('isAccessibleFromOutside', function () {
            return $this->component->restrictions()->isUnitOfCodeAccessibleFromOutside($this);
        });
    }

    /**
     * Проверяет, существует ли зависимость в конфиге разрешенного состояния
     */
    public function isDependencyInAllowedState(UnitOfCode $dependency): bool
    {
        $key = 'isDependencyInAllowedState' . $dependency->name();

        return $this->execWithCache($key, function () use ($dependency) {
            return $this->component->restrictions()->isUnitOfCodeDependencyInAllowedState($dependency, $this);
        });
    }

    /**
     * Возвращает массив входящих зависимостей (элементов, которые каким-то образом зависят от текущего)
     *
     * @param Component|null $component Если передан, метод вернет только его зависимые элементы, иначе зависимые элементы
     * всех компонентов
     *
     * @return array<UnitOfCode>
     */
    public function inputDependencies(?Component $component = null): array
    {
        if (!$component) {
            return $this->inputDependencies;
        }

        $inputDependencies = [];
        foreach ($this->inputDependencies as $dependency) {
            if ($dependency->belongToComponent($component)) {
                $inputDependencies[] = $dependency;
            }
        }

        return $inputDependencies;
    }

    /**
     * Добавляет связь с входящей зависимостью, одновременно устанавливает ей исходящую зависимость от текущего элемента
     */
    public function addInputDependency(self $unitOfCode): self
    {
        if ($unitOfCode === $this) {
            return $this;
        }

        $this->inputDependencies[] = $unitOfCode;
        if (!in_array($this, $unitOfCode->outputDependencies(), true)) {
            $unitOfCode->addOutputDependency($this);
        }

        return $this;
    }

    /**
     * Возвращает массив исходящих зависимостей (элементов, от которых каким-то образом зависит текущий)
     *
     * @param Component|null $component Если передан, метод вернет только его элементы, иначе элементы всех компонентов
     *
     * @return array<UnitOfCode>
     */
    public function outputDependencies(?Component $component = null): array
    {
        if (!$component) {
            return $this->outputDependencies;
        }

        $outputDependencies = [];
        foreach ($this->outputDependencies as $dependency) {
            if ($dependency->belongToComponent($component)) {
                $outputDependencies[] = $dependency;
            }
        }

        return $outputDependencies;
    }

    /**
     * Добавляет связь с исходящей зависимостью, одновременно устанавливает ей входящую зависимость от текущего элемента
     */
    public function addOutputDependency(self $unitOfCode): self
    {
        if ($unitOfCode === $this) {
            return $this;
        }

        $this->outputDependencies[] = $unitOfCode;
        if (!in_array($this, $unitOfCode->inputDependencies(), true)) {
            $unitOfCode->addInputDependency($this);
        }

        return $this;
    }

    /**
     * Является-ли элемент абстрактным?
     *
     * @return bool|null Трэйты, примитивы и элементы, тип которых определить не удалось, не относятся ни к абстрактным,
     * ни к НЕ абстрактным, в этом случае метод вернёт null
     */
    public function isAbstract(): ?bool
    {
        return $this->type->isAbstract();
    }

    /**
     * Является-ли элемент примитивом?
     */
    public function isPrimitive(): bool
    {
        return $this->type instanceof TypePrimitive;
    }

    /**
     * Является-ли элемент классом?
     */
    public function isClass(): bool
    {
        return $this->type instanceof TypeClass;
    }

    /**
     * Является-ли элемент интерфейсом?
     */
    public function isInterface(): bool
    {
        return $this->type instanceof TypeInterface;
    }

    /**
     * Является-ли элемент трэйтом?
     */
    public function isTrait(): bool
    {
        return $this->type instanceof TypeTrait;
    }

    /**
     * Рассчитывает неустойчивость элемента <br>
     *
     * I = Fan-out ÷ (Fan-in + Fan-out) <br>
     * Где Fan-in - количество входящих зависимостей (элементов зависящих от текущего), а Fan-out - количество исходящих
     * зависимостей (элементов, от которых зависит текущий)
     *
     * @return float 0..1 (0 - элемент максимально устойчив, 1 - элемент максимально неустойчив)
     */
    public function calculateInstabilityRate(): float
    {
        $numOfOutputDependencies = count($this->outputDependencies);
        $numOfInputDependencies = count($this->inputDependencies);
        $total = $numOfInputDependencies + $numOfOutputDependencies;

        return $total === 0 ? 0 : round($numOfOutputDependencies / $total, 3);
    }

    /**
     * Рассчитывает примитивность элемента
     */
    public function calculatePrimitivenessRate(): float
    {
        if ($this->isPrimitive()) {
            return 1;
        }

        $numOfPrimitiveOutputDependencies = 0;
        $numOfOutputDependencies = count($this->outputDependencies());
        foreach ($this->outputDependencies() as $outputDependency) {
            if ($outputDependency->isPrimitive()) {
                $numOfPrimitiveOutputDependencies++;
            }
        }

        if (!$numOfOutputDependencies) {
            return 0;
        }

        return round($numOfPrimitiveOutputDependencies / $numOfOutputDependencies, 3);
    }

    /**
     * @param array<Path> $allowedPaths
     */
    public function filterDependenciesByPaths(array $allowedPaths, bool $filterDependenciesByAllowedState): void
    {
        if (empty($allowedPaths)) {
            return;
        }

        foreach ($this->inputDependencies as $index => $dependent) {
            if ($filterDependenciesByAllowedState && $dependent->isDependencyInAllowedState($this)) {
                unset($this->inputDependencies[$index]);
                continue;
            }

            $isAllowed = false;
            foreach ($allowedPaths as $allowedPath) {
                if ($allowedPath->isPartOfPath($dependent->path())) {
                    $isAllowed = true;
                    break;
                }
            }

            if (!$isAllowed) {
                unset($this->inputDependencies[$index]);
            }
        }

        foreach ($this->outputDependencies as $index => $dependency) {
            if ($filterDependenciesByAllowedState && $this->isDependencyInAllowedState($dependency)) {
                unset($this->outputDependencies[$index]);
            }
        }
    }
}
