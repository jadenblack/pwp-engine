<?php
/**
 * EngineMCP Installer Class
 * Handles installation of MCP servers for Claude Desktop and Cursor
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class EngineMCP_Installer {
    
    /**
     * Top 15 MCP servers configuration
     */
    private static $mcp_servers = [
        'sequential-thinking' => [
            'package' => '@modelcontextprotocol/server-sequential-thinking',
            'name' => 'Sequential Thinking',
            'description' => 'Dynamic and reflective problem-solving through structured thinking',
            'requires_api_key' => false,
            'priority' => 1
        ],
        'browserbase' => [
            'package' => '@browserbasehq/mcp-browserbase',
            'name' => 'Browserbase',
            'description' => 'Cloud browser automation capabilities',
            'requires_api_key' => true,
            'env_vars' => ['BROWSERBASE_API_KEY'],
            'priority' => 2
        ],
        'duckduckgo-search' => [
            'package' => '@nickclyde/duckduckgo-mcp-server',
            'name' => 'DuckDuckGo Search',
            'description' => 'Web search capabilities through DuckDuckGo',
            'requires_api_key' => false,
            'priority' => 3
        ],
        'github' => [
            'package' => '@anthropic-samples/github-mcp-server',
            'name' => 'GitHub',
            'description' => 'GitHub API integration for repository management',
            'requires_api_key' => true,
            'env_vars' => ['GITHUB_TOKEN'],
            'priority' => 4
        ],
        'memory-kg' => [
            'package' => '@modelcontextprotocol/memory-kg',
            'name' => 'Knowledge Graph Memory',
            'description' => 'Local knowledge graph for persistent memory',
            'requires_api_key' => false,
            'priority' => 5
        ],
        'memory-bank' => [
            'package' => '@mcpserver/memory-bank',
            'name' => 'Memory Bank',
            'description' => 'Remote memory management across sessions',
            'requires_api_key' => false,
            'priority' => 6
        ],
        'playwright' => [
            'package' => '@anthropic-samples/playwright-mcp-server',
            'name' => 'Playwright Automation',
            'description' => 'Browser automation using Playwright',
            'requires_api_key' => false,
            'priority' => 7
        ],
        'think-tool' => [
            'package' => '@PhillipRt/think-mcp-server',
            'name' => 'Think Tool Server',
            'description' => 'Enhanced reasoning capabilities',
            'requires_api_key' => false,
            'priority' => 8
        ],
        'mas-sequential-thinking' => [
            'package' => '@FradSer/mcp-server-mas-sequential-thinking',
            'name' => 'Multi-Agent Sequential Thinking',
            'description' => 'Multiple specialized AI agents working together',
            'requires_api_key' => true,
            'env_vars' => ['DEEPSEEK_API_KEY', 'EXA_API_KEY'],
            'priority' => 9
        ],
        'apify-actors' => [
            'package' => '@apify/actors-mcp-server',
            'name' => 'Apify Actors',
            'description' => 'Connect to 3000+ pre-built cloud tools',
            'requires_api_key' => true,
            'env_vars' => ['APIFY_TOKEN'],
            'priority' => 10
        ],
        'flux-imagegen' => [
            'package' => '@falahgs/flux-imagegen-mcp-server',
            'name' => 'Flux ImageGen',
            'description' => 'Generate and manipulate images using AI',
            'requires_api_key' => false,
            'priority' => 11
        ],
        'file-context' => [
            'package' => '@bsmi021/mcp-file-context-server',
            'name' => 'File Context Server',
            'description' => 'Read, search, and analyze code files',
            'requires_api_key' => false,
            'priority' => 12
        ],
        'mcp-painter' => [
            'package' => '@flrngel/mcp-painter',
            'name' => 'Drawing Tool',
            'description' => 'Create and manipulate drawings with canvas interface',
            'requires_api_key' => false,
            'priority' => 13
        ],
        'mermaid' => [
            'package' => '@peng-shawn/mermaid-mcp-server',
            'name' => 'Mermaid Diagram Generator',
            'description' => 'Convert text to high-quality PNG diagrams',
            'requires_api_key' => false,
            'priority' => 14
        ],
        'desktop-commander' => [
            'package' => '@wonderwhy-er/desktop-commander-mcp',
            'name' => 'Desktop Commander',
            'description' => 'Execute terminal commands and manage files',
            'requires_api_key' => false,
            'priority' => 15
        ]
    ];

    /**
     * Install MCP servers
     */
    public static function install_mcp_servers($selected_servers = null) {
        $results = [
            'success' => [],
            'failed' => [],
            'skipped' => []
        ];

        // If no specific servers selected, install all
        if ($selected_servers === null) {
            $selected_servers = array_keys(self::$mcp_servers);
        }

        // Sort by priority
        $servers_to_install = [];
        foreach ($selected_servers as $server_key) {
            if (isset(self::$mcp_servers[$server_key])) {
                $servers_to_install[$server_key] = self::$mcp_servers[$server_key];
            }
        }

        uasort($servers_to_install, function($a, $b) {
            return $a['priority'] - $b['priority'];
        });

        foreach ($servers_to_install as $key => $server) {
            try {
                $install_result = self::install_single_mcp_server($key, $server);
                if ($install_result) {
                    $results['success'][] = $server['name'];
                } else {
                    $results['failed'][] = $server['name'];
                }
            } catch (Exception $e) {
                $results['failed'][] = $server['name'] . ' (' . $e->getMessage() . ')';
            }
        }

        return $results;
    }

    /**
     * Install a single MCP server
     */
    private static function install_single_mcp_server($key, $server) {
        // Check if Node.js is available
        if (!self::check_nodejs()) {
            throw new Exception('Node.js is required but not found');
        }

        // Try to install globally first, then cache for npx
        $install_command = "npm install -g {$server['package']} 2>&1";
        $output = [];
        $return_var = 0;

        exec($install_command, $output, $return_var);

        if ($return_var !== 0) {
            // If global install fails, try npx caching
            $cache_command = "npx -y {$server['package']} --help 2>&1";
            exec($cache_command, $cache_output, $cache_return);
            
            if ($cache_return !== 0) {
                return false;
            }
        }

        // Update installation status
        update_option("enginemcp_server_{$key}_installed", true);
        update_option("enginemcp_server_{$key}_package", $server['package']);
        
        return true;
    }

    /**
     * Generate Claude Desktop configuration
     */
    public static function generate_claude_config($installed_servers = null) {
        if ($installed_servers === null) {
            $installed_servers = self::get_installed_servers();
        }

        $config = ['mcpServers' => []];

        foreach ($installed_servers as $key) {
            if (isset(self::$mcp_servers[$key])) {
                $server = self::$mcp_servers[$key];
                $server_config = [
                    'command' => 'npx',
                    'args' => ['-y', $server['package']]
                ];

                // Add environment variables if required
                if (!empty($server['env_vars'])) {
                    $server_config['env'] = [];
                    foreach ($server['env_vars'] as $env_var) {
                        // Get from WordPress options or use placeholder
                        $option_key = 'enginemcp_' . strtolower(str_replace('_', '', $env_var));
                        $value = get_option($option_key, "your_{$env_var}_here");
                        $server_config['env'][$env_var] = $value;
                    }
                }

                $config['mcpServers'][$key] = $server_config;
            }
        }

        return json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Generate Cursor configuration
     */
    public static function generate_cursor_config($installed_servers = null) {
        if ($installed_servers === null) {
            $installed_servers = self::get_installed_servers();
        }

        $config = ['mcpServers' => []];

        // Use a subset for Cursor (lighter configuration)
        $cursor_servers = ['sequential-thinking', 'duckduckgo-search', 'github', 'memory-kg', 'playwright', 'file-context', 'desktop-commander'];

        foreach ($installed_servers as $key) {
            if (in_array($key, $cursor_servers) && isset(self::$mcp_servers[$key])) {
                $server = self::$mcp_servers[$key];
                $server_config = [
                    'command' => 'npx',
                    'args' => ['-y', $server['package']]
                ];

                // Add environment variables if required
                if (!empty($server['env_vars'])) {
                    $server_config['env'] = [];
                    foreach ($server['env_vars'] as $env_var) {
                        $option_key = 'enginemcp_' . strtolower(str_replace('_', '', $env_var));
                        $value = get_option($option_key, "your_{$env_var}_here");
                        $server_config['env'][$env_var] = $value;
                    }
                }

                $config['mcpServers'][$key] = $server_config;
            }
        }

        return json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Create installation script for manual installation
     */
    public static function generate_install_script() {
        $script_content = file_get_contents(__DIR__ . '/../templates/install_script_template.sh');
        
        // Replace placeholders with actual server data
        $servers_list = '';
        $servers_names = '';
        
        foreach (self::$mcp_servers as $key => $server) {
            $servers_list .= "        \"{$server['package']}\"\n";
            $servers_names .= "        \"{$server['name']}\"\n";
        }
        
        $script_content = str_replace('{{SERVERS_LIST}}', trim($servers_list), $script_content);
        $script_content = str_replace('{{SERVERS_NAMES}}', trim($servers_names), $script_content);
        
        return $script_content;
    }

    /**
     * Check if Node.js is installed
     */
    private static function check_nodejs() {
        $node_check = shell_exec('node --version 2>&1');
        $npm_check = shell_exec('npm --version 2>&1');
        
        return !empty($node_check) && !empty($npm_check) && strpos($node_check, 'not found') === false;
    }

    /**
     * Get list of installed servers
     */
    public static function get_installed_servers() {
        $installed = [];
        foreach (array_keys(self::$mcp_servers) as $key) {
            if (get_option("enginemcp_server_{$key}_installed", false)) {
                $installed[] = $key;
            }
        }
        return $installed;
    }

    /**
     * Get server information
     */
    public static function get_server_info($key = null) {
        if ($key) {
            return isset(self::$mcp_servers[$key]) ? self::$mcp_servers[$key] : null;
        }
        return self::$mcp_servers;
    }

    /**
     * Uninstall MCP server
     */
    public static function uninstall_mcp_server($key) {
        if (!isset(self::$mcp_servers[$key])) {
            return false;
        }

        $server = self::$mcp_servers[$key];
        
        // Try to uninstall globally
        $uninstall_command = "npm uninstall -g {$server['package']} 2>&1";
        exec($uninstall_command, $output, $return_var);

        // Update installation status regardless of uninstall success
        delete_option("enginemcp_server_{$key}_installed");
        delete_option("enginemcp_server_{$key}_package");

        return true;
    }

    /**
     * Test MCP server installation
     */
    public static function test_mcp_server($key) {
        if (!isset(self::$mcp_servers[$key])) {
            return false;
        }

        $server = self::$mcp_servers[$key];
        $test_command = "npx -y {$server['package']} --help 2>&1";
        
        exec($test_command, $output, $return_var);
        
        return $return_var === 0;
    }

    /**
     * Generate API keys template
     */
    public static function generate_api_keys_template() {
        $template = "MCP Servers API Keys Configuration\n";
        $template .= "==================================\n\n";
        $template .= "Please obtain the following API keys and update your WordPress options:\n\n";

        $api_keys_info = [
            'BROWSERBASE_API_KEY' => [
                'url' => 'https://browserbase.com',
                'description' => 'Cloud browser automation',
                'servers' => ['Browserbase']
            ],
            'GITHUB_TOKEN' => [
                'url' => 'https://github.com/settings/tokens',
                'description' => 'GitHub API access (scopes: repo, user)',
                'servers' => ['GitHub']
            ],
            'DEEPSEEK_API_KEY' => [
                'url' => 'https://platform.deepseek.com',
                'description' => 'DeepSeek AI model access',
                'servers' => ['Multi-Agent Sequential Thinking']
            ],
            'EXA_API_KEY' => [
                'url' => 'https://exa.ai',
                'description' => 'Exa search capabilities (optional)',
                'servers' => ['Multi-Agent Sequential Thinking']
            ],
            'APIFY_TOKEN' => [
                'url' => 'https://apify.com/account/integrations',
                'description' => 'Apify platform access',
                'servers' => ['Apify Actors']
            ]
        ];

        $counter = 1;
        foreach ($api_keys_info as $key => $info) {
            $template .= "{$counter}. {$key}\n";
            $template .= "   - Get from: {$info['url']}\n";
            $template .= "   - Description: {$info['description']}\n";
            $template .= "   - Used by: " . implode(', ', $info['servers']) . "\n";
            $template .= "   - WordPress option: enginemcp_" . strtolower(str_replace('_', '', $key)) . "\n\n";
            $counter++;
        }

        $template .= "Configuration Management:\n";
        $template .= "------------------------\n";
        $template .= "Update API keys in WordPress Admin:\n";
        $template .= "PilotWP > EngineMCP > API Configuration\n\n";

        $template .= "Or use WordPress CLI:\n";
        foreach ($api_keys_info as $key => $info) {
            $option_name = 'enginemcp_' . strtolower(str_replace('_', '', $key));
            $template .= "wp option update {$option_name} 'your_key_here'\n";
        }

        return $template;
    }
}
