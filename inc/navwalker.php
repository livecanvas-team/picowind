<?php
/**
 * Picowind Tailwind + DaisyUI NavWalker
 * Base walker for menus in Picowind theme.
 */

 
class Picowind_Navwalker extends Walker_Nav_Menu {

    function start_lvl( &$output, $depth = 0, $args = null ) {
        $indent = str_repeat("\t", $depth);
        $classes = "p-2 shadow menu dropdown-content bg-base-100 rounded-box w-52 z-50";
        $output .= "\n$indent<ul class=\"$classes\">\n";
    }

    function start_el( &$output, $item, $depth = 0, $args = null, $id = 0 ) {
        $indent = ($depth) ? str_repeat("\t", $depth) : '';
        $classes = empty( $item->classes ) ? [] : (array) $item->classes;

        $has_children = in_array('menu-item-has-children', $classes, true);

        // Check for current item or ancestor
        $is_active = in_array('current-menu-item', $classes, true) ||
                     in_array('current-menu-ancestor', $classes, true);

        $li_classes = ['relative'];
        if ($has_children) $li_classes[] = 'menu-item-has-children';
        if ($is_active) $li_classes[] = 'active'; // DaisyUI active class

        $output .= $indent . '<li class="' . esc_attr(implode(' ', $li_classes)) . '">';

        $atts = [];
        $atts['href'] = ! empty( $item->url ) ? $item->url : '';
        $atts['class'] = 'px-4 py-2 hover:bg-base-200';

        if ($is_active) {
            // Tailwind/DaisyUI highlight
            $atts['class'] .= ' bg-primary text-primary-content rounded-md';
        }

        $attributes = '';
        foreach ( $atts as $attr => $value ) {
            if ( ! empty( $value ) ) {
                $attributes .= ' ' . $attr . '="' . esc_attr( $value ) . '"';
            }
        }

        $title = apply_filters( 'the_title', $item->title, $item->ID );

        if ( $has_children ) {
            $output .= '<div class="dropdown dropdown-hover">';
            $output .= "<a tabindex='0'$attributes>$title</a>";
        } else {
            $output .= "<a$attributes>$title</a>";
        }
    }

    function end_el( &$output, $item, $depth = 0, $args = null ) {
        if ( in_array('menu-item-has-children', (array) $item->classes, true) ) {
            $output .= "</div>"; // close dropdown
        }
        $output .= "</li>\n";
    }
}
