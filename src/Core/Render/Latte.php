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
use Picowind\Utils\Theme as UtilsTheme;

use function Picowind\render;

#[Service]
class Latte
{
    private readonly Engine $latte;

    public function __construct()
    {
        $cache_path = UtilsTheme::get_cache_path('latte');
        if (! file_exists($cache_path)) {
            wp_mkdir_p($cache_path);
        }

        $this->latte = new Engine();
        $this->latte->setTempDirectory($cache_path);

        // Configure custom loader to support multiple template directories with fallback
        $template_dirs = UtilsTheme::get_template_directories();
        $loader = new MultiDirectoryLoader($template_dirs);
        $this->latte->setLoader($loader);

        // Auto-refresh in development
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $this->latte->setAutoRefresh(true);
        }

        // Register custom extension with tags and functions
        $this->latte->addExtension(new LatteExtension());

        $this->registerTwigFunction();
        $this->registerBladeFunction();
        $this->registerOmniIconFunction();
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
