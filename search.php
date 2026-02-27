<?php

declare (strict_types=1);
/**
 * Search Results Template
 *
 * @package Picowind
 * @subpackage Picowind
 * @since 1.0.0
 */
namespace Picowind;

$context = context();
$context['title'] = sprintf(
    /* translators: %s: search query */
    __('Search Results for: %s', 'picowind'),
    get_search_query()
);
$context['search_query'] = get_search_query();
$context['posts'] = \PicowindDeps\Timber\Timber::get_posts();
render(template_fallbacks(['search', 'archive', 'index']), $context);
