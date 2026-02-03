<?php

declare(strict_types=1);

/**
 * @package Picowind Child
 * @subpackage Picowind
 * @since 1.0.0
 */

defined('ABSPATH') || exit();

if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    if (file_exists(__DIR__ . '/vendor/scoper-autoload.php')) {
        require_once __DIR__ . '/vendor/scoper-autoload.php';
    } else {
        require_once __DIR__ . '/vendor/autoload.php';
    }
}

/* You can add your custom functions below this line */
