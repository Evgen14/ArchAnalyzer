<?php

declare(strict_types=1);

namespace ArchAnalyzer\Model\Event;

trait TimedTrait
{
    /**
     * @var float
     */
    private $microTime;

    public function getMicroTime(): float
    {
        return $this->microTime;
    }
}
