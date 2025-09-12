<?php
/**
 * Optional Topbar for Picowind
 *
 * TailwindCSS v4 + DaisyUI
 */

if ( ! get_theme_mod('enable_topbar') ) {
    return;
}

$topbar_color = get_theme_mod('topbar_color', 'base');
$topbar_classes = $topbar_color === 'transparent'
    ? 'bg-transparent'
    : "bg-{$topbar_color} text-{$topbar_color}-content";

$content = get_theme_mod('topbar_content', '');
?>

<div class="w-full <?php echo esc_attr($topbar_classes); ?> py-1 text-sm">
  <div class="container mx-auto px-4 flex justify-between items-center">
    <div class="flex-1">
      <?php echo wp_kses_post($content); ?>
    </div>
  </div>
</div>
