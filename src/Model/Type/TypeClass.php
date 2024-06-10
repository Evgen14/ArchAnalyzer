<?php

declare(strict_types=1);

namespace ArchAnalyzer\Model\Type;

class TypeClass extends Type
{
    /**
     * @var bool
     */
    private $isAbstract;

    public function __construct(bool $isAbstract = false)
    {
        $this->isAbstract = $isAbstract;
    }

    public static function getInstance(bool $isAbstract = false): Type
    {
        $uniqueKey = sha1(static::class . $isAbstract);
        if (!isset(self::$instances[$uniqueKey])) {
            self::$instances[$uniqueKey] = new self($isAbstract);
        }

        return self::$instances[$uniqueKey];
    }

    public function isAbstract(): bool
    {
        return $this->isAbstract;
    }

    public static function isThisType(string $fullName): bool
    {
        return class_exists($fullName);
    }
}
