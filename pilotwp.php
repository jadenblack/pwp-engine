<?php
/**
 * Plugin Name: PilotWP Engine
 * Plugin URI: https://github.com/jadenblack/pwp-engine
 * Description: Enterprise-grade WordPress enhancement platform with submodule support for forms, security, MCP servers, and n8n integration.
 * Version: 1.0.0
 * Author: PilotWP Team
 * Author URI: https://github.com/pilotwp
 * License: GPL-3.0+
 * Text Domain: pilotwp
 * Requires at least: 5.9
 * Tested up to: 6.7
 * Requires PHP: 7.4
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Main PilotWP Class
 */
final class PilotWP {

    /**
     * @var PilotWP The single instance of the class
     */
    protected static $_instance = null;

    /**
     * @var array Loaded submodules
     */
    protected $submodules = [];

    /**
     * Main PilotWP Instance
     */
    public static function instance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * PilotWP Constructor.
     */
    public function __construct() {
        $this->define_constants();
        $this->includes();
        $this->init_hooks();
    }

    /**
     * Define constants
     */
    private function define_constants() {
        $this->define('PILOTWP_VERSION', '1.0.0');
        $this->define('PILOTWP_PLUGIN_DIR', plugin_dir_path(__FILE__));
        $this->define('PILOTWP_PLUGIN_URL', plugin_dir_url(__FILE__));
        $this->define('PILOTWP_SUBMODULES_DIR', PILOTWP_PLUGIN_DIR . 'submodules/');
        $this->define('PILOTWP_ACTIVE_SUBMODULES', 'pilotwp_active_submodules');
    }

    /**
     * Define constant if not already set
     */
    private function define($name, $value) {
        if (!defined($name)) {
            define($name, $value);
        }
    }

    /**
     * Include required files
     */
    private function includes() {
        // Core functions
        require_once PILOTWP_PLUGIN_DIR . 'includes/core-functions.php';
        
        // Admin
        if (is_admin()) {
            require_once PILOTWP_PLUGIN_DIR . 'includes/admin/admin.php';
        }
        
        // API
        require_once PILOTWP_PLUGIN_DIR . 'includes/api/api.php';
        
        // Submodule Manager
        require_once PILOTWP_PLUGIN_DIR . 'includes/submodule-manager.php';
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        register_activation_hook(__FILE__, [$this, 'activation']);
        register_deactivation_hook(__FILE__, [$this, 'deactivation']);
        
        add_action('plugins_loaded', [$this, 'load_submodules']);
        add_action('init', [$this, 'init'], 0);
    }

    /**
     * Plugin activation
     */
    public function activation() {
        // Create necessary directories if they don't exist
        if (!file_exists(PILOTWP_SUBMODULES_DIR)) {
            wp_mkdir_p(PILOTWP_SUBMODULES_DIR);
        }
        
        // Initialize default options
        if (!get_option(PILOTWP_ACTIVE_SUBMODULES)) {
            update_option(PILOTWP_ACTIVE_SUBMODULES, []);
        }
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Plugin deactivation
     */
    public function deactivation() {
        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Init PilotWP when WordPress initializes
     */
    public function init() {
        // Load text domain
        load_plugin_textdomain('pilotwp', false, dirname(plugin_basename(__FILE__)) . '/languages');
        
        // Fire init action
        do_action('pilotwp_init');
    }

    /**
     * Load active submodules
     */
    public function load_submodules() {
        $submodule_manager = new PilotWP_Submodule_Manager();
        $active_submodules = get_option(PILOTWP_ACTIVE_SUBMODULES, []);
        
        foreach ($active_submodules as $submodule) {
            $this->load_submodule($submodule);
        }
        
        do_action('pilotwp_submodules_loaded');
    }

    /**
     * Load a specific submodule
     */
    public function load_submodule($submodule_id) {
        $submodule_path = PILOTWP_SUBMODULES_DIR . $submodule_id . '/main.php';
        
        if (file_exists($submodule_path)) {
            require_once $submodule_path;
            $this->submodules[$submodule_id] = true;
            return true;
        }
        
        return false;
    }

    /**
     * Get loaded submodules
     */
    public function get_loaded_submodules() {
        return array_keys($this->submodules);
    }
}

// Initialize PilotWP
function PilotWP() {
    return PilotWP::instance();
}

// Global for backwards compatibility
$GLOBALS['pilotwp'] = PilotWP();
