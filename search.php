<?php
/**
 * @package WordPress
 * @subpackage Picowind
 * @since Picowind 1.0.0
 */

$templates = ['search.twig', 'archive.twig', 'index.twig'] ;

$context          = Timber::context();
$context['title'] = 'Search results for ' . get_search_query();
$context['posts'] = Timber::get_posts();

Timber::render( $templates, $context );
