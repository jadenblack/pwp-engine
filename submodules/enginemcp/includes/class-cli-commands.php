<?php
/**
 * EngineMCP WP-CLI Commands
 * Command-line interface for MCP server management
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class EngineMCP_CLI_Commands {

    /**
     * Install MCP servers
     *
     * ## OPTIONS
     *
     * [--servers=<servers>]
     * : Comma-separated list of server keys to install. Defaults to all.
     *
     * [--essential]
     * : Install only essential servers (sequential-thinking, duckduckgo-search, github, memory-kg, playwright)
     *
     * [--force]
     * : Force reinstallation of already installed servers
     *
     * ## EXAMPLES
     *
     *     wp enginemcp install
     *     wp enginemcp install --essential
     *     wp enginemcp install --servers=sequential-thinking,github,duckduckgo-search
     *
     * @when after_wp_load
     */
    public function install($args, $assoc_args) {
        WP_CLI::log('Starting MCP servers installation...');

        // Check Node.js availability
        if (!$this->check_nodejs()) {
            WP_CLI::error('Node.js and npm are required but not found. Please install Node.js from https://nodejs.org/');
        }

        // Determine which servers to install
        $servers_to_install = null;

        if (isset($assoc_args['essential'])) {
            $servers_to_install = ['sequential-thinking', 'duckduckgo-search', 'github', 'memory-kg', 'playwright'];
            WP_CLI::log('Installing essential MCP servers...');
        } elseif (isset($assoc_args['servers'])) {
            $servers_to_install = array_map('trim', explode(',', $assoc_args['servers']));
            WP_CLI::log('Installing selected MCP servers: ' . implode(', ', $servers_to_install));
        } else {
            WP_CLI::log('Installing all available MCP servers...');
        }

        // Install servers
        $results = EngineMCP_Installer::install_mcp_servers($servers_to_install);

        // Display results
        if (!empty($results['success'])) {
            WP_CLI::success('Successfully installed: ' . implode(', ', $results['success']));
        }

        if (!empty($results['failed'])) {
            WP_CLI::warning('Failed to install: ' . implode(', ', $results['failed']));
        }

        if (!empty($results['skipped'])) {
            WP_CLI::log('Skipped: ' . implode(', ', $results['skipped']));
        }

        WP_CLI::success('Installation completed!');
    }

    /**
     * List available and installed MCP servers
     *
     * ## OPTIONS
     *
     * [--installed]
     * : Show only installed servers
     *
     * [--available]
     * : Show only available (not installed) servers
     *
     * [--format=<format>]
     * : Render output in a particular format.
     * ---
     * default: table
     * options:
     *   - table
     *   - csv
     *   - json
     *   - yaml
     * ---
     *
     * ## EXAMPLES
     *
     *     wp enginemcp list
     *     wp enginemcp list --installed
     *     wp enginemcp list --format=json
     *
     * @when after_wp_load
     */
    public function list($args, $assoc_args) {
        $all_servers = EngineMCP_Installer::get_server_info();
        $installed_servers = EngineMCP_Installer::get_installed_servers();

        $output = [];

        foreach ($all_servers as $key => $server) {
            $is_installed = in_array($key, $installed_servers);

            // Filter based on flags
            if (isset($assoc_args['installed']) && !$is_installed) {
                continue;
            }
            if (isset($assoc_args['available']) && $is_installed) {
                continue;
            }

            $output[] = [
                'key' => $key,
                'name' => $server['name'],
                'description' => $server['description'],
                'status' => $is_installed ? 'Installed' : 'Available',
                'requires_api_key' => $server['requires_api_key'] ? 'Yes' : 'No',
                'priority' => $server['priority']
            ];
        }

        $format = $assoc_args['format'] ?? 'table';
        WP_CLI\Utils\format_items($format, $output, ['key', 'name', 'status', 'requires_api_key', 'priority']);
    }

    /**
     * Test MCP server installations
     *
     * ## OPTIONS
     *
     * [<server>]
     * : Test a specific server. If not provided, tests all installed servers.
     *
     * ## EXAMPLES
     *
     *     wp enginemcp test
     *     wp enginemcp test sequential-thinking
     *
     * @when after_wp_load
     */
    public function test($args, $assoc_args) {
        $server_to_test = $args[0] ?? null;

        if ($server_to_test) {
            $servers = [$server_to_test];
        } else {
            $servers = EngineMCP_Installer::get_installed_servers();
        }

        if (empty($servers)) {
            WP_CLI::warning('No MCP servers to test.');
            return;
        }

        WP_CLI::log('Testing MCP servers...');

        $results = [];
        foreach ($servers as $server_key) {
            $is_working = EngineMCP_Installer::test_mcp_server($server_key);
            $server_info = EngineMCP_Installer::get_server_info($server_key);
            
            $results[] = [
                'server' => $server_info['name'] ?? $server_key,
                'status' => $is_working ? 'Working' : 'Failed',
                'key' => $server_key
            ];

            if ($is_working) {
                WP_CLI::log("✓ {$server_key}: Working");
            } else {
                WP_CLI::warning("✗ {$server_key}: Failed");
            }
        }

        $working_count = count(array_filter($results, function($r) { return $r['status'] === 'Working'; }));
        $total_count = count($results);

        WP_CLI::success("Test completed: {$working_count}/{$total_count} servers working");
    }

    /**
     * Generate configuration files
     *
     * ## OPTIONS
     *
     * [--type=<type>]
     * : Configuration type to generate
     * ---
     * default: both
     * options:
     *   - claude
     *   - cursor
     *   - both
     * ---
     *
     * [--output=<path>]
     * : Output directory for configuration files
     *
     * ## EXAMPLES
     *
     *     wp enginemcp config
     *     wp enginemcp config --type=claude --output=/tmp
     *
     * @when after_wp_load
     */
    public function config($args, $assoc_args) {
        $type = $assoc_args['type'] ?? 'both';
        $output_dir = $assoc_args['output'] ?? getcwd();

        if (!is_dir($output_dir)) {
            WP_CLI::error("Output directory does not exist: {$output_dir}");
        }

        if (!is_writable($output_dir)) {
            WP_CLI::error("Output directory is not writable: {$output_dir}");
        }

        WP_CLI::log('Generating configuration files...');

        if ($type === 'claude' || $type === 'both') {
            $claude_config = EngineMCP_Installer::generate_claude_config();
            $claude_file = $output_dir . '/claude_desktop_config.json';
            
            if (file_put_contents($claude_file, $claude_config)) {
                WP_CLI::success("Claude Desktop configuration saved to: {$claude_file}");
            } else {
                WP_CLI::error("Failed to save Claude Desktop configuration");
            }
        }

        if ($type === 'cursor' || $type === 'both') {
            $cursor_config = EngineMCP_Installer::generate_cursor_config();
            $cursor_file = $output_dir . '/mcp.json';
            
            if (file_put_contents($cursor_file, $cursor_config)) {
                WP_CLI::success("Cursor configuration saved to: {$cursor_file}");
            } else {
                WP_CLI::error("Failed to save Cursor configuration");
            }
        }

        // Generate API keys template
        $api_template = EngineMCP_Installer::generate_api_keys_template();
        $api_file = $output_dir . '/api_keys_setup.txt';
        
        if (file_put_contents($api_file, $api_template)) {
            WP_CLI::log("API keys setup guide saved to: {$api_file}");
        }

        WP_CLI::success('Configuration generation completed!');
    }

    /**
     * Manage API keys
     *
     * ## OPTIONS
     *
     * <action>
     * : Action to perform
     * ---
     * options:
     *   - list
     *   - set
     *   - get
     *   - delete
     * ---
     *
     * [<key>]
     * : API key name (required for set, get, delete actions)
     *
     * [<value>]
     * : API key value (required for set action)
     *
     * ## EXAMPLES
     *
     *     wp enginemcp apikey list
     *     wp enginemcp apikey set GITHUB_TOKEN ghp_1234567890
     *     wp enginemcp apikey get GITHUB_TOKEN
     *     wp enginemcp apikey delete GITHUB_TOKEN
     *
     * @when after_wp_load
     */
    public function apikey($args, $assoc_args) {
        $action = $args[0] ?? '';
        $key = $args[1] ?? '';
        $value = $args[2] ?? '';

        $valid_keys = ['BROWSERBASE_API_KEY', 'GITHUB_TOKEN', 'DEEPSEEK_API_KEY', 'EXA_API_KEY', 'APIFY_TOKEN'];

        switch ($action) {
            case 'list':
                $output = [];
                foreach ($valid_keys as $api_key) {
                    $option_name = 'enginemcp_' . strtolower(str_replace('_', '', $api_key));
                    $stored_value = get_option($option_name, '');
                    
                    $output[] = [
                        'key' => $api_key,
                        'status' => $stored_value ? 'Configured' : 'Not Set',
                        'length' => $stored_value ? strlen($stored_value) : 0
                    ];
                }
                
                WP_CLI\Utils\format_items('table', $output, ['key', 'status', 'length']);
                break;

            case 'set':
                if (empty($key) || empty($value)) {
                    WP_CLI::error('Key and value are required for set action');
                }
                
                if (!in_array($key, $valid_keys)) {
                    WP_CLI::error('Invalid API key. Valid keys: ' . implode(', ', $valid_keys));
                }
                
                $option_name = 'enginemcp_' . strtolower(str_replace('_', '', $key));
                update_option($option_name, $value);
                WP_CLI::success("API key {$key} has been set");
                break;

            case 'get':
                if (empty($key)) {
                    WP_CLI::error('Key is required for get action');
                }
                
                if (!in_array($key, $valid_keys)) {
                    WP_CLI::error('Invalid API key. Valid keys: ' . implode(', ', $valid_keys));
                }
                
                $option_name = 'enginemcp_' . strtolower(str_replace('_', '', $key));
                $stored_value = get_option($option_name, '');
                
                if ($stored_value) {
                    WP_CLI::log("{$key}: {$stored_value}");
                } else {
                    WP_CLI::warning("{$key} is not configured");
                }
                break;

            case 'delete':
                if (empty($key)) {
                    WP_CLI::error('Key is required for delete action');
                }
                
                if (!in_array($key, $valid_keys)) {
                    WP_CLI::error('Invalid API key. Valid keys: ' . implode(', ', $valid_keys));
                }
                
                $option_name = 'enginemcp_' . strtolower(str_replace('_', '', $key));
                delete_option($option_name);
                WP_CLI::success("API key {$key} has been deleted");
                break;

            default:
                WP_CLI::error('Invalid action. Valid actions: list, set, get, delete');
        }
    }

    /**
     * Show system status and health information
     *
     * ## EXAMPLES
     *
     *     wp enginemcp status
     *
     * @when after_wp_load
     */
    public function status($args, $assoc_args) {
        WP_CLI::log('EngineMCP System Status');
        WP_CLI::log('======================');

        // Node.js status
        $nodejs_status = $this->check_nodejs();
        WP_CLI::log('Node.js: ' . ($nodejs_status ? '✓ Available' : '✗ Not Found'));
        
        if ($nodejs_status) {
            $node_version = trim(shell_exec('node --version 2>&1'));
            $npm_version = trim(shell_exec('npm --version 2>&1'));
            WP_CLI::log("  Node.js Version: {$node_version}");
            WP_CLI::log("  npm Version: {$npm_version}");
        }

        // MCP servers status
        $installed_servers = EngineMCP_Installer::get_installed_servers();
        $all_servers = EngineMCP_Installer::get_server_info();
        
        WP_CLI::log('');
        WP_CLI::log('MCP Servers: ' . count($installed_servers) . '/' . count($all_servers) . ' installed');

        if (!empty($installed_servers)) {
            foreach ($installed_servers as $server_key) {
                $server_info = EngineMCP_Installer::get_server_info($server_key);
                $is_working = EngineMCP_Installer::test_mcp_server($server_key);
                $status_icon = $is_working ? '✓' : '✗';
                WP_CLI::log("  {$status_icon} {$server_info['name']}");
            }
        }

        // API keys status
        $api_keys = ['BROWSERBASE_API_KEY', 'GITHUB_TOKEN', 'DEEPSEEK_API_KEY', 'EXA_API_KEY', 'APIFY_TOKEN'];
        $configured_keys = 0;
        
        foreach ($api_keys as $key) {
            $option_name = 'enginemcp_' . strtolower(str_replace('_', '', $key));
            if (get_option($option_name, '')) {
                $configured_keys++;
            }
        }
        
        WP_CLI::log('');
        WP_CLI::log("API Keys: {$configured_keys}/" . count($api_keys) . ' configured');

        // Health check status
        $health_report = get_option('enginemcp_health_report', []);
        if (!empty($health_report)) {
            $last_check = max(array_column($health_report, 'checked_at'));
            WP_CLI::log('');
            WP_CLI::log('Last Health Check: ' . date('Y-m-d H:i:s', $last_check));
            
            $healthy_servers = array_filter($health_report, function($report) {
                return $report['status'] === 'healthy';
            });
            
            WP_CLI::log('Health Status: ' . count($healthy_servers) . '/' . count($health_report) . ' servers healthy');
        }

        WP_CLI::log('');
        WP_CLI::success('Status check completed');
    }

    /**
     * Uninstall MCP servers
     *
     * ## OPTIONS
     *
     * [<server>]
     * : Server key to uninstall. If not provided, shows list of installed servers.
     *
     * [--all]
     * : Uninstall all installed servers
     *
     * [--force]
     * : Skip confirmation prompt
     *
     * ## EXAMPLES
     *
     *     wp enginemcp uninstall
     *     wp enginemcp uninstall sequential-thinking
     *     wp enginemcp uninstall --all --force
     *
     * @when after_wp_load
     */
    public function uninstall($args, $assoc_args) {
        $server_to_remove = $args[0] ?? '';
        $uninstall_all = isset($assoc_args['all']);
        $force = isset($assoc_args['force']);

        $installed_servers = EngineMCP_Installer::get_installed_servers();

        if (empty($installed_servers)) {
            WP_CLI::warning('No MCP servers are currently installed.');
            return;
        }

        if (!$server_to_remove && !$uninstall_all) {
            WP_CLI::log('Installed MCP servers:');
            foreach ($installed_servers as $server_key) {
                $server_info = EngineMCP_Installer::get_server_info($server_key);
                WP_CLI::log("  - {$server_key} ({$server_info['name']})");
            }
            WP_CLI::log('');
            WP_CLI::log('Use: wp enginemcp uninstall <server-key> to uninstall a specific server');
            WP_CLI::log('Use: wp enginemcp uninstall --all to uninstall all servers');
            return;
        }

        $servers_to_remove = [];

        if ($uninstall_all) {
            $servers_to_remove = $installed_servers;
        } else {
            if (!in_array($server_to_remove, $installed_servers)) {
                WP_CLI::error("Server '{$server_to_remove}' is not installed.");
            }
            $servers_to_remove = [$server_to_remove];
        }

        // Confirmation
        if (!$force) {
            $server_names = array_map(function($key) {
                $info = EngineMCP_Installer::get_server_info($key);
                return $info['name'];
            }, $servers_to_remove);

            WP_CLI::confirm('Are you sure you want to uninstall: ' . implode(', ', $server_names) . '?');
        }

        // Uninstall servers
        $success_count = 0;
        foreach ($servers_to_remove as $server_key) {
            if (EngineMCP_Installer::uninstall_mcp_server($server_key)) {
                $server_info = EngineMCP_Installer::get_server_info($server_key);
                WP_CLI::log("✓ Uninstalled: {$server_info['name']}");
                $success_count++;
            } else {
                WP_CLI::warning("✗ Failed to uninstall: {$server_key}");
            }
        }

        WP_CLI::success("Uninstalled {$success_count}/" . count($servers_to_remove) . ' servers');
    }

    /**
     * Generate installation script
     *
     * ## OPTIONS
     *
     * [--output=<path>]
     * : Output file path for the installation script
     *
     * ## EXAMPLES
     *
     *     wp enginemcp script
     *     wp enginemcp script --output=/tmp/install_mcp.sh
     *
     * @when after_wp_load
     */
    public function script($args, $assoc_args) {
        $output_file = $assoc_args['output'] ?? getcwd() . '/install_mcp_servers.sh';
        
        WP_CLI::log('Generating installation script...');
        
        $script_content = EngineMCP_Installer::generate_install_script();
        
        if (file_put_contents($output_file, $script_content)) {
            chmod($output_file, 0755); // Make executable
            WP_CLI::success("Installation script saved to: {$output_file}");
            WP_CLI::log('Run the script with: bash ' . $output_file);
        } else {
            WP_CLI::error('Failed to save installation script');
        }
    }

    /**
     * Check if Node.js and npm are available
     */
    private function check_nodejs() {
        $node_check = shell_exec('node --version 2>&1');
        $npm_check = shell_exec('npm --version 2>&1');
        
        return !empty($node_check) && !empty($npm_check) && 
               strpos($node_check, 'not found') === false &&
               strpos($npm_check, 'not found') === false;
    }
}
