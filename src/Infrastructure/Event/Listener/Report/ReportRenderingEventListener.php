<?php

declare(strict_types=1);

namespace ArchAnalyzer\Infrastructure\Event\Listener\Report;

use ArchAnalyzer\Infrastructure\Console\Console;
use ArchAnalyzer\Model\Event\EventInterface;
use ArchAnalyzer\Service\EventListenerInterface;
use ArchAnalyzer\Service\Report\DefaultReport\Event\ReportRenderingFinishedEvent;
use ArchAnalyzer\Service\Report\DefaultReport\Event\ReportRenderingStartedEvent;

class ReportRenderingEventListener implements EventListenerInterface
{
    /**
     * @var float|null
     */
    private $startedAt;

    public function handle(EventInterface $event): void
    {
        switch (true) {
            case $event instanceof ReportRenderingStartedEvent:
                $this->handleStart($event);
                break;

            case $event instanceof ReportRenderingFinishedEvent:
                $this->handleFinish($event);
                break;
        }
    }

    private function handleStart(ReportRenderingStartedEvent $event): void
    {
        if (!$this->startedAt) {
            $this->startedAt = $event->getMicroTime();
        }

        Console::writeln('//////////////////////////////');
        Console::writeln('// Report rendering started //');
        Console::writeln('//////////////////////////////');
        Console::writeln();
    }

    private function handleFinish(ReportRenderingFinishedEvent $event): void
    {
        $startedAt = $this->startedAt ?? microtime(true);
        $this->startedAt = null;

        $executionTime = round($event->getMicroTime() - $startedAt, 3);
        Console::write(sprintf('Report rendering finished. Execution time: %s sec.', $executionTime), true);
        Console::writeln();
    }
}
