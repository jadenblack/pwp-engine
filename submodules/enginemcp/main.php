<?php
/**
 * EngineMCP Submodule - main.php
 * 
 * Enhanced MCP server capabilities with integrated installation and management
 * for the top 15 MCP servers plus custom functionality.
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define constants
define('ENGINEMCP_VERSION', '2.0.0');
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
        // Core functionality
        require_once ENGINEMCP_DIR . 'includes/core-functions.php';
        require_once ENGINEMCP_DIR . 'includes/autoloader.php';
        require_once ENGINEMCP_DIR . 'includes/class-provider-base.php';
        require_once ENGINEMCP_DIR . 'includes/class-tool-base.php';
        require_once ENGINEMCP_DIR . 'includes/class-n8n-client.php';
        
        // NEW: MCP Installation Management
        require_once ENGINEMCP_DIR . 'includes/class-mcp-installer.php';
        
        // MCP API
        require_once ENGINEMCP_DIR . 'includes/api/class-mcp-api.php';
        require_once ENGINEMCP_DIR . 'includes/api/class-mcp-json-server.php';
        
        // Admin interface
        if (is_admin()) {
            require_once ENGINEMCP_DIR . 'includes/class-admin.php';
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
        
        // NEW: MCP Server Management hooks
        add_action('wp_ajax_enginemcp_bulk_install', [$this, 'ajax_bulk_install_servers']);
        add_action('wp_ajax_enginemcp_export_configs', [$this, 'ajax_export_configs']);
        
        // CLI support
        if (defined('WP_CLI') && WP_CLI) {
            add_action('cli_init', [$this, 'register_cli_commands']);
        }
    }

    /**
     * Register WP-CLI commands
     */
    public function register_cli_commands() {
        if (class_exists('WP_CLI')) {
            WP_CLI::add_command('enginemcp', 'EngineMCP_CLI_Commands');
        }
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
            ['jquery', 'jquery-ui-tabs'],
            ENGINEMCP_VERSION,
            true
        );
        
        wp_localize_script('enginemcp-admin', 'engineMCP', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('enginemcp-admin'),
            'installed_servers' => EngineMCP_Installer::get_installed_servers(),
            'available_servers' => array_keys(EngineMCP_Installer::get_server_info()),
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
     * AJAX: Bulk install MCP servers
     */
    public function ajax_bulk_install_servers() {
        check_ajax_referer('enginemcp-admin', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }

        $action = sanitize_text_field($_POST['bulk_action'] ?? '');
        $servers = array_map('sanitize_text_field', $_POST['servers'] ?? []);

        switch ($action) {
            case 'install_all':
                $results = EngineMCP_Installer::install_mcp_servers();
                wp_send_json_success([
                    'message' => 'Bulk installation completed',
                    'results' => $results
                ]);
                break;

            case 'install_essential':
                $essential = ['sequential-thinking', 'duckduckgo-search', 'github', 'memory-kg', 'playwright'];
                $results = EngineMCP_Installer::install_mcp_servers($essential);
                wp_send_json_success([
                    'message' => 'Essential servers installed',
                    'results' => $results
                ]);
                break;

            case 'generate_configs':
                $claude_config = EngineMCP_Installer::generate_claude_config();
                $cursor_config = EngineMCP_Installer::generate_cursor_config();
                
                wp_send_json_success([
                    'claude_config' => $claude_config,
                    'cursor_config' => $cursor_config
                ]);
                break;

            default:
                wp_send_json_error('Invalid bulk action');
        }
    }

    /**
     * AJAX: Export configuration files
     */
    public function ajax_export_configs() {
        check_ajax_referer('enginemcp-admin', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }

        $configs = [
            'claude' => EngineMCP_Installer::generate_claude_config(),
            'cursor' => EngineMCP_Installer::generate_cursor_config(),
            'api_keys_template' => EngineMCP_Installer::generate_api_keys_template()
        ];

        wp_send_json_success($configs);
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

    /**
     * Get installation status summary
     */
    public function get_installation_status() {
        $installed_servers = EngineMCP_Installer::get_installed_servers();
        $all_servers = EngineMCP_Installer::get_server_info();
        
        return [
            'total_available' => count($all_servers),
            'total_installed' => count($installed_servers),
            'installed_servers' => $installed_servers,
            'nodejs_available' => $this->check_nodejs(),
            'api_keys_configured' => $this->count_configured_api_keys()
        ];
    }

    /**
     * Check if Node.js is available
     */
    private function check_nodejs() {
        $node_check = shell_exec('node --version 2>&1');
        $npm_check = shell_exec('npm --version 2>&1');
        
        return !empty($node_check) && !empty($npm_check) && 
               strpos($node_check, 'not found') === false &&
               strpos($npm_check, 'not found') === false;
    }

    /**
     * Count configured API keys
     */
    private function count_configured_api_keys() {
        $api_keys = ['browserbaseapikey', 'githubtoken', 'deepseekapikey', 'exaapikey', 'apifytoken'];
        $configured = 0;
        
        foreach ($api_keys as $key) {
            if (get_option("enginemcp_{$key}", '')) {
                $configured++;
            }
        }
        
        return $configured;
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
 * Enhanced installation script with MCP server management
 */
function enginemcp_install() {
    // Create necessary directories
    $directories = [
        ENGINEMCP_VENDORS_DIR,
        ENGINEMCP_TOOLS_DIR,
        ENGINEMCP_CACHE_DIR,
        ENGINEMCP_DIR . 'logs/',
        ENGINEMCP_DIR . 'templates/'
    ];
    
    foreach ($directories as $dir) {
        if (!file_exists($dir)) {
            wp_mkdir_p($dir);
        }
    }
    
    // Set default options
    update_option('enginemcp_version', ENGINEMCP_VERSION);
    update_option('enginemcp_enable_frontend', false);
    update_option('enginemcp_enable_auth', true);
    update_option('enginemcp_api_key', enginemcp_generate_api_key());
    
    // Provider settings
    update_option('enginemcp_wp_core_enabled', true);
    update_option('enginemcp_automattic_enabled', true);
    update_option('enginemcp_emzimmer_enabled', false);
    update_option('enginemcp_n8n_enabled', false);
    
    // MCP Server installation tracking
    update_option('enginemcp_mcp_servers_last_check', time());
    update_option('enginemcp_auto_install_essentials', false);
    
    // Install Automattic's MCP plugin if it doesn't exist
    if (!class_exists('WordPress_MCP')) {
        enginemcp_install_automattic_mcp();
    }
    
    // Install n8n client libraries
    enginemcp_install_n8n_client();
    
    // Schedule daily check for vendor updates
    if (!wp_next_scheduled('enginemcp_check_vendor_updates')) {
        wp_schedule_event(time(), 'daily', 'enginemcp_check_vendor_updates');
    }
    
    // Schedule weekly MCP server health check
    if (!wp_next_scheduled('enginemcp_health_check')) {
        wp_schedule_event(time(), 'weekly', 'enginemcp_health_check');
    }
    
    // Create initial installation notice
    update_option('enginemcp_show_welcome_notice', true);
}

/**
 * Activation script
 */
function enginemcp_activate() {
    // Flush rewrite rules for REST API
    flush_rewrite_rules();
    
    // Check Node.js availability and show notice if missing
    $nodejs_available = shell_exec('node --version 2>&1') && shell_exec('npm --version 2>&1');
    if (!$nodejs_available) {
        update_option('enginemcp_nodejs_warning', true);
    }
}

/**
 * Deactivation script
 */
function enginemcp_deactivate() {
    // Clear scheduled events
    wp_clear_scheduled_hook('enginemcp_check_vendor_updates');
    wp_clear_scheduled_hook('enginemcp_health_check');
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
                    if (!file_exists($target_dir . '/' . $file_name)) {
                        rename($file, $target_dir . '/' . $file_name);
                    }
                }
                
                // Remove the extracted directory
                array_map('unlink', glob($extracted_dir . '/*'));
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
        ],
        'config' => [
            'vendor-dir' => 'vendor'
        ]
    ];
    
    $n8n_client_dir = ENGINEMCP_VENDORS_DIR . 'n8n-client';
    
    if (!file_exists($n8n_client_dir)) {
        wp_mkdir_p($n8n_client_dir);
    }
    
    file_put_contents($n8n_client_dir . '/composer.json', json_encode($composer_json, JSON_PRETTY_PRINT));
    
    // Try to run composer if available
    $composer_phar = $n8n_client_dir . '/composer.phar';
    
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
        if (chdir($n8n_client_dir)) {
            @exec('php composer.phar install --no-dev --optimize-autoloader 2>&1', $output, $return_var);
            chdir($current_dir);
        }
    }
}

/**
 * Generate a random API key
 */
function enginemcp_generate_api_key() {
    return wp_generate_password(32, false, false);
}

/**
 * Health check for MCP servers
 */
function enginemcp_health_check() {
    $installed_servers = EngineMCP_Installer::get_installed_servers();
    $health_report = [];
    
    foreach ($installed_servers as $server_key) {
        $is_working = EngineMCP_Installer::test_mcp_server($server_key);
        $health_report[$server_key] = [
            'status' => $is_working ? 'healthy' : 'error',
            'checked_at' => time()
        ];
    }
    
    update_option('enginemcp_health_report', $health_report);
    
    // Send notification if any servers are down
    $failed_servers = array_filter($health_report, function($report) {
        return $report['status'] === 'error';
    });
    
    if (!empty($failed_servers)) {
        $admin_email = get_option('admin_email');
        $subject = 'EngineMCP Health Check: Server Issues Detected';
        $message = 'The following MCP servers have issues:\n\n';
        
        foreach ($failed_servers as $server => $report) {
            $message .= "- {$server}\n";
        }
        
        $message .= "\nPlease check your EngineMCP configuration.";
        
        wp_mail($admin_email, $subject, $message);
    }
}

// Register scheduled events
add_action('enginemcp_health_check', 'enginemcp_health_check');

/**
 * Add admin notices
 */
add_action('admin_notices', function() {
    // Welcome notice
    if (get_option('enginemcp_show_welcome_notice')) {
        echo '<div class="notice notice-success is-dismissible">';
        echo '<p><strong>EngineMCP Installed!</strong> ';
        echo 'Visit <a href="' . admin_url('admin.php?page=enginemcp') . '">PilotWP > EngineMCP</a> to install MCP servers and configure your AI workflow.';
        echo '</p></div>';
        delete_option('enginemcp_show_welcome_notice');
    }
    
    // Node.js warning
    if (get_option('enginemcp_nodejs_warning')) {
        echo '<div class="notice notice-warning is-dismissible">';
        echo '<p><strong>Node.js Required:</strong> ';
        echo 'EngineMCP requires Node.js and npm to install MCP servers. ';
        echo '<a href="https://nodejs.org/" target="_blank">Download Node.js</a>';
        echo '</p></div>';
    }
});

/**
 * Add action links on plugins page
 */
add_filter('plugin_action_links_' . plugin_basename(PILOTWP_PLUGIN_FILE), function($links) {
    $enginemcp_link = '<a href="' . admin_url('admin.php?page=enginemcp') . '">EngineMCP</a>';
    array_unshift($links, $enginemcp_link);
    return $links;
});
