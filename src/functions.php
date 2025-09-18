<?php

/**
 * @package WordPress
 * @subpackage Picowind
 * @since Picowind 1.0.0
 */

namespace Picowind;

use Timber\Timber;

function acf_block_render_callback($block, $content = '', $is_preview = false, $post_id = 0)
{
	$context = Timber::context();
	$context['post']       = Timber::get_post();
	$context['block']      = $block;
	$context['fields']     = get_fields();
	$context['content']    = $content;
	$context['is_preview'] = $is_preview;

	$slug     = explode('/', $block['name'])[1];
	$template = 'blocks/' . $slug . '/index.twig';

	Timber::render($template, $context);
}

function get_symfony_finder()
{
	if (class_exists('\WindPressDeps\Symfony\Component\Finder\Finder')) {
		return new \WindPressDeps\Symfony\Component\Finder\Finder();
	} elseif (class_exists('\Symfony\Component\Finder\Finder')) {
		return new \Symfony\Component\Finder\Finder();
	} else {
		return null;
	}
}
