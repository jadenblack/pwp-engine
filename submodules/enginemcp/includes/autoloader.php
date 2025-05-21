<?php
/**
 * EngineMCP Autoloader
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Simple autoloader for EngineMCP classes
 */
spl_autoload_register(function ($class_name) {
    // Check if this is an EngineMCP class
    if (strpos($class_name, 'EngineMCP_') !== 0) {
        return;
    }
    
    // Convert class name to file path
    $class_file = str_replace('EngineMCP_', '', $class_name);
    $class_file = str_replace('_', '-', strtolower($class_file));
    
    // Define possible locations for the class file
    $possible_paths = [
        ENGINEMCP_DIR . 'includes/class-' . $class_file . '.php',
        ENGINEMCP_DIR . 'includes/providers/class-' . $class_file . '.php',
        ENGINEMCP_DIR . 'includes/tools/class-' . $class_file . '.php',
        ENGINEMCP_DIR . 'includes/api/class-' . $class_file . '.php',
        ENGINEMCP_DIR . 'includes/admin/class-' . $class_file . '.php',
    ];
    
    // Try to include the class file
    foreach ($possible_paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            return;
        }
    }
});

/**
 * Load third-party autoloaders
 */
function enginemcp_load_vendor_autoloaders() {
    // Load Guzzle autoloader for n8n client
    $guzzle_autoloader = ENGINEMCP_VENDORS_DIR . 'n8n-client/vendor/autoload.php';
    if (file_exists($guzzle_autoloader)) {
        require_once $guzzle_autoloader;
    }
    
    // Load Automattic MCP autoloader if it exists
    $automattic_autoloader = ENGINEMCP_VENDORS_DIR . 'automattic-mcp/vendor/autoload.php';
    if (file_exists($automattic_autoloader)) {
        require_once $automattic_autoloader;
    }
}

// Load vendor autoloaders
enginemcp_load_vendor_autoloaders();
