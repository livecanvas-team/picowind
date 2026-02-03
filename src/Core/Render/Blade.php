<?php

declare(strict_types=1);

/**
 * @package Picowind
 * @subpackage Picowind
 * @since 1.0.0
 */

namespace Picowind\Core\Render;

use Jenssegers\Blade\Blade as BladeBlade;
use Picowind\Core\Discovery\Attributes\Service;
use Picowind\Utils\Theme as UtilsTheme;

use function Picowind\render;

#[Service]
class Blade
{
    private readonly ?BladeBlade $bladeBlade;

    public function __construct()
    {
        $cache_path = UtilsTheme::get_cache_path('blade');
        if (! file_exists($cache_path)) {
            wp_mkdir_p($cache_path);
        }

        $this->bladeBlade = new BladeBlade(UtilsTheme::get_template_directories(), $cache_path);
        $this->registerTwigDirective();
        $this->registerLatteDirective();
        $this->registerOmniIconDirective();
    }

    private function registerTwigDirective(): void
    {
        $this->bladeBlade->directive('twig', function ($expression) {
            // Wrap the expression in array brackets to handle multiple arguments
            return "<?php
                \$__twigArgs = [{$expression}];
                \$__twigTemplate = isset(\$__twigArgs[0]) ? \$__twigArgs[0] : '';
                \$__twigExtra = isset(\$__twigArgs[1]) ? \$__twigArgs[1] : [];
                if (!empty(\$__twigTemplate)) {
                    \$__twigContext = array_filter(get_defined_vars(), function(\$k) {
                        return substr(\$k, 0, 2) !== '__';
                    }, ARRAY_FILTER_USE_KEY);
                    \$__twigContext = array_merge(\$__twigContext, \$__twigExtra);
                    echo \\Picowind\\render(\$__twigTemplate, \$__twigContext, 'twig', false);
                }
            ?>";
        });
    }

    private function registerLatteDirective(): void
    {
        $this->bladeBlade->directive('latte', function ($expression) {
            // Wrap the expression in array brackets to handle multiple arguments
            return "<?php
                \$__latteArgs = [{$expression}];
                \$__latteTemplate = isset(\$__latteArgs[0]) ? \$__latteArgs[0] : '';
                \$__latteExtra = isset(\$__latteArgs[1]) ? \$__latteArgs[1] : [];
                if (!empty(\$__latteTemplate)) {
                    \$__latteContext = array_filter(get_defined_vars(), function(\$k) {
                        return substr(\$k, 0, 2) !== '__';
                    }, ARRAY_FILTER_USE_KEY);
                    \$__latteContext = array_merge(\$__latteContext, \$__latteExtra);
                    echo \\Picowind\\render(\$__latteTemplate, \$__latteContext, 'latte', false);
                }
            ?>";
        });
    }

    private function registerOmniIconDirective(): void
    {
        $this->bladeBlade->directive('omni_icon', function ($expression) {
            // Wrap the expression in array brackets to handle multiple arguments
            // Uses Omni Icon plugin via OmniIcon wrapper
            return "<?php
                \$__omniIconArgs = [{$expression}];
                \$__iconName = isset(\$__omniIconArgs[0]) ? \$__omniIconArgs[0] : '';
                \$__iconAttrs = isset(\$__omniIconArgs[1]) ? \$__omniIconArgs[1] : [];
                if (!empty(\$__iconName)) {
                    echo \\Picowind\\omni_icon(\$__iconName, \$__iconAttrs);
                }
            ?>";
        });
    }

    /**
     * Render a Blade template.
     *
     * @param string|array $paths The path(s) to the Blade template file(s).
     * @param array  $context The context data to pass to the template.
     * @param bool   $print Whether to print the output directly or return it.
     * @return string|null
     */
    public function render_template($paths, array $context = [], bool $print = true)
    {
        $view_name = null;
        $template_dirs = UtilsTheme::get_template_directories();
        $resolve_view_name = function ($path) use ($template_dirs) {
            foreach ($template_dirs as $template_dir) {
                if (str_starts_with($path, $template_dir)) {
                    $relative_path = substr($path, strlen($template_dir) + 1);
                    return str_replace(['/', '.blade.php'], ['.', ''], $relative_path);
                }
            }

            // If not absolute, treat as relative to template_dirs
            $relative_path = substr($path, -10) === '.blade.php' ? $path : $path . '.blade.php';

            $relative_path = ltrim($relative_path, '/');
            return str_replace(['/', '.blade.php'], ['.', ''], $relative_path);
        };

        if (is_array($paths)) {
            foreach ($paths as $path) {
                $view_name = $resolve_view_name($path);
                if ('' !== $view_name && '0' !== $view_name) {
                    break;
                }
            }
        } else {
            $view_name = $resolve_view_name($paths);
        }

        if (null === $view_name) {
            // Fallback: use the basename without extension
            $view_name = pathinfo($paths, PATHINFO_FILENAME);
        }

        $output = $this->bladeBlade->make($view_name, $context)->render();

        if ($print) {
            echo $output;
        } else {
            return $output;
        }
        return null;
    }

    /**
     * Render a Blade template string.
     *
     * @param string $template_string The Blade template string to render.
     * @param array  $context The context data to pass to the template.
     * @param bool   $print Whether to print the output directly or return it.
     * @return string|null
     */
    public function render_string(string $template_string, array $context = [], bool $print = true)
    {
        // Use the BladeCompiler's render method to compile and render the string
        $output = $this->bladeBlade->compiler()->render($template_string, $context);

        if ($print) {
            echo $output;
        } else {
            return $output;
        }
        return null;
    }
}
