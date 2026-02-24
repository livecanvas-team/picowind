<?php

declare(strict_types=1);

/**
 * Sidebar Template
 *
 * @package Picowind
 * @subpackage Picowind
 * @since 1.0.0
 */

namespace Picowind;

$context = context();
render(template_fallbacks('sidebar'), $context, null, true, true);
