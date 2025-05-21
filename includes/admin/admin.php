<?php
/**
 * PilotWP Admin
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class PilotWP_Admin {

    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            __('PilotWP', 'pilotwp'),
            __('PilotWP', 'pilotwp'),
            'manage_options',
            'pilotwp',
            [$this, 'admin_page'],
            'dashicons-airplane',
            30
        );
        
        add_submenu_page(
            'pilotwp',
            __('Submodules', 'pilotwp'),
            __('Submodules', 'pilotwp'),
            'manage_options',
            'pilotwp-submodules',
            [$this, 'submodules_page']
        );
    }

    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'pilotwp') === false) {
            return;
        }
        
        wp_enqueue_style(
            'pilotwp-admin',
            PILOTWP_PLUGIN_URL . 'assets/css/admin.css',
            [],
            PILOTWP_VERSION
        );
        
        wp_enqueue_script(
            'pilotwp-admin',
            PILOTWP_PLUGIN_URL . 'assets/js/admin.js',
            ['jquery'],
            PILOTWP_VERSION,
            true
        );
        
        wp_localize_script('pilotwp-admin', 'pilotWP', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('pilotwp_admin'),
        ]);
    }

    /**
     * Main admin page
     */
    public function admin_page() {
        echo '<div class="wrap">';
        echo '<h1>' . __('PilotWP Dashboard', 'pilotwp') . '</h1>';
        echo '<p>' . __('Welcome to PilotWP Engine - Enterprise WordPress enhancement platform.', 'pilotwp') . '</p>';
        
        // Show active submodules
        $active_submodules = pilotwp_get_active_submodules();
        if (!empty($active_submodules)) {
            echo '<h2>' . __('Active Submodules', 'pilotwp') . '</h2>';
            echo '<ul>';
            foreach ($active_submodules as $submodule) {
                echo '<li>' . esc_html($submodule) . '</li>';
            }
            echo '</ul>';
        }
        
        echo '</div>';
    }

    /**
     * Submodules admin page
     */
    public function submodules_page() {
        $manager = new PilotWP_Submodule_Manager();
        $registry = $manager->get_registry();
        $installed = $manager->get_installed_submodules();
        $active = pilotwp_get_active_submodules();
        
        echo '<div class="wrap">';
        echo '<h1>' . __('PilotWP Submodules', 'pilotwp') . '</h1>';
        echo '<p>' . __('Manage your PilotWP submodules here.', 'pilotwp') . '</p>';
        
        if (!empty($registry)) {
            foreach ($registry as $submodule_id => $submodule) {
                $is_installed = isset($installed[$submodule_id]);
                $is_active = in_array($submodule_id, $active);
                
                echo '<div class="pilotwp-submodule' . ($is_active ? ' active' : '') . '">';
                echo '<div class="pilotwp-submodule-info">';
                echo '<h3>' . esc_html($submodule['name']) . '</h3>';
                echo '<p>' . esc_html($submodule['description']) . '</p>';
                echo '</div>';
                echo '<div class="pilotwp-submodule-actions">';
                
                if (!$is_installed) {
                    echo '<button class="pilotwp-btn primary pilotwp-install-submodule" data-submodule-id="' . esc_attr($submodule_id) . '">' . __('Install', 'pilotwp') . '</button>';
                } elseif (!$is_active) {
                    echo '<button class="pilotwp-btn primary pilotwp-activate-submodule" data-submodule-id="' . esc_attr($submodule_id) . '">' . __('Activate', 'pilotwp') . '</button>';
                } else {
                    echo '<button class="pilotwp-btn pilotwp-deactivate-submodule" data-submodule-id="' . esc_attr($submodule_id) . '">' . __('Deactivate', 'pilotwp') . '</button>';
                }
                
                echo '</div>';
                echo '</div>';
            }
        } else {
            echo '<p>' . __('No submodules available. Check your internet connection or registry configuration.', 'pilotwp') . '</p>';
        }
        
        echo '</div>';
    }
}

new PilotWP_Admin();
