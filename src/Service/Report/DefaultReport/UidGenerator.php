<?php

declare(strict_types=1);

namespace ArchAnalyzer\Service\Report\DefaultReport;

trait UidGenerator
{
    private function generateUid(string $name): string
    {
        return strtolower((string) preg_replace('/[ \/\\\]/', '-', $name));
    }
}
