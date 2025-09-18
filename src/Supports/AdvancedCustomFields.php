<?php

/**
 * @package WordPress
 * @subpackage Picowind
 * @since Picowind 1.0.0
 */

namespace Picowind\Supports;

use DirectoryIterator;

use function acf_add_options_page;
use function acf_add_options_sub_page;

class AdvancedCustomFields
{
    public function __construct()
    {
		add_action('acf/init', [$this, 'acf_register_blocks']);
		// add_action('acf/init', [$this, 'register_options_pages']);
    }

	public function acf_register_blocks()
	{
		$blocks = [];

		foreach (new DirectoryIterator(get_template_directory() . '/blocks') as $dir) {
			if ($dir->isDot()) {
				continue;
			}

			if (file_exists($dir->getPathname() . '/block.json')) {
				$blocks[] = $dir->getPathname();
			}
		}

		asort($blocks);

		foreach ($blocks as $block) {
			register_block_type($block);
		}
	}

	public function register_options_pages()
	{
		if (function_exists('acf_add_options_page')) {
			acf_add_options_page([
				'page_title' => 'Theme Settings',
				'menu_title' => 'Theme Settings',
				'menu_slug'  => 'theme-settings',
				'capability' => 'manage_options',
				'redirect'   => false,
				'icon_url' 	 => 'dashicons-admin-generic',
			]);

			acf_add_options_sub_page([
				'page_title'  => 'Header',
				'menu_title'  => 'Header',
				'parent_slug' => 'theme-settings',
			]);
			acf_add_options_sub_page([
				'page_title'  => 'Footer',
				'menu_title'  => 'Footer',
				'parent_slug' => 'theme-settings',
			]);
		}
	}
}
