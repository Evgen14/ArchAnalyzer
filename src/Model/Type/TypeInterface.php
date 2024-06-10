<?php

declare(strict_types=1);

namespace ArchAnalyzer\Model\Type;

class TypeInterface extends Type
{
    public function isAbstract(): bool
    {
        return true;
    }

    public static function isThisType(string $fullName): bool
    {
        return interface_exists($fullName, false);
    }
}
