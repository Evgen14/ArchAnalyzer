<?php

declare(strict_types=1);

namespace ArchAnalyzer\Service\Helper;

class StringHelper
{
    public static function removeSpaces(string $subject): string
    {
        return (string) preg_replace('/[ ]*/u', '', $subject);
    }

    public static function removeDoubleSpaces(string $subject): string
    {
        return (string) preg_replace('/[ ]+/u', ' ', $subject);
    }

    public static function escapeBackslashes(string $subject): string
    {
        return str_replace('\\', '\\\\', $subject);
    }
}
