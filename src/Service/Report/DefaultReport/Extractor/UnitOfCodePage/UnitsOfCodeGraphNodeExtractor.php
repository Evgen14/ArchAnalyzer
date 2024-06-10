<?php

declare(strict_types=1);

namespace ArchAnalyzer\Service\Report\DefaultReport\Extractor\UnitOfCodePage;

use ArchAnalyzer\Model\UnitOfCode;

class UnitsOfCodeGraphNodeExtractor
{
    /**
     * @return array<string, mixed>
     */
    public function extract(UnitOfCode $node): array
    {
        return [
            'id' => spl_object_hash($node),
            'label' => $node->name(),
        ];
    }
}
