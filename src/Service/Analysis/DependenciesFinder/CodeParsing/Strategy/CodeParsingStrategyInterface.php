<?php

declare(strict_types=1);

namespace ArchAnalyzer\Service\Analysis\DependenciesFinder\CodeParsing\Strategy;

interface CodeParsingStrategyInterface
{
    /**
     * @return array<string>
     */
    public function parse(string $content): array;
}
