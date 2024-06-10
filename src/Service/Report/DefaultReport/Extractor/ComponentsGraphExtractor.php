<?php

declare(strict_types=1);

namespace ArchAnalyzer\Service\Report\DefaultReport\Extractor;

use ArchAnalyzer\Model\Component;
use ArchAnalyzer\Service\Report\DefaultReport\ObjectsGraphBuilder;

class ComponentsGraphExtractor
{
    /**
     * @var ComponentsGraphNodeExtractor
     */
    private $nodeExtractor;

    /**
     * @var ComponentsGraphEdgeExtractor
     */
    private $edgeExtractor;

    public function __construct()
    {
        $this->nodeExtractor = new ComponentsGraphNodeExtractor();
        $this->edgeExtractor = new ComponentsGraphEdgeExtractor();
    }

    /**
     * @return array<string, mixed>
     */
    public function extract(ObjectsGraphBuilder $graphBuilder): array
    {
        return [
            'nodes' => json_encode(array_map(function (Component $node) {
                return $this->nodeExtractor->extract($node);
            }, $graphBuilder->getNodes())),
            'edges' => json_encode(array_map(function (array $edge) {
                return $this->edgeExtractor->extract($edge);
            }, $graphBuilder->getEdges())),
        ];
    }
}
