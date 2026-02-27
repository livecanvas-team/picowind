<?php

/**
 * Template Name: Blank Page Template
 *
 * A truly blank template - no header, no footer, just wp_head and wp_footer hooks.
 * Perfect for custom landing pages or full-custom markup.
 *
 * @package Picowind\Child\Tw
 * @since 1.0.0
 */
declare (strict_types=1);
namespace PicowindDeps;

$context = \Picowind\context();
$timber_post = \PicowindDeps\Timber\Timber::get_post();
$context['post'] = $timber_post;
\Picowind\render(\Picowind\template_fallbacks('page-templates/blank'), $context);
