<?php

/**
 * @package WordPress
 * @subpackage Picowind
 * @since Picowind 1.0.0
 */

use Picowind\Theme;
use Timber\Timber;

require_once __DIR__ . '/vendor/autoload.php';

// as per https://livecanvas.com/faq/which-themes-with-livecanvas/
function lc_theme_is_livecanvas_friendly(){} // phpcs:ignore

class PICO_WIND {
	const VERSION = '1.0.0';
	const TEXTDOMAIN = 'picowind';
	const FILE = __FILE__;
	const PATH = __DIR__;
}

Timber::init();
Timber::$dirname    = ['views', 'blocks'];

new Theme();
