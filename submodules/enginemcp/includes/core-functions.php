<?php
/**
 * EngineMCP Core Functions
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get the EngineMCP version
 */
function enginemcp_get_version() {
    return ENGINEMCP_VERSION;
}

/**
 * Check if a provider is enabled
 */
function enginemcp_is_provider_enabled($provider_id) {
    return get_option("enginemcp_{$provider_id}_enabled", false);
}

/**
 * Enable a provider
 */
function enginemcp_enable_provider($provider_id) {
    update_option("enginemcp_{$provider_id}_enabled", true);
}

/**
 * Disable a provider
 */
function enginemcp_disable_provider($provider_id) {
    update_option("enginemcp_{$provider_id}_enabled", false);
}

/**
 * Get all enabled providers
 */
function enginemcp_get_enabled_providers() {
    $providers = ['wp_core', 'automattic', 'emzimmer', 'n8n'];
    $enabled = [];
    
    foreach ($providers as $provider) {
        if (enginemcp_is_provider_enabled($provider)) {
            $enabled[] = $provider;
        }
    }
    
    return $enabled;
}

/**
 * Log a message to the EngineMCP log
 */
function enginemcp_log($message, $level = 'info') {
    if (defined('WP_DEBUG') && WP_DEBUG) {
        $log_message = sprintf('[EngineMCP] [%s] %s', strtoupper($level), $message);
        error_log($log_message);
        
        // Also log to our custom log file if directory exists
        $log_dir = ENGINEMCP_DIR . 'logs';
        if (is_dir($log_dir) && is_writable($log_dir)) {
            $log_file = $log_dir . '/enginemcp.log';
            $timestamp = date('Y-m-d H:i:s');
            $log_entry = "[{$timestamp}] [{$level}] {$message}" . PHP_EOL;
            file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
        }
    }
}

/**
 * Get the MCP server status
 */
function enginemcp_get_server_status() {
    $providers = EngineMCP()->get_providers();
    $status = [];
    
    foreach ($providers as $id => $provider) {
        $status[$id] = [
            'id' => $id,
            'name' => $provider->get_name(),
            'enabled' => $provider->is_enabled(),
            'tools_count' => count($provider->get_tools()),
        ];
    }
    
    return $status;
}

/**
 * Validate MCP tool parameters
 */
function enginemcp_validate_tool_params($params, $schema) {
    // Basic validation implementation
    if (!is_array($params)) {
        return new WP_Error('invalid_params', 'Parameters must be an array');
    }
    
    if (isset($schema['required']) && is_array($schema['required'])) {
        foreach ($schema['required'] as $required_param) {
            if (!isset($params[$required_param])) {
                return new WP_Error(
                    'missing_required_param',
                    sprintf('Missing required parameter: %s', $required_param)
                );
            }
        }
    }
    
    return true;
}

/**
 * Generate a secure API key
 */
function enginemcp_generate_api_key() {
    return wp_generate_password(32, false, false);
}

/**
 * Check if the current request is authenticated for MCP access
 */
function enginemcp_is_authenticated() {
    // Check if authentication is disabled
    if (!get_option('enginemcp_enable_auth', true)) {
        return true;
    }
    
    // Check for API key in headers
    $headers = getallheaders();
    $api_key = null;
    
    if (isset($headers['X-EngineMCP-API-Key'])) {
        $api_key = $headers['X-EngineMCP-API-Key'];
    } elseif (isset($headers['Authorization'])) {
        // Check for Bearer token
        if (preg_match('/Bearer\s+(.*)$/i', $headers['Authorization'], $matches)) {
            $api_key = $matches[1];
        }
    }
    
    // Validate API key
    if ($api_key) {
        $stored_key = get_option('enginemcp_api_key');
        return hash_equals($stored_key, $api_key);
    }
    
    // Check if user is logged in and has appropriate capabilities
    return current_user_can('manage_options');
}

/**
 * Format MCP tool response
 */
function enginemcp_format_response($data, $success = true, $message = '') {
    $response = [
        'success' => $success,
        'data' => $data,
    ];
    
    if (!empty($message)) {
        $response['message'] = $message;
    }
    
    return $response;
}

/**
 * Get tool by ID from all providers
 */
function enginemcp_get_tool_by_id($tool_id) {
    $providers = EngineMCP()->get_providers();
    
    foreach ($providers as $provider) {
        $tools = $provider->get_tools();
        if (isset($tools[$tool_id])) {
            return new $tools[$tool_id]();
        }
    }
    
    return null;
}

/**
 * Get all available tools from all providers
 */
function enginemcp_get_all_tools() {
    $all_tools = [];
    $providers = EngineMCP()->get_providers();
    
    foreach ($providers as $provider_id => $provider) {
        if (!$provider->is_enabled()) {
            continue;
        }
        
        $tools = $provider->get_tools();
        foreach ($tools as $tool_id => $tool_class) {
            $tool = new $tool_class();
            $all_tools[$tool_id] = [
                'id' => $tool_id,
                'name' => $tool->get_name(),
                'description' => $tool->get_description(),
                'provider' => $provider_id,
                'schema' => $tool->get_schema(),
            ];
        }
    }
    
    return $all_tools;
}
