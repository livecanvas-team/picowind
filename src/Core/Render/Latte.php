<?php

declare(strict_types=1);

/**
 * @package Picowind
 * @subpackage Picowind
 * @since 1.0.0
 */

namespace Picowind\Core\Render;

use Latte\Engine;
use Latte\Loaders\StringLoader;
use Latte\Runtime\Html;
use Picowind\Core\Discovery\Attributes\Service;
use Picowind\Core\Render\Latte\LatteExtension;
use Picowind\Core\Render\Latte\MultiDirectoryLoader;
use Picowind\Core\Render\Latte\TimberFunctionsExtension;
use Picowind\Core\Render\TimberFunctionBridge;
use Picowind\Utils\Theme as UtilsTheme;

use function Picowind\render;

#[Service]
class Latte
{
    /**
     * @var array<string, string>
     */
    private const LATTE_FUNCTION_ALIASES = [
        'function' => 'call',
        'fn' => 'call',
    ];

    private readonly Engine $latte;
    private readonly TimberFunctionBridge $timberFunctions;

    public function __construct()
    {
        $cache_path = UtilsTheme::get_cache_path('latte');
        if (! file_exists($cache_path)) {
            call_user_func('wp_mkdir_p', $cache_path);
        }

        $this->latte = new Engine();
        $this->latte->setTempDirectory($cache_path);
        $this->timberFunctions = new TimberFunctionBridge();

        // Configure custom loader to support multiple template directories with fallback
        $template_dirs = UtilsTheme::get_template_directories();
        $loader = new MultiDirectoryLoader($template_dirs);
        $this->latte->setLoader($loader);

        // Auto-refresh in development
        if (\defined('WP_DEBUG') && \constant('WP_DEBUG')) {
            $this->latte->setAutoRefresh(true);
        }

        // Register custom extension with tags and functions
        $this->latte->addExtension(new LatteExtension());

        $this->registerTimberFunctions();
        $this->registerTwigFunction();
        $this->registerBladeFunction();
        $this->registerOmniIconFunction();
    }

    private function registerTimberFunctions(): void
    {
        $functions = [];

        foreach ($this->timberFunctions->all() as $name => $callable) {
            $alias = self::LATTE_FUNCTION_ALIASES[$name] ?? null;

            if (! array_key_exists($name, $functions)) {
                $functions[$name] = $callable;
            }

            if (is_string($alias) && ! array_key_exists($alias, $functions)) {
                $functions[$alias] = $callable;
            }
        }

        $functions['timber'] = fn (string $name, ...$args) => $this->timberFunctions->call($name, ...$args);

        $this->latte->addExtension(new TimberFunctionsExtension($functions));
    }

    private function withTimberHelpers(array $context): array
    {
        if (! array_key_exists('timber', $context)) {
            $context['timber'] = $this->timberFunctions;
        }

        return $context;
    }

    private function registerTwigFunction(): void
    {
        // Register function syntax: {twig('template.twig', [vars])}
        $this->latte->addFunction('twig', function (string $template, array $context = []) {
            $output = render($template, $context, 'twig', false) ?? '';
            return new Html($output);
        });
    }

    private function registerBladeFunction(): void
    {
        // Register function syntax: {blade('template.blade.php', [vars])}
        $this->latte->addFunction('blade', function (string $template, array $context = []) {
            $output = render($template, $context, 'blade', false) ?? '';
            return new Html($output);
        });
    }

    private function registerOmniIconFunction(): void
    {
        // Register function syntax: {omni_icon('mdi:home', ['class' => 'icon'])}
        // Uses Omni Icon plugin via OmniIcon wrapper
        $this->latte->addFunction('omni_icon', function (string $iconName, array $attributes = []) {
            $output = \Picowind\omni_icon($iconName, $attributes);
            return new Html($output);
        });
    }

    /**
     * Render a Latte template.
     *
     * @param string|array $paths The path(s) to the Latte template file(s).
     * @param array  $context The context data to pass to the template.
     * @param bool   $print Whether to print the output directly or return it.
     * @return string|null
     */
    public function render_template($paths, array $context = [], bool $print = true)
    {
        $context = $this->withTimberHelpers($context);

        $template_name = null;
        $template_dirs = UtilsTheme::get_template_directories();

        // Find which template exists - store the relative name, not absolute path
        $templates = is_array($paths) ? $paths : [$paths];

        foreach ($templates as $path) {
            foreach ($template_dirs as $dir) {
                $full_path = rtrim($dir, '/') . '/' . ltrim($path, '/');
                if (file_exists($full_path)) {
                    $template_name = $path; // Use the relative path
                    break 2;
                }
            }
        }

        if (null === $template_name) {
            throw new \RuntimeException('Latte template not found: ' . (is_array($paths) ? implode(', ', $paths) : $paths));
        }

        // Render with the relative template name - MultiDirectoryLoader will resolve it
        try {
            $output = $this->latte->renderToString($template_name, $context);
        } catch (\Throwable $e) {
            throw $e;
        }

        if ($print) {
            echo $output;
        } else {
            return $output;
        }
        return null;
    }

    /**
     * Render a Latte template string.
     *
     * @param string $template_string The Latte template string to render.
     * @param array  $context The context data to pass to the template.
     * @param bool   $print Whether to print the output directly or return it.
     * @return string|null
     */
    public function render_string(string $template_string, array $context = [], bool $print = true)
    {
        $context = $this->withTimberHelpers($context);

        try {
            // Save the current loader
            $originalLoader = $this->latte->getLoader();

            // Create a unique key for this string template
            $templateKey = '__string_template__';

            // Use StringLoader with the template string
            $this->latte->setLoader(new StringLoader([
                $templateKey => $template_string,
            ]));

            // Render the string template
            $output = $this->latte->renderToString($templateKey, $context);

            // Restore the original loader
            $this->latte->setLoader($originalLoader);
        } catch (\Throwable $e) {
            // Ensure loader is restored even on error
            if (isset($originalLoader)) {
                $this->latte->setLoader($originalLoader);
            }
            throw $e;
        }

        if ($print) {
            echo $output;
        } else {
            return $output;
        }
        return null;
    }
}
