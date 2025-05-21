<?php
/**
 * EngineMCP Submodule - main.php
 * 
 * Provides MCP server capabilities by combining Automattic's wordpress-mcp, 
 * emzimmer/server-wp-mcp with custom functionality for n8n integration.
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define constants
define('ENGINEMCP_VERSION', '1.0.0');
define('ENGINEMCP_DIR', PILOTWP_SUBMODULES_DIR . 'enginemcp/');
define('ENGINEMCP_URL', PILOTWP_PLUGIN_URL . 'submodules/enginemcp/');
define('ENGINEMCP_ASSETS_URL', ENGINEMCP_URL . 'assets/');
define('ENGINEMCP_VENDORS_DIR', ENGINEMCP_DIR . 'vendors/');
define('ENGINEMCP_TOOLS_DIR', ENGINEMCP_DIR . 'tools/');
define('ENGINEMCP_CACHE_DIR', ENGINEMCP_DIR . 'cache/');

/**
 * Main EngineMCP Class
 */
class EngineMCP {

    /**
     * @var EngineMCP The single instance of the class
     */
    protected static $_instance = null;

    /**
     * @var array Loaded MCP providers
     */
    protected $providers = [];

    /**
     * @var array Registered MCP tools
     */
    protected $tools = [];

    /**
     * Main EngineMCP Instance
     */
    public static function instance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * EngineMCP Constructor.
     */
    public function __construct() {
        $this->includes();
        $this->init_providers();
        $this->init_hooks();
    }

    /**
     * Include required files
     */
    private function includes() {
        // Core
        require_once ENGINEMCP_DIR . 'includes/core-functions.php';
        require_once ENGINEMCP_DIR . 'includes/autoloader.php';
        require_once ENGINEMCP_DIR . 'includes/class-provider-base.php';
        require_once ENGINEMCP_DIR . 'includes/class-tool-base.php';
        require_once ENGINEMCP_DIR . 'includes/class-n8n-client.php';
        
        // MCP API
        require_once ENGINEMCP_DIR . 'includes/api/class-mcp-api.php';
        require_once ENGINEMCP_DIR . 'includes/api/class-mcp-json-server.php';
        
        // Admin
        if (is_admin()) {
            require_once ENGINEMCP_DIR . 'includes/admin/admin.php';
            require_once ENGINEMCP_DIR . 'includes/admin/settings.php';
        }
        
        // Providers
        require_once ENGINEMCP_DIR . 'includes/providers/class-wp-core-provider.php';
        require_once ENGINEMCP_DIR . 'includes/providers/class-automattic-provider.php';
        require_once ENGINEMCP_DIR . 'includes/providers/class-emzimmer-provider.php';
        require_once ENGINEMCP_DIR . 'includes/providers/class-n8n-provider.php';
        
        // Tools
        $this->load_tools();
    }

    /**
     * Load all available tools
     */
    private function load_tools() {
        if (!is_dir(ENGINEMCP_TOOLS_DIR)) {
            return;
        }

        $tool_directories = glob(ENGINEMCP_TOOLS_DIR . '*', GLOB_ONLYDIR);
        
        foreach ($tool_directories as $dir) {
            $tool_file = $dir . '/tool.php';
            if (file_exists($tool_file)) {
                require_once $tool_file;
            }
        }

        // Apply filter to allow other plugins to register tools
        $this->tools = apply_filters('enginemcp_tools', $this->tools);
    }

    /**
     * Initialize MCP providers
     */
    private function init_providers() {
        // Initialize the providers in this specific order
        $this->providers['wp_core'] = new EngineMCP_WP_Core_Provider();
        $this->providers['automattic'] = new EngineMCP_Automattic_Provider();
        $this->providers['emzimmer'] = new EngineMCP_Emzimmer_Provider();
        $this->providers['n8n'] = new EngineMCP_N8n_Provider();
        
        // Apply filter to allow other plugins to add providers
        $this->providers = apply_filters('enginemcp_providers', $this->providers);
    }

    /**
     * Register hooks
     */
    private function init_hooks() {
        add_action('rest_api_init', [$this, 'register_rest_routes']);
        add_action('admin_enqueue_scripts', [$this, 'register_admin_assets']);
        add_action('wp_enqueue_scripts', [$this, 'register_frontend_assets']);
        add_action('init', [$this, 'register_tools'], 20);
    }

    /**
     * Register REST API routes
     */
    public function register_rest_routes() {
        $api = new EngineMCP_API();
        $api->register_routes();
    }

    /**
     * Register admin assets
     */
    public function register_admin_assets() {
        wp_register_style(
            'enginemcp-admin',
            ENGINEMCP_ASSETS_URL . 'css/admin.css',
            [],
            ENGINEMCP_VERSION
        );
        
        wp_register_script(
            'enginemcp-admin',
            ENGINEMCP_ASSETS_URL . 'js/admin.js',
            ['jquery'],
            ENGINEMCP_VERSION,
            true
        );
        
        wp_localize_script('enginemcp-admin', 'engineMCP', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('enginemcp-admin'),
        ]);
    }

    /**
     * Register frontend assets
     */
    public function register_frontend_assets() {
        // Only register if frontend MCP access is enabled
        if (!get_option('enginemcp_enable_frontend', false)) {
            return;
        }
        
        wp_register_script(
            'enginemcp-frontend',
            ENGINEMCP_ASSETS_URL . 'js/frontend.js',
            ['jquery'],
            ENGINEMCP_VERSION,
            true
        );
        
        wp_localize_script('enginemcp-frontend', 'engineMCP', [
            'rest_url' => esc_url_raw(rest_url('enginemcp/v1')),
            'nonce' => wp_create_nonce('wp_rest'),
        ]);
    }

    /**
     * Register MCP tools from all providers
     */
    public function register_tools() {
        foreach ($this->providers as $provider) {
            $provider->register_tools();
        }
        
        do_action('enginemcp_tools_registered');
    }

    /**
     * Get registered providers
     */
    public function get_providers() {
        return $this->providers;
    }

    /**
     * Get a specific provider
     */
    public function get_provider($provider_id) {
        return isset($this->providers[$provider_id]) ? $this->providers[$provider_id] : null;
    }

    /**
     * Register a MCP tool
     */
    public function register_tool($tool_id, $tool_class) {
        $this->tools[$tool_id] = $tool_class;
    }

    /**
     * Get registered tools
     */
    public function get_tools() {
        return $this->tools;
    }

    /**
     * Get a specific tool
     */
    public function get_tool($tool_id) {
        return isset($this->tools[$tool_id]) ? $this->tools[$tool_id] : null;
    }
}

/**
 * Main EngineMCP Instance
 */
function EngineMCP() {
    return EngineMCP::instance();
}

// Initialize
EngineMCP();

/**
 * Installation script
 */
function enginemcp_install() {
    // Create necessary directories
    $directories = [
        ENGINEMCP_VENDORS_DIR,
        ENGINEMCP_TOOLS_DIR,
        ENGINEMCP_CACHE_DIR
    ];
    
    foreach ($directories as $dir) {
        if (!file_exists($dir)) {
            wp_mkdir_p($dir);
        }
    }
    
    // Install Automattic's MCP plugin if it doesn't exist
    if (!class_exists('WordPress_MCP')) {
        enginemcp_install_automattic_mcp();
    }
    
    // Install n8n client libraries
    enginemcp_install_n8n_client();
    
    // Set default options
    update_option('enginemcp_version', ENGINEMCP_VERSION);
    update_option('enginemcp_enable_frontend', false);
    update_option('enginemcp_enable_auth', true);
    update_option('enginemcp_api_key', enginemcp_generate_api_key());
    
    // Schedule daily check for vendor updates
    if (!wp_next_scheduled('enginemcp_check_vendor_updates')) {
        wp_schedule_event(time(), 'daily', 'enginemcp_check_vendor_updates');
    }
}

/**
 * Activation script
 */
function enginemcp_activate() {
    // Flush rewrite rules for REST API
    flush_rewrite_rules();
}

/**
 * Deactivation script
 */
function enginemcp_deactivate() {
    // Clear scheduled events
    wp_clear_scheduled_hook('enginemcp_check_vendor_updates');
}

/**
 * Install Automattic's wordpress-mcp
 */
function enginemcp_install_automattic_mcp() {
    $github_repo = 'Automattic/wordpress-mcp';
    $download_url = "https://github.com/{$github_repo}/archive/refs/heads/main.zip";
    $target_dir = ENGINEMCP_VENDORS_DIR . 'automattic-mcp';
    
    // Create target directory
    if (!file_exists($target_dir)) {
        wp_mkdir_p($target_dir);
    }
    
    // Download and extract
    $temp_file = download_url($download_url);
    
    if (!is_wp_error($temp_file)) {
        $result = unzip_file($temp_file, $target_dir);
        @unlink($temp_file); // Delete the temp file
        
        if (!is_wp_error($result)) {
            // Move files from extracted directory
            $extracted_dir = glob($target_dir . '/*', GLOB_ONLYDIR);
            
            if (!empty($extracted_dir)) {
                $extracted_dir = $extracted_dir[0]; // Get the first directory
                $files = glob($extracted_dir . '/*');
                
                foreach ($files as $file) {
                    $file_name = basename($file);
                    rename($file, $target_dir . '/' . $file_name);
                }
                
                // Remove the extracted directory
                rmdir($extracted_dir);
            }
        }
    }
}

/**
 * Install n8n client libraries
 */
function enginemcp_install_n8n_client() {
    // Create composer.json
    $composer_json = [
        'require' => [
            'guzzlehttp/guzzle' => '^7.0'
        ]
    ];
    
    $n8n_client_dir = ENGINEMCP_VENDORS_DIR . 'n8n-client';
    
    if (!file_exists($n8n_client_dir)) {
        wp_mkdir_p($n8n_client_dir);
    }
    
    file_put_contents($n8n_client_dir . '/composer.json', json_encode($composer_json));
    
    // Try to run composer if available
    $composer_command = 'composer install --no-dev --optimize-autoloader';
    $current_dir = getcwd();
    
    if (chdir($n8n_client_dir)) {
        @exec($composer_command);
        chdir($current_dir);
    }
}

/**
 * Generate a random API key
 */
function enginemcp_generate_api_key() {
    return wp_generate_password(32, false, false);
}
