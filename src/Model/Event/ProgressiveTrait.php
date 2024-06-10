<?php

declare(strict_types=1);

namespace ArchAnalyzer\Model\Event;

trait ProgressiveTrait
{
    /**
     * @var int
     */
    private $position;

    /**
     * @var int
     */
    private $totalPositions;

    public function getPosition(): int
    {
        return $this->position;
    }

    public function getTotalPositions(): int
    {
        return $this->totalPositions;
    }
}
