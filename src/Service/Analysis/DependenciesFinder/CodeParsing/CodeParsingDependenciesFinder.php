<?php

declare(strict_types=1);

namespace ArchAnalyzer\Service\Analysis\DependenciesFinder\CodeParsing;

use ArchAnalyzer\Service\Helper\PathHelper;
use ArchAnalyzer\Service\Helper\StringHelper;
use ArchAnalyzer\Model\Type\TypePrimitive;
use ArchAnalyzer\Model\UnitOfCode;
use ArchAnalyzer\Service\Analysis\DependenciesFinder\CodeParsing\Strategy\CodeParsingStrategyInterface;
use ArchAnalyzer\Service\Analysis\DependenciesFinder\DependenciesFinderInterface;
use ArchAnalyzer\Service\Analysis\DependenciesFinder\ExclusionChecker;

class CodeParsingDependenciesFinder implements DependenciesFinderInterface
{
    /**
     * @var array<CodeParsingStrategyInterface>
     */
    private $codeParsingStrategies;

    public function __construct(CodeParsingStrategyInterface ...$codeParsingStrategies)
    {
        $this->codeParsingStrategies = $codeParsingStrategies;
    }

    public function find(UnitOfCode $unitOfCode): array
    {
        $dependencies = [];
        if ($unitOfCode->path() === null
            || !$content = file_get_contents($unitOfCode->path())
        ) {
            return $dependencies;
        }

        preg_match('/namespace +(?P<namespace>[\w\\\]+);/ium', $content, $matches);
        $namespace = $matches['namespace'] ?? '';

        [$existingClasses, $importedNamespaceParts] = $this->parseUses($content);
        $dependencies[] = $existingClasses;

        [$existingClasses, $importedClassNames] = $this->parseCode($content);
        $dependencies[] = $existingClasses;

        $dependencies = array_merge(...$dependencies);
        foreach ($importedClassNames as $importedClassName) {
            $tmp = explode('\\', $importedClassName);
            $startOfImportedClassName = trim(array_shift($tmp));

            $dependency = PathHelper::removeDoubleBackslashes($namespace . '\\' . $importedClassName);
            if ($this->isElementExists($dependency)) {
                $dependencies[] = $dependency;
                continue;
            }

            foreach ($importedNamespaceParts as $importedNamespacePart) {
                $tmp = explode('\\', $importedNamespacePart);
                $endOfImportedNamespacePart = trim(array_pop($tmp));
                $importedNamespacePart = implode('\\', $tmp);

                if ($startOfImportedClassName !== $endOfImportedNamespacePart) {
                    continue;
                }

                $dependencies[] = PathHelper::removeDoubleBackslashes($importedNamespacePart . '\\' . $importedClassName);
            }
        }

        foreach ($dependencies as &$dependency) {
            $dependency = trim($dependency, '\\');
        }

        return array_unique($dependencies);
    }

    /**
     * @return array<array<string>>
     */
    private function parseUses(string $content): array
    {
        $existingClasses = [];
        $importedNamespaceParts = [];

        preg_match_all('/^use ([^;]*);$/ium', $content, $matches);
        [, $results] = $matches;

        foreach ($results as $row) {
            $row = preg_replace('/( {2,}|[\n]+)/ium', ' ', $row);

            if (preg_match('/(.*){(.*)}/u', $row, $matches)) {
                $rows = [];
                [, $commonNamespacePart, $details] = $matches;
                $specificNamespaceParts = explode(',', $details);
                foreach ($specificNamespaceParts as $specificNamespacePart) {
                    $rows[] = $commonNamespacePart . $specificNamespacePart;
                }
            } else {
                $rows = [$row];
            }

            foreach ($rows as $one) {
                [$className] = explode(' as ', StringHelper::removeDoubleSpaces($one));
                $namespace = StringHelper::removeSpaces($className);

                if ($this->isElementExists($namespace)) {
                    $existingClasses[$namespace] = true;
                } else {
                    $importedNamespaceParts[$namespace] = true;
                }
            }
        }
        return [array_keys($existingClasses), array_keys($importedNamespaceParts)];
    }

    /**
     * @return array<array<string>>
     */
    private function parseCode(string $content): array
    {
        $dependencies = [];
        foreach ($this->codeParsingStrategies as $codeParsingStrategy) {
            $dependencies[] = $codeParsingStrategy->parse($content);
        }

        $fullNames = [];
        $importedClassNames = [];
        foreach (array_unique(array_merge(...$dependencies)) as $dependency) {
            if (ExclusionChecker::isExclusion($dependency)) {
                continue;
            }

            if (TypePrimitive::isThisType($dependency)
                || $this->isElementExists($dependency)
            ) {
                $fullNames[] = $dependency;
            } else {
                $importedClassNames[] = $dependency;
            }
        }

        return [$fullNames, $importedClassNames];
    }

    private function isElementExists(string $name): bool
    {
        return class_exists($name, false) || interface_exists($name, false) || trait_exists($name, false);
    }
}
