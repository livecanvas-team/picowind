<?php

declare(strict_types=1);

/**
 * @package Picowind
 * @subpackage Picowind
 * @since 1.0.0
 */

namespace Picowind\Supports;

use Picowind\Core\Discovery\Attributes\Hook;
use Picowind\Core\Discovery\Attributes\Service;
use Timber\Site;
use Timber\Timber as TimberTimber;

use function get_fields;

#[Service]
class Timber extends Site
{
    private array $included_templates = [];
    private ?string $original_template = null;

    public function __construct()
    {
        TimberTimber::init();
        parent::__construct();
    }

    #[Hook('f!picowind/context', 'filter')]
    public function add_to_context(array $context): array
    {
        $context['site'] = $this;
        $context['options'] = function_exists('get_fields') ? get_fields('option') : [];

        return $context;
    }

    #[Hook('timber/twig/functions', 'filter')]
    public function add_timber_functions($functions)
    {
        $functions['function_exists'] = [
            'callable' => 'function_exists',
        ];

        return $functions;
    }

    #[Hook('template_include', 'filter')]
    public function template_include(string $template): string
    {
        $this->included_templates[] = $template;

        // Check if this template should be processed
        if ($this->should_process_template($template)) {
            // Store the original template for later use
            $this->original_template = $template;

            // Return path to our blank template
            return get_template_directory() . '/blank.php';
        }

        return $template;
    }

    #[Hook('wp_before_include_template', 'action')]
    public function handle_template_processing(string $template): void
    {
        // Only process if we have an original template stored and current template is blank.php
        if (! $this->original_template || ! str_ends_with($template, '/blank.php')) {
            return;
        }

        // Capture the output from the original template
        ob_start();
        include $this->original_template;
        $output = ob_get_contents();
        ob_end_clean();

        // Check if the template produced any meaningful output
        // If it didn't (meaning Twig rendering failed and no fallback was triggered),
        // then include index.php as fallback
        if (empty(trim($output))) {
            include get_template_directory() . '/index.php';
        } else {
            echo $output;
        }

        // Clear the stored template
        $this->original_template = null;
    }

    /**
     * Determine if a template should be processed through our system.
     * Uses opt-out approach - excludes partial/miscellaneous templates.
     *
     * @see https://developer.wordpress.org/themes/classic-themes/templates/partial-and-miscellaneous-template-files/#sidebar-php
     */
    private function should_process_template(string $template): bool
    {
        $template_name = basename($template, '.php');

        // Partial and miscellaneous templates that should NOT be processed
        $excluded_templates = [
            'header', // Partial template
            'footer', // Partial template
            'comments', // Partial template
            'sidebar', // Partial template
            'blank', // Our own blank template
        ];

        // Exclude content-* templates (e.g., content-page.php, content-post.php)
        if (str_starts_with($template_name, 'content-')) {
            return false;
        }

        // Check against excluded list
        if (in_array($template_name, $excluded_templates)) {
            return false;
        }

        // Process all other template files
        return true;
    }

    #[Hook('f!picowind/timber:included_templates', 'filter')]
    public function get_included_templates($templates): array
    {
        return array_merge($templates, $this->included_templates);
    }
}
