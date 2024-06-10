<?php

declare(strict_types=1);

namespace ArchAnalyzer\Service\Analysis\DependenciesFinder\CodeParsing\Strategy;

use ArchAnalyzer\Service\Helper\StringHelper;

class ThrowsAnnotationsParsingStrategy implements CodeParsingStrategyInterface
{
    /**
     * Возвращает типы исключений найденные в аннотациях throws
     */
    public function parse(string $content): array
    {
        preg_match_all("/@throws\s+(?P<exceptions>[\w|\\\\\$]*)/ium", $content, $matches);

        $dependencies = [];
        foreach (array_filter($matches['exceptions']) as $exceptionsAsString) {
            foreach (explode('|', StringHelper::removeSpaces($exceptionsAsString)) as $exception) {
                $dependencies[(string) $exception] = true;
            }
        }

        return array_keys($dependencies);
    }
}
