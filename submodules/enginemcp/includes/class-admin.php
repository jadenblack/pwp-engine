<?php
/**
 * EngineMCP Admin Interface
 * MCP Server Management Dashboard
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class EngineMCP_Admin {
    
    public function __construct() {
        add_action('admin_menu', [$this, 'add_admin_pages']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        add_action('wp_ajax_enginemcp_install_servers', [$this, 'ajax_install_servers']);
        add_action('wp_ajax_enginemcp_uninstall_server', [$this, 'ajax_uninstall_server']);
        add_action('wp_ajax_enginemcp_test_server', [$this, 'ajax_test_server']);
        add_action('wp_ajax_enginemcp_save_api_key', [$this, 'ajax_save_api_key']);
        add_action('wp_ajax_enginemcp_download_config', [$this, 'ajax_download_config']);
        add_action('wp_ajax_enginemcp_generate_script', [$this, 'ajax_generate_script']);
    }

    public function add_admin_pages() {
        add_submenu_page(
            'pilotwp',
            'EngineMCP - MCP Server Manager',
            'EngineMCP',
            'manage_options',
            'enginemcp',
            [$this, 'admin_page']
        );
    }

    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'enginemcp') === false) {
            return;
        }

        wp_enqueue_script('jquery');
        wp_enqueue_script('jquery-ui-tabs');
        wp_enqueue_style('jquery-ui-tabs');
        
        wp_add_inline_script('jquery', '
            jQuery(document).ready(function($) {
                $("#enginemcp-tabs").tabs();
                
                // Install servers
                $("#install-selected-servers").on("click", function() {
                    var selectedServers = [];
                    $("input[name=\"selected_servers[]\"]:checked").each(function() {
                        selectedServers.push($(this).val());
                    });
                    
                    if (selectedServers.length === 0) {
                        alert("Please select at least one server to install.");
                        return;
                    }
                    
                    $(this).prop("disabled", true).text("Installing...");
                    
                    $.ajax({
                        url: ajaxurl,
                        type: "POST",
                        data: {
                            action: "enginemcp_install_servers",
                            servers: selectedServers,
                            nonce: "' . wp_create_nonce('enginemcp_nonce') . '"
                        },
                        success: function(response) {
                            if (response.success) {
                                alert("Installation completed:\\n\\nSuccess: " + response.data.success.join(", ") + "\\nFailed: " + response.data.failed.join(", "));
                                location.reload();
                            } else {
                                alert("Installation failed: " + response.data);
                            }
                        },
                        error: function() {
                            alert("Installation failed due to a server error.");
                        },
                        complete: function() {
                            $("#install-selected-servers").prop("disabled", false).text("Install Selected");
                        }
                    });
                });
                
                // Save API key
                $(".save-api-key").on("click", function() {
                    var keyName = $(this).data("key");
                    var keyValue = $("#" + keyName.toLowerCase().replace(/_/g, "") + "_input").val();
                    
                    if (!keyValue) {
                        alert("Please enter a valid API key.");
                        return;
                    }
                    
                    $.ajax({
                        url: ajaxurl,
                        type: "POST",
                        data: {
                            action: "enginemcp_save_api_key",
                            key_name: keyName,
                            key_value: keyValue,
                            nonce: "' . wp_create_nonce('enginemcp_nonce') . '"
                        },
                        success: function(response) {
                            if (response.success) {
                                alert("API key saved successfully!");
                                $("#" + keyName.toLowerCase().replace(/_/g, "") + "_status").text("✓ Configured").css("color", "green");
                            } else {
                                alert("Failed to save API key: " + response.data);
                            }
                        }
                    });
                });
            });
        ');
    }

    public function admin_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        $installed_servers = EngineMCP_Installer::get_installed_servers();
        $all_servers = EngineMCP_Installer::get_server_info();
        $nodejs_available = shell_exec('node --version 2>&1') && shell_exec('npm --version 2>&1');
        ?>
        <div class="wrap">
            <h1>🚀 EngineMCP - MCP Server Manager</h1>
            <p>Manage Model Context Protocol servers for Claude Desktop and Cursor AI.</p>

            <div id="enginemcp-tabs">
                <ul>
                    <li><a href="#tab-servers">MCP Servers</a></li>
                    <li><a href="#tab-api-keys">API Configuration</a></li>
                    <li><a href="#tab-configs">Download Configs</a></li>
                    <li><a href="#tab-install-script">Installation Script</a></li>
                    <li><a href="#tab-status">System Status</a></li>
                </ul>

                <!-- MCP Servers Tab -->
                <div id="tab-servers">
                    <h2>Available MCP Servers</h2>
                    
                    <?php if (!$nodejs_available): ?>
                        <div class="notice notice-error">
                            <p><strong>Node.js Required:</strong> Please install Node.js and npm to use MCP servers. <a href="https://nodejs.org/" target="_blank">Download Node.js</a></p>
                        </div>
                    <?php endif; ?>

                    <form method="post">
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th style="width: 40px;"><input type="checkbox" id="select-all"></th>
                                    <th>Server Name</th>
                                    <th>Description</th>
                                    <th>Status</th>
                                    <th>Priority</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($all_servers as $key => $server): ?>
                                    <?php 
                                    $is_installed = in_array($key, $installed_servers);
                                    $requires_api = $server['requires_api_key'] ?? false;
                                    ?>
                                    <tr>
                                        <td>
                                            <input type="checkbox" name="selected_servers[]" value="<?php echo esc_attr($key); ?>" <?php checked(!$is_installed); ?> <?php disabled($is_installed); ?>>
                                        </td>
                                        <td>
                                            <strong><?php echo esc_html($server['name']); ?></strong>
                                            <?php if ($requires_api): ?>
                                                <span class="dashicons dashicons-admin-network" title="Requires API Key"></span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo esc_html($server['description']); ?></td>
                                        <td>
                                            <?php if ($is_installed): ?>
                                                <span style="color: green;">✓ Installed</span>
                                            <?php else: ?>
                                                <span style="color: #ccc;">Not Installed</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo esc_html($server['priority']); ?></td>
                                        <td>
                                            <?php if ($is_installed): ?>
                                                <button type="button" class="button test-server" data-server="<?php echo esc_attr($key); ?>">Test</button>
                                                <button type="button" class="button uninstall-server" data-server="<?php echo esc_attr($key); ?>">Uninstall</button>
                                            <?php else: ?>
                                                <em>Select to install</em>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                        <p class="submit">
                            <button type="button" id="install-selected-servers" class="button button-primary" <?php disabled(!$nodejs_available); ?>>
                                Install Selected Servers
                            </button>
                        </p>
                    </form>
                </div>

                <!-- API Keys Tab -->
                <div id="tab-api-keys">
                    <h2>API Key Configuration</h2>
                    <p>Configure API keys for MCP servers that require external service access.</p>

                    <table class="form-table">
                        <?php 
                        $api_keys = [
                            'BROWSERBASE_API_KEY' => [
                                'label' => 'Browserbase API Key',
                                'description' => 'Required for cloud browser automation',
                                'url' => 'https://browserbase.com'
                            ],
                            'GITHUB_TOKEN' => [
                                'label' => 'GitHub Token',
                                'description' => 'Required for GitHub API access (scopes: repo, user)',
                                'url' => 'https://github.com/settings/tokens'
                            ],
                            'DEEPSEEK_API_KEY' => [
                                'label' => 'DeepSeek API Key',
                                'description' => 'Required for Multi-Agent Sequential Thinking',
                                'url' => 'https://platform.deepseek.com'
                            ],
                            'EXA_API_KEY' => [
                                'label' => 'Exa API Key',
                                'description' => 'Optional for enhanced search capabilities',
                                'url' => 'https://exa.ai'
                            ],
                            'APIFY_TOKEN' => [
                                'label' => 'Apify Token',
                                'description' => 'Required for Apify Actors integration',
                                'url' => 'https://apify.com/account/integrations'
                            ]
                        ];

                        foreach ($api_keys as $key => $info):
                            $option_name = 'enginemcp_' . strtolower(str_replace('_', '', $key));
                            $current_value = get_option($option_name, '');
                        ?>
                            <tr>
                                <th scope="row"><?php echo esc_html($info['label']); ?></th>
                                <td>
                                    <input type="password" id="<?php echo esc_attr(strtolower(str_replace('_', '', $key))); ?>_input" 
                                           class="regular-text" value="<?php echo esc_attr($current_value); ?>" 
                                           placeholder="Enter your <?php echo esc_attr($info['label']); ?>">
                                    <button type="button" class="button save-api-key" data-key="<?php echo esc_attr($key); ?>">Save</button>
                                    <span id="<?php echo esc_attr(strtolower(str_replace('_', '', $key))); ?>_status" style="margin-left: 10px;">
                                        <?php echo $current_value ? '<span style="color: green;">✓ Configured</span>' : '<span style="color: #ccc;">Not configured</span>'; ?>
                                    </span>
                                    <br>
                                    <small><?php echo esc_html($info['description']); ?> - <a href="<?php echo esc_url($info['url']); ?>" target="_blank">Get API Key</a></small>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                </div>

                <!-- Download Configs Tab -->
                <div id="tab-configs">
                    <h2>Configuration Files</h2>
                    <p>Download configuration files for Claude Desktop and Cursor.</p>

                    <table class="form-table">
                        <tr>
                            <th scope="row">Claude Desktop Configuration</th>
                            <td>
                                <button type="button" class="button" onclick="downloadConfig('claude')">Download claude_desktop_config.json</button>
                                <br><small>
                                    Save to:
                                    <br>• macOS: <code>~/Library/Application Support/Claude/claude_desktop_config.json</code>
                                    <br>• Windows: <code>%APPDATA%/Claude/claude_desktop_config.json</code>
                                </small>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Cursor Configuration</th>
                            <td>
                                <button type="button" class="button" onclick="downloadConfig('cursor')">Download mcp.json</button>
                                <br><small>
                                    Save to:
                                    <br>• Global: <code>~/.cursor/mcp.json</code>
                                    <br>• Project: <code>.cursor/mcp.json</code> (in project root)
                                </small>
                            </td>
                        </tr>
                    </table>

                    <script>
                        function downloadConfig(type) {
                            var form = document.createElement('form');
                            form.method = 'POST';
                            form.action = ajaxurl;
                            
                            var actionInput = document.createElement('input');
                            actionInput.type = 'hidden';
                            actionInput.name = 'action';
                            actionInput.value = 'enginemcp_download_config';
                            
                            var typeInput = document.createElement('input');
                            typeInput.type = 'hidden';
                            typeInput.name = 'config_type';
                            typeInput.value = type;
                            
                            var nonceInput = document.createElement('input');
                            nonceInput.type = 'hidden';
                            nonceInput.name = 'nonce';
                            nonceInput.value = '<?php echo wp_create_nonce('enginemcp_nonce'); ?>';
                            
                            form.appendChild(actionInput);
                            form.appendChild(typeInput);
                            form.appendChild(nonceInput);
                            
                            document.body.appendChild(form);
                            form.submit();
                            document.body.removeChild(form);
                        }
                    </script>
                </div>

                <!-- Installation Script Tab -->
                <div id="tab-install-script">
                    <h2>Installation Script</h2>
                    <p>Download a bash script to install all MCP servers automatically.</p>

                    <table class="form-table">
                        <tr>
                            <th scope="row">Bash Installation Script</th>
                            <td>
                                <button type="button" class="button button-primary" onclick="downloadScript()">Download install_mcp_servers.sh</button>
                                <br><small>Run this script on your local machine to install all MCP servers.</small>
                            </td>
                        </tr>
                    </table>

                    <script>
                        function downloadScript() {
                            var form = document.createElement('form');
                            form.method = 'POST';
                            form.action = ajaxurl;
                            
                            var actionInput = document.createElement('input');
                            actionInput.type = 'hidden';
                            actionInput.name = 'action';
                            actionInput.value = 'enginemcp_generate_script';
                            
                            var nonceInput = document.createElement('input');
                            nonceInput.type = 'hidden';
                            nonceInput.name = 'nonce';
                            nonceInput.value = '<?php echo wp_create_nonce('enginemcp_nonce'); ?>';
                            
                            form.appendChild(actionInput);
                            form.appendChild(nonceInput);
                            
                            document.body.appendChild(form);
                            form.submit();
                            document.body.removeChild(form);
                        }
                    </script>
                </div>

                <!-- System Status Tab -->
                <div id="tab-status">
                    <h2>System Status</h2>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">Node.js</th>
                            <td>
                                <?php 
                                $node_version = shell_exec('node --version 2>&1');
                                if ($node_version && strpos($node_version, 'not found') === false): ?>
                                    <span style="color: green;">✓ Installed</span> (<?php echo esc_html(trim($node_version)); ?>)
                                <?php else: ?>
                                    <span style="color: red;">✗ Not Found</span> - <a href="https://nodejs.org/" target="_blank">Install Node.js</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">npm</th>
                            <td>
                                <?php 
                                $npm_version = shell_exec('npm --version 2>&1');
                                if ($npm_version && strpos($npm_version, 'not found') === false): ?>
                                    <span style="color: green;">✓ Installed</span> (<?php echo esc_html(trim($npm_version)); ?>)
                                <?php else: ?>
                                    <span style="color: red;">✗ Not Found</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Installed MCP Servers</th>
                            <td>
                                <?php if (empty($installed_servers)): ?>
                                    <span style="color: #ccc;">None installed</span>
                                <?php else: ?>
                                    <span style="color: green;"><?php echo count($installed_servers); ?> servers installed</span>
                                    <ul>
                                        <?php foreach ($installed_servers as $server_key): ?>
                                            <li><?php echo esc_html($all_servers[$server_key]['name'] ?? $server_key); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <script>
            jQuery(document).ready(function($) {
                $('#select-all').on('change', function() {
                    $('input[name="selected_servers[]"]:not(:disabled)').prop('checked', this.checked);
                });
            });
        </script>
        <?php
    }

    // AJAX Handlers
    public function ajax_install_servers() {
        check_ajax_referer('enginemcp_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }

        $servers = isset($_POST['servers']) ? (array) $_POST['servers'] : [];
        
        if (empty($servers)) {
            wp_send_json_error('No servers selected');
        }

        $results = EngineMCP_Installer::install_mcp_servers($servers);
        wp_send_json_success($results);
    }

    public function ajax_save_api_key() {
        check_ajax_referer('enginemcp_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }

        $key_name = sanitize_text_field($_POST['key_name']);
        $key_value = sanitize_text_field($_POST['key_value']);
        
        $option_name = 'enginemcp_' . strtolower(str_replace('_', '', $key_name));
        update_option($option_name, $key_value);
        
        wp_send_json_success('API key saved');
    }

    public function ajax_download_config() {
        check_ajax_referer('enginemcp_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }

        $config_type = sanitize_text_field($_POST['config_type']);
        
        if ($config_type === 'claude') {
            $config = EngineMCP_Installer::generate_claude_config();
            $filename = 'claude_desktop_config.json';
        } elseif ($config_type === 'cursor') {
            $config = EngineMCP_Installer::generate_cursor_config();
            $filename = 'mcp.json';
        } else {
            wp_die('Invalid config type');
        }

        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($config));
        echo $config;
        exit;
    }

    public function ajax_generate_script() {
        check_ajax_referer('enginemcp_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }

        $script = EngineMCP_Installer::generate_install_script();

        header('Content-Type: application/x-sh');
        header('Content-Disposition: attachment; filename="install_mcp_servers.sh"');
        header('Content-Length: ' . strlen($script));
        echo $script;
        exit;
    }
}

// Initialize the admin interface
new EngineMCP_Admin();
