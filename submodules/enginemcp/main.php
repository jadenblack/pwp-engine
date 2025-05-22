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