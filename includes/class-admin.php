<?php
namespace CPG;

defined('ABSPATH') || exit;

class Admin {
    public function __construct() {
        add_action('admin_menu', array($this, 'add_menu_items'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }
    
    public function add_menu_items() {
        add_menu_page(
            __('تنظیمات درگاه پرداخت', 'shetab-card-to-card-payment-gateway'),
            __('درگاه پرداخت', 'shetab-card-to-card-payment-gateway'),
            'manage_options',
            'shetab-card-to-card-payment-gateway',
            array($this, 'settings_page'),
            'dashicons-money-alt',
            56
        );

        add_submenu_page(
            'shetab-card-to-card-payment-gateway',
            __('تراکنش‌ها', 'shetab-card-to-card-payment-gateway'),
            __('تراکنش‌ها', 'shetab-card-to-card-payment-gateway'),
            'manage_options',
            'shetab-transactions',
            array($this, 'transactions_page')
        );
    }
    
    public function enqueue_admin_scripts($hook) {
        if ('toplevel_page_shetab-card-to-card-payment-gateway' !== $hook) {
            return;
        }
        
        wp_enqueue_style('dashicons');
        wp_add_inline_style('admin-bar', $this->get_admin_styles());
        wp_add_inline_script('jquery', $this->get_admin_scripts(), 'after');
    }
    
    public function settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        include CPG_PLUGIN_DIR . 'templates/admin/settings.php';
    }
    
    public function transactions_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        include CPG_PLUGIN_DIR . 'templates/admin/transactions.php';
    }
    
    private function get_admin_styles() {
        return '
            .copy-field-container {
                display: flex;
                gap: 5px;
                margin-bottom: 5px;
            }
            .copy-button {
                display: flex !important;
                align-items: center;
                gap: 3px;
            }
            .copy-button .dashicons {
                font-size: 16px;
                width: 16px;
                height: 16px;
            }
        ';
    }
    
    private function get_admin_scripts() {
        return '
            jQuery(document).ready(function($) {
                $(".copy-button").on("click", function() {
                    var targetId = $(this).data("clipboard-target");
                    var $input = $(targetId);
                    
                    $input.select();
                    document.execCommand("copy");
                    
                    var $button = $(this);
                    var originalText = $button.html();
                    $button.html("<span class=\"dashicons dashicons-yes\"></span> کپی شد!");
                    
                    setTimeout(function() {
                        $button.html(originalText);
                    }, 2000);
                });
            });
        ';
    }
} 