<?php

declare(strict_types=1);

/**
 * @package Picowind
 * @subpackage Picowind
 * @since 1.0.0
 */

namespace Picowind\Core;

use Picowind\Core\Discovery\Attributes\Service;
use Picowind\Core\Render\Blade as RenderBlade;
use Picowind\Core\Render\Latte as RenderLatte;
use Picowind\Core\Render\Twig as RenderTwig;
use Picowind\Exceptions\UnsupportedRenderEngineException;

/**
 * Template rendering
 *
 * @package Picowind
 */
#[Service]
class Template
{
    public function __construct(
        private readonly RenderTwig $renderTwig,
        private readonly RenderBlade $renderBlade,
        private readonly RenderLatte $renderLatte,
    ) {}

    /**
     * Renders a template string with the given context.
     *
     * @param string $template_string The template string to render
     * @param array $context Context data to pass to template
     * @param string $engine Engine to use ('twig', 'blade', 'latte'). Default is 'twig'
     * @param ?bool $print Whether to print output. Default true
     * @return void|string Rendered output if $print is false, otherwise void
     */
    public function render_string(string $template_string, array $context = [], string $engine = 'twig', ?bool $print = true): ?string
    {
        do_action('a!picowind/template/render_string:before', $template_string, $context, $engine, $print);

        // Allow complete override of rendering logic
        $customRender = apply_filters('f!picowind/template/render_string:output', null, $template_string, $context, $engine);
        if (null !== $customRender) {
            if ($print) {
                echo $customRender;
                do_action('a!picowind/template/render_string:after', $customRender, $template_string, $context, $engine);
                return null;
            }
            do_action('a!picowind/template/render_string:after', $customRender, $template_string, $context, $engine);
            return $customRender;
        }

        $output = $this->renderStringWithEngine($engine, $template_string, $context);

        if ($print) {
            echo $output;
            do_action('a!picowind/template/render_string:after', $output, $template_string, $context, $engine);
            return null;
        }

        do_action('a!picowind/template/render_string:after', $output, $template_string, $context, $engine);
        return $output;
    }

    /**
     * @param string|array $paths Template path(s) including file extension
     * @param array $context Context data to pass to template
     * @param ?string $engine Engine to use ('twig', 'blade', 'latte', etc). Auto-detected if null
     * @param ?bool $print Whether to print output. Default true
     * @return void|string Rendered output if $print is false, otherwise void
     */
    public function render_template(string|array $paths, array $context = [], ?string $engine = null, ?bool $print = true): ?string
    {
        do_action('a!picowind/template/render:before', $paths, $context, $engine, $print);

        // Allow complete override of rendering logic
        $customRender = apply_filters('f!picowind/template/render:output', null, $paths, $context, $engine);
        if (null !== $customRender) {
            if ($print) {
                echo $customRender;
                do_action('a!picowind/template/render:after', $customRender, $paths, $context, $engine);
                return null;
            }
            do_action('a!picowind/template/render:after', $customRender, $paths, $context, $engine);
            return $customRender;
        }

        // Handle array of paths for fallback support
        if (is_array($paths)) {
            if (null !== $engine) {
                $paths = array_map(fn ($single_path) => $this->process_path_extension($single_path, $engine), $paths);
                $output = $this->renderWithEngine($engine, $paths, $context);
                do_action('a!picowind/template/render:after', $output, $paths, $context, $engine);
                return $output;
            }

            $errors = [];
            foreach ($paths as $singlePath) {
                try {
                    $detectedEngine = $this->detectEngineFromPath($singlePath);
                    $processedPath = $this->process_path_extension($singlePath, $detectedEngine);
                    $output = $this->renderWithEngine($detectedEngine, $processedPath, $context, false);

                    if (empty($output) || ! is_string($output)) {
                        $engineInfo = isset($detectedEngine) && $detectedEngine ? " [{$detectedEngine}]" : '';
                        $errors[] = "{$singlePath}:{$engineInfo} Rendering returned empty output.";
                        continue;
                    }

                    if ($print) {
                        echo $output;
                        do_action('a!picowind/template/render:after', $output, $paths, $context, $detectedEngine);
                        return null;
                    }

                    do_action('a!picowind/template/render:after', $output, $paths, $context, $detectedEngine);
                    return $output;
                } catch (\Exception $e) {
                    $engineInfo = isset($detectedEngine) && $detectedEngine ? " [{$detectedEngine}]" : '';
                    $errors[] = "{$singlePath}:{$engineInfo} {$e->getMessage()}";
                    continue;
                }
            }

            throw new UnsupportedRenderEngineException(
                'mixed',
                "\nNo template could be rendered. Tried:\n"
                . implode("\n", array_map(
                    fn ($err, $i) => "#{$i} {$err}",
                    $errors,
                    array_keys($errors),
                ))
                . "\n",
            );
        }

        if (null === $engine) {
            $engine = $this->detectEngineFromPath($paths);
        }

        $paths = $this->process_path_extension($paths, $engine);
        $output = $this->renderWithEngine($engine, $paths, $context);

        if ($print) {
            echo $output;
            do_action('a!picowind/template/render:after', $output, $paths, $context, $engine);
            return null;
        }

        do_action('a!picowind/template/render:after', $output, $paths, $context, $engine);
        return $output;
    }

    private function detectEngineFromPath(string $path): string
    {
        $ext = pathinfo($path, PATHINFO_EXTENSION);

        $engine = match ($ext) {
            'twig' => 'twig',
            'latte' => 'latte',
            'php' => 'blade',
            '?' => throw new UnsupportedRenderEngineException('?', 'Cannot determine engine from `.?` extension.'),
            default => 'twig',
        };

        // Allow custom engine detection based on file extension
        return apply_filters('f!picowind/template/detect:engine', $engine, $path, $ext);
    }

    private function renderWithEngine(string $engine, string|array $paths, array $context)
    {
        // Allow custom rendering engines via filter
        $customEngineOutput = apply_filters('f!picowind/template/engine:render', null, $engine, $paths, $context);
        if (null !== $customEngineOutput) {
            return $customEngineOutput;
        }

        return match ($engine) {
            'twig' => $this->renderTwig->render_template($paths, $context, false),
            'blade' => $this->renderBlade->render_template($paths, $context, false),
            'latte' => $this->renderLatte->render_template($paths, $context, false),
            default => throw new UnsupportedRenderEngineException($engine),
        };
    }

    private function renderStringWithEngine(string $engine, string $template_string, array $context)
    {
        // Allow custom rendering engines via filter
        $customEngineOutput = apply_filters('f!picowind/template/engine:render_string', null, $engine, $template_string, $context);
        if (null !== $customEngineOutput) {
            return $customEngineOutput;
        }

        return match ($engine) {
            'twig' => $this->renderTwig->render_string($template_string, $context, false),
            'blade' => $this->renderBlade->render_string($template_string, $context, false),
            'latte' => $this->renderLatte->render_string($template_string, $context, false),
            default => throw new UnsupportedRenderEngineException($engine),
        };
    }

    private function process_path_extension(string $path, ?string $engine = null): string
    {
        // Handle `.?` extension placeholder - replace with actual extension
        if (str_ends_with($path, '.?')) {
            $path = substr($path, 0, -2);
            $extension = match ($engine) {
                'twig' => '.twig',
                'blade' => '.blade.php',
                'latte' => '.latte',
                default => throw new UnsupportedRenderEngineException($engine),
            };
            // Allow custom extension mapping for custom engines
            $extension = apply_filters('f!picowind/template/engine:extension', $extension, $engine);
            $path .= $extension;
        }

        // Add missing extension based on engine
        $ext = pathinfo($path, PATHINFO_EXTENSION);
        if ('' === $ext) {
            $extension = match ($engine) {
                'twig' => '.twig',
                'blade' => '.blade.php',
                'latte' => '.latte',
                default => throw new UnsupportedRenderEngineException($engine),
            };
            // Allow custom extension mapping for custom engines
            $extension = apply_filters('f!picowind/template/engine:extension', $extension, $engine);
            $path .= $extension;
        }

        return $path;
    }
}
