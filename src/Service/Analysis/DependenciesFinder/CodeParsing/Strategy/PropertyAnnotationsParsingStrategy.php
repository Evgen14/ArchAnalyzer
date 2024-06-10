<?php

declare(strict_types=1);

namespace ArchAnalyzer\Service\Analysis\DependenciesFinder\CodeParsing\Strategy;

use ArchAnalyzer\Service\Helper\StringHelper;

class PropertyAnnotationsParsingStrategy implements CodeParsingStrategyInterface
{
    /**
     * Возвращает типы найденные в аннотациях param
     */
    public function parse(string $content): array
    {
        $pattern = '/(@property|@property-read|@property-write)[\s]+(?P<types>[^$]*)/ium';
        preg_match_all($pattern, $content, $matches);

        $dependencies = [];
        foreach (array_filter($matches['types']) as $typesAsString) {
            foreach (explode('|', str_replace('[]', '', StringHelper::removeSpaces($typesAsString))) as $type) {
                $dependencies[(string) $type] = true;
            }
        }

        return array_keys($dependencies);
    }
}
