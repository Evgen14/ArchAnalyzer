<?php

declare(strict_types=1);

namespace ArchAnalyzer\Service\Report\DefaultReport;

use ArchAnalyzer\Model\Component;
use ArchAnalyzer\Service\EventManagerInterface;
use ArchAnalyzer\Service\Report\DefaultReport\Event\ComponentReportRenderingFinishedEvent;
use ArchAnalyzer\Service\Report\DefaultReport\Event\ComponentReportRenderingStartedEvent;
use ArchAnalyzer\Service\Report\DefaultReport\Event\ReportRenderingFinishedEvent;
use ArchAnalyzer\Service\Report\DefaultReport\Event\ReportRenderingStartedEvent;
use ArchAnalyzer\Service\Report\DefaultReport\Event\UnitOfCodeReportRenderedEvent;
use ArchAnalyzer\Service\Report\ReportRenderingServiceInterface;
use ArchAnalyzer\Service\Report\TemplateRendererInterface;

class ReportRenderingService implements ReportRenderingServiceInterface
{
    /**
     * @var EventManagerInterface
     */
    private $eventManager;

    /**
     * @var IndexPageRenderingService
     */
    private $indexPageRenderingService;

    /**
     * @var ComponentPageRenderingService
     */
    private $componentPageRenderingService;

    /**
     * @var UnitOfCodePageRenderingService
     */
    private $unitOfCodePageRenderingService;

    public function __construct(EventManagerInterface $eventManager, TemplateRendererInterface $templateRenderer)
    {
        $this->eventManager = $eventManager;
        $this->indexPageRenderingService = new IndexPageRenderingService($templateRenderer);
        $this->componentPageRenderingService = new ComponentPageRenderingService($templateRenderer);
        $this->unitOfCodePageRenderingService = new UnitOfCodePageRenderingService($templateRenderer);
    }

    public function render(string $reportPath, Component ...$components): void
    {
        $this->eventManager->notify(new ReportRenderingStartedEvent());

        $totalComponents = count($components);
        foreach ($components as $componentPosition => $component) {
            if (!$component->isEnabledForAnalysis()) {
                continue;
            }

            $this->eventManager->notify(new ComponentReportRenderingStartedEvent($componentPosition, $totalComponents, $component));
            $unitOfCodePosition = 0;
            $totalUnitsOfCode = count($component->unitsOfCode());
            foreach ($component->unitsOfCode() as $unitOfCode) {
                $this->unitOfCodePageRenderingService->render($reportPath, $unitOfCode, ...$components);
                $this->eventManager->notify(new UnitOfCodeReportRenderedEvent($unitOfCodePosition++, $totalUnitsOfCode, $unitOfCode));
            }

            $this->componentPageRenderingService->render($reportPath, $component, ...$components);
            $this->eventManager->notify(new ComponentReportRenderingFinishedEvent($componentPosition, $totalComponents, $component));
        }

        $this->indexPageRenderingService->render($reportPath, ...$components);
        $this->eventManager->notify(new ReportRenderingFinishedEvent());
    }

    public static function templatesPath(): string
    {
        return __DIR__ . '/Template';
    }
}
