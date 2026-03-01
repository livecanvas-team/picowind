<?php

declare(strict_types=1);

use Isolated\Symfony\Component\Finder\Finder;

// You can do your own things here, e.g. collecting symbols to expose dynamically
// or files to exclude.
// However beware that this file is executed by PHP-Scoper, hence if you are using
// the PHAR it will be loaded by the PHAR. So it is highly recommended to avoid
// to auto-load any code here: it can result in a conflict or even corrupt
// the PHP-Scoper analysis.

$wp_classes   = json_decode(file_get_contents('deploy/php-scoper-wordpress-excludes-master/generated/exclude-wordpress-classes.json'));
$wp_functions = json_decode(file_get_contents('deploy/php-scoper-wordpress-excludes-master/generated/exclude-wordpress-functions.json'));
$wp_constants = json_decode(file_get_contents('deploy/php-scoper-wordpress-excludes-master/generated/exclude-wordpress-constants.json'));

/**
 * @see https://github.com/humbug/php-scoper/blob/main/docs/further-reading.md#polyfills
 */
$polyfillsBootstraps = array_map(
    static fn (SplFileInfo $fileInfo) => $fileInfo->getPathname(),
    iterator_to_array(
        Finder::create()
            ->files()
            ->in(dirname(__DIR__) . '/vendor/symfony/polyfill-*')
            ->name('bootstrap*.php'),
        false,
    ),
);

$polyfillsStubs = array_map(
    static fn (SplFileInfo $fileInfo) => $fileInfo->getPathname(),
    iterator_to_array(
        Finder::create()
            ->files()
            ->in(dirname(__DIR__) . '/vendor/symfony/polyfill-*/Resources/stubs')
            ->name('*.php'),
        false,
    ),
);

return [
    // The prefix configuration. If a non null value is be used, a random prefix
    // will be generated instead.
    //
    // For more see: https://github.com/humbug/php-scoper/blob/master/docs/configuration.md#prefix
    'prefix' => 'PicowindDeps',

    // By default when running php-scoper add-prefix, it will prefix all relevant code found in the current working
    // directory. You can however define which files should be scoped by defining a collection of Finders in the
    // following configuration key.
    //
    // This configuration entry is completely ignored when using Box.
    //
    // For more see: https://github.com/humbug/php-scoper/blob/master/docs/configuration.md#finders-and-paths
    // 'finders' => [],

    // List of excluded files, i.e. files for which the content will be left untouched.
    // Paths are relative to the configuration file unless if they are already absolute
    //
    // For more see: https://github.com/humbug/php-scoper/blob/master/docs/configuration.md#patchers
    'exclude-files' => [
        ...$polyfillsBootstraps,
        ...$polyfillsStubs,
    ],

    // When scoping PHP files, there will be scenarios where some of the code being scoped indirectly references the
    // original namespace. These will include, for example, strings or string manipulations. PHP-Scoper has limited
    // support for prefixing such strings. To circumvent that, you can define patchers to manipulate the file to your
    // heart contents.
    //
    // For more see: https://github.com/humbug/php-scoper/blob/master/docs/configuration.md#patchers
    'patchers' => [
        // Fix Symfony Cache ValueWrapper class (uses © character as class name)
        static function (string $filePath, string $prefix, string $contents): string {
            // Preserve the class name in ValueWrapper.php
            if (str_ends_with($filePath, 'symfony/cache/Traits/ValueWrapper.php')) {
                $contents = preg_replace('/^class\s+[^\s\{]+/m', 'class ©', $contents);
            }
            
            // Update the constant reference in CacheItem.php
            if (str_ends_with($filePath, 'symfony/cache/CacheItem.php')) {
                $contents = str_replace(
                    'private const VALUE_WRAPPER = "\xa9";',
                    'private const VALUE_WRAPPER = \'' . $prefix . '\©\';',
                    $contents
                );
            }
            
            // Fix Redis proxy for PhpRedis 6.3.0+ compatibility
            // Force use of Redis6Proxy instead of Redis5Proxy for all versions >= 6.0
            // Redis5Proxy has incompatible method signatures with PhpRedis 6.3.0+
            if (str_ends_with($filePath, 'symfony/cache/Traits/RedisProxy.php')) {
                $contents = str_replace(
                    'class_alias(6.0 <= (float) phpversion(\'redis\') ? Redis6Proxy::class : Redis5Proxy::class, RedisProxy::class);',
                    'class_alias(Redis6Proxy::class, RedisProxy::class);',
                    $contents
                );
            }
            if (str_ends_with($filePath, 'symfony/cache/Traits/RedisClusterProxy.php')) {
                $contents = str_replace(
                    'class_alias(6.0 <= (float) phpversion(\'redis\') ? RedisCluster6Proxy::class : RedisCluster5Proxy::class, RedisClusterProxy::class);',
                    'class_alias(RedisCluster6Proxy::class, RedisClusterProxy::class);',
                    $contents
                );
            }

            $namespaceSeparatorPattern = '(?:\\\\\\\\|\\\\)';

            $buildFlexibleReferencePattern = static function (string $reference) use ($namespaceSeparatorPattern): string {
                return implode(
                    $namespaceSeparatorPattern,
                    array_map(
                        static fn (string $segment): string => preg_quote($segment, '/'),
                        explode('\\', $reference)
                    )
                );
            };

            $prefixLeadingReference = static function (string $code, string $reference) use ($buildFlexibleReferencePattern, $prefix): string {
                return preg_replace_callback(
                    '/(^|[^A-Za-z0-9_\\\\])(?:\\\\\\\\|\\\\)' . $buildFlexibleReferencePattern($reference) . '/',
                    static fn (array $matches): string => $matches[1] . '\\' . $prefix . '\\' . $reference,
                    $code
                ) ?? $code;
            };

            $prefixBareReference = static function (string $code, string $reference) use ($buildFlexibleReferencePattern, $prefix): string {
                return preg_replace_callback(
                    '/(^|[^A-Za-z0-9_\\\\])' . $buildFlexibleReferencePattern($reference) . '/',
                    static fn (array $matches): string => $matches[1] . $prefix . '\\' . $reference,
                    $code
                ) ?? $code;
            };

            $prefixLeadingNamespace = static function (string $code, string $namespace) use ($prefix): string {
                return preg_replace_callback(
                    '/(^|[^A-Za-z0-9_\\\\])(?:\\\\\\\\|\\\\)' . preg_quote($namespace, '/') . '(?:\\\\\\\\|\\\\)/',
                    static fn (array $matches): string => $matches[1] . '\\' . $prefix . '\\' . $namespace . '\\',
                    $code
                ) ?? $code;
            };

            // Fix Twig runtime-compiled templates in scoped builds.
            // Twig\Node\ModuleNode generates PHP code with hardcoded `use Twig\...` imports,
            // so generated cache files must reference the scoped namespace.
            if (str_ends_with($filePath, 'twig/twig/src/Node/ModuleNode.php')) {
                $contents = preg_replace_callback(
                    '/(->write\("use\s+)Twig(?:\\\\\\\\|\\\\)/',
                    static fn (array $matches): string => $matches[1] . $prefix . '\\Twig\\',
                    $contents
                ) ?? $contents;
            }

            if (str_ends_with($filePath, 'twig/twig/src/Node/CaptureNode.php')) {
                $contents = $prefixLeadingReference($contents, 'Twig\\Extension\\CoreExtension::captureOutput');
                $contents = $prefixBareReference($contents, 'Twig\\Extension\\CoreExtension::captureOutput');
            }

            if (str_ends_with($filePath, 'twig/twig/src/Node/Expression/Binary/ObjectDestructuringSetBinary.php')) {
                $contents = $prefixLeadingReference($contents, 'Twig\\Template::ANY_CALL');
                $contents = $prefixBareReference($contents, 'Twig\\Template::ANY_CALL');
            }

            if (str_ends_with($filePath, 'twig/twig/src/Profiler/Node/EnterProfileNode.php')) {
                $contents = $prefixLeadingReference($contents, 'Twig\\Profiler\\Profile(');
                $contents = $prefixBareReference($contents, 'Twig\\Profiler\\Profile(');
            }

            // Fix Latte runtime-compiled templates in scoped builds.
            // Latte\Compiler\TemplateGenerator emits hardcoded `Latte\Runtime` references.
            if (str_ends_with($filePath, 'latte/latte/src/Latte/Compiler/TemplateGenerator.php')) {
                $contents = $prefixLeadingReference($contents, 'Latte\\Runtime');
                $contents = $prefixBareReference($contents, 'Latte\\Runtime');
            }

            if (str_ends_with($filePath, 'latte/latte/src/Latte/Essential/Nodes/TemplatePrintNode.php')) {
                $contents = $prefixLeadingReference($contents, 'Latte\\Essential\\Blueprint');
                $contents = $prefixBareReference($contents, 'Latte\\Essential\\Blueprint');
            }

            if (str_ends_with($filePath, 'latte/latte/src/Latte/Essential/Nodes/VarPrintNode.php')) {
                $contents = $prefixLeadingReference($contents, 'Latte\\Essential\\Blueprint');
                $contents = $prefixBareReference($contents, 'Latte\\Essential\\Blueprint');
            }

            if (str_ends_with($filePath, 'latte/latte/src/Latte/Essential/Nodes/TraceNode.php')) {
                $contents = $prefixLeadingReference($contents, 'Latte\\Essential\\Tracer::throw() %line;');
                $contents = $prefixBareReference($contents, 'Latte\\Essential\\Tracer::throw() %line;');
            }

            if (str_ends_with($filePath, 'latte/latte/src/Latte/Essential/Nodes/ForeachNode.php')) {
                $contents = $prefixLeadingReference($contents, 'Latte\\Essential\\CachingIterator(');
                $contents = $prefixBareReference($contents, 'Latte\\Essential\\CachingIterator(');
            }

            if (str_ends_with($filePath, 'latte/latte/src/Latte/Essential/Nodes/SpacelessNode.php')) {
                $contents = $prefixLeadingReference($contents, 'Latte\\Essential\\Filters::%raw');
                $contents = $prefixBareReference($contents, 'Latte\\Essential\\Filters::%raw');
            }

            if (str_ends_with($filePath, 'latte/latte/src/Latte/Essential/Nodes/TryNode.php')) {
                $contents = $prefixLeadingReference($contents, 'Latte\\Essential\\RollbackException');
                $contents = $prefixBareReference($contents, 'Latte\\Essential\\RollbackException');
            }

            if (str_ends_with($filePath, 'latte/latte/src/Latte/Essential/Nodes/RollbackNode.php')) {
                $contents = $prefixLeadingReference($contents, 'Latte\\Essential\\RollbackException');
                $contents = $prefixBareReference($contents, 'Latte\\Essential\\RollbackException');
            }

            // Fix Blade runtime-compiled templates in scoped builds.
            // Illuminate view compilers emit hardcoded `\Illuminate\...` class references.
            if (str_contains($filePath, 'illuminate/view/Compilers/')) {
                $contents = $prefixLeadingNamespace($contents, 'Illuminate');
            }

            if (str_ends_with($filePath, 'illuminate/view/Compilers/ComponentTagCompiler.php')) {
                $contents = preg_replace(
                    '/make\(Illuminate(?:\\\\\\\\|\\\\)View(?:\\\\\\\\|\\\\)Factory::class\)/',
                    'make(' . $prefix . '\\Illuminate\\View\\Factory::class)',
                    $contents
                ) ?? $contents;

                $contents = preg_replace(
                    '/instanceof Illuminate(?:\\\\\\\\|\\\\)View(?:\\\\\\\\|\\\\)ComponentAttributeBag/',
                    'instanceof ' . $prefix . '\\Illuminate\\View\\ComponentAttributeBag',
                    $contents
                ) ?? $contents;
            }

            if (str_ends_with($filePath, 'illuminate/view/Compilers/Concerns/CompilesComponents.php')) {
                $contents = preg_replace(
                    '/instanceof Illuminate(?:\\\\\\\\|\\\\)View(?:\\\\\\\\|\\\\)ComponentAttributeBag/',
                    'instanceof ' . $prefix . '\\Illuminate\\View\\ComponentAttributeBag',
                    $contents
                ) ?? $contents;
            }
            
            return $contents;
        },
    ],

    // List of symbols to consider internal i.e. to leave untouched.
    //
    // For more information see: https://github.com/humbug/php-scoper/blob/master/docs/configuration.md#excluded-symbols
    'exclude-namespaces' => [
        'Picowind',
        'WP_CLI',
        'Symfony\Polyfill',

        // WindPress
        'WindPress',

        // Page builders
        'Bricks',
        
        'Breakdance',
        'EssentialElements',

        'Elementor',
        
        'LiveCanvas',

        'Etch',

        // Cache plugins
    ],
    'exclude-classes' => array_merge(
        $wp_classes,
        [
            'WP_CLI',
            'WP_CLI_Command',
            'DOMXPath',

            'acf_field',
        ]
    ),
    'exclude-functions' => array_merge(
        $wp_functions,
        [
            // Cache clearing functions

            // Page builder functions
            'bricks_is_builder_main',
            'bricks_is_builder_iframe',
            'bricks_render_dynamic_data',

            'acf_get_url',
            'acf_register_field_type',

            // LiveCanvas functions
            'lc_theme_is_livecanvas_friendly',
            'lc_define_editor_config',
        ]
    ),
    'exclude-constants' => array_merge(
        $wp_constants,
        [
            // Symfony global constants
            '/^SYMFONY\_[\p{L}_]+$/',

            // WordPress constants
            'WP_CONTENT_DIR',
            'WP_CONTENT_URL',
            'ABSPATH',
            'WPINC',
            'WP_DEBUG_DISPLAY',
            'WPMU_PLUGIN_DIR',
            'WP_PLUGIN_DIR',
            'WP_PLUGIN_URL',
            'WPMU_PLUGIN_URL',
            'MINUTE_IN_SECONDS',
            'HOUR_IN_SECONDS',
            'DAY_IN_SECONDS',
            'MONTH_IN_SECONDS',
            'debugger',

            // Bricks
            'BRICKS_VERSION',

            // Breakdance
            '__BREAKDANCE_VERSION',
            'BREAKDANCE_MODE',

            // Elementor
            'ELEMENTOR_VERSION',

            // LiveCanvas
            'LC_MU_PLUGIN_NAME',

            // Etch
            'ETCH_PLUGIN_FILE',
        ]
    ),

    // List of symbols to expose.
    //
    // For more information see: https://github.com/humbug/php-scoper/blob/master/docs/configuration.md#exposed-symbols
    'expose-global-constants' => false,
    'expose-global-classes' => false,
    'expose-global-functions' => false,
    'expose-namespaces' => [],
    'expose-classes' => [
        // 'Twig\Environment',
        // 'Twig\Error\LoaderError',
        // 'Twig\Error\RuntimeError',
        // 'Twig\Extension\CoreExtension',
        // 'Twig\Extension\SandboxExtension',
        // 'Twig\Markup',
        // 'Twig\Sandbox\SecurityError',
        // 'Twig\Sandbox\SecurityNotAllowedTagError',
        // 'Twig\Sandbox\SecurityNotAllowedFilterError',
        // 'Twig\Sandbox\SecurityNotAllowedFunctionError',
        // 'Twig\Source',
        // 'Twig\Template',
        // 'Twig\TemplateWrapper',
    ],
    'expose-functions' => [
        // Illuminate/Laravel helper functions used via unqualified calls in prefixed namespaces.
        'append_config',
        'blank',
        'class_basename',
        'class_uses_recursive',
        'collect',
        'data_fill',
        'data_forget',
        'data_get',
        'data_set',
        'e',
        'env',
        'filled',
        'head',
        'last',
        'laravel_cloud',
        'object_get',
        'optional',
        'preg_replace_array',
        'retry',
        'str',
        'tap',
        'throw_if',
        'throw_unless',
        'trait_uses_recursive',
        'transform',
        'value',
        'windows_os',
        'with',
    ],
    'expose-constants' => [],
];
