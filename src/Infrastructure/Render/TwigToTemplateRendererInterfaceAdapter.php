<?php

declare(strict_types=1);

namespace ArchAnalyzer\Infrastructure\Render;

use ArchAnalyzer\Service\Report\TemplateRendererInterface;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class TwigToTemplateRendererInterfaceAdapter implements TemplateRendererInterface
{
    /** @var Environment */
    private $twig;

    /**
     * @param Environment $twig
     */
    public function __construct(Environment $twig)
    {
        $this->twig = $twig;
    }

    /**
     * @param string $name
     * @param array<string, mixed> $variables
     * @return string
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function render(string $name, array $variables = []): string
    {
        return $this->twig->render($name, $variables);
    }
}
