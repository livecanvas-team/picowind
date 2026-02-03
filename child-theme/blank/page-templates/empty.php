<?php
/**
 * Template Name: Empty Page Template
 *
 * Template for displaying a page just with the header and footer area
 * and a "naked" content area in between.
 * Good for landing pages and other types of pages where you want to
 * add a lot of custom markup.
 *
 * @package Picowind\Child\Tw
 * @since 1.0.0
 */

declare(strict_types=1);

$context = \Picowind\context();
$timber_post = \Timber\Timber::get_post();
$context['post'] = $timber_post;

\Picowind\render('page-templates/empty.twig', $context);
