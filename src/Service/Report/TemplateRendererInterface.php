<?php

declare(strict_types=1);

namespace ArchAnalyzer\Service\Report;

interface TemplateRendererInterface
{
    /**
     * @param string $name Template name
     * @param array<string, mixed> $variables Template variables
     * @return string
     */
    public function render(string $name, array $variables = []): string;
}
