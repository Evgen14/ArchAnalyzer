<?php

declare(strict_types=1);

namespace ArchAnalyzer\Service\Helper;

class PathHelper
{
    public static function removeDoubleSlashes(string $subject): string
    {
        return (string) preg_replace("/\/{2,}/u", '/', $subject);
    }

    public static function removeDoubleBackslashes(string $subject): string
    {
        return (string) preg_replace("/\\\{2,}/u", '\\', $subject);
    }

    public static function pathToNamespace(string $filePath): string
    {
        return str_replace(['/', '.php'], ['\\', ''], self::removeDoubleSlashes($filePath));
    }

    public static function detectPath(string $fullName): ?string
    {
        try {
            assert(class_exists($fullName, false)
                || trait_exists($fullName, false)
                || interface_exists($fullName, false));
            $reflection = new \ReflectionClass($fullName);
            $path = $reflection->getFileName() ?: null;
        } catch (\ReflectionException $e) {
            $path = null;
        }

        return $path;
    }
}
