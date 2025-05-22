<?php
/**
 * Plugin Manager Admin View
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

$plugin_manager = new PilotWP_Plugin_Manager();
$tracked_plugins = $plugin_manager->get_tracked_plugins();
$sources = $plugin_manager->get_sources();
?>

<div class="wrap">
    <h1>🔌 PilotWP Plugin Manager</h1>
    <p>Install and manage plugins from multiple sources including GitHub, WordPress.org, and direct URLs.</p>

    <div id="plugin-manager-tabs">
        <ul>
            <li><a href="#tab-install">Install Plugins</a></li>
            <li><a href="#tab-managed">Managed Plugins</a></li>
            <li><a href="#tab-development">Development Setup</a></li>
            <li><a href="#tab-github-updater">GitHub Updater</a></li>
        </ul>

        <!-- Install Plugins Tab -->
        <div id="tab-install">
            <h2>Install New Plugin</h2>
            
            <form id="install-plugin-form">
                <?php wp_nonce_field('pilotwp_nonce', 'nonce'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">Plugin Source</th>
                        <td>
                            <select name="source" id="plugin-source" required>
                                <option value="">Select Source</option>
                                <?php foreach ($sources as $key => $label): ?>
                                    <option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    
                    <tr id="wordpress-org-fields" style="display: none;">
                        <th scope="row">Plugin Slug</th>
                        <td>
                            <input type="text" name="wp_slug" placeholder="e.g., github-updater" class="regular-text">
                            <p class="description">Plugin slug from WordPress.org (found in the plugin URL)</p>
                        </td>
                    </tr>
                    
                    <tr id="github-fields" style="display: none;">
                        <th scope="row">GitHub Repository</th>
                        <td>
                            <input type="url" name="github_url" placeholder="https://github.com/username/repository" class="regular-text">
                            <p class="description">Full GitHub repository URL</p>
                        </td>
                    </tr>
                    
                    <tr id="url-fields" style="display: none;">
                        <th scope="row">ZIP File URL</th>
                        <td>
                            <input type="url" name="zip_url" placeholder="https://example.com/plugin.zip" class="regular-text">
                            <p class="description">Direct URL to plugin ZIP file</p>
                        </td>
                    </tr>
                    
                    <tr id="composer-fields" style="display: none;">
                        <th scope="row">Composer Package</th>
                        <td>
                            <input type="text" name="composer_package" placeholder="vendor/package-name" class="regular-text">
                            <p class="description">Composer package name (requires WP-CLI)</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">Options</th>
                        <td>
                            <label>
                                <input type="checkbox" name="activate" value="1" checked>
                                Activate plugin after installation
                            </label>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <button type="submit" class="button button-primary">Install Plugin</button>
                </p>
            </form>

            <!-- Quick Install Section -->
            <div class="postbox">
                <div class="postbox-header">
                    <h2 class="hndle">Quick Install - Recommended Plugins</h2>
                </div>
                <div class="inside">
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>Plugin</th>
                                <th>Description</th>
                                <th>Source</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong>GitHub Updater</strong></td>
                                <td>Automatically update plugins from GitHub</td>
                                <td>WordPress.org</td>
                                <td>
                                    <button class="button quick-install" 
                                            data-source="wordpress.org" 
                                            data-slug="github-updater">Install</button>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Easy Theme and Plugin Upgrades</strong></td>
                                <td>Upload ZIP files via admin interface</td>
                                <td>WordPress.org</td>
                                <td>
                                    <button class="button quick-install" 
                                            data-source="wordpress.org" 
                                            data-slug="easy-theme-and-plugin-upgrades">Install</button>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>WP CLI</strong></td>
                                <td>WordPress command line interface</td>
                                <td>WordPress.org</td>
                                <td>
                                    <button class="button quick-install" 
                                            data-source="wordpress.org" 
                                            data-slug="wp-cli">Install</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Managed Plugins Tab -->
        <div id="tab-managed">
            <h2>Managed Plugins</h2>
            
            <?php if (empty($tracked_plugins)): ?>
                <p>No plugins are currently being managed by PilotWP.</p>
            <?php else: ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Plugin</th>
                            <th>Source</th>
                            <th>Repository</th>
                            <th>Last Checked</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tracked_plugins as $plugin): ?>
                            <?php 
                            $plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin->plugin_file);
                            $is_active = is_plugin_active($plugin->plugin_file);
                            ?>
                            <tr>
                                <td>
                                    <strong><?php echo esc_html($plugin_data['Name'] ?? dirname($plugin->plugin_file)); ?></strong>
                                    <br>
                                    <small><?php echo esc_html($plugin->plugin_file); ?></small>
                                    <?php if ($is_active): ?>
                                        <span class="plugin-status active">Active</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo esc_html(ucfirst($plugin->source)); ?></td>
                                <td>
                                    <?php if ($plugin->repository): ?>
                                        <a href="<?php echo esc_url($plugin->repository); ?>" target="_blank">
                                            <?php echo esc_html($plugin->repository); ?>
                                        </a>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td><?php echo esc_html($plugin->last_checked); ?></td>
                                <td>
                                    <button class="button update-plugin" 
                                            data-plugin="<?php echo esc_attr($plugin->plugin_file); ?>">
                                        Update
                                    </button>
                                    <button class="button check-update" 
                                            data-plugin="<?php echo esc_attr($plugin->plugin_file); ?>">
                                        Check
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <!-- Development Setup Tab -->
        <div id="tab-development">
            <h2>Development Environment Setup</h2>
            <p>Quickly install essential plugins for your LocalWP development environment.</p>
            
            <div class="postbox">
                <div class="postbox-header">
                    <h2 class="hndle">Essential Development Plugins</h2>
                </div>
                <div class="inside">
                    <p>Install the following plugins to enhance your development workflow:</p>
                    
                    <ul>
                        <li><strong>GitHub Updater</strong> - Automatic updates from GitHub repositories</li>
                        <li><strong>Easy Theme and Plugin Upgrades</strong> - Upload ZIP files via admin</li>
                        <li><strong>WP CLI</strong> - Command line interface for WordPress</li>
                        <li><strong>Query Monitor</strong> - Debug queries and performance</li>
                        <li><strong>Debug Bar</strong> - Development debugging tools</li>
                    </ul>
                    
                    <p class="submit">
                        <button type="button" id="install-dev-plugins" class="button button-primary">
                            Install All Development Plugins
                        </button>
                    </p>
                </div>
            </div>

            <!-- LocalWP Integration -->
            <div class="postbox">
                <div class="postbox-header">
                    <h2 class="hndle">LocalWP Integration</h2>
                </div>
                <div class="inside">
                    <p>For LocalWP users, you can also manage plugins via command line:</p>
                    
                    <h4>WP-CLI Commands:</h4>
                    <pre><code># Install plugin from WordPress.org
wp plugin install github-updater --activate

# Install from GitHub
wp plugin install https://github.com/user/repo/archive/main.zip --activate

# Update all plugins
wp plugin update --all

# List installed plugins
wp plugin list</code></pre>

                    <h4>Composer Integration:</h4>
                    <pre><code># Install via Composer (requires composer.json)
composer require wpackagist-plugin/github-updater

# Update Composer packages
composer update</code></pre>
                </div>
            </div>
        </div>

        <!-- GitHub Updater Tab -->
        <div id="tab-github-updater">
            <h2>GitHub Updater Configuration</h2>
            
            <form method="post" action="options.php">
                <?php settings_fields('pilotwp_github_settings'); ?>
                <?php do_settings_sections('pilotwp_github_settings'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">GitHub Personal Access Token</th>
                        <td>
                            <input type="password" 
                                   name="pilotwp_github_token" 
                                   value="<?php echo esc_attr(get_option('pilotwp_github_token')); ?>" 
                                   class="regular-text">
                            <p class="description">
                                <a href="https://github.com/settings/tokens" target="_blank">Generate a GitHub token</a> 
                                for private repositories and increased API limits.
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">Auto-Update GitHub Plugins</th>
                        <td>
                            <label>
                                <input type="checkbox" 
                                       name="pilotwp_github_auto_update" 
                                       value="1" 
                                       <?php checked(get_option('pilotwp_github_auto_update'), 1); ?>>
                                Automatically update GitHub-sourced plugins
                            </label>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
            </form>

            <!-- GitHub Updater Status -->
            <div class="postbox">
                <div class="postbox-header">
                    <h2 class="hndle">GitHub Updater Status</h2>
                </div>
                <div class="inside">
                    <?php if (is_plugin_active('github-updater/github-updater.php')): ?>
                        <p style="color: green;">✅ GitHub Updater is active and ready to use.</p>
                        
                        <h4>How to use GitHub Updater:</h4>
                        <ol>
                            <li>Add <code>GitHub Plugin URI: username/repository</code> to your plugin header</li>
                            <li>Add <code>GitHub Branch: main</code> to specify the branch</li>
                            <li>GitHub Updater will automatically detect and offer updates</li>
                        </ol>
                        
                        <h4>Example Plugin Header:</h4>
                        <pre><code>/**
 * Plugin Name: My Plugin
 * GitHub Plugin URI: jadenblack/my-plugin
 * GitHub Branch: main
 * Version: 1.0.0
 */</code></pre>
                        
                    <?php else: ?>
                        <p style="color: orange;">⚠️ GitHub Updater is not installed or active.</p>
                        <p>
                            <button class="button button-primary quick-install" 
                                    data-source="wordpress.org" 
                                    data-slug="github-updater">
                                Install GitHub Updater
                            </button>
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.plugin-status.active {
    background: #00a32a;
    color: white;
    padding: 2px 8px;
    border-radius: 3px;
    font-size: 11px;
    font-weight: bold;
    margin-left: 8px;
}

#plugin-manager-tabs {
    margin-top: 20px;
}

#plugin-manager-tabs .ui-tabs-nav {
    background: none;
    border: none;
    padding: 0;
}

#plugin-manager-tabs .ui-tabs-nav li {
    background: #f1f1f1;
    border: 1px solid #ccd0d4;
    margin-right: 5px;
}

#plugin-manager-tabs .ui-tabs-nav li.ui-tabs-active {
    background: white;
    border-bottom-color: white;
}

.postbox {
    margin-top: 20px;
}

pre {
    background: #f6f7f7;
    padding: 15px;
    border-radius: 4px;
    overflow-x: auto;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Initialize tabs
    $("#plugin-manager-tabs").tabs();
    
    // Show/hide fields based on source selection
    $('#plugin-source').on('change', function() {
        const source = $(this).val();
        
        // Hide all fields
        $('#wordpress-org-fields, #github-fields, #url-fields, #composer-fields').hide();
        
        // Show relevant fields
        if (source === 'wordpress.org') {
            $('#wordpress-org-fields').show();
        } else if (source === 'github') {
            $('#github-fields').show();
        } else if (source === 'url') {
            $('#url-fields').show();
        } else if (source === 'composer') {
            $('#composer-fields').show();
        }
    });
    
    // Handle plugin installation form
    $('#install-plugin-form').on('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const source = formData.get('source');
        
        let pluginData = {
            action: 'pilotwp_install_plugin',
            nonce: formData.get('nonce'),
            source: source,
            activate: formData.get('activate') ? true : false
        };
        
        // Add source-specific data
        if (source === 'wordpress.org') {
            pluginData.slug = formData.get('wp_slug');
        } else if (source === 'github') {
            pluginData.url = formData.get('github_url');
            pluginData.slug = formData.get('github_url').split('/').pop();
        } else if (source === 'url') {
            pluginData.url = formData.get('zip_url');
        } else if (source === 'composer') {
            pluginData.slug = formData.get('composer_package');
        }
        
        const submitButton = $(this).find('button[type="submit"]');
        submitButton.prop('disabled', true).text('Installing...');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: pluginData,
            success: function(response) {
                if (response.success) {
                    alert('Plugin installed successfully!');
                    location.reload();
                } else {
                    alert('Installation failed: ' + response.data);
                }
            },
            error: function() {
                alert('Installation failed due to server error.');
            },
            complete: function() {
                submitButton.prop('disabled', false).text('Install Plugin');
            }
        });
    });
    
    // Quick install buttons
    $('.quick-install').on('click', function() {
        const button = $(this);
        const source = button.data('source');
        const slug = button.data('slug');
        
        button.prop('disabled', true).text('Installing...');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'pilotwp_install_plugin',
                nonce: $('input[name="nonce"]').val(),
                source: source,
                slug: slug,
                activate: true
            },
            success: function(response) {
                if (response.success) {
                    alert('Plugin installed successfully!');
                    button.text('Installed').css('color', 'green');
                } else {
                    alert('Installation failed: ' + response.data);
                    button.prop('disabled', false).text('Install');
                }
            },
            error: function() {
                alert('Installation failed due to server error.');
                button.prop('disabled', false).text('Install');
            }
        });
    });
    
    // Install development plugins
    $('#install-dev-plugins').on('click', function() {
        const button = $(this);
        button.prop('disabled', true).text('Installing...');
        
        // Install development plugins sequentially
        const plugins = [
            { source: 'wordpress.org', slug: 'github-updater' },
            { source: 'wordpress.org', slug: 'easy-theme-and-plugin-upgrades' },
            { source: 'wordpress.org', slug: 'wp-cli' },
            { source: 'wordpress.org', slug: 'query-monitor' },
            { source: 'wordpress.org', slug: 'debug-bar' }
        ];
        
        let completed = 0;
        const total = plugins.length;
        
        plugins.forEach(function(plugin, index) {
            setTimeout(function() {
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'pilotwp_install_plugin',
                        nonce: $('input[name="nonce"]').val(),
                        source: plugin.source,
                        slug: plugin.slug,
                        activate: true
                    },
                    success: function(response) {
                        completed++;
                        button.text(`Installing... (${completed}/${total})`);
                        
                        if (completed === total) {
                            button.text('All Installed!').css('color', 'green');
                            alert('All development plugins installed successfully!');
                        }
                    },
                    error: function() {
                        completed++;
                        if (completed === total) {
                            button.prop('disabled', false).text('Install All Development Plugins');
                        }
                    }
                });
            }, index * 2000); // Stagger installations by 2 seconds
        });
    });
    
    // Update plugin buttons
    $('.update-plugin').on('click', function() {
        const button = $(this);
        const plugin = button.data('plugin');
        
        button.prop('disabled', true).text('Updating...');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'pilotwp_update_plugin',
                nonce: $('input[name="nonce"]').val(),
                plugin: plugin
            },
            success: function(response) {
                if (response.success) {
                    alert('Plugin updated successfully!');
                    location.reload();
                } else {
                    alert('Update failed: ' + response.data);
                }
            },
            error: function() {
                alert('Update failed due to server error.');
            },
            complete: function() {
                button.prop('disabled', false).text('Update');
            }
        });
    });
});
</script>
