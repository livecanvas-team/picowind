<?php

declare(strict_types=1);

namespace Picowind;

use Picowind\Core\Discovery\Attributes\Hook;
use Picowind\Core\Discovery\Attributes\Service;
use Timber\Timber;

#[Service]
class Menu
{
    /**
     * Convert Timber menu to hierarchical object tree
     *
     * @param \Timber\MenuItem[] $items Menu items from Timber
     * @return array Hierarchical menu structure
     */
    private function build_menu_tree(array $items): array
    {
        $menu_tree = [];
        $menu_items_by_id = [];

        // First pass: index by ID and convert to arrays
        foreach ($items as $item) {
            $menu_items_by_id[$item->id] = [
                'id' => $item->id,
                'title' => $item->title,
                'url' => $item->url,
                'target' => $item->target ?: null,
                'classes' => implode(' ', $item->classes ?: []),
                'current' => $item->current,
                'current_item_ancestor' => $item->current_item_ancestor,
                'current_item_parent' => $item->current_item_parent,
                'attr_title' => $item->attr_title ?: null,
                'description' => $item->description ?: null,
                'parent_id' => (int) $item->menu_item_parent,
                'children' => [],
            ];
        }

        // Second pass: build tree by linking children to parents
        foreach ($menu_items_by_id as $id => $item) {
            if ($item['parent_id'] == 0) {
                // Top level item
                $menu_tree[] = &$menu_items_by_id[$id];
            } else {
                // Child item - add to parent's children array
                if (isset($menu_items_by_id[$item['parent_id']])) {
                    $menu_items_by_id[$item['parent_id']]['children'][] = &$menu_items_by_id[$id];
                }
            }
        }

        return $menu_tree;
    }

    #[Hook('timber/context', 'filter')]
    public function add_to_context(array $context): array
    {
        if (!isset($context['menu'])) {
            $context['menu'] = [];
        }

        if (!isset($context['nav_items'])) {
            $context['nav_items'] = [];
        }

        $locations = get_nav_menu_locations();

        foreach ($locations as $location => $menu_id) {
            // Use WordPress native function to get ALL menu items including children
            $items = wp_get_nav_menu_items($menu_id);

            if ($items) {
                // Also keep Timber menu object for compatibility
                $timber_menu = Timber::get_menu($location);
                if ($timber_menu) {
                    $context['menu'][$location] = $timber_menu;
                }

                // Convert WP menu items to our format
                $formatted_items = [];
                foreach ($items as $item) {
                    $formatted_items[] = (object) [
                        'id' => $item->ID,
                        'title' => $item->title,
                        'url' => $item->url,
                        'target' => $item->target ?: '_self',
                        'classes' => $item->classes,
                        'current' => $item->current,
                        'current_item_ancestor' => $item->current_item_ancestor,
                        'current_item_parent' => $item->current_item_parent,
                        'attr_title' => $item->attr_title,
                        'description' => $item->description,
                        'menu_item_parent' => $item->menu_item_parent,
                    ];
                }

                // Add hierarchical tree structure for easier Twig rendering
                $context['nav_items'][$location] = $this->build_menu_tree($formatted_items);
            }
        }

        return $context;
    }
}
