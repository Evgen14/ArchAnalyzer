<?php

declare(strict_types=1);

namespace ArchAnalyzer\Service\Report\DefaultReport\Extractor\UnitOfCodePage;

use ArchAnalyzer\Model\Component;
use ArchAnalyzer\Model\UnitOfCode;
use ArchAnalyzer\Service\Report\DefaultReport\UidGenerator;

class DependencyUnitOfCodeExtractor
{
    use UidGenerator;

    /**
     * @param array<Component> $processedComponents
     *
     * @return array<string, mixed>
     */
    public function extract(UnitOfCode $unitOfCode, UnitOfCode $dependency, array $processedComponents, bool $isInputDependency = true): array
    {
        $data = [
            'name' => $dependency->name(),
        ];

        foreach ($processedComponents as $processedComponent) {
            if ($dependency->belongToComponent($processedComponent)) {
                $data['uid'] = $this->generateUid($dependency->name());
                break;
            }
        }

        $data['is_allowed'] = $isInputDependency
            ? $unitOfCode->belongToComponent($dependency->component()) || ($dependency->component()->isDependencyAllowed($unitOfCode->component()) && $unitOfCode->isAccessibleFromOutside())
            : $dependency->belongToComponent($unitOfCode->component()) || ($unitOfCode->component()->isDependencyAllowed($dependency->component()) && $dependency->isAccessibleFromOutside());

        $data['in_allowed_state'] = $isInputDependency
            ? $dependency->isDependencyInAllowedState($unitOfCode)
            : $unitOfCode->isDependencyInAllowedState($dependency);

        return $data;
    }
}
