/**
 * PilotWP Admin JavaScript
 */
(function($) {
    'use strict';

    var PilotWPAdmin = {
        
        init: function() {
            this.bindEvents();
        },
        
        bindEvents: function() {
            $(document).on('click', '.pilotwp-install-submodule', this.installSubmodule);
            $(document).on('click', '.pilotwp-activate-submodule', this.activateSubmodule);
            $(document).on('click', '.pilotwp-deactivate-submodule', this.deactivateSubmodule);
            
            // EngineMCP specific events
            $(document).on('click', '.enginemcp-test-connection', this.testConnection);
            $(document).on('click', '.enginemcp-refresh-tools', this.refreshTools);
        },
        
        installSubmodule: function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var submoduleId = $button.data('submodule-id');
            
            if (!submoduleId) {
                alert('Invalid submodule ID');
                return;
            }
            
            $button.addClass('pilotwp-loading').text('Installing...');
            
            $.ajax({
                url: pilotWP.ajax_url,
                type: 'POST',
                data: {
                    action: 'pilotwp_install_submodule',
                    submodule_id: submoduleId,
                    security: pilotWP.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $button.removeClass('pilotwp-loading').text('Installed');
                        location.reload();
                    } else {
                        alert('Installation failed: ' + (response.data.message || 'Unknown error'));
                        $button.removeClass('pilotwp-loading').text('Install');
                    }
                },
                error: function() {
                    alert('Installation failed: Network error');
                    $button.removeClass('pilotwp-loading').text('Install');
                }
            });
        },
        
        activateSubmodule: function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var submoduleId = $button.data('submodule-id');
            
            if (!submoduleId) {
                alert('Invalid submodule ID');
                return;
            }
            
            $button.addClass('pilotwp-loading').text('Activating...');
            
            $.ajax({
                url: pilotWP.ajax_url,
                type: 'POST',
                data: {
                    action: 'pilotwp_activate_submodule',
                    submodule_id: submoduleId,
                    security: pilotWP.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $button.removeClass('pilotwp-loading').text('Activated');
                        location.reload();
                    } else {
                        alert('Activation failed: ' + (response.data.message || 'Unknown error'));
                        $button.removeClass('pilotwp-loading').text('Activate');
                    }
                },
                error: function() {
                    alert('Activation failed: Network error');
                    $button.removeClass('pilotwp-loading').text('Activate');
                }
            });
        },
        
        deactivateSubmodule: function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var submoduleId = $button.data('submodule-id');
            
            if (!submoduleId || !confirm('Are you sure you want to deactivate this submodule?')) {
                return;
            }
            
            $button.addClass('pilotwp-loading').text('Deactivating...');
            
            $.ajax({
                url: pilotWP.ajax_url,
                type: 'POST',
                data: {
                    action: 'pilotwp_deactivate_submodule',
                    submodule_id: submoduleId,
                    security: pilotWP.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $button.removeClass('pilotwp-loading').text('Deactivated');
                        location.reload();
                    } else {
                        alert('Deactivation failed: ' + (response.data.message || 'Unknown error'));
                        $button.removeClass('pilotwp-loading').text('Deactivate');
                    }
                },
                error: function() {
                    alert('Deactivation failed: Network error');
                    $button.removeClass('pilotwp-loading').text('Deactivate');
                }
            });
        },
        
        testConnection: function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var provider = $button.data('provider');
            
            $button.addClass('pilotwp-loading').text('Testing...');
            
            $.ajax({
                url: pilotWP.ajax_url,
                type: 'POST',
                data: {
                    action: 'enginemcp_test_connection',
                    provider: provider,
                    security: pilotWP.nonce
                },
                success: function(response) {
                    if (response.success) {
                        alert('Connection successful!');
                    } else {
                        alert('Connection failed: ' + (response.data.message || 'Unknown error'));
                    }
                    $button.removeClass('pilotwp-loading').text('Test Connection');
                },
                error: function() {
                    alert('Connection test failed: Network error');
                    $button.removeClass('pilotwp-loading').text('Test Connection');
                }
            });
        },
        
        refreshTools: function(e) {
            e.preventDefault();
            
            var $button = $(this);
            
            $button.addClass('pilotwp-loading').text('Refreshing...');
            
            $.ajax({
                url: pilotWP.ajax_url,
                type: 'POST',
                data: {
                    action: 'enginemcp_refresh_tools',
                    security: pilotWP.nonce
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Refresh failed: ' + (response.data.message || 'Unknown error'));
                        $button.removeClass('pilotwp-loading').text('Refresh Tools');
                    }
                },
                error: function() {
                    alert('Refresh failed: Network error');
                    $button.removeClass('pilotwp-loading').text('Refresh Tools');
                }
            });
        }
    };
    
    $(document).ready(function() {
        PilotWPAdmin.init();
    });
    
})(jQuery);
