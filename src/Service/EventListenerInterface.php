<?php

declare(strict_types=1);

namespace ArchAnalyzer\Service;

use ArchAnalyzer\Model\Event\EventInterface;

interface EventListenerInterface
{
    public function handle(EventInterface $event): void;
}
