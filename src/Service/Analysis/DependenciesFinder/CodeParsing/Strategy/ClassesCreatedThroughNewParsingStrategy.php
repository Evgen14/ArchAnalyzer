<?php

declare(strict_types=1);

namespace ArchAnalyzer\Service\Analysis\DependenciesFinder\CodeParsing\Strategy;

class ClassesCreatedThroughNewParsingStrategy implements CodeParsingStrategyInterface
{
    /**
     * Возвращает типы, экземпляры которых создаются через new
     */
    public function parse(string $content): array
    {
        preg_match_all('/new\s*([^(]*)/ium', $content, $matches);
        [, $result] = $matches;

        return array_unique($result);
    }
}
