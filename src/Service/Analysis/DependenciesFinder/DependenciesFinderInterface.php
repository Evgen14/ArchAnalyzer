<?php

declare(strict_types=1);

namespace ArchAnalyzer\Service\Analysis\DependenciesFinder;

use ArchAnalyzer\Model\UnitOfCode;

interface DependenciesFinderInterface
{
    /**
     * @return array<string>
     */
    public function find(UnitOfCode $unitOfCode): array;
}
