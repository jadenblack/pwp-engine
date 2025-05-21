<?php
/**
 * EngineMCP Activation Script
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Activation function called when the submodule is activated
 */
function enginemcp_activate() {
    // Flush rewrite rules to ensure REST API endpoints are available
    flush_rewrite_rules();
    
    // Register the MCP server with the system
    update_option('enginemcp_activated_at', time());
    
    // Add admin notice for successful activation
    add_option('enginemcp_activation_notice', true);
    
    // Schedule health check
    if (!wp_next_scheduled('enginemcp_health_check')) {
        wp_schedule_event(time(), 'hourly', 'enginemcp_health_check');
    }
    
    return true;
}

/**
 * Show activation notice
 */
function enginemcp_activation_notice() {
    if (get_option('enginemcp_activation_notice')) {
        echo '<div class="notice notice-success is-dismissible">';
        echo '<p><strong>EngineMCP activated successfully!</strong> You can now configure MCP providers and tools in the PilotWP settings.</p>';
        echo '</div>';
        
        delete_option('enginemcp_activation_notice');
    }
}
add_action('admin_notices', 'enginemcp_activation_notice');

/**
 * Health check function
 */
function enginemcp_health_check() {
    // Check if all providers are functioning correctly
    $providers = [
        'wp_core' => true,
        'automattic' => class_exists('WordPress_MCP'),
        'emzimmer' => !empty(get_option('enginemcp_emzimmer_sites', [])),
        'n8n' => !empty(get_option('enginemcp_n8n_api_url', ''))
    ];
    
    update_option('enginemcp_provider_health', $providers);
    
    // Log any issues
    foreach ($providers as $provider => $status) {
        if (!$status && get_option("enginemcp_{$provider}_enabled", false)) {
            error_log("EngineMCP: Provider {$provider} is enabled but not functioning correctly");
        }
    }
}
add_action('enginemcp_health_check', 'enginemcp_health_check');
