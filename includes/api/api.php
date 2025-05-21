<?php
/**
 * PilotWP REST API
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class PilotWP_API {

    /**
     * Constructor
     */
    public function __construct() {
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    /**
     * Register REST API routes
     */
    public function register_routes() {
        register_rest_route('pilotwp/v1', '/submodules', [
            'methods' => 'GET',
            'callback' => [$this, 'get_submodules'],
            'permission_callback' => [$this, 'check_permissions'],
        ]);
        
        register_rest_route('pilotwp/v1', '/submodules/(?P<id>[a-zA-Z0-9_-]+)', [
            'methods' => 'GET',
            'callback' => [$this, 'get_submodule'],
            'permission_callback' => [$this, 'check_permissions'],
        ]);
    }

    /**
     * Check permissions for API access
     */
    public function check_permissions() {
        return current_user_can('manage_options');
    }

    /**
     * Get all submodules
     */
    public function get_submodules($request) {
        $manager = new PilotWP_Submodule_Manager();
        
        return rest_ensure_response([
            'installed' => $manager->get_installed_submodules(),
            'active' => pilotwp_get_active_submodules(),
        ]);
    }

    /**
     * Get a specific submodule
     */
    public function get_submodule($request) {
        $submodule_id = $request['id'];
        $manager = new PilotWP_Submodule_Manager();
        
        $submodule_info = $manager->get_submodule_info($submodule_id);
        
        if (!$submodule_info) {
            return new WP_Error('submodule_not_found', 'Submodule not found', ['status' => 404]);
        }
        
        return rest_ensure_response($submodule_info);
    }
}

new PilotWP_API();
