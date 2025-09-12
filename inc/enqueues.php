<?php
/**
 * Enqueue the CSS and JS files
 *
 * @package picowind
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

 

///ADD THE MAIN JS FILES
//enqueue js in footer, async
add_action( 'wp_enqueue_scripts', function() {

    //MAIN BOOTSTRAP JS
    //want to override file in child theme? use get_stylesheet_directory_uri in place of get_template_directory_uri 
    //this was done for compatibility reasons towards older child themes
    //wp_enqueue_script( 'bootstrap5', get_template_directory_uri() . "/js/bootstrap.bundle.min.js", array(), null, array('strategy' => 'defer', 'in_footer' => true) );

    //DARK MODE SWITCH SUPPORT
    //if (get_theme_mod('enable_dark_mode_switch')) wp_enqueue_script( 'dark-mode-switch', get_template_directory_uri() . "/js/dark-mode-switch.js", array(), null,  array('strategy' => 'defer', 'in_footer' => true) );
    
} ,100);

// PREVENT FOUC IN DARK MODE PAGE RELOAD
add_action('wp_head', function () {
    if (!get_theme_mod('enable_dark_mode_switch')) return;
    ?>
    <script>
        (function setThemeFromPreference() {
            const docEl = document.documentElement;
            const defaultTheme = 'light';

            try {
                let theme = localStorage.getItem('theme');

                if (!theme) {
                    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                    theme = prefersDark ? 'dark' : defaultTheme;
                }

                docEl.setAttribute('data-bs-theme', theme);
            } catch (error) {
                docEl.setAttribute('data-bs-theme', defaultTheme);
                    console.error('Theme detection failed:', error);
                }
        })();
    </script>
    <?php
}, 1);  

//ADD THE CUSTOM HEADER CODE (SET IN CUSTOMIZER)
add_action( 'wp_head', 'picowind_add_header_code' );
function picowind_add_header_code() {
    if (!get_theme_mod("picowind_fonts_header_code_disable")) {
        echo  get_theme_mod("picowind_fonts_header_code")." ";
    }
    echo get_theme_mod("picowind_header_code");
}

//ADD THE CUSTOM FOOTER CODE (SET IN CUSTOMIZER)
add_action( 'wp_footer', 'picowind_add_footer_code' );
function picowind_add_footer_code() {
	  //if (!current_user_can('administrator'))
      echo get_theme_mod("picowind_footer_code");
}

//ADD THE CUSTOM CHROME COLOR TAG (SET IN CUSTOMIZER)
add_action( 'wp_head', 'picowind_add_header_chrome_color' );
function picowind_add_header_chrome_color() {
	 if (get_theme_mod('picowind_header_chrome_color')!=""):
        ?><meta name="theme-color" content="<?php echo get_theme_mod('picowind_header_chrome_color'); ?>" />
	<?php endif;
}
 