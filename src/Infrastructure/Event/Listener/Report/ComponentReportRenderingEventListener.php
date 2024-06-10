<?php

declare(strict_types=1);

namespace ArchAnalyzer\Infrastructure\Event\Listener\Report;

use ArchAnalyzer\Infrastructure\Console\Console;
use ArchAnalyzer\Model\Event\EventInterface;
use ArchAnalyzer\Service\EventListenerInterface;
use ArchAnalyzer\Service\Report\DefaultReport\Event\ComponentReportRenderingEvent;
use ArchAnalyzer\Service\Report\DefaultReport\Event\ComponentReportRenderingFinishedEvent;
use ArchAnalyzer\Service\Report\DefaultReport\Event\ComponentReportRenderingStartedEvent;

class ComponentReportRenderingEventListener implements EventListenerInterface
{
    /**
     * @var array<float>
     */
    private $startedAt = [];

    public function handle(EventInterface $event): void
    {
        if (!$event instanceof ComponentReportRenderingEvent) {
            return;
        }

        switch (true) {
            case $event instanceof ComponentReportRenderingStartedEvent:
                $this->handleStart($event);
                break;

            case $event instanceof ComponentReportRenderingFinishedEvent:
                $this->handleFinish($event);
                break;
        }
    }

    private function handleStart(ComponentReportRenderingStartedEvent $event): void
    {
        $componentName = $event->getComponent()->name();

        if (!isset($this->startedAt[$componentName])) {
            $this->startedAt[$componentName] = $event->getMicroTime();
        }
    }

    private function handleFinish(ComponentReportRenderingFinishedEvent $event): void
    {
        $componentName = $event->getComponent()->name();
        $startedAt = $this->startedAt[$componentName] ?? microtime(true);
        unset($this->startedAt[$componentName]);

        $executionTime = round($event->getMicroTime() - $startedAt, 3);
        Console::write(sprintf('%s: %s sec.', $componentName, $executionTime), true);
        Console::writeln();
    }
}
