<?php

declare(strict_types=1);

/**
 * Third party plugins that hijack the theme will call wp_footer() to get the footer template.
 * We use this to end our output buffer (started in header.php) and render into the views/footer.twig template.
 *
 * If you're not using a plugin that requries this behavior (ones that do include Events Calendar Pro and
 * WooCommerce) you can delete this file and header.php
 *
 * @package Picowind
 * @subpackage Picowind
 * @since 1.0.0
 */

namespace Picowind;

use Exception;

// @mago-expect lint:no-global
$timberContext = $GLOBALS['timberContext']; // @codingStandardsIgnoreFile
if (! isset($timberContext)) {
    throw new Exception('Timber context not set in footer.');
}
$timberContext['content'] = ob_get_contents();
ob_end_clean();
$templates = ['page-plugin.twig'];

render($templates, $timberContext, 'twig', true, true);
