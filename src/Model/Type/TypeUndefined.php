<?php

declare(strict_types=1);

namespace ArchAnalyzer\Model\Type;

class TypeUndefined extends Type
{
    public function isAbstract(): ?bool
    {
        return null;
    }

    public static function isThisType(string $fullName): bool
    {
        return !TypeInterface::isThisType($fullName)
            && !TypeClass::isThisType($fullName)
            && !TypeTrait::isThisType($fullName)
            && !TypePrimitive::isThisType($fullName);
    }
}
