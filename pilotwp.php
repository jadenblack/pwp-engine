<?php
/**
 * Plugin Name: PilotWP Engine
 * Plugin URI: https://github.com/jadenblack/pwp-engine
 * Description: Enterprise-grade WordPress enhancement platform with submodule support for forms, security, MCP servers, and n8n integration
 * Version: 2.0.0
 * Author: Jaden Black
 * Author URI: https://jadenblack.com
 * License: GPL-3.0+
 * License URI: http://www.gnu.org/licenses/gpl-3.0.txt
 * Text Domain: pilotwp
 * Domain Path: /languages
 * Requires at least: 5.9
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * Network: false
 * GitHub Plugin URI: jadenblack/pwp-engine
 * GitHub Branch: main
 * Primary Branch: main
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Plugin constants
define('PILOTWP_VERSION', '2.0.0');
define('PILOTWP_PLUGIN_FILE', __FILE__);
define('PILOTWP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('PILOTWP_PLUGIN_URL', plugin_dir_url(__FILE__));
define('PILOTWP_SUBMODULES_DIR', PILOTWP_PLUGIN_DIR . 'submodules/');
define('PILOTWP_ASSETS_URL', PILOTWP_PLUGIN_URL . 'assets/');

/**
 * Main PilotWP Class
 */
class PilotWP {
    
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
     * PilotWP Constructor
     */
    public function __construct() {
        $this->includes();
        $this->init_hooks();
        $this->load_submodules();
    }
    
    /**
     * Include required files
     */
    private function includes() {
        require_once PILOTWP_PLUGIN_DIR . 'includes/core-functions.php';
        require_once PILOTWP_PLUGIN_DIR . 'includes/submodule-manager.php';
        
        if (is_admin()) {
            require_once PILOTWP_PLUGIN_DIR . 'includes/admin/admin.php';
            require_once PILOTWP_PLUGIN_DIR . 'includes/admin/plugin-manager.php';
        }
        
        require_once PILOTWP_PLUGIN_DIR . 'includes/api/api.php';
    }
    
    /**
     * Hook into actions and filters
     */
    private function init_hooks() {
        add_action('init', [$this, 'init'], 0);
        add_action('admin_menu', [$this, 'admin_menu']);
        add_action('admin_enqueue_scripts', [$this, 'admin_assets']);
        
        // GitHub Updater support
        add_filter('github_updater_token', [$this, 'github_updater_token']);
        
        // Plugin management hooks
        add_action('wp_ajax_pilotwp_install_plugin', [$this, 'ajax_install_plugin']);
        add_action('wp_ajax_pilotwp_update_plugin', [$this, 'ajax_update_plugin']);
        
        // CLI support
        if (defined('WP_CLI') && WP_CLI) {
            add_action('cli_init', [$this, 'register_cli_commands']);
        }
    }
    
    /**
     * Initialize the plugin
     */
    public function init() {
        // Load text domain
        load_plugin_textdomain('pilotwp', false, dirname(plugin_basename(__FILE__)) . '/languages');
        
        // Initialize submodules
        do_action('pilotwp_init');
    }
    
    /**
     * Load submodules
     */
    private function load_submodules() {
        $submodule_manager = new PilotWP_Submodule_Manager();
        $this->submodules = $submodule_manager->load_submodules();
    }
    
    /**
     * Add admin menu
     */
    public function admin_menu() {
        add_menu_page(
            'PilotWP',
            'PilotWP',
            'manage_options',
            'pilotwp',
            [$this, 'admin_page'],
            'dashicons-airplane',
            30
        );
        
        add_submenu_page(
            'pilotwp',
            'Plugin Manager',
            'Plugin Manager',
            'manage_options',
            'pilotwp-plugins',
            [$this, 'plugin_manager_page']
        );
    }
    
    /**
     * Main admin page
     */
    public function admin_page() {
        require_once PILOTWP_PLUGIN_DIR . 'includes/admin/views/dashboard.php';
    }
    
    /**
     * Plugin manager page
     */
    public function plugin_manager_page() {
        require_once PILOTWP_PLUGIN_DIR . 'includes/admin/views/plugin-manager.php';
    }
    
    /**
     * Enqueue admin assets
     */
    public function admin_assets($hook) {
        if (strpos($hook, 'pilotwp') === false) {
            return;
        }
        
        wp_enqueue_style('pilotwp-admin', PILOTWP_ASSETS_URL . 'css/admin.css', [], PILOTWP_VERSION);
        wp_enqueue_script('pilotwp-admin', PILOTWP_ASSETS_URL . 'js/admin.js', ['jquery'], PILOTWP_VERSION, true);
        
        wp_localize_script('pilotwp-admin', 'pilotWP', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('pilotwp_nonce'),
            'version' => PILOTWP_VERSION,
        ]);
    }
    
    /**
     * GitHub Updater token filter
     */
    public function github_updater_token($token) {
        $github_token = get_option('pilotwp_github_token');
        return $github_token ? $github_token : $token;
    }
    
    /**
     * AJAX: Install plugin
     */
    public function ajax_install_plugin() {
        check_ajax_referer('pilotwp_nonce', 'nonce');
        
        if (!current_user_can('install_plugins')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $plugin_data = [
            'source' => sanitize_text_field($_POST['source']),
            'slug' => sanitize_text_field($_POST['slug']),
            'url' => esc_url_raw($_POST['url']),
            'activate' => isset($_POST['activate'])
        ];
        
        $plugin_manager = new PilotWP_Plugin_Manager();
        $result = $plugin_manager->install_plugin($plugin_data);
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result['message']);
        }
    }
    
    /**
     * AJAX: Update plugin
     */
    public function ajax_update_plugin() {
        check_ajax_referer('pilotwp_nonce', 'nonce');
        
        if (!current_user_can('update_plugins')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $plugin_file = sanitize_text_field($_POST['plugin']);
        
        $plugin_manager = new PilotWP_Plugin_Manager();
        $result = $plugin_manager->update_plugin($plugin_file);
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result['message']);
        }
    }
    
    /**
     * Register WP-CLI commands
     */
    public function register_cli_commands() {
        if (class_exists('WP_CLI')) {
            WP_CLI::add_command('pilotwp', 'PilotWP_CLI_Commands');
        }
    }
    
    /**
     * Get loaded submodules
     */
    public function get_submodules() {
        return $this->submodules;
    }
}

/**
 * Main instance of PilotWP
 */
function PilotWP() {
    return PilotWP::instance();
}

// Initialize
PilotWP();

/**
 * Activation hook
 */
register_activation_hook(__FILE__, 'pilotwp_activate');
function pilotwp_activate() {
    // Create necessary database tables
    pilotwp_create_tables();
    
    // Set default options
    update_option('pilotwp_version', PILOTWP_VERSION);
    update_option('pilotwp_activated_time', time());
    
    // Schedule events
    if (!wp_next_scheduled('pilotwp_daily_check')) {
        wp_schedule_event(time(), 'daily', 'pilotwp_daily_check');
    }
    
    // Flush rewrite rules
    flush_rewrite_rules();
}

/**
 * Deactivation hook
 */
register_deactivation_hook(__FILE__, 'pilotwp_deactivate');
function pilotwp_deactivate() {
    // Clear scheduled events
    wp_clear_scheduled_hook('pilotwp_daily_check');
}

/**
 * Create database tables
 */
function pilotwp_create_tables() {
    global $wpdb;
    
    $charset_collate = $wpdb->get_charset_collate();
    
    // Submodules table
    $table_name = $wpdb->prefix . 'pilotwp_submodules';
    
    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        name varchar(100) NOT NULL,
        slug varchar(100) NOT NULL,
        version varchar(20) DEFAULT '',
        status varchar(20) DEFAULT 'inactive',
        settings longtext,
        installed_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY slug (slug)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
    
    // Plugin management table
    $table_name = $wpdb->prefix . 'pilotwp_plugins';
    
    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        plugin_file varchar(255) NOT NULL,
        source varchar(50) NOT NULL,
        repository varchar(255) DEFAULT '',
        version varchar(20) DEFAULT '',
        auto_update tinyint(1) DEFAULT 0,
        last_checked datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY plugin_file (plugin_file)
    ) $charset_collate;";
    
    dbDelta($sql);
}
