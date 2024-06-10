<?php

declare(strict_types=1);

namespace ArchAnalyzer\Service\Report\DefaultReport\Event;

use ArchAnalyzer\Model\Component;
use ArchAnalyzer\Model\Event\EventInterface;
use ArchAnalyzer\Model\Event\ProgressiveTrait;
use ArchAnalyzer\Model\Event\TimedTrait;

abstract class ComponentReportRenderingEvent implements EventInterface
{
    use TimedTrait, ProgressiveTrait;

    /** @var Component */
    private $component;

    /**
     * @param int $position
     * @param int $totalPositions
     * @param Component $component
     */
    public function __construct(int $position, int $totalPositions, Component $component)
    {
        $this->position = $position;
        $this->totalPositions = $totalPositions;
        $this->component = $component;
        $this->microTime = microtime(true);
    }

    /**
     * @return Component
     */
    public function getComponent(): Component
    {
        return $this->component;
    }
}
