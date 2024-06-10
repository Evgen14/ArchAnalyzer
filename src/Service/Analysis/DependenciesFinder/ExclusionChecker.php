<?php

declare(strict_types=1);

namespace ArchAnalyzer\Service\Analysis\DependenciesFinder;

class ExclusionChecker
{
    public static function isExclusion(string $element): bool
    {
        return in_array($element, ['self', 'static', 'parent', 'void']);
    }
}
