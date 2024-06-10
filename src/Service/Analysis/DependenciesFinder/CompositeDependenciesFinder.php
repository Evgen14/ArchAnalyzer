<?php

declare(strict_types=1);

namespace ArchAnalyzer\Service\Analysis\DependenciesFinder;

use ArchAnalyzer\Model\UnitOfCode;

class CompositeDependenciesFinder implements DependenciesFinderInterface
{
    /**
     * @var array<DependenciesFinderInterface>
     */
    private $strategies;

    public function __construct(DependenciesFinderInterface ...$strategies)
    {
        $this->strategies = $strategies;
    }

    public function find(UnitOfCode $unitOfCode): array
    {
        $dependencies = [];
        foreach ($this->strategies as $strategy) {
            $dependencies[] = $strategy->find($unitOfCode);
        }

        return array_unique(array_merge(...$dependencies));
    }
}
