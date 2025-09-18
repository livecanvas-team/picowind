<?php

/**
 * @package WordPress
 * @subpackage Picowind
 * @since Picowind 1.0.0
 */

namespace Picowind;

use Picowind\Supports\Timber as SupportsTimber;
use Picowind\Supports\WindPress as SupportsWindPress;
use Picowind\Supports\AdvancedCustomFields as SupportAdvancedCustomFields;
use Timber\Site;

class Theme extends Site
{
	public function __construct()
	{
		add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
		add_action('after_setup_theme', [$this, 'theme_supports']);
		add_action('block_categories_all', [$this, 'block_categories_all']);
		add_action('enqueue_block_editor_assets', [$this, 'enqueue_assets']);
		new SupportsTimber($this);
		new SupportAdvancedCustomFields();
		new SupportsWindPress();

		parent::__construct();
	}

	public function theme_supports()
	{
		add_theme_support('automatic-feed-links');
		add_theme_support(
			'html5',
			[
				'comment-form',
				'comment-list',
				'gallery',
				'caption',
			]
		);
		add_theme_support('menus');
		add_theme_support('post-thumbnails');
		add_theme_support('title-tag');
		add_theme_support('editor-styles');

		register_nav_menus([
			'primary' => 'Primary Menu',
			'footer'  => 'Footer Menu',
		]);
	}

	public function enqueue_assets()
	{
		wp_dequeue_style('wp-block-library');
		wp_dequeue_style('wp-block-library-theme');
		wp_dequeue_style('wc-block-style');
		wp_dequeue_script('jquery');
		wp_dequeue_style('global-styles');

		$vite_env = 'production';

		if (file_exists(get_template_directory() . '/../config.json')) {
			$config   = json_decode(file_get_contents(get_template_directory() . '/../config.json'), true);
			$vite_env = $config['vite']['environment'] ?? 'production';
		}

		$dist_uri  = get_template_directory_uri() . '/assets/dist';
		$dist_path = get_template_directory() . '/assets/dist';
		$manifest  = null;

		if (file_exists($dist_path . '/.vite/manifest.json')) {
			$manifest = json_decode(file_get_contents($dist_path . '/.vite/manifest.json'), true);
		}

		if (is_array($manifest) && ($vite_env === 'production' || is_admin())) {
			$js_file = 'theme/assets/main.js';
			wp_enqueue_style('main', $dist_uri . '/' . $manifest[$js_file]['css'][0]);
			$strategy = is_admin() ? 'async' : 'defer';
			$in_footer = !(bool) is_admin();
			wp_enqueue_script(
				'main',
				$dist_uri . '/' . $manifest[$js_file]['file'],
				[],
				'',
				[
					'strategy'  => $strategy,
					'in_footer' => $in_footer,
				]
			);
			$editor_css_file = 'theme/assets/styles/editor-style.css';
			add_editor_style($dist_uri . '/' . $manifest[$editor_css_file]['file']);
		}

		if ($vite_env === 'development') {
			function vite_head_module_hook()
			{
				echo '<script type="module" crossorigin src="http://localhost:3001/@vite/client"></script>';
				echo '<script type="module" crossorigin src="http://localhost:3001/theme/assets/main.js"></script>';
			}

			add_action('wp_head', 'vite_head_module_hook');
		}
	}

	public function block_categories_all($categories)
	{
		return array_merge(
			[
				[
					'slug'  => 'custom',
					'title' => __('Custom'),
				],
			],
			$categories
		);
	}
}
