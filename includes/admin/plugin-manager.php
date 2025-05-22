<?php
/**
 * PilotWP Plugin Manager
 * Handles installation and updates of plugins from various sources
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class PilotWP_Plugin_Manager {
    
    /**
     * Supported plugin sources
     */
    private $sources = [
        'wordpress.org' => 'WordPress.org Repository',
        'github' => 'GitHub Repository', 
        'url' => 'Direct URL',
        'composer' => 'Composer Package'
    ];
    
    /**
     * Install plugin from various sources
     */
    public function install_plugin($plugin_data) {
        $source = $plugin_data['source'];
        
        switch ($source) {
            case 'wordpress.org':
                return $this->install_from_wordpress_org($plugin_data);
                
            case 'github':
                return $this->install_from_github($plugin_data);
                
            case 'url':
                return $this->install_from_url($plugin_data);
                
            case 'composer':
                return $this->install_from_composer($plugin_data);
                
            default:
                return [
                    'success' => false,
                    'message' => 'Unsupported plugin source'
                ];
        }
    }
    
    /**
     * Install from WordPress.org
     */
    private function install_from_wordpress_org($plugin_data) {
        if (!class_exists('Plugin_Upgrader')) {
            require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
        }
        
        $upgrader = new Plugin_Upgrader(new Automatic_Upgrader_Skin());
        $result = $upgrader->install("https://downloads.wordpress.org/plugin/{$plugin_data['slug']}.zip");
        
        if (is_wp_error($result)) {
            return [
                'success' => false,
                'message' => $result->get_error_message()
            ];
        }
        
        if ($plugin_data['activate']) {
            $plugin_file = $this->get_plugin_file($plugin_data['slug']);
            if ($plugin_file) {
                activate_plugin($plugin_file);
            }
        }
        
        return [
            'success' => true,
            'message' => 'Plugin installed successfully',
            'plugin_file' => $plugin_file ?? null
        ];
    }
    
    /**
     * Install from GitHub
     */
    private function install_from_github($plugin_data) {
        $github_url = $plugin_data['url'];
        
        // Extract GitHub info
        if (preg_match('/github\.com\/([^\/]+)\/([^\/]+)/', $github_url, $matches)) {
            $user = $matches[1];
            $repo = $matches[2];
            $download_url = "https://github.com/{$user}/{$repo}/archive/refs/heads/main.zip";
        } else {
            return [
                'success' => false,
                'message' => 'Invalid GitHub URL'
            ];
        }
        
        return $this->install_from_zip_url($download_url, $plugin_data);
    }
    
    /**
     * Install from direct URL
     */
    private function install_from_url($plugin_data) {
        return $this->install_from_zip_url($plugin_data['url'], $plugin_data);
    }
    
    /**
     * Install from ZIP URL
     */
    private function install_from_zip_url($zip_url, $plugin_data) {
        if (!class_exists('Plugin_Upgrader')) {
            require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
        }
        
        $upgrader = new Plugin_Upgrader(new Automatic_Upgrader_Skin());
        $result = $upgrader->install($zip_url);
        
        if (is_wp_error($result)) {
            return [
                'success' => false,
                'message' => $result->get_error_message()
            ];
        }
        
        // Try to find the installed plugin
        $plugin_file = null;
        if (isset($plugin_data['slug'])) {
            $plugin_file = $this->get_plugin_file($plugin_data['slug']);
        }
        
        if ($plugin_data['activate'] && $plugin_file) {
            activate_plugin($plugin_file);
        }
        
        // Track in database
        $this->track_plugin($plugin_file, $plugin_data);
        
        return [
            'success' => true,
            'message' => 'Plugin installed successfully',
            'plugin_file' => $plugin_file
        ];
    }
    
    /**
     * Install from Composer (using WP-CLI)
     */
    private function install_from_composer($plugin_data) {
        if (!class_exists('WP_CLI')) {
            return [
                'success' => false,
                'message' => 'WP-CLI is required for Composer installations'
            ];
        }
        
        $package = $plugin_data['slug']; // Should be composer package name
        
        try {
            // Install via WP-CLI
            $result = WP_CLI::runcommand("package install {$package}", [
                'return' => true,
                'launch' => false
            ]);
            
            return [
                'success' => true,
                'message' => 'Composer package installed successfully'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Composer installation failed: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Update plugin
     */
    public function update_plugin($plugin_file) {
        if (!current_user_can('update_plugins')) {
            return [
                'success' => false,
                'message' => 'Insufficient permissions'
            ];
        }
        
        // Check if it's a GitHub-tracked plugin
        $plugin_data = $this->get_tracked_plugin($plugin_file);
        
        if ($plugin_data && $plugin_data->source === 'github') {
            return $this->update_github_plugin($plugin_file, $plugin_data);
        }
        
        // Standard WordPress update
        if (!class_exists('Plugin_Upgrader')) {
            require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
        }
        
        $upgrader = new Plugin_Upgrader(new Automatic_Upgrader_Skin());
        $result = $upgrader->upgrade($plugin_file);
        
        if (is_wp_error($result)) {
            return [
                'success' => false,
                'message' => $result->get_error_message()
            ];
        }
        
        return [
            'success' => true,
            'message' => 'Plugin updated successfully'
        ];
    }
    
    /**
     * Update GitHub plugin
     */
    private function update_github_plugin($plugin_file, $plugin_data) {
        $repo = $plugin_data->repository;
        $download_url = "https://github.com/{$repo}/archive/refs/heads/main.zip";
        
        // Deactivate plugin
        $was_active = is_plugin_active($plugin_file);
        if ($was_active) {
            deactivate_plugins($plugin_file);
        }
        
        // Remove old version
        $plugin_dir = dirname(WP_PLUGIN_DIR . '/' . $plugin_file);
        if (is_dir($plugin_dir)) {
            $this->delete_directory($plugin_dir);
        }
        
        // Install new version
        $install_result = $this->install_from_zip_url($download_url, [
            'slug' => dirname($plugin_file),
            'activate' => $was_active
        ]);
        
        return $install_result;
    }
    
    /**
     * Get plugin file from slug
     */
    private function get_plugin_file($slug) {
        $plugins = get_plugins();
        
        foreach ($plugins as $plugin_file => $plugin_info) {
            if (strpos($plugin_file, $slug . '/') === 0) {
                return $plugin_file;
            }
        }
        
        return null;
    }
    
    /**
     * Track plugin in database
     */
    private function track_plugin($plugin_file, $plugin_data) {
        if (!$plugin_file) {
            return;
        }
        
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'pilotwp_plugins';
        
        $wpdb->replace($table_name, [
            'plugin_file' => $plugin_file,
            'source' => $plugin_data['source'],
            'repository' => $plugin_data['url'] ?? '',
            'auto_update' => 0,
            'last_checked' => current_time('mysql')
        ]);
    }
    
    /**
     * Get tracked plugin data
     */
    private function get_tracked_plugin($plugin_file) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'pilotwp_plugins';
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE plugin_file = %s",
            $plugin_file
        ));
    }
    
    /**
     * Get all tracked plugins
     */
    public function get_tracked_plugins() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'pilotwp_plugins';
        
        return $wpdb->get_results("SELECT * FROM {$table_name} ORDER BY plugin_file");
    }
    
    /**
     * Delete directory recursively
     */
    private function delete_directory($dir) {
        if (!is_dir($dir)) {
            return false;
        }
        
        $files = array_diff(scandir($dir), ['.', '..']);
        
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            if (is_dir($path)) {
                $this->delete_directory($path);
            } else {
                unlink($path);
            }
        }
        
        return rmdir($dir);
    }
    
    /**
     * Get supported sources
     */
    public function get_sources() {
        return $this->sources;
    }
    
    /**
     * Check for updates
     */
    public function check_for_updates() {
        $tracked_plugins = $this->get_tracked_plugins();
        $updates_available = [];
        
        foreach ($tracked_plugins as $plugin) {
            if ($plugin->source === 'github') {
                // Check GitHub for updates
                $has_update = $this->check_github_update($plugin);
                if ($has_update) {
                    $updates_available[] = $plugin;
                }
            }
        }
        
        return $updates_available;
    }
    
    /**
     * Check GitHub for updates
     */
    private function check_github_update($plugin) {
        // This would require GitHub API integration
        // For now, return false
        return false;
    }
    
    /**
     * Install specific plugins for LocalWP/Development
     */
    public function install_development_plugins() {
        $dev_plugins = [
            [
                'source' => 'wordpress.org',
                'slug' => 'github-updater',
                'activate' => true
            ],
            [
                'source' => 'wordpress.org', 
                'slug' => 'wp-cli',
                'activate' => true
            ],
            [
                'source' => 'wordpress.org',
                'slug' => 'easy-theme-and-plugin-upgrades',
                'activate' => true
            ]
        ];
        
        $results = [];
        
        foreach ($dev_plugins as $plugin) {
            $result = $this->install_plugin($plugin);
            $results[] = [
                'plugin' => $plugin['slug'],
                'result' => $result
            ];
        }
        
        return $results;
    }
}
