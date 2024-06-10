<?php

declare(strict_types=1);

namespace ArchAnalyzer\Model\Type;

class TypeTrait extends Type
{
    public function isAbstract(): ?bool
    {
        return null;
    }

    public static function isThisType(string $fullName): bool
    {
        return trait_exists($fullName, false);
    }
}
