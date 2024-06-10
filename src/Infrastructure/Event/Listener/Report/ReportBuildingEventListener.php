<?php

declare(strict_types=1);

namespace ArchAnalyzer\Infrastructure\Event\Listener\Report;

use ArchAnalyzer\Infrastructure\Console\Console;
use ArchAnalyzer\Model\Event\EventInterface;
use ArchAnalyzer\Service\EventListenerInterface;
use ArchAnalyzer\Service\Report\DefaultReport\Event\ReportBuildingFinishedEvent;
use ArchAnalyzer\Service\Report\DefaultReport\Event\ReportBuildingStartedEvent;

class ReportBuildingEventListener implements EventListenerInterface
{
    /**
     * @var float|null
     */
    private $startedAt;

    public function handle(EventInterface $event): void
    {
        switch (true) {
            case $event instanceof ReportBuildingStartedEvent:
                $this->handleStart($event);
                break;

            case $event instanceof ReportBuildingFinishedEvent:
                $this->handleFinish($event);
                break;
        }
    }

    private function handleStart(ReportBuildingStartedEvent $event): void
    {
        if (!$this->startedAt) {
            $this->startedAt = $event->getMicroTime();
        }

        Console::writeln('   Report building started    ');
        Console::writeln('‾‾‾‾‾‾‾‾‾‾‾‾‾‾‾‾‾‾‾‾‾‾‾‾‾‾‾‾‾‾');
    }

    private function handleFinish(ReportBuildingFinishedEvent $event): void
    {
        $startedAt = $this->startedAt ?? microtime(true);
        $this->startedAt = null;

        $executionTime = round($event->getMicroTime() - $startedAt, 3);
        Console::writeln('_____________________________________________________');
        Console::writeln(sprintf('Report building finished. Execution time: %s sec.', $executionTime));
        Console::writeln();
    }
}
