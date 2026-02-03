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
render('sidebar.twig', $context, null, true, true);
