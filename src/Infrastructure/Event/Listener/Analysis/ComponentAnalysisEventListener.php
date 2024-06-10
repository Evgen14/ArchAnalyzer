<?php

declare(strict_types=1);

namespace ArchAnalyzer\Infrastructure\Event\Listener\Analysis;

use ArchAnalyzer\Infrastructure\Console\Console;
use ArchAnalyzer\Service\Analysis\Event\ComponentAnalysisEvent;
use ArchAnalyzer\Service\Analysis\Event\ComponentAnalysisStartedEvent;
use ArchAnalyzer\Service\Analysis\Event\ComponentAnalysisFinishedEvent;
use ArchAnalyzer\Model\Event\EventInterface;
use ArchAnalyzer\Service\EventListenerInterface;

class ComponentAnalysisEventListener implements EventListenerInterface
{
    /**
     * @var array<float>
     */
    private $startedAt = [];

    public function handle(EventInterface $event): void
    {
        if (!$event instanceof ComponentAnalysisEvent) {
            return;
        }

        switch (true) {
            case $event instanceof ComponentAnalysisStartedEvent:
                $this->handleStart($event);
                break;

            case $event instanceof ComponentAnalysisFinishedEvent:
                $this->handleFinish($event);
                break;
        }
    }

    private function handleStart(ComponentAnalysisStartedEvent $event): void
    {
        $componentName = $event->getComponent()->name();

        if (!isset($this->startedAt[$componentName])) {
            $this->startedAt[$componentName] = $event->getMicroTime();
        }
    }

    private function handleFinish(ComponentAnalysisFinishedEvent $event): void
    {
        $componentName = $event->getComponent()->name();
        $startedAt = $this->startedAt[$componentName] ?? microtime(true);
        unset($this->startedAt[$componentName]);

        $executionTime = round($event->getMicroTime() - $startedAt, 3);
        Console::write(sprintf('%s: %s sec.', $componentName, $executionTime), true);
        Console::writeln();
    }
}
