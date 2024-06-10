<?php

declare(strict_types=1);

namespace ArchAnalyzer\Service\Report\DefaultReport\Extractor;

use ArchAnalyzer\Model\Component;

class ComponentsGraphNodeExtractor
{
    /**
     * @return array<string, mixed>
     */
    public function extract(Component $node): array
    {
        $fanIn = array_map(static function (Component $inputDependency) {
            return $inputDependency->name();
        }, $node->getDependentComponents());

        $fanOut = array_map(static function (Component $outputDependency) {
            return $outputDependency->name();
        }, $node->getDependencyComponents());

        $title = 'Abstractness: ' . $node->calculateAbstractnessRate() . ', ';
        $title .= 'Instability: ' . $node->calculateInstabilityRate() . ', ';
        $title .= 'Fan-in: ' . implode(', ', $fanIn) . ', ';
        $title .= 'Fan-out: ' . implode(', ', $fanOut) . '';

        return [
            'id' => spl_object_hash($node),
            'label' => $node->name(),
            'title' => $title,
        ];
    }
}
