<?php

declare(strict_types=1);

namespace ArchAnalyzer\Service\Report\DefaultReport\Extractor\ComponentPage;

use ArchAnalyzer\Model\Component;
use ArchAnalyzer\Model\UnitOfCode;
use ArchAnalyzer\Service\Report\DefaultReport\UidGenerator;

class DependencyComponentExtractor
{
    use UidGenerator;

    /**
     * @param array<Component> $processedComponents
     *
     * @return array<string, mixed>
     */
    public function extract(Component $component, Component $linkedComponent, array $processedComponents, bool $linkedComponentIsDependent = false): array
    {
        $extracted = [
            'name' => $this->generateUid($component->name()),
            'linked_component_name' => $this->generateUid($linkedComponent->name()),
            'units_of_code' => [],
            'reverted_units_of_code' => [],
        ];

        if ($linkedComponentIsDependent) {
            foreach ($linkedComponent->getDependencyUnitsOfCode($component) as $unitOfCode) {
                $isAllowed = true;
                $inAllowedState = false;
                $dependencies = [];
                foreach ($unitOfCode->inputDependencies() as $dependent) {
                    if ($dependent->component() !== $linkedComponent) {
                        continue;
                    }

                    $dependencies[] = $this->extractDependency($unitOfCode, $dependent, $isAllowed, $inAllowedState);
                }

                $extractedRevertedUnitOfCode = [
                    'name' => $unitOfCode->name(),
                    'dependencies' => $dependencies,
                    'is_allowed' => $isAllowed,
                    'in_allowed_state' => $inAllowedState,
                ];

                foreach ($processedComponents as $processedComponent) {
                    if ($unitOfCode->belongToComponent($processedComponent)) {
                        $extractedRevertedUnitOfCode['uid'] = $this->generateUid($unitOfCode->name());
                        break;
                    }
                }

                $extracted['reverted_units_of_code'][] = $extractedRevertedUnitOfCode;
            }

            $unitsOfCodes = $linkedComponent->getDependentUnitsOfCode($component);
        } else {
            $unitsOfCodes = $component->getDependentUnitsOfCode($linkedComponent);
        }

        foreach ($unitsOfCodes as $unitOfCode) {
            $isAllowed = true;
            $inAllowedState = false;
            $dependencies = [];
            foreach ($unitOfCode->outputDependencies() as $dependency) {
                $dependencies[] = $this
                    ->extractDependency($dependency, $unitOfCode, $isAllowed, $inAllowedState, true);
            }

            $extractedUnitOfCode = [
                'name' => $unitOfCode->name(),
                'dependencies' => $dependencies,
                'is_allowed' => $isAllowed,
                'in_allowed_state' => $inAllowedState,
            ];

            foreach ($processedComponents as $processedComponent) {
                if ($unitOfCode->belongToComponent($processedComponent)) {
                    $extractedUnitOfCode['uid'] = $this->generateUid($unitOfCode->name());
                    break;
                }
            }

            $extracted['units_of_code'][] = $extractedUnitOfCode;
        }

        return $extracted;
    }

    /**
     * @return array<string, mixed>
     */
    private function extractDependency(
        UnitOfCode $dependency,
        UnitOfCode $dependent,
        bool &$isAllowed,
        bool &$inAllowedState,
        bool $isOutputDependency = false
    ): array {
        $isInnerDependency = $dependency->belongToComponent($dependent->component());
        $isValidExternalDependency = $dependent->component()->isDependencyAllowed($dependency->component())
            && $dependency->isAccessibleFromOutside();

        $dependencyIsAllowed = $isInnerDependency || $isValidExternalDependency;
        if (!$dependencyIsAllowed) {
            $isAllowed = false;
        }

        $dependencyInAllowedState = $dependent->isDependencyInAllowedState($dependency);
        if ($dependencyInAllowedState) {
            $inAllowedState = true;
        }

        return [
            'name' => $isOutputDependency ? $dependency->name() : $dependent->name(),
            'is_allowed' => $dependencyIsAllowed,
            'in_allowed_state' => $dependencyInAllowedState,
        ];
    }
}
