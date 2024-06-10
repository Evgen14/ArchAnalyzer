<?php

declare(strict_types=1);

use ArchAnalyzer\Infrastructure\Event\EventManager;
use ArchAnalyzer\Infrastructure\Event\Listener\Report\ComponentReportRenderingEventListener;
use ArchAnalyzer\Infrastructure\Event\Listener\Report\ReportBuildingEventListener;
use ArchAnalyzer\Infrastructure\Event\Listener\Report\ReportRenderingEventListener;
use ArchAnalyzer\Infrastructure\Event\Listener\Report\UnitOfCodeReportRenderedEventListener;
use ArchAnalyzer\Infrastructure\Render\TwigToTemplateRendererInterfaceAdapter;
use ArchAnalyzer\Service\EventManagerInterface;
use ArchAnalyzer\Infrastructure\Event\Listener\Analysis\AnalysisEventListener;
use ArchAnalyzer\Infrastructure\Event\Listener\Analysis\ComponentAnalysisEventListener;
use ArchAnalyzer\Infrastructure\Event\Listener\Analysis\FileAnalyzedEventListener;
use ArchAnalyzer\Service\Analysis\DependenciesFinder\CompositeDependenciesFinder;
use ArchAnalyzer\Service\Analysis\DependenciesFinder\CodeParsing\CodeParsingDependenciesFinder;
use ArchAnalyzer\Service\Analysis\DependenciesFinder\CodeParsing\Strategy\ClassesCalledStaticallyParsingStrategy;
use ArchAnalyzer\Service\Analysis\DependenciesFinder\CodeParsing\Strategy\ClassesCreatedThroughNewParsingStrategy;
use ArchAnalyzer\Service\Analysis\DependenciesFinder\CodeParsing\Strategy\ClassesFromInstanceofConstructionParsingStrategy;
use ArchAnalyzer\Service\Analysis\DependenciesFinder\CodeParsing\Strategy\MethodAnnotationsParsingStrategy;
use ArchAnalyzer\Service\Analysis\DependenciesFinder\CodeParsing\Strategy\ParamAnnotationsParsingStrategy;
use ArchAnalyzer\Service\Analysis\DependenciesFinder\CodeParsing\Strategy\PropertyAnnotationsParsingStrategy;
use ArchAnalyzer\Service\Analysis\DependenciesFinder\CodeParsing\Strategy\ReturnAnnotationsParsingStrategy;
use ArchAnalyzer\Service\Analysis\DependenciesFinder\CodeParsing\Strategy\ThrowsAnnotationsParsingStrategy;
use ArchAnalyzer\Service\Analysis\DependenciesFinder\CodeParsing\Strategy\VarAnnotationsParsingStrategy;
use ArchAnalyzer\Service\Analysis\DependenciesFinder\DependenciesFinderInterface;
use ArchAnalyzer\Service\Analysis\DependenciesFinder\ReflectionDependenciesFinder;
use ArchAnalyzer\Service\Report\DefaultReport\ReportRenderingService;
use ArchAnalyzer\Service\Report\ReportRenderingServiceInterface;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

return [
    // Директория в которую будут складываться файлы отчета
    'reports_dir' => (string) getenv('ARCH_REPORTS_DIR') ?: __DIR__ . '/arch-analyzer-reports',

    // Учет vendor пакетов (каждый подключенный пакет, за исключением перечисленных в excluded, будет представлен компонентом)
    'vendor_based_components' => [
        'enabled' => true,
        'vendor_path' => '/path/to/vendor',
        'excluded' => [
            '/excluded/vendor/package/dir',
        ],
    ],

    // Общие для всех компонентов ограничения
    'restrictions' => [
        // Включение/отключение обнаружения нарушений принципа ацикличности зависимостей.
        // 'check_acyclic_dependencies_principle' => true,

        // Включение/отключение обнаружения нарушений принципа устойчивых зависимостей.
        // 'check_stable_dependencies_principle' => true,

        // Максимально допустимое расстояние до главной диагонали.
        // Элемент может отсутствовать или быть null, в таком случае ограничения не будут применены.
        // 'max_allowable_distance' => 0.1,
    ],

    // Описание компонентов и их ограничений
    'components' => [
        [
            // Требуется-ли анализировать содержимое компонента, или он описан исключительно для возможности
            // сопоставления зависимости других компонентов от элементов текущего?
            // Значение по умолчанию true (в случае отсутствия его в конфиге).
            'is_analyze_enabled' => true,
            'name' => 'FirstComponent',
            'roots' => [
                [
                    'path' => '/path/to/First/Component',
                    'namespace' => 'First\Component',
                ],
                // Иногда, особенно в старых проектах, код логически относящийся к одному компоненту, разбросан по разным частям
                // системы. В таком случае можно указать в конфиге несколько корневых директорий и, т.о. отнести их содержимое
                // какому-то одному компоненту.
                //
                // [
                //     'path' => '/path/to/component/first',
                //     'namespace' => 'Component\First',
                // ],
            ],
            //Директории или файлы, которые будут пропущены в процессе анализа
            'excluded' => [
                '/path/to/First/Component/dir1',
                '/path/to/First/Component/dir2',
            ],
            'restrictions' => [
                // Имеет приоритет над общей настройкой restrictions->max_allowable_distance
                // 'max_allowable_distance' => 0.1,

                // Список РАЗРЕШЕННЫХ исходящих зависимостей. Заполняется именами других компонентов.
                // Может отсутствовать, быть [] или null, в таком случае никакие ограничения накладываться не будут.
                // Не должен использоваться совместно с forbidden_dependencies!
                // 'allowed_dependencies' => ['SecondComponent'],

                // Список ЗАПРЕЩЕННЫХ исходящих зависимостей. Заполняется именами других компонентов.
                // Может отсутствовать, быть [] или null, в таком случае никакие ограничения накладываться не будут.
                // Не должен использоваться совместно с allowed_dependencies!
                // 'forbidden_dependencies' => ['ThirdComponent'],

                // Список публичных элементов компонента. Если отсутствует или пустой, все элементы считаются публичными.
                // Если не пустой, не перечисленные в списке элементы будут считаться приватными.
                // Не должен использоваться совместно с private_elements!
                // 'public_elements' => [
                //     First\Component\FirstClass::class,
                //     First\Component\SecondClass::class,
                //     __DIR__ . '/directory/with/public/elements',
                // ],

                // Список приватных элементов компонента. Если отсутствует или пустой, все элементы считаются публичными.
                // Не должен использоваться совместно с public_elements!
                // 'private_elements' => [
                //     First\Component\FirstClass::class,
                //     First\Component\SecondClass::class,
                //     __DIR__ . '/directory/with/private/elements',
                // ],
            ],
        ],
        [
            'name' => 'SecondComponent',
            'roots' => [
                [
                    'path' => '/path/to/Component/Second',
                    'namespace' => 'Component\Second',
                ],
            ],
        ],
        [
            'name' => 'ThirdComponent',
            'roots' => [
                [
                    'path' => '/path/to/Component/Third',
                    'namespace' => 'Component\Third',
                ],
            ],
        ],
    ],

    // Исключения
    'exclusions' => [
        'allowed_state' => [
            'enabled' => false,
            'storage' => __DIR__ . '/arch-analyzer-allowed-state.php',
        ],
    ],

    'factories' => [
        // Фабрика, собирающая DependenciesFinder
        'dependencies_finder' => static function (): DependenciesFinderInterface {
            return new CompositeDependenciesFinder(...[
                new ReflectionDependenciesFinder(),
                new CodeParsingDependenciesFinder(...[
                    new ClassesCreatedThroughNewParsingStrategy(),
                    new ClassesCalledStaticallyParsingStrategy(),
                    new ClassesFromInstanceofConstructionParsingStrategy(),
                    new PropertyAnnotationsParsingStrategy(),
                    new MethodAnnotationsParsingStrategy(),
                    new ParamAnnotationsParsingStrategy(),
                    new ReturnAnnotationsParsingStrategy(),
                    new ThrowsAnnotationsParsingStrategy(),
                    new VarAnnotationsParsingStrategy(),
                ]),
            ]);
        },
        //Фабрика, собирающая сервис рендеринга отчетов
        'report_rendering_service' => static function (EventManagerInterface $eventManager): ReportRenderingServiceInterface {
            $templatesLoader = new FilesystemLoader(ReportRenderingService::templatesPath());
            $twigRenderer = new Environment($templatesLoader);
            $twigAdapter = new TwigToTemplateRendererInterfaceAdapter($twigRenderer);
            return new ReportRenderingService($eventManager, $twigAdapter);
        },
        //Фабрика, собирающая и настраивающая EventManager
        'event_manager' => static function (): EventManagerInterface {
            return new EventManager([
                new ReportBuildingEventListener(),
                new AnalysisEventListener(),
                new ComponentAnalysisEventListener(),
                new FileAnalyzedEventListener(),
                new ReportBuildingEventListener(),
                new ReportRenderingEventListener(),
                new ComponentReportRenderingEventListener(),
                new UnitOfCodeReportRenderedEventListener(),
            ]);
        }
    ],
];