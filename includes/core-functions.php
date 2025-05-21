<?php
/**
 * PilotWP Core Functions
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get the plugin version
 */
function pilotwp_get_version() {
    return PILOTWP_VERSION;
}

/**
 * Check if a submodule is active
 */
function pilotwp_is_submodule_active($submodule_id) {
    $active_submodules = get_option(PILOTWP_ACTIVE_SUBMODULES, []);
    return in_array($submodule_id, $active_submodules);
}

/**
 * Get active submodules
 */
function pilotwp_get_active_submodules() {
    return get_option(PILOTWP_ACTIVE_SUBMODULES, []);
}

/**
 * Log a message to the WordPress debug log
 */
function pilotwp_log($message, $level = 'info') {
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log("[PilotWP] [{$level}] {$message}");
    }
}

/**
 * Generate a random API key
 */
function pilotwp_generate_api_key() {
    return wp_generate_password(32, false, false);
}
