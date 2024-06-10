<?php

declare(strict_types=1);

namespace ArchAnalyzer\Service\Analysis\DependenciesFinder;

use ArchAnalyzer\Model\UnitOfCode;

class ReflectionDependenciesFinder implements DependenciesFinderInterface
{
    public function find(UnitOfCode $unitOfCode): array
    {
        try {
            assert(class_exists($unitOfCode->name(), false)
                || trait_exists($unitOfCode->name(), false)
                || interface_exists($unitOfCode->name(), false));
            $class = new \ReflectionClass($unitOfCode->name());

            $dependencies = [];

            $parent = $class->getParentClass();
            if ($parent) {
                $dependencies[] = $parent->getName();
            }

            foreach ($class->getInterfaces() as $interface) {
                $dependencies[] = $interface->getName();
            }

            foreach ($class->getTraits() as $trait) {
                $dependencies[] = $trait->getName();
            }

            $methods = array_filter(array_merge($class->getMethods(), [$class->getConstructor()]));
            foreach ($methods as $method) {
                $returnType = $method->getReturnType();
                if ($returnType && method_exists($returnType, 'getName')) {
                    $dependencies[] = $returnType->getName();
                }

                foreach ($method->getParameters() as $parameter) {
                    $type = $parameter->getType();
                    if ($type && method_exists($type, 'getName')) {
                        $dependencies[] = $type->getName();
                    }
                }
            }

            foreach ($class->getProperties() as $property) {
                if (method_exists($property, 'getType')) {
                    $propertyType = $property->getType();
                    if ($propertyType instanceof \ReflectionNamedType) {
                        $dependencies[] = $propertyType->getName();
                    }
                }
            }
        } catch (\ReflectionException $e) {
            $dependencies = [];
        }

        foreach ($dependencies as &$dependency) {
            $dependency = trim($dependency, '\\');
        }

        return array_filter(array_unique($dependencies), static function (string $dependency) {
            return !ExclusionChecker::isExclusion($dependency);
        });
    }
}
