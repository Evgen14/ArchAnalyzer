<?php

declare(strict_types=1);

namespace ArchAnalyzer\Service\Report\DefaultReport;

use ArchAnalyzer\Model\Component;
use ArchAnalyzer\Service\Report\DefaultReport\Extractor\IndexPage\ComponentExtractor;
use ArchAnalyzer\Service\Report\DefaultReport\Extractor\ComponentsGraphExtractor;
use ArchAnalyzer\Service\Report\TemplateRendererInterface;

class IndexPageRenderingService
{
    /**
     * @var TemplateRendererInterface
     */
    private $templateRenderer;

    /**
     * @var ObjectsGraphBuilder
     */
    private $componentsGraphBuilder;

    /**
     * @var ComponentExtractor
     */
    private $componentExtractor;

    /**
     * @var ComponentsGraphExtractor
     */
    private $componentsGraphExtractor;

    public function __construct(TemplateRendererInterface $templateRenderer)
    {
        $this->templateRenderer = $templateRenderer;
        $this->componentsGraphBuilder = new ObjectsGraphBuilder();
        $this->componentExtractor = new ComponentExtractor();
        $this->componentsGraphExtractor = new ComponentsGraphExtractor();
    }

    public function render(string $reportsPath, Component ...$components): void
    {
        $extractedComponentsData = [];
        $this->componentsGraphBuilder->reset();

        foreach ($components as $component) {
            $extractedComponentsData[] = $this->componentExtractor->extract($component);
            foreach ($component->getDependentComponents() as $dependentComponent) {
                $this->componentsGraphBuilder->addEdge($dependentComponent, $component);
            }

            foreach ($component->getDependencyComponents() as $dependencyComponent) {
                if ($dependencyComponent->isPrimitives() || $dependencyComponent->isGlobal()) {
                    continue;
                }

                $this->componentsGraphBuilder->addEdge($component, $dependencyComponent);
            }
        }

        $reportContent = $this->templateRenderer->render('index.twig', [
            'components_graph' => $this->componentsGraphExtractor->extract($this->componentsGraphBuilder),
            'components' => $extractedComponentsData,
        ]);

        file_put_contents($reportsPath . '/' . 'index.html', $reportContent);
    }
}
