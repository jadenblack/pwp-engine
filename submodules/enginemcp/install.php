<?php
/**
 * EngineMCP Installation Script
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Installation function called when the submodule is installed
 */
function enginemcp_install() {
    // Create necessary directories
    $directories = [
        PILOTWP_SUBMODULES_DIR . 'enginemcp/vendors',
        PILOTWP_SUBMODULES_DIR . 'enginemcp/tools',
        PILOTWP_SUBMODULES_DIR . 'enginemcp/cache',
        PILOTWP_SUBMODULES_DIR . 'enginemcp/logs'
    ];
    
    foreach ($directories as $dir) {
        if (!file_exists($dir)) {
            wp_mkdir_p($dir);
        }
    }
    
    // Set default options
    add_option('enginemcp_version', '1.0.0');
    add_option('enginemcp_enable_frontend', false);
    add_option('enginemcp_enable_auth', true);
    add_option('enginemcp_api_key', wp_generate_password(32, false, false));
    
    // Initialize provider settings
    add_option('enginemcp_wp_core_enabled', true);
    add_option('enginemcp_automattic_enabled', true);
    add_option('enginemcp_emzimmer_enabled', false);
    add_option('enginemcp_n8n_enabled', false);
    
    // Multi-site configuration (empty by default)
    add_option('enginemcp_emzimmer_sites', []);
    
    // n8n configuration (empty by default)
    add_option('enginemcp_n8n_api_url', '');
    add_option('enginemcp_n8n_api_key', '');
    
    // Download and install dependencies asynchronously
    wp_schedule_single_event(time(), 'enginemcp_install_dependencies');
    
    return true;
}

/**
 * Install dependencies in the background
 */
function enginemcp_install_dependencies() {
    // Install Automattic's MCP if enabled
    if (get_option('enginemcp_automattic_enabled', true)) {
        enginemcp_download_automattic_mcp();
    }
    
    // Install Guzzle for n8n client
    if (get_option('enginemcp_n8n_enabled', false)) {
        enginemcp_install_guzzle();
    }
}

/**
 * Download Automattic's MCP plugin
 */
function enginemcp_download_automattic_mcp() {
    $target_dir = PILOTWP_SUBMODULES_DIR . 'enginemcp/vendors/automattic-mcp';
    $download_url = 'https://github.com/Automattic/wordpress-mcp/archive/refs/heads/main.zip';
    
    if (!file_exists($target_dir)) {
        wp_mkdir_p($target_dir);
    }
    
    $temp_file = download_url($download_url);
    
    if (!is_wp_error($temp_file)) {
        $result = unzip_file($temp_file, $target_dir);
        @unlink($temp_file);
        
        if (!is_wp_error($result)) {
            // Move files from extracted directory
            $extracted_dirs = glob($target_dir . '/*', GLOB_ONLYDIR);
            
            if (!empty($extracted_dirs)) {
                $extracted_dir = $extracted_dirs[0];
                $files = glob($extracted_dir . '/*');
                
                foreach ($files as $file) {
                    $file_name = basename($file);
                    if (!file_exists($target_dir . '/' . $file_name)) {
                        rename($file, $target_dir . '/' . $file_name);
                    }
                }
                
                // Clean up extracted directory
                array_map('unlink', glob($extracted_dir . '/*'));
                rmdir($extracted_dir);
            }
        }
    }
}

/**
 * Install Guzzle HTTP client for n8n
 */
function enginemcp_install_guzzle() {
    $vendor_dir = PILOTWP_SUBMODULES_DIR . 'enginemcp/vendors/n8n-client';
    
    if (!file_exists($vendor_dir)) {
        wp_mkdir_p($vendor_dir);
    }
    
    // Create a minimal composer.json
    $composer_json = [
        'require' => [
            'guzzlehttp/guzzle' => '^7.0'
        ],
        'config' => [
            'vendor-dir' => 'vendor'
        ]
    ];
    
    file_put_contents($vendor_dir . '/composer.json', json_encode($composer_json, JSON_PRETTY_PRINT));
    
    // Try to run composer install if composer is available
    $composer_phar = $vendor_dir . '/composer.phar';
    
    // Download composer.phar if it doesn't exist
    if (!file_exists($composer_phar)) {
        $composer_installer = download_url('https://getcomposer.org/composer.phar');
        
        if (!is_wp_error($composer_installer)) {
            copy($composer_installer, $composer_phar);
            @unlink($composer_installer);
            chmod($composer_phar, 0755);
        }
    }
    
    // Run composer install
    if (file_exists($composer_phar)) {
        $current_dir = getcwd();
        if (chdir($vendor_dir)) {
            @exec('php composer.phar install --no-dev --optimize-autoloader 2>&1', $output, $return_var);
            chdir($current_dir);
        }
    }
}

// Register the background installation hook
add_action('enginemcp_install_dependencies', 'enginemcp_install_dependencies');
