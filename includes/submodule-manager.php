<?php
/**
 * PilotWP Submodule Manager
 * 
 * Handles installation, activation, deactivation and updates of submodules
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class PilotWP_Submodule_Manager {

    /**
     * Available submodules registry
     */
    private $registry = [];

    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_init', [$this, 'init_registry']);
        add_action('admin_init', [$this, 'check_for_updates']);
        add_action('wp_ajax_pilotwp_install_submodule', [$this, 'ajax_install_submodule']);
        add_action('wp_ajax_pilotwp_activate_submodule', [$this, 'ajax_activate_submodule']);
        add_action('wp_ajax_pilotwp_deactivate_submodule', [$this, 'ajax_deactivate_submodule']);
    }

    /**
     * Initialize the registry of available submodules
     */
    public function init_registry() {
        // Default registry URL - can be filtered
        $registry_url = apply_filters('pilotwp_registry_url', 'https://raw.githubusercontent.com/jadenblack/pwp-engine/main/registry.json');
        
        // Get registry from transient or remote
        $registry = get_transient('pilotwp_submodules_registry');
        
        if (false === $registry) {
            $response = wp_remote_get($registry_url);
            
            if (!is_wp_error($response) && 200 === wp_remote_retrieve_response_code($response)) {
                $registry = json_decode(wp_remote_retrieve_body($response), true);
                set_transient('pilotwp_submodules_registry', $registry, DAY_IN_SECONDS);
            }
        }
        
        if (is_array($registry)) {
            $this->registry = $registry;
        }
    }

    /**
     * Get all registered submodules
     */
    public function get_registry() {
        return $this->registry;
    }

    /**
     * Get a specific submodule from registry
     */
    public function get_submodule_info($submodule_id) {
        return isset($this->registry[$submodule_id]) ? $this->registry[$submodule_id] : false;
    }

    /**
     * Get all installed submodules
     */
    public function get_installed_submodules() {
        $installed = [];
        $submodules_dir = PILOTWP_SUBMODULES_DIR;
        
        if (!file_exists($submodules_dir)) {
            return $installed;
        }
        
        $directories = glob($submodules_dir . '/*', GLOB_ONLYDIR);
        
        foreach ($directories as $dir) {
            $submodule_id = basename($dir);
            $manifest_file = $dir . '/manifest.json';
            
            if (file_exists($manifest_file)) {
                $manifest = json_decode(file_get_contents($manifest_file), true);
                if ($manifest) {
                    $installed[$submodule_id] = $manifest;
                }
            }
        }
        
        return $installed;
    }

    /**
     * Install a submodule from GitHub
     */
    public function install_submodule($submodule_id) {
        $submodule_info = $this->get_submodule_info($submodule_id);
        
        if (!$submodule_info) {
            return new WP_Error('invalid_submodule', __('Submodule not found in registry', 'pilotwp'));
        }
        
        // Create submodules directory if it doesn't exist
        if (!file_exists(PILOTWP_SUBMODULES_DIR)) {
            wp_mkdir_p(PILOTWP_SUBMODULES_DIR);
        }
        
        $target_dir = PILOTWP_SUBMODULES_DIR . $submodule_id;
        
        // If already installed, clean up first
        if (file_exists($target_dir)) {
            $this->delete_directory($target_dir);
        }
        
        // Create target directory
        wp_mkdir_p($target_dir);
        
        // Get the zip file from GitHub
        $github_repo = $submodule_info['repository'];
        $github_branch = isset($submodule_info['branch']) ? $submodule_info['branch'] : 'main';
        $download_url = "https://github.com/{$github_repo}/archive/refs/heads/{$github_branch}.zip";
        
        $temp_file = download_url($download_url);
        
        if (is_wp_error($temp_file)) {
            return $temp_file;
        }
        
        // Unzip the file
        $result = unzip_file($temp_file, $target_dir);
        @unlink($temp_file); // Delete the temp file
        
        if (is_wp_error($result)) {
            $this->delete_directory($target_dir);
            return $result;
        }
        
        // Move files from the extracted directory to the target directory
        $extracted_dir = glob($target_dir . '/*', GLOB_ONLYDIR);
        
        if (!empty($extracted_dir)) {
            $extracted_dir = $extracted_dir[0]; // Get the first directory
            $files = glob($extracted_dir . '/{*,.*}', GLOB_BRACE);
            
            foreach ($files as $file) {
                $file_name = basename($file);
                if ($file_name === '.' || $file_name === '..') {
                    continue;
                }
                
                rename($file, $target_dir . '/' . $file_name);
            }
            
            // Remove the extracted directory
            $this->delete_directory($extracted_dir);
        }
        
        // Create manifest file
        $manifest = [
            'id' => $submodule_id,
            'name' => $submodule_info['name'],
            'version' => $submodule_info['version'],
            'description' => $submodule_info['description'],
            'repository' => $submodule_info['repository'],
            'installed_at' => time(),
        ];
        
        file_put_contents($target_dir . '/manifest.json', json_encode($manifest));
        
        // Run installation script if exists
        $install_script = $target_dir . '/install.php';
        if (file_exists($install_script)) {
            include_once $install_script;
            if (function_exists($submodule_id . '_install')) {
                call_user_func($submodule_id . '_install');
            }
        }
        
        return true;
    }

    /**
     * Activate a submodule
     */
    public function activate_submodule($submodule_id) {
        $installed_submodules = $this->get_installed_submodules();
        
        if (!isset($installed_submodules[$submodule_id])) {
            return new WP_Error('not_installed', __('Submodule is not installed', 'pilotwp'));
        }
        
        $active_submodules = get_option(PILOTWP_ACTIVE_SUBMODULES, []);
        
        if (in_array($submodule_id, $active_submodules)) {
            return true; // Already active
        }
        
        // Add to active submodules
        $active_submodules[] = $submodule_id;
        update_option(PILOTWP_ACTIVE_SUBMODULES, $active_submodules);
        
        return true;
    }

    /**
     * Check for updates for all installed submodules
     */
    public function check_for_updates() {
        // Implementation for checking updates
    }

    /**
     * AJAX handlers
     */
    public function ajax_install_submodule() {
        check_ajax_referer('pilotwp_admin', 'security');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('You do not have permission to install submodules', 'pilotwp')]);
            exit;
        }
        
        $submodule_id = isset($_POST['submodule_id']) ? sanitize_text_field($_POST['submodule_id']) : '';
        
        if (empty($submodule_id)) {
            wp_send_json_error(['message' => __('Invalid submodule ID', 'pilotwp')]);
            exit;
        }
        
        $result = $this->install_submodule($submodule_id);
        
        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        } else {
            wp_send_json_success(['message' => __('Submodule installed successfully', 'pilotwp')]);
        }
        
        exit;
    }

    public function ajax_activate_submodule() {
        check_ajax_referer('pilotwp_admin', 'security');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('You do not have permission to activate submodules', 'pilotwp')]);
            exit;
        }
        
        $submodule_id = isset($_POST['submodule_id']) ? sanitize_text_field($_POST['submodule_id']) : '';
        
        if (empty($submodule_id)) {
            wp_send_json_error(['message' => __('Invalid submodule ID', 'pilotwp')]);
            exit;
        }
        
        $result = $this->activate_submodule($submodule_id);
        
        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        } else {
            wp_send_json_success(['message' => __('Submodule activated successfully', 'pilotwp')]);
        }
        
        exit;
    }

    public function ajax_deactivate_submodule() {
        // Implementation for deactivation
    }

    /**
     * Recursively delete a directory
     */
    private function delete_directory($dir) {
        if (!file_exists($dir)) {
            return true;
        }
        
        if (!is_dir($dir)) {
            return unlink($dir);
        }
        
        foreach (scandir($dir) as $item) {
            if ($item == '.' || $item == '..') {
                continue;
            }
            
            if (!$this->delete_directory($dir . DIRECTORY_SEPARATOR . $item)) {
                return false;
            }
        }
        
        return rmdir($dir);
    }
}
