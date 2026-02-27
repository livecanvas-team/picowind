<?php

/**
 * Template Name: Page with Sidebar on the Right
 *
 * Page template with a sidebar on the right side.
 *
 * @package Picowind\Child\Tw
 * @since 1.0.0
 */
declare (strict_types=1);
namespace PicowindDeps;

$context = \Picowind\context();
$timber_post = \PicowindDeps\Timber\Timber::get_post();
$context['post'] = $timber_post;
\Picowind\render(\Picowind\template_fallbacks('page-templates/page-sidebar-right'), $context);
