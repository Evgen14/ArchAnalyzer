<?php

declare(strict_types=1);

namespace ArchAnalyzer\Infrastructure\Event\Listener\Analysis;

use ArchAnalyzer\Infrastructure\Console\Console;
use ArchAnalyzer\Service\Analysis\Event\AnalysisFinishedEvent;
use ArchAnalyzer\Service\Analysis\Event\AnalysisStartedEvent;
use ArchAnalyzer\Model\Event\EventInterface;
use ArchAnalyzer\Service\EventListenerInterface;

class AnalysisEventListener implements EventListenerInterface
{
    /**
     * @var float|null 
     */
    private $startedAt;

    public function handle(EventInterface $event): void
    {
        switch (true) {
            case $event instanceof AnalysisStartedEvent:
                $this->handleStart($event);
                break;

            case $event instanceof AnalysisFinishedEvent:
                $this->handleFinish($event);
                break;
        }
    }

    private function handleStart(AnalysisStartedEvent $event): void
    {
        if (!$this->startedAt) {
            $this->startedAt = $event->getMicroTime();
        }

        Console::writeln('//////////////////////////////');
        Console::writeln('//     Analysis started     //');
        Console::writeln('//////////////////////////////');
        Console::writeln();
    }

    private function handleFinish(AnalysisFinishedEvent $event): void
    {
        $startedAt = $this->startedAt ?? microtime(true);
        $this->startedAt = null;

        $executionTime = round($event->getMicroTime() - $startedAt, 3);
        Console::write(sprintf('Analysis finished. Execution time: %s sec.', $executionTime), true);
        Console::writeln();
    }
}
