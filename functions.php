<?php

declare(strict_types=1);

/**
 * @package Picowind
 * @subpackage Picowind
 * @since 1.0.0
 */

use Picowind\Supports\LiveCanvas as LiveCanvasSupports;
use Picowind\Theme;

if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    if (file_exists(__DIR__ . '/vendor/scoper-autoload.php')) {
        require_once __DIR__ . '/vendor/scoper-autoload.php';
    } else {
        require_once __DIR__ . '/vendor/autoload.php';
    }
}

Theme::get_instance();

if (! function_exists('lc_theme_is_livecanvas_friendly')) {
    function lc_theme_is_livecanvas_friendly(): bool
    {
        return LiveCanvasSupports::lc_theme_is_livecanvas_friendly();
    }
}

if (! function_exists('lc_define_editor_config')) {
    function lc_define_editor_config($key)
    {
        return LiveCanvasSupports::lc_define_editor_config($key);
    }
}
