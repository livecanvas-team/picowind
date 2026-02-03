<?php

declare(strict_types=1);

/**
 * @package Picowind
 * @subpackage Picowind
 * @since 1.0.0
 */

namespace Picowind;

use Picowind\Core\Template;
use Picowind\Supports\OmniIcon;
use Timber\Timber;

/**
 * Render a template using the specified engine.
 *
 * @param string|array $paths The path to the template file(s) including the file extension.
 * @param array  $context The context data to pass to the template.
 * @param ?string $engine The template engine to use ('twig', 'latte', 'blade', 'php', etc). Default is 'twig' or determined by file extension.
 * @param ?bool $print Whether to print the rendered template. Default is true.
 * @return void|string The rendered template output if $print is false, otherwise void.
 */
function render($paths, array $context = [], ?string $engine = null, ?bool $print = true, ?bool $silent = false)
{
    $theme = Theme::get_instance();
    $container = $theme->container();
    /** @var Template */
    $template = $container->get(Template::class);

    try {
        return $template->render_template($paths, $context, $engine, $print);
    } catch (\Throwable $e) {
        // TODO: instead of $silent flag, use logging service with levels.
        if (! $silent) {
            error_log(sprintf(
                "[Picowind Render Error]\nPaths: %s\nEngine: %s\nMessage: %s\nFile: %s:%d\nTrace:\n%s",
                is_array($paths) ? implode(', ', $paths) : $paths,
                $engine ?? 'auto-detect',
                $e->getMessage(),
                $e->getFile(),
                $e->getLine(),
                $e->getTraceAsString(),
            ));
        }

        // Engine and paths info.
        $failMessage = sprintf(
            "[Picowind Render Failed]\nEngine: %s\nPaths: %s\n",
            $engine ?? 'auto-detect',
            is_array($paths) ? implode(', ', $paths) : $paths,
        );

        if ($print && ! $silent) {
            echo $failMessage;
        }
        return null;
    }
}

/**
 * Render a template string using the specified engine.
 *
 * @param string $template_string The template string to render.
 * @param array  $context The context data to pass to the template.
 * @param string $engine The template engine to use ('twig', 'latte', 'blade'). Default is 'twig'.
 * @param ?bool $print Whether to print the rendered template. Default is true.
 * @return void|string The rendered template output if $print is false, otherwise void.
 */
function render_string(string $template_string, array $context = [], string $engine = 'twig', ?bool $print = true)
{
    $theme = Theme::get_instance();
    $container = $theme->container();
    /** @var Template */
    $template = $container->get(Template::class);

    try {
        return $template->render_string($template_string, $context, $engine, $print);
    } catch (\Throwable $e) {
        error_log(sprintf(
            "[Picowind Render String Error]\nEngine: %s\nTemplate: %s\nMessage: %s\nFile: %s:%d\nTrace:\n%s",
            $engine,
            substr($template_string, 0, 200),
            $e->getMessage(),
            $e->getFile(),
            $e->getLine(),
            $e->getTraceAsString(),
        ));

        $failMessage = sprintf(
            "[Picowind Render String Failed]\nEngine: %s\n",
            $engine,
        );

        if ($print) {
            echo $failMessage;
            return null;
        }
        return $failMessage;
    }
}

/**
 * Render an icon using Omni Icon plugin.
 *
 * This function wraps the Omni Icon plugin's IconService to render icons.
 * Requires the Omni Icon plugin to be installed and activated.
 *
 * @param string $iconName Icon name in format "prefix:icon-name" (e.g., "mdi:home", "local:my-logo", "omni:windpress")
 * @param array  $attributes Optional HTML attributes to add to the SVG element
 * @return string SVG HTML string or empty string if icon not found
 *
 * @example
 * // Basic usage
 * echo Picowind\omni_icon('mdi:home');
 * echo Picowind\omni_icon('local:my-logo');
 * echo Picowind\omni_icon('omni:windpress');
 *
 * // With attributes
 * echo Picowind\omni_icon('mdi:home', ['class' => 'icon-large', 'width' => '32']);
 */
function omni_icon(string $iconName, array $attributes = []): string
{
    $theme = Theme::get_instance();
    $container = $theme->container();
    /** @var \Picowind\Supports\OmniIcon */
    $omniIcon = $container->get(OmniIcon::class);

    return $omniIcon->get_icon($iconName, $attributes) ?? '';
}

/**
 * Gets the global context.
 *
 * The context always contains the global context with the following variables:
 *
 * - `site` – An instance of `Timber\Site`.
 * - `request` - An instance of `Timber\Request`.
 * - `theme` - An instance of `Timber\Theme`.
 * - `user` - An instance of `Timber\User`.
 * - `http_host` - The HTTP host.
 * - `wp_title` - Title retrieved for the currently displayed page, retrieved through
 * `wp_title()`.
 * - `body_class` - The body class retrieved through `get_body_class()`.
 *
 * The global context will be cached, which means that you can call this function again without
 * losing performance.
 *
 * In addition to that, the context will contain template contexts depending on which template
 * is being displayed. For archive templates, a `posts` variable will be present that will
 * contain a collection of `Timber\Post` objects for the default query. For singular templates,
 * a `post` variable will be present that that contains a `Timber\Post` object of the `$post`
 * global.
 *
 * @api
 * @since 2.0.0
 *
 * @param array $extra Any extra data to merge in. Overrides whatever is already there for this
 *                     call only. In other words, the underlying context data is immutable and
 *                     unaffected by passing this param.
 *
 * @return array An array of context variables that is used to pass into Twig templates through
 *               a render or compile function.
 *
 * @see \Timber\Timber::context()
 */
function context(): array
{
    return apply_filters('f!picowind/context', Timber::context());
}
