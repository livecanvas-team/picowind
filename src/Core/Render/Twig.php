<?php

declare(strict_types=1);

/**
 * @package Picowind
 * @subpackage Picowind
 * @since 1.0.0
 */

namespace Picowind\Core\Render;

use Picowind\Core\Discovery\Attributes\Hook;
use Picowind\Core\Discovery\Attributes\Service;
use Picowind\Core\Render\Twig\BladeTokenParser;
use Picowind\Core\Render\Twig\LatteTokenParser;
use Picowind\Utils\Theme as UtilsTheme;
use Timber\Timber;
use Twig\Environment;
use Twig\TwigFunction;

use function Picowind\omni_icon;
use function Picowind\render;

#[Service]
class Twig
{
    public function __construct()
    {
        $cache_path = UtilsTheme::get_cache_path('twig');
        if (! file_exists($cache_path)) {
            wp_mkdir_p($cache_path);
        }

        Timber::$dirname = [
            UtilsTheme::get_template_directory_names(),
        ];
    }

    public function locations(array $locations): array
    {
        $locations = array_unique(array_merge($locations, UtilsTheme::get_template_directories()));

        return $locations;
    }

    #[Hook('timber/twig/environment/options', 'filter')]
    public function filter_env(array $options): array
    {
        $options['cache'] = UtilsTheme::get_cache_path('twig');

        // Auto-reload in development, set to `false` in production for better performance
        $options['auto_reload'] = defined('WP_DEBUG') && WP_DEBUG;

        return $options;
    }

    /**
     * Renders a Twig template with the given context.
     *
     * @param string|array $paths The path(s) to the Twig template file(s).
     * @param array $context The context data to pass to the template.
     * @param bool $print Whether to print the output (true) or return it (false). Default is true.
     * @return bool|string|null Returns the rendered output if $print is false, otherwise void.
     */
    public function render_template($paths, array $context = [], bool $print = true)
    {
        $output = Timber::compile($paths, $context);
        if ($print) {
            echo $output;
        } else {
            return $output;
        }
        return null;
    }

    /**
     * Renders a Twig template string with the given context.
     *
     * @param string $template_string The Twig template string to render.
     * @param array $context The context data to pass to the template.
     * @param bool $print Whether to print the output (true) or return it (false). Default is true.
     * @return bool|string|null Returns the rendered output if $print is false, otherwise void.
     */
    public function render_string(string $template_string, array $context = [], bool $print = true)
    {
        $output = Timber::compile_string($template_string, $context);
        if ($print) {
            echo $output;
        } else {
            return $output;
        }
        return null;
    }

    /**
     * Renders a Blade template from within Twig.
     * Mimics Twig's include behavior with context passing.
     *
     * @param array $context The current Twig context (automatically passed by needs_context)
     * @param string $template The Blade template path
     * @param array $with Additional variables to pass to the template
     * @param bool $only Whether to pass only the 'with' variables (no parent context)
     * @return string The rendered Blade template
     */
    public function renderBladeTemplate(array $context, string $template, array $with = [], bool $only = false): string
    {
        // Determine which variables to pass
        if ($only) {
            // Only pass the 'with' variables
            $finalContext = $with;
        } else {
            // Merge parent context with 'with' variables (with variables take precedence)
            $finalContext = array_merge($context, $with);
        }

        // Render the Blade template without printing
        return render($template, $finalContext, 'blade', false) ?? '';
    }

    /**
     * Renders a Latte template from within Twig.
     * Mimics Twig's include behavior with context passing.
     *
     * @param array $context The current Twig context (automatically passed by needs_context)
     * @param string $template The Latte template path
     * @param array $with Additional variables to pass to the template
     * @param bool $only Whether to pass only the 'with' variables (no parent context)
     * @return string The rendered Latte template
     */
    public function renderLatteTemplate(array $context, string $template, array $with = [], bool $only = false): string
    {
        // Determine which variables to pass
        if ($only) {
            // Only pass the 'with' variables
            $finalContext = $with;
        } else {
            // Merge parent context with 'with' variables (with variables take precedence)
            $finalContext = array_merge($context, $with);
        }

        // Render the Latte template without printing
        return render($template, $finalContext, 'latte', false) ?? '';
    }

    #[Hook('timber/twig', 'filter')]
    public function add_blade_function_to_twig(Environment $twigEnvironment): Environment
    {
        $twigEnvironment->addFunction(
            new TwigFunction(
                'blade',
                $this->renderBladeTemplate(...),
                [
                    'is_safe' => ['html'],
                    'needs_context' => true,
                ],
            ),
        );
        return $twigEnvironment;
    }

    #[Hook('timber/twig', 'filter')]
    public function add_blade_tag_to_twig(Environment $twigEnvironment): Environment
    {
        $twigEnvironment->addTokenParser(new BladeTokenParser());
        return $twigEnvironment;
    }

    #[Hook('timber/twig', 'filter')]
    public function add_latte_function_to_twig(Environment $twigEnvironment): Environment
    {
        $twigEnvironment->addFunction(
            new TwigFunction(
                'latte',
                $this->renderLatteTemplate(...),
                [
                    'is_safe' => ['html'],
                    'needs_context' => true,
                ],
            ),
        );
        return $twigEnvironment;
    }

    #[Hook('timber/twig', 'filter')]
    public function add_latte_tag_to_twig(Environment $twigEnvironment): Environment
    {
        $twigEnvironment->addTokenParser(new LatteTokenParser());
        return $twigEnvironment;
    }

    #[Hook('timber/twig', 'filter')]
    public function add_omni_icon_function_to_twig(Environment $twigEnvironment): Environment
    {
        $twigEnvironment->addFunction(
            new TwigFunction(
                'omni_icon',
                omni_icon(...),
                ['is_safe' => ['html']],
            ),
        );
        return $twigEnvironment;
    }
}
