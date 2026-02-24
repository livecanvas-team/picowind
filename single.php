<?php

declare(strict_types=1);

/**
 * The template for displaying single posts
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/#single-post
 *
 * @package Picowind
 * @subpackage Picowind
 * @since 1.0.0
 */

namespace Picowind;

$context = context();
$timber_post = \Timber\Timber::get_post();
$context['post'] = $timber_post;

if (post_password_required($timber_post->ID)) {
    render(template_fallbacks('single-password'), $context, null, true, true);
} else {
    render(
        template_fallbacks([
            'single-' . $timber_post->ID,
            'single-' . $timber_post->post_type,
            'single-' . $timber_post->slug,
            'single',
        ]),
        $context,
        null,
        true,
        true,
    );
}
