<?php

declare(strict_types=1);

namespace ArchAnalyzer\Service\Report\DefaultReport\Extractor\UnitOfCodePage;

use ArchAnalyzer\Model\UnitOfCode;
use ArchAnalyzer\Service\Report\DefaultReport\ObjectsGraphBuilder;

class UnitsOfCodeGraphExtractor
{
    /**
     * @var UnitsOfCodeGraphNodeExtractor
     */
    private $nodeExtractor;

    /**
     * @var UnitsOfCodeGraphEdgeExtractor
     */
    private $edgeExtractor;

    public function __construct()
    {
        $this->nodeExtractor = new UnitsOfCodeGraphNodeExtractor();
        $this->edgeExtractor = new UnitsOfCodeGraphEdgeExtractor();
    }

    /**
     * @return array<string, mixed>
     */
    public function extract(ObjectsGraphBuilder $graphBuilder): array
    {
        return [
            'nodes' => json_encode(array_map(function (UnitOfCode $node) {
                return $this->nodeExtractor->extract($node);
            }, $graphBuilder->getNodes())),
            'edges' => json_encode(array_map(function (array $edge) {
                return $this->edgeExtractor->extract($edge);
            }, $graphBuilder->getEdges())),
        ];
    }
}
