<?php
/**
 * @package WordPress
 * @subpackage Picowind
 * @since Picowind 1.0.0
 */

global $wp_query;

$context          = Timber::context();
$context['posts'] = Timber::get_posts();
if ( isset( $wp_query->query_vars['author'] ) ) {
	$author            = Timber::get_user( $wp_query->query_vars['author'] );
	$context['author'] = $author;
	$context['title']  = 'Author Archives: ' . $author->name();
}
Timber::render( [ 'author.twig', 'archive.twig' ], $context );
