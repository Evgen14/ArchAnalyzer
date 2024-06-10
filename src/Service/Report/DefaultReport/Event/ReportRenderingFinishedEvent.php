<?php

declare(strict_types=1);

namespace ArchAnalyzer\Service\Report\DefaultReport\Event;

use ArchAnalyzer\Model\Event\EventInterface;
use ArchAnalyzer\Model\Event\TimedTrait;

class ReportRenderingFinishedEvent implements EventInterface
{
    use TimedTrait;

    public function __construct()
    {
        $this->microTime = microtime(true);
    }
}
