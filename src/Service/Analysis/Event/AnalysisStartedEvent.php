<?php

declare(strict_types=1);

namespace ArchAnalyzer\Service\Analysis\Event;

use ArchAnalyzer\Model\Event\EventInterface;
use ArchAnalyzer\Model\Event\TimedTrait;

class AnalysisStartedEvent implements EventInterface
{
    use TimedTrait;

    public function __construct()
    {
        $this->microTime = microtime(true);
    }
}
