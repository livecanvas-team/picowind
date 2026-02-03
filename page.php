<?php

declare(strict_types=1);

/**
 * The template for displaying all pages
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/#single-page
 *
 * @package Picowind
 * @subpackage Picowind
 * @since 1.0.0
 */

namespace Picowind;

$context = context();

$timber_post = \Timber\Timber::get_post();
$context['post'] = $timber_post;

$r = render(
    [
        'page-' . $timber_post->post_name . '.twig',
        'page.twig',
    ],
    $context,
    null,
    true,
    true,
);

// Fallback to page.php if Twig rendering fails.
// $r ? print $r : require_once __DIR__ . '/page.php';
