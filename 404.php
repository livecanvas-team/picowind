<?php

declare(strict_types=1);

/**
 * The template for displaying 404 pages (not found)
 *
 * @link https://codex.wordpress.org/Creating_an_Error_404_Page
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package Picowind
 * @subpackage Picowind
 * @since 1.0.0
 */

namespace Picowind;

$context = context();

render('404.twig', $context, null, true, true);
