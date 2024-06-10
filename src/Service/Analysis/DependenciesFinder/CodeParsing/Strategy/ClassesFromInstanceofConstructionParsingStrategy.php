<?php

declare(strict_types=1);

namespace ArchAnalyzer\Service\Analysis\DependenciesFinder\CodeParsing\Strategy;

class ClassesFromInstanceofConstructionParsingStrategy implements CodeParsingStrategyInterface
{
    /**
     * Возвращает типы используемые в конструкциях instanceof
     */
    public function parse(string $content): array
    {
        preg_match_all('/(?P<variable>\$\w+) +instanceof +(?P<class>[\w\\\]+)/ium', $content, $matches);

        return array_unique($matches['class']);
    }
}
