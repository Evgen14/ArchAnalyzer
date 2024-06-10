<?php

declare(strict_types=1);

namespace ArchAnalyzer\Service\Report;

use ArchAnalyzer\Model\Component;

interface ReportRenderingServiceInterface
{
    public function render(string $reportPath, Component ...$components): void;
}
