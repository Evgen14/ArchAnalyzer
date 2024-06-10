<?php

declare(strict_types=1);

namespace ArchAnalyzer\Service\Analysis\DependenciesFinder\CodeParsing\Strategy;

class ClassesCalledStaticallyParsingStrategy implements CodeParsingStrategyInterface
{
    /**
     * Возвращает типы, к которым есть обращения через ::
     */
    public function parse(string $content): array
    {
        preg_match_all('/([\w\\\]*)\s*:{2}/um', $content, $matches);
        [, $result] = $matches;

        return array_unique($result);
    }
}
