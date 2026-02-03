<?php
/**
 * Template Name: daisyUI 5 Demo Template
 *
 * Showcases daisyUI 5 components and their usage.
 *
 * @package Picowind\Child\Tw
 * @since 1.0.0
 */

declare(strict_types=1);

$context = \Picowind\context();
$timber_post = \Timber\Timber::get_post();
$context['post'] = $timber_post;

\Picowind\render('page-templates/daisyui-demo.twig', $context);
