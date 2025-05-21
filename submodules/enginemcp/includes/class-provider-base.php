<?php
/**
 * EngineMCP Provider Base Class
 * 
 * Base class for all MCP providers
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Base class for MCP Providers
 */
abstract class EngineMCP_Provider_Base {

    /**
     * Provider ID
     */
    protected $id = '';
    
    /**
     * Provider name
     */
    protected $name = '';
    
    /**
     * Provider description
     */
    protected $description = '';
    
    /**
     * Provider version
     */
    protected $version = '';
    
    /**
     * Provider source URL (GitHub repo, etc.)
     */
    protected $source_url = '';
    
    /**
     * Provider settings
     */
    protected $settings = [];
    
    /**
     * Provider tools
     */
    protected $tools = [];
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->init();
        add_action('admin_init', [$this, 'register_settings']);
    }
    
    /**
     * Initialize the provider
     */
    abstract public function init();
    
    /**
     * Register provider settings
     */
    public function register_settings() {
        // Base implementation does nothing
    }
    
    /**
     * Register provider tools
     */
    abstract public function register_tools();
    
    /**
     * Get provider ID
     */
    public function get_id() {
        return $this->id;
    }
    
    /**
     * Get provider name
     */
    public function get_name() {
        return $this->name;
    }
    
    /**
     * Get provider description
     */
    public function get_description() {
        return $this->description;
    }
    
    /**
     * Get provider version
     */
    public function get_version() {
        return $this->version;
    }
    
    /**
     * Get provider source URL
     */
    public function get_source_url() {
        return $this->source_url;
    }
    
    /**
     * Get provider settings
     */
    public function get_settings() {
        return $this->settings;
    }
    
    /**
     * Get provider tools
     */
    public function get_tools() {
        return $this->tools;
    }
    
    /**
     * Register a tool
     */
    public function register_tool($tool_id, $tool_class) {
        $this->tools[$tool_id] = $tool_class;
        EngineMCP()->register_tool($tool_id, $tool_class);
        return $this;
    }
    
    /**
     * Check if provider is enabled
     */
    public function is_enabled() {
        return get_option("enginemcp_{$this->id}_enabled", true);
    }
    
    /**
     * Enable provider
     */
    public function enable() {
        update_option("enginemcp_{$this->id}_enabled", true);
        return $this;
    }
    
    /**
     * Disable provider
     */
    public function disable() {
        update_option("enginemcp_{$this->id}_enabled", false);
        return $this;
    }
    
    /**
     * Get a setting value
     */
    public function get_setting($key, $default = null) {
        $option_name = "enginemcp_{$this->id}_{$key}";
        return get_option($option_name, $default);
    }
    
    /**
     * Update a setting value
     */
    public function update_setting($key, $value) {
        $option_name = "enginemcp_{$this->id}_{$key}";
        update_option($option_name, $value);
        return $this;
    }
    
    /**
     * Test provider connection/functionality
     */
    public function test_connection() {
        // Default implementation returns true
        return true;
    }
    
    /**
     * Get provider status
     */
    public function get_status() {
        return [
            'enabled' => $this->is_enabled(),
            'tools_count' => count($this->tools),
            'connection' => $this->test_connection(),
        ];
    }
}
