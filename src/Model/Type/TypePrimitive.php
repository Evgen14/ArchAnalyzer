<?php

declare(strict_types=1);

namespace ArchAnalyzer\Model\Type;

class TypePrimitive extends Type
{
    private const EXISTING_PRIMITIVE_TYPES = [
        'int',
        'bool',
        'float',
        'string',
        'array',
        'object',
        'iterable',
        'callable',
        'resource',
        'integer',
        'boolean'
    ];

    private const EXISTING_PSEUDO_TYPES = [
        'mixed',
        'number',
        'callback',
        'void',
        'null'
    ];

    public function isAbstract(): ?bool
    {
        return null;
    }

    public static function isThisType(string $fullName): bool
    {
        return in_array($fullName, array_merge(
            self::EXISTING_PRIMITIVE_TYPES,
            self::EXISTING_PSEUDO_TYPES
        ), true);
    }
}
