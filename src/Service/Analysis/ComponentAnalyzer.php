<?php

declare(strict_types=1);

namespace ArchAnalyzer\Service\Analysis;

use ArchAnalyzer\Service\Helper\PathHelper;
use ArchAnalyzer\Model\Component;
use ArchAnalyzer\Service\Analysis\Event\FileAnalyzedEvent;
use ArchAnalyzer\Service\EventManagerInterface;
use ArchAnalyzer\Model\Path;
use ArchAnalyzer\Model\UnitOfCode;
use ArchAnalyzer\Service\Analysis\DependenciesFinder\DependenciesFinderInterface;

class ComponentAnalyzer
{
    /**
     * @var DependenciesFinderInterface
     */
    private $dependenciesFinder;

    /**
     * @var EventManagerInterface
     */
    private $eventManager;

    public function __construct(DependenciesFinderInterface $dependenciesFinder, EventManagerInterface $eventManager)
    {
        $this->dependenciesFinder = $dependenciesFinder;
        $this->eventManager = $eventManager;
    }

    public function analyze(Component $component): void
    {
        if (!$component->isEnabledForAnalysis()) {
            return;
        }

        $analyzedFileIndex = 0;
        $totalFiles = $this->getFiles($component->rootPaths())->count();

        foreach ($component->rootPaths() as $path) {
            /** @var \SplFileInfo $file */
            foreach ($this->getFiles([$path]) as $file) {
                $analyzedFileIndex++;

                $fullPath = $file->getRealPath();
                if (!$fullPath) {
                    continue;
                }

                $fileAnalyzedEvent = new FileAnalyzedEvent($analyzedFileIndex, $totalFiles, $fullPath);

                if ($component->isExcluded($fullPath)) {
                    $fileAnalyzedEvent->toSkipped();
                    $this->eventManager->notify($fileAnalyzedEvent);
                    continue;
                }

                $fullName = PathHelper::removeDoubleBackslashes($path->namespace() .
                    PathHelper::pathToNamespace($path->getRelativePath($fullPath)));

                $unitOfCode = UnitOfCode::create($fullName, $component, $fullPath);
                $dependencies = $this->dependenciesFinder->find($unitOfCode);
                foreach ($dependencies as $dependency) {
                    $unitOfCode->addOutputDependency(UnitOfCode::create($dependency));
                }

                $this->eventManager->notify($fileAnalyzedEvent);
            }
        }
    }

    /**
     * @param array<Path> $paths
     * @param array<string> $shebangTemplates example: ['/usr/bin/env php', '/usr/bin/php']
     *
     * @return CompositeCountableIterator<\SplFileInfo>
     */
    private function getFiles(
        array $paths,
        string $fileExtension = '.php',
        array $shebangTemplates = ['/usr/bin/env php', '/usr/bin/php']
    ): CompositeCountableIterator {
        $filesIterator = new CompositeCountableIterator();
        foreach ($paths as $path) {
            $recursiveDirectoryIterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path->path()));
            $phpExtIterator = new \RegexIterator($recursiveDirectoryIterator, "/\\$fileExtension$/i");
            $filesIterator->addIterator($phpExtIterator);

            $phpFilesWithoutPhpExtensions = [];
            $notPhpExtIterator = new \RegexIterator($recursiveDirectoryIterator, "/^((?!\\$fileExtension).)*$/i");

            /** @var \SplFileInfo $notPhpFile */
            foreach ($notPhpExtIterator as $notPhpFile) {
                if (!$notPhpFile->isFile()) {
                    continue;
                }

                $content = file_get_contents($notPhpFile->getRealPath());
                if (!$content) {
                    continue;
                }

                foreach ($shebangTemplates as $shebang) {
                    if (false !== stripos($content, "#!$shebang")) {
                        $phpFilesWithoutPhpExtensions[] = $notPhpFile;
                        break;
                    }
                }
            }

            if (!empty($phpFilesWithoutPhpExtensions)) {
                $filesIterator->addIterator(new \ArrayIterator($phpFilesWithoutPhpExtensions));
            }
        }

        return $filesIterator;
    }
}
