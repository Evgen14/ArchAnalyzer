<?php

declare(strict_types=1);

namespace ArchAnalyzer\Service\Report\DefaultReport\Extractor\UnitOfCodePage;

use ArchAnalyzer\Model\UnitOfCode;

class UnitsOfCodeGraphEdgeExtractor
{
    /**
     * @param array<UnitOfCode> $edge [$from, $to]
     *
     * @return array<string, mixed>
     */
    public function extract(array $edge): array
    {
        $from = $edge['from'];
        $to = $edge['to'];

        $extractedData = [
            'from' => spl_object_hash($from),
            'to' => spl_object_hash($to),
        ];

        $isDependencyInAllowedState = $from->isDependencyInAllowedState($to);

        if (!$from->component()->isDependencyAllowed($to->component())) {
            $extractedData['color'] = $isDependencyInAllowedState ? 'yellow' : 'red';
        } elseif (!$to->isAccessibleFromOutside() && !$to->belongToComponent($from->component())) {
            $extractedData['color'] = $isDependencyInAllowedState ? 'yellow' : 'orange';
        }

        return $extractedData;
    }
}
