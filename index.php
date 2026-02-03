<?php

declare(strict_types=1);

/**
 * The main template file
 * This is the most generic template file in a WordPress theme
 * and one of the two required files for a theme (the other being style.css).
 * It is used to display a page when nothing more specific matches a query.
 * E.g., it puts together the home page when no home.php file exists
 *
 * Methods for TimberHelper can be found in the /lib sub-directory
 *
 * @package Picowind
 * @subpackage Picowind
 * @since 1.0.0
 */

namespace Picowind;

$context = context();

$context['included_templates'] = array_merge(
    apply_filters('f!picowind/timber:included_templates', []),
    [
        __FILE__,
    ],
);

$templates = ['index.twig'];
if (is_home()) {
    array_unshift($templates, 'front-page.twig', 'home.twig');
}

render($templates, $context);
