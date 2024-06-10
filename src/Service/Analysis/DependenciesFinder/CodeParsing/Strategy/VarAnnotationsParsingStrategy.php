<?php

declare(strict_types=1);

namespace ArchAnalyzer\Service\Analysis\DependenciesFinder\CodeParsing\Strategy;

use ArchAnalyzer\Service\Helper\StringHelper;

class VarAnnotationsParsingStrategy implements CodeParsingStrategyInterface
{
    /**
     * Возвращает типы найденные в аннотациях var
     */
    public function parse(string $content): array
    {
        $filter = static function (string $element) {
            return !empty($element)
                && !is_numeric($element)
                && strpos($element, '$') === false;
        };

        $groupPattern = '\s*([\w|\[\]\\\\\$]*)';
        preg_match_all("/@var{$groupPattern}{$groupPattern}/ium", $content, $matches);
        [, $group1, $group2] = $matches;

        $dependencies = [];
        foreach (array_merge(array_filter($group1, $filter), array_filter($group2, $filter)) as $one) {
            foreach (explode('|', str_replace('[]', '', StringHelper::removeSpaces($one))) as $type) {
                $dependencies[(string) $type] = true;
            }
        }

        return array_keys($dependencies);
    }
}
