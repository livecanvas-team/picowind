<?php

/**
 * Template Name: Page with Sidebar on the Left
 *
 * Page template with a sidebar on the left side.
 *
 * @package Picowind\Child\Tw
 * @since 1.0.0
 */
declare (strict_types=1);
namespace PicowindDeps;

$context = \Picowind\context();
$timber_post = \PicowindDeps\Timber\Timber::get_post();
$context['post'] = $timber_post;
\Picowind\render(\Picowind\template_fallbacks('page-templates/page-sidebar-left'), $context);
