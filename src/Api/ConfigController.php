<?php

declare(strict_types=1);

namespace Picowind\Api;

use Exception;
use Picowind\Core\Discovery\Attributes\Controller;
use Picowind\Core\Discovery\Attributes\Route;
use Picowind\Utils\Config;
use WP_REST_Request;
use WP_REST_Response;

#[Controller(namespace: 'picowind/v1', prefix: '/config')]
final class ConfigController
{
    /**
     * Get config value by path
     */
    #[Route(
        path: '',
        methods: 'GET',
        permission_callback: 'manage_options',
        args: [
            'path' => [
                'required' => false,
                'type' => 'string',
                'description' => 'Property path to read (e.g., "settings.theme.color")',
                'sanitize_callback' => 'sanitize_text_field',
            ],
        ],
    )]
    public function get(WP_REST_Request $request): WP_REST_Response
    {
        $path = $request->get_param('path');

        try {
            if (empty($path)) {
                // Return all config if no path specified
                $allOptions = json_decode(
                    get_option('picowind_options', '{}'),
                    null,
                    512,
                    JSON_THROW_ON_ERROR,
                );

                return new WP_REST_Response([
                    'success' => true,
                    'data' => $allOptions,
                ], 200);
            }

            $value = Config::get($path);

            return new WP_REST_Response([
                'success' => true,
                'data' => $value,
                'path' => $path,
            ], 200);
        } catch (Exception $e) {
            return new WP_REST_Response([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Replace entire config (bulk update)
     */
    #[Route(
        path: '',
        methods: 'PUT',
        permission_callback: 'manage_options',
        args: [
            'data' => [
                'required' => true,
                'type' => 'object',
                'description' => 'Complete config object to save',
            ],
        ],
    )]
    public function replace(WP_REST_Request $request): WP_REST_Response
    {
        $data = $request->get_param('data');

        try {
            $jsonData = wp_json_encode($data, JSON_THROW_ON_ERROR);
            update_option('picowind_options', $jsonData);

            return new WP_REST_Response([
                'success' => true,
                'message' => 'Config replaced successfully',
                'data' => $data,
            ], 200);
        } catch (Exception $e) {
            return new WP_REST_Response([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Update config value by path (single update) or merge object (bulk update)
     */
    #[Route(
        path: '',
        methods: 'POST',
        permission_callback: 'manage_options',
        args: [
            'path' => [
                'required' => false,
                'type' => 'string',
                'description' => 'Property path to update (e.g., "settings.theme.color")',
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'value' => [
                'required' => false,
                'description' => 'Value to set',
            ],
        ],
    )]
    public function update(WP_REST_Request $request): WP_REST_Response
    {
        $path = $request->get_param('path');
        $value = $request->get_param('value');
        $body = $request->get_json_params();

        try {
            // If path is provided, do single update
            if ($path !== null) {
                Config::set($path, $value);

                return new WP_REST_Response([
                    'success' => true,
                    'message' => 'Config updated successfully',
                    'path' => $path,
                    'value' => $value,
                ], 200);
            }

            // Otherwise, merge the entire body object with existing config
            if (!empty($body)) {
                $currentOptions = json_decode(
                    get_option('picowind_options', '{}'),
                    true,
                    512,
                    JSON_THROW_ON_ERROR,
                );

                // Recursively merge arrays
                $mergedOptions = array_replace_recursive($currentOptions, $body);
                
                $jsonData = wp_json_encode($mergedOptions, JSON_THROW_ON_ERROR);
                update_option('picowind_options', $jsonData);

                return new WP_REST_Response([
                    'success' => true,
                    'message' => 'Config updated successfully',
                    'data' => $mergedOptions,
                ], 200);
            }

            return new WP_REST_Response([
                'success' => false,
                'message' => 'Either path+value or request body must be provided',
            ], 400);
        } catch (Exception $e) {
            return new WP_REST_Response([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Delete entire config or specific path
     */
    #[Route(
        path: '',
        methods: 'DELETE',
        permission_callback: 'manage_options',
        args: [
            'path' => [
                'required' => false,
                'type' => 'string',
                'description' => 'Property path to delete. If empty, deletes all config.',
                'sanitize_callback' => 'sanitize_text_field',
            ],
        ],
    )]
    public function delete(WP_REST_Request $request): WP_REST_Response
    {
        $path = $request->get_param('path');

        try {
            if (empty($path)) {
                // Delete all config
                delete_option('picowind_options');

                return new WP_REST_Response([
                    'success' => true,
                    'message' => 'All config deleted successfully',
                ], 200);
            }

            // Set path to null to delete it
            Config::set($path, null);

            return new WP_REST_Response([
                'success' => true,
                'message' => 'Config path deleted successfully',
                'path' => $path,
            ], 200);
        } catch (Exception $e) {
            return new WP_REST_Response([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
