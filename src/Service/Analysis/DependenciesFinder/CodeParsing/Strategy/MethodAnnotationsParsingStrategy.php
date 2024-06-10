<?php

declare(strict_types=1);

namespace ArchAnalyzer\Service\Analysis\DependenciesFinder\CodeParsing\Strategy;

use ArchAnalyzer\Service\Helper\StringHelper;

class MethodAnnotationsParsingStrategy implements CodeParsingStrategyInterface
{
    /**
     * Возвращает типы найденные в аннотациях method
     */
    public function parse(string $content): array
    {
        $pattern = '/(?P<annotation>@method[\s]+static|@method)[\s]*(?P<leftTypes>[\w\[\]|\\\\]*)[\s]+(?P<methodName>[^\(\s]+)[\s\(]+(?P<arguments>[^\(\)]*)[\)][\s:]+(?P<rightTypes>[\w\[\]|\\\\]*)/ium';
        preg_match_all($pattern, $content, $matches);

        $types = array_filter(array_merge($matches['leftTypes'], $matches['rightTypes']));

        foreach ($matches['arguments'] as $argumentsAsString) {
            $arguments = explode(',', $argumentsAsString);

            foreach ($arguments as $argumentAsString) {
                [$variableWithType] = explode('=', $argumentAsString);
                $argumentParts = array_values(array_filter(explode(' ', $variableWithType)));

                if (isset($argumentParts[1])) {
                    $types[] = $argumentParts[0];
                }
            }
        }

        $dependencies = [];
        foreach ($types as $typesAsString) {
            foreach (explode('|', str_replace('[]', '', StringHelper::removeSpaces($typesAsString))) as $type) {
                $dependencies[(string) $type] = true;
            }
        }

        return array_keys($dependencies);
    }
}
