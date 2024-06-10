<?php

declare(strict_types=1);

namespace ArchAnalyzer\Infrastructure\Event\Listener\Analysis;

use ArchAnalyzer\Infrastructure\Console\Console;
use ArchAnalyzer\Infrastructure\Console\ProgressBar;
use ArchAnalyzer\Service\Analysis\Event\AnalysisStartedEvent;
use ArchAnalyzer\Service\Analysis\Event\ComponentAnalysisStartedEvent;
use ArchAnalyzer\Service\Analysis\Event\FileAnalyzedEvent;
use ArchAnalyzer\Model\Event\EventInterface;
use ArchAnalyzer\Service\EventListenerInterface;

class FileAnalyzedEventListener implements EventListenerInterface
{
    /**
     * @var AnalysisStartedEvent
     */
    private $lastAnalysisStartedEvent;

    /**
     * @var ComponentAnalysisStartedEvent
     */
    private $lastComponentAnalysisStartedEvent;

    /**
     * @var int
     */
    private $counter = 0;

    public function handle(EventInterface $event): void
    {
        switch (true) {
            case $event instanceof AnalysisStartedEvent:
                $this->lastAnalysisStartedEvent = $event;
                break;

            case $event instanceof ComponentAnalysisStartedEvent:
                $this->lastComponentAnalysisStartedEvent = $event;
                break;

            case $event instanceof FileAnalyzedEvent:
                $this->counter++;
                if ($this->counter % 10 !== 0) {
                    return;
                }

                $executionTime = (int) (microtime(true) - $this->lastAnalysisStartedEvent->getMicroTime());
                $componentAnalysisProgress = $this->calculateComponentAnalysisProgress($event);
                $fullProgress = $this->calculateFullProgress($componentAnalysisProgress);

                $progressOutput = $this->getFullProgressBar()->getOutput($fullProgress) .
                    $this->getComponentAnalysisProgressBar()->getOutput($componentAnalysisProgress, sprintf(
                        '[%ss] %s: [%s] %s',
                        $executionTime,
                        $this->lastComponentAnalysisStartedEvent->getComponent()->name(),
                        $event->getStatus(),
                        $event->getFullPath()
                    )) . "\r";
                Console::write($progressOutput);
                break;
        }
    }

    private function calculateComponentAnalysisProgress(FileAnalyzedEvent $event): int
    {
        return (int) ($event->getPosition() / $event->getTotalPositions() * 100);
    }

    private function calculateFullProgress(int $componentAnalysisProgress): int
    {
        $stage = 100 / $this->lastComponentAnalysisStartedEvent->getTotalPositions();
        $progressOfStage = $stage / 100 * $componentAnalysisProgress;

        return (int) ($progressOfStage +
            ($this->lastComponentAnalysisStartedEvent->getPosition() /
                $this->lastComponentAnalysisStartedEvent->getTotalPositions() * 100));
    }

    private function getFullProgressBar(): ProgressBar
    {
        return ProgressBar::getInstance(0, 25);
    }

    private function getComponentAnalysisProgressBar(): ProgressBar
    {
        return ProgressBar::getInstance(75, 75);
    }
}
