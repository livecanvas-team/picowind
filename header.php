<?php

declare(strict_types=1);

/**
 * Third party plugins that hijack the theme will call wp_head() to get the header template.
 * We use this to start our output buffer and render into the views/footer.twig template in footer.php
 *
 * If you're not using a plugin that requries this behavior (ones that do include Events Calendar Pro and
 * WooCommerce) you can delete this file and footer.php
 *
 * @package Picowind
 * @subpackage Picowind
 * @since 1.0.0
 */

namespace Picowind;

// @mago-expect lint:no-global
$GLOBALS['timberContext'] = context();
ob_start();
