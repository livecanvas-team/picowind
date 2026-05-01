<?php

declare (strict_types=1);
/**
 * @package Picowind
 * @subpackage Picowind
 * @since 1.0.0
 */
namespace Picowind\Core\Render;

use Closure;
use PicowindDeps\Composer\InstalledVersions;
use PicowindDeps\DevTheorem\Handlebars\Handlebars as HandlebarsEngine;
use PicowindDeps\DevTheorem\Handlebars\HelperOptions;
use PicowindDeps\DevTheorem\Handlebars\Options;
use PicowindDeps\DevTheorem\Handlebars\SafeString;
use Picowind\Core\Discovery\Attributes\Service;
use Picowind\Utils\Theme as UtilsTheme;
use RuntimeException;
use Throwable;
use Traversable;
use function Picowind\omni_icon;
use function Picowind\render;
#[Service]
class Handlebars
{
    private const CACHE_VERSION = '1';
    private const DEFAULT_EXTENSION = '.hbs';
    private const STRING_CACHE_LIMIT = 64;
    private readonly string $cache_path;
    private readonly Options $options;
    private readonly \Picowind\Core\Render\TimberFunctionBridge $timberFunctions;
    /** @var array<string, Closure> */
    private array $helpers = [];
    /** @var array<string, Closure> */
    private array $compiled_templates = [];
    /** @var array<string, Closure> */
    private array $compiled_strings = [];
    /** @var array<string, string> */
    private array $resolved_template_paths = [];
    private ?string $compilation_cache_key = null;
    public function __construct()
    {
        $cache_path = UtilsTheme::get_cache_path('handlebars');
        if (!file_exists($cache_path)) {
            wp_mkdir_p($cache_path);
        }
        $this->cache_path = $cache_path;
        $this->options = $this->create_options();
        $this->timberFunctions = new \Picowind\Core\Render\TimberFunctionBridge();
        $this->helpers = $this->create_helpers();
    }
    /**
     * Render a Handlebars template.
     *
     * @param string|array $paths The path(s) to the Handlebars template file(s).
     * @param array $context The context data to pass to the template.
     * @param bool $print Whether to print the output directly or return it.
     * @return string|null
     */
    public function render_template($paths, array $context = [], bool $print = \true)
    {
        $template_path = $this->resolve_template_path($paths);
        $template = $this->compile_file($template_path);
        $output = $template($this->normalize_context($context), $this->runtime_options());
        if ($print) {
            // @mago-expect lint:no-unescaped-output template engines intentionally output rendered HTML.
            echo $output;
        } else {
            return $output;
        }
        return null;
    }
    /**
     * Render a Handlebars template string.
     *
     * @param string $template_string The Handlebars template string to render.
     * @param array $context The context data to pass to the template.
     * @param bool $print Whether to print the output directly or return it.
     * @return string|null
     */
    public function render_string(string $template_string, array $context = [], bool $print = \true)
    {
        $template = $this->compile_string($template_string);
        $output = $template($this->normalize_context($context), $this->runtime_options());
        if ($print) {
            // @mago-expect lint:no-unescaped-output template engines intentionally output rendered HTML.
            echo $output;
        } else {
            return $output;
        }
        return null;
    }
    private function create_options(): Options
    {
        $options = new Options();
        $filtered = apply_filters('f!picowind/render/handlebars:options', $options);
        return $filtered instanceof Options ? $filtered : $options;
    }
    /**
     * @return array<string, Closure>
     */
    private function create_helpers(): array
    {
        $helpers = ['blade' => fn(mixed ...$args): SafeString => $this->render_engine_helper('blade', $args), 'handlebars' => fn(mixed ...$args): SafeString => $this->render_engine_helper('handlebars', $args), 'latte' => fn(mixed ...$args): SafeString => $this->render_engine_helper('latte', $args), 'omni_icon' => fn(mixed ...$args): SafeString => $this->omni_icon_helper($args), 'timber' => fn(mixed ...$args): mixed => $this->timber_helper($args), 'twig' => fn(mixed ...$args): SafeString => $this->render_engine_helper('twig', $args)];
        foreach ($this->timberFunctions->all() as $name => $timberCallable) {
            if (array_key_exists($name, $helpers)) {
                continue;
            }
            $helpers[$name] = function (mixed ...$args) use ($timberCallable): mixed {
                $this->pop_helper_options($args);
                return $timberCallable(...$this->denormalize_values($args));
            };
        }
        $filtered = apply_filters('f!picowind/render/handlebars:helpers', $helpers, $this);
        if (is_array($filtered)) {
            $helpers = $this->normalize_helpers($filtered);
        }
        return $helpers;
    }
    /**
     * @param array<string, mixed> $helpers
     * @return array<string, Closure>
     */
    private function normalize_helpers(array $helpers): array
    {
        $normalized = [];
        foreach ($helpers as $name => $helper) {
            if (!is_string($name) || '' === $name || !is_callable($helper)) {
                continue;
            }
            $normalized[$name] = $helper instanceof Closure ? $helper : Closure::fromCallable($helper);
        }
        return $normalized;
    }
    /**
     * @return array<string, mixed>
     */
    private function runtime_options(): array
    {
        $options = ['helpers' => $this->helpers, 'partialResolver' => fn(string $name): ?Closure => $this->resolve_partial($name)];
        $filtered = apply_filters('f!picowind/render/handlebars:runtime_options', $options, $this);
        return is_array($filtered) ? $filtered : $options;
    }
    private function render_engine_helper(string $engine, array $args): SafeString
    {
        $options = $this->pop_helper_options($args);
        $template = array_shift($args);
        if (!is_string($template) || '' === $template) {
            return new SafeString('');
        }
        $context = $this->helper_context($options, $args);
        $output = render($template, $context, $engine, \false) ?? '';
        return new SafeString($output);
    }
    private function omni_icon_helper(array $args): SafeString
    {
        $options = $this->pop_helper_options($args);
        $icon_name = array_shift($args);
        if (!is_string($icon_name) || '' === $icon_name) {
            return new SafeString('');
        }
        $attributes = [];
        $first_arg = $args[0] ?? null;
        if (is_array($first_arg)) {
            $attributes = $this->denormalize_value($first_arg);
        }
        if ($options instanceof HelperOptions) {
            $attributes = array_merge($attributes, $this->denormalize_value($options->hash));
        }
        return new SafeString(omni_icon($icon_name, $attributes));
    }
    private function timber_helper(array $args): mixed
    {
        $this->pop_helper_options($args);
        $function_name = array_shift($args);
        if (!is_string($function_name) || '' === $function_name) {
            return null;
        }
        return $this->timberFunctions->call($function_name, ...$this->denormalize_values($args));
    }
    /**
     * @param array<int, mixed> $args
     * @return array<string, mixed>
     */
    private function helper_context(?HelperOptions $options, array $args): array
    {
        $context = [];
        $hash = [];
        $only = \false;
        if ($options instanceof HelperOptions) {
            $hash = $this->denormalize_value($options->hash);
            $only = (bool) ($hash['only'] ?? \false);
            unset($hash['only']);
            if (!$only) {
                $scope = $this->denormalize_value($options->scope);
                if (is_array($scope)) {
                    $context = $scope;
                } elseif (is_object($scope)) {
                    $context = ['this' => $scope];
                }
            }
        }
        foreach ($args as $arg) {
            $arg = $this->denormalize_value($arg);
            if (is_array($arg)) {
                $context = array_merge($context, $arg);
            }
        }
        return array_merge($context, $hash);
    }
    private function compile_file(string $source_path): Closure
    {
        $cache_file = $this->cache_file_for_source($source_path);
        if ($this->is_cache_stale($source_path, $cache_file)) {
            $source = $this->read_source($source_path);
            $this->write_compiled_cache($cache_file, $source);
            unset($this->compiled_templates[$cache_file]);
        }
        try {
            return $this->load_compiled_cache($cache_file);
        } catch (Throwable $e) {
            unset($this->compiled_templates[$cache_file]);
            $source = $this->read_source($source_path);
            $this->write_compiled_cache($cache_file, $source);
            return $this->load_compiled_cache($cache_file);
        }
    }
    private function compile_string(string $template_string): Closure
    {
        $cache_key = $this->cache_key_for_string($template_string);
        if (isset($this->compiled_strings[$cache_key])) {
            return $this->compiled_strings[$cache_key];
        }
        $template = HandlebarsEngine::compile($template_string, $this->options);
        if (count($this->compiled_strings) < self::STRING_CACHE_LIMIT) {
            $this->compiled_strings[$cache_key] = $template;
        }
        return $template;
    }
    private function read_source(string $source_path): string
    {
        $source = file_get_contents($source_path);
        if (\false === $source) {
            throw new RuntimeException('Unable to read Handlebars template: ' . $source_path);
        }
        return $source;
    }
    private function is_cache_stale(string $source_path, string $cache_file): bool
    {
        if (!file_exists($cache_file)) {
            return \true;
        }
        if (!UtilsTheme::is_template_cache_auto_refresh()) {
            return \false;
        }
        $source_mtime = filemtime($source_path);
        $cache_mtime = filemtime($cache_file);
        return \false === $source_mtime || \false === $cache_mtime || $source_mtime > $cache_mtime;
    }
    private function write_compiled_cache(string $cache_file, string $source): void
    {
        $code = HandlebarsEngine::precompile($source, $this->options);
        $tmp_file = $cache_file . '.' . uniqid('tmp-', \true);
        // @mago-expect lint:use-wp-functions cache writes need atomic local filesystem writes.
        if (\false === file_put_contents($tmp_file, '<?php ' . $code, \LOCK_EX)) {
            throw new RuntimeException('Unable to write Handlebars cache file: ' . $cache_file);
        }
        // @mago-expect lint:use-wp-functions cache writes need atomic local filesystem writes.
        if (!rename($tmp_file, $cache_file)) {
            // @mago-expect lint:use-wp-functions cache writes need atomic local filesystem cleanup.
            @unlink($tmp_file);
            throw new RuntimeException('Unable to move Handlebars cache file into place: ' . $cache_file);
        }
    }
    private function load_compiled_cache(string $cache_file): Closure
    {
        if (isset($this->compiled_templates[$cache_file])) {
            return $this->compiled_templates[$cache_file];
        }
        $template = require $cache_file;
        if (!$template instanceof Closure) {
            throw new RuntimeException('Invalid Handlebars cache file: ' . $cache_file);
        }
        $this->compiled_templates[$cache_file] = $template;
        return $template;
    }
    private function cache_file_for_source(string $source_path): string
    {
        return $this->cache_path . '/' . $this->hash('file|' . $source_path . '|' . $this->compilation_cache_key()) . '.php';
    }
    private function cache_key_for_string(string $template_string): string
    {
        return $this->hash('string|' . $template_string . '|' . $this->compilation_cache_key());
    }
    private function compilation_cache_key(): string
    {
        if (null !== $this->compilation_cache_key) {
            return $this->compilation_cache_key;
        }
        $known_helpers = $this->options->knownHelpers;
        ksort($known_helpers);
        $fingerprint = ['package' => $this->package_version(), 'cache_version' => self::CACHE_VERSION, 'compat' => $this->options->compat, 'known_helpers' => $known_helpers, 'known_helpers_only' => $this->options->knownHelpersOnly, 'no_escape' => $this->options->noEscape, 'strict' => $this->options->strict, 'assume_objects' => $this->options->assumeObjects, 'prevent_indent' => $this->options->preventIndent, 'ignore_standalone' => $this->options->ignoreStandalone, 'explicit_partial_context' => $this->options->explicitPartialContext];
        $this->compilation_cache_key = $this->hash(serialize($fingerprint));
        return $this->compilation_cache_key;
    }
    private function hash(string $value): string
    {
        return hash('xxh128', $value);
    }
    private function package_version(): string
    {
        if (class_exists(InstalledVersions::class) && InstalledVersions::isInstalled('devtheorem/php-handlebars')) {
            return (InstalledVersions::getPrettyVersion('devtheorem/php-handlebars') ?? 'unknown') . ':' . (InstalledVersions::getReference('devtheorem/php-handlebars') ?? 'unknown') . ':' . self::CACHE_VERSION;
        }
        return self::CACHE_VERSION;
    }
    private function resolve_partial(string $name): ?Closure
    {
        try {
            return $this->compile_file($this->resolve_template_path($this->template_candidates($name)));
        } catch (Throwable) {
            return null;
        }
    }
    /**
     * @param string|array $paths
     */
    private function resolve_template_path($paths): string
    {
        $cache_key = is_array($paths) ? serialize($paths) : (string) $paths;
        if (isset($this->resolved_template_paths[$cache_key])) {
            return $this->resolved_template_paths[$cache_key];
        }
        $templates = is_array($paths) ? $paths : [$paths];
        $template_dirs = UtilsTheme::get_template_directories();
        foreach ($templates as $path) {
            if (!is_string($path) || '' === $path) {
                continue;
            }
            foreach ($this->template_candidates($path) as $candidate) {
                if ($this->is_absolute_path($candidate) && file_exists($candidate)) {
                    return $this->resolved_template_paths[$cache_key] = realpath($candidate) ?: $candidate;
                }
                foreach ($template_dirs as $dir) {
                    $full_path = rtrim($dir, '/') . '/' . ltrim($candidate, '/');
                    if (file_exists($full_path)) {
                        return $this->resolved_template_paths[$cache_key] = realpath($full_path) ?: $full_path;
                    }
                }
            }
        }
        throw new RuntimeException('Handlebars template not found: ' . implode(', ', array_map('strval', $templates)));
    }
    /**
     * @return array<int, string>
     */
    private function template_candidates(string $path): array
    {
        if ('' !== pathinfo($path, \PATHINFO_EXTENSION)) {
            return [$path];
        }
        return [$path . self::DEFAULT_EXTENSION, $path . '.handlebars'];
    }
    private function is_absolute_path(string $path): bool
    {
        return str_starts_with($path, '/') || 1 === preg_match('/^[A-Za-z]:[\\\\\\/]/', $path);
    }
    /**
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    private function normalize_context(array $context): array
    {
        return $this->normalize_value($context);
    }
    private function normalize_value(mixed $value): mixed
    {
        if ($value instanceof Closure || $value instanceof SafeString) {
            return $value;
        }
        if (is_array($value)) {
            return array_map($this->normalize_value(...), $value);
        }
        if (is_object($value)) {
            return new \Picowind\Core\Render\HandlebarsContextValue($value, $this->normalize_value(...));
        }
        return $value;
    }
    /**
     * @param array<int, mixed> $args
     */
    private function pop_helper_options(array &$args): ?HelperOptions
    {
        $last = end($args);
        if ($last instanceof HelperOptions) {
            array_pop($args);
            return $last;
        }
        return null;
    }
    /**
     * @param array<int, mixed> $values
     * @return array<int, mixed>
     */
    private function denormalize_values(array $values): array
    {
        return array_map($this->denormalize_value(...), $values);
    }
    private function denormalize_value(mixed $value): mixed
    {
        if ($value instanceof \Picowind\Core\Render\HandlebarsContextValue) {
            return $value->value();
        }
        if (is_array($value)) {
            return array_map($this->denormalize_value(...), $value);
        }
        if ($value instanceof Traversable) {
            return array_map($this->denormalize_value(...), iterator_to_array($value));
        }
        return $value;
    }
}
