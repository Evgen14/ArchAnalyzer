<?php

declare(strict_types=1);

namespace ArchAnalyzer\Service\Report\DefaultReport\Extractor\IndexPage;

use ArchAnalyzer\Model\Component;
use ArchAnalyzer\Service\Report\DefaultReport\UidGenerator;

class ComponentExtractor
{
    use UidGenerator;

    /**
     * @return array<string, mixed>
     */
    public function extract(Component $component): array
    {
        $distanceRate = $component->calculateDistanceRate();
        $distanceRateOverage = $component->calculateDistanceRateOverage();
        $distanceRateNorma = $distanceRate - $distanceRateOverage;

        return [
            'uid' => $this->generateUid($component->name()),
            'name' => $component->name(),
            'abstractness_rate' => $component->calculateAbstractnessRate(),
            'instability_rate' => $component->calculateInstabilityRate(),
            'distance_rate' => $distanceRate,
            'distance_norma' => $distanceRateNorma,
            'distance_overage' => $distanceRateOverage,
            'num_of_dependency' => count($component->getDependencyComponents()),
        ];
    }
}
