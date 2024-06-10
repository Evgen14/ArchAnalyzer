<?php

declare(strict_types=1);

namespace ArchAnalyzer\Service\Report\DefaultReport;

use ArchAnalyzer\Service\Helper\StringHelper;
use ArchAnalyzer\Model\Component;
use ArchAnalyzer\Service\Report\DefaultReport\Extractor\ComponentPage\DependencyComponentExtractor;
use ArchAnalyzer\Service\Report\DefaultReport\Extractor\ComponentsGraphExtractor;
use ArchAnalyzer\Service\Report\TemplateRendererInterface;

class ComponentPageRenderingService
{
    use UidGenerator;

    /**
     * @var TemplateRendererInterface
     */
    private $templateRenderer;

    /**
     * @var ObjectsGraphBuilder
     */
    private $componentsGraphBuilder;

    /**
     * @var DependencyComponentExtractor
     */
    private $dependencyComponentExtractor;

    /**
     * @var ComponentsGraphExtractor
     */
    private $componentsGraphExtractor;

    public function __construct(TemplateRendererInterface $templateRenderer)
    {
        $this->templateRenderer = $templateRenderer;
        $this->componentsGraphBuilder = new ObjectsGraphBuilder();
        $this->dependencyComponentExtractor = new DependencyComponentExtractor();
        $this->componentsGraphExtractor = new ComponentsGraphExtractor();
    }

    public function render(string $reportsPath, Component $component, Component ...$processedComponents): void
    {
        $this->componentsGraphBuilder->reset();

        $extractedDependentComponentsData = [];
        foreach ($component->getDependentComponents() as $dependentComponent) {
            $this->componentsGraphBuilder->addEdge($dependentComponent, $component);
            $extractedDependentComponentsData[] = $this->dependencyComponentExtractor->extract($dependentComponent, $component, $processedComponents);
        }

        $extractedDependencyComponentsData = [];
        foreach ($component->getDependencyComponents() as $dependencyComponent) {
            if ($dependencyComponent->isGlobal() || $dependencyComponent->isPrimitives()) {
                continue;
            }

            $this->componentsGraphBuilder->addEdge($component, $dependencyComponent);
            $extractedDependencyComponentsData[] = $this->dependencyComponentExtractor->extract($dependencyComponent, $component, $processedComponents, true);
        }

        $reportContent = $this->templateRenderer->render('component-info.twig', [
            'name' => $component->name(),
            'primitiveness_rate' => $component->calculatePrimitivenessRate(),
            'abstractness_rate' => $component->calculateAbstractnessRate(),
            'instability_rate' => $component->calculateInstabilityRate(),
            'distance_rate' => $component->calculateDistanceRate(),
            'dependent_components' => $extractedDependentComponentsData,
            'dependency_components' => $extractedDependencyComponentsData,
            'dependent_components_json' => StringHelper::escapeBackslashes((string) json_encode($extractedDependentComponentsData)),
            'dependency_components_json' => StringHelper::escapeBackslashes((string) json_encode($extractedDependencyComponentsData)),
            'components_graph' => $this->componentsGraphExtractor->extract($this->componentsGraphBuilder),
        ]);

        file_put_contents($reportsPath . '/' . $this->generateUid($component->name()) . '.html', $reportContent);
    }
}
