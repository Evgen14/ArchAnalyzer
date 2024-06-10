<?php

declare(strict_types=1);

namespace ArchAnalyzer\Model;

use ArchAnalyzer\Service\Helper\PathHelper;

class Path
{
    /**
     * @var string
     */
    private $path;

    /**
     * @var string
     */
    private $namespace;

    public function __construct(string $path, string $namespace = '')
    {
        $this->namespace = $namespace;
        $this->path = $path;

        if ($path) {
            $this->path = (string) realpath($path) ?: $path;
        }
    }

    /**
     * @param string $value directory path, filepath or namespace
     */
    public static function fromString(string $value): self
    {
        if (class_exists($value) || trait_exists($value) || interface_exists($value)) {
            return new self((string) PathHelper::detectPath($value), $value);
        }

        if (file_exists($value) || is_dir($value)) {
            $value = realpath($value);
        }

        return new self($value);
    }

    public function path(): string
    {
        return $this->path;
    }

    public function namespace(): string
    {
        return $this->namespace;
    }

    public function getRelativePath(string $realPath): string
    {
        return str_replace($this->path(), '', $realPath);
    }

    public function isPartOfPath(string $fullPath): bool
    {
        return stripos($fullPath, $this->path()) === 0;
    }

    public function isPartOfNamespace(string $namespace): bool
    {
        return stripos($namespace, $this->namespace()) === 0;
    }

    public function isContains(UnitOfCode $unitOfCode): bool
    {
        if ($unitOfCode->path() !== null) {
            return $this->isPartOfPath($unitOfCode->path());
        }

        return $this->isPartOfNamespace($unitOfCode->name());
    }
}
