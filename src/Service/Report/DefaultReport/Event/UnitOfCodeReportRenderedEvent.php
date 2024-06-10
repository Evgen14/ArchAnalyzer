<?php

declare(strict_types=1);

namespace ArchAnalyzer\Service\Report\DefaultReport\Event;

use ArchAnalyzer\Model\Event\EventInterface;
use ArchAnalyzer\Model\Event\ProgressiveTrait;
use ArchAnalyzer\Model\UnitOfCode;

class UnitOfCodeReportRenderedEvent implements EventInterface
{
    use ProgressiveTrait;

    /** @var UnitOfCode */
    private $unitOfCOde;

    /**
     * @param int $position
     * @param int $totalPositions
     * @param UnitOfCode $unitOfCode
     */
    public function __construct(
        int $position,
        int $totalPositions,
        UnitOfCode $unitOfCode
    ) {
        $this->position = $position;
        $this->totalPositions = $totalPositions;
        $this->unitOfCOde = $unitOfCode;
    }

    /**
     * @return UnitOfCode
     */
    public function getUnitOfCode(): UnitOfCode
    {
        return $this->unitOfCOde;
    }
}
