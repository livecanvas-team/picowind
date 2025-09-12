<?php
/**
 * Header Navbar template part for Picowind
 *
 * TailwindCSS v4 + DaisyUI
 */

// Customizer settings
$breakpoint = get_theme_mod('picowind_header_navbar_breakpoint', 'md');
$position   = get_theme_mod('picowind_header_navbar_position', 'static');
$nav_color  = get_theme_mod('picowind_header_navbar_color', 'base');

// Tailwind breakpoint classes
$desktop_class = $breakpoint === 'none' ? 'hidden' : "hidden {$breakpoint}:flex";
$mobile_class  = $breakpoint === 'none' ? '' : "{$breakpoint}:hidden";

// Navbar position
$pos_class = match ($position) {
  'fixed-top'    => 'fixed top-0 inset-x-0 z-50',
  'fixed-bottom' => 'fixed bottom-0 inset-x-0 z-50',
  'hidden'       => 'hidden',
  default        => ''
};

// Navbar color classes (DaisyUI bg + matching text)
$nav_classes = $nav_color === 'transparent'
  ? 'bg-transparent'
  : "bg-{$nav_color} text-{$nav_color}-content";
?>

<div class="flex items-center justify-between py-2 <?php echo esc_attr("$pos_class $nav_classes"); ?>">

  <!-- Branding -->
  <div class="flex-1 py-2">
    <?php if ( has_custom_logo() ) : ?>
      <?php the_custom_logo(); ?>
    <?php else : ?>
      <a href="<?php echo esc_url( home_url( '/' ) ); ?>" 
         class="text-xl font-bold text-primary block">
        <?php bloginfo( 'name' ); ?>
      </a>
    <?php endif; ?>

    <?php if ( get_bloginfo( 'description', 'display' ) ) : ?>
      <p class="text-sm opacity-70"><?php bloginfo( 'description' ); ?></p>
    <?php endif; ?>
  </div>

  <!-- Desktop Navigation -->
  <nav class="<?php echo esc_attr($desktop_class); ?> items-center space-x-2">
    <?php
      wp_nav_menu([
        'theme_location' => 'primary',
        'container'      => false,
        'menu_class'     => 'menu menu-horizontal rounded-box',
        'walker'         => new Picowind_Navwalker()
      ]);
    ?>
    <!-- Search Dropdown -->
    <div class="dropdown dropdown-end">
      <label tabindex="0" class="btn btn-ghost btn-square">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5"
             fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                d="M21 21l-4.35-4.35M9.5 17a7.5 7.5 0 1 1 0-15 
                   7.5 7.5 0 0 1 0 15z" />
        </svg>
      </label>
      <div tabindex="0"
           class="dropdown-content z-[1] p-2 shadow bg-base-100 rounded-box w-72">
        <?php get_search_form(); ?>
      </div>
    </div>
  </nav>

  <!-- Mobile Drawer -->
  <div class="<?php echo esc_attr($mobile_class); ?>">
    <div class="drawer">
      <input id="mobile-drawer" type="checkbox" class="drawer-toggle" />
      <div class="drawer-content">
        <label for="mobile-drawer" class="btn btn-square btn-ghost">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6"
               fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M4 6h16M4 12h16M4 18h16"/>
          </svg>
        </label>
      </div>
      <div class="drawer-side">
        <label for="mobile-drawer" class="drawer-overlay"></label>
        <ul class="menu p-4 w-80 min-h-full bg-base-200 space-y-4">
          <li><?php get_search_form(); ?></li>
          <?php
            wp_nav_menu([
              'theme_location' => 'primary',
              'container'      => false,
              'items_wrap'     => '%3$s',
              'walker'         => new Picowind_Navwalker()
            ]);
          ?>
        </ul>
      </div>
    </div>
  </div>

</div>
