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
        
        // اضافه کردن کتابخانه QR Code
        wp_enqueue_script(
            'qrcode-js',
            'https://cdn.jsdelivr.net/npm/qrcode-generator@1.4.4/qrcode.min.js',
            array(),
            '1.4.4',
            true
        );
        
        wp_add_inline_script('qrcode-js', $this->get_admin_scripts(), 'after');
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
            
            .cpg-modal {
                display: none;
                position: fixed;
                z-index: 999999;
                left: 0;
                top: 0;
                width: 100%;
                height: 100%;
                background-color: rgba(0,0,0,0.5);
            }
            
            .cpg-modal-content {
                background-color: #fefefe;
                margin: 15% auto;
                padding: 20px;
                border: 1px solid #888;
                width: 300px;
                text-align: center;
                position: relative;
                border-radius: 8px;
            }
            
            .cpg-close {
                position: absolute;
                right: 10px;
                top: 5px;
                color: #aaa;
                font-size: 28px;
                font-weight: bold;
                cursor: pointer;
            }
            
            .cpg-close:hover {
                color: black;
            }
            
            #qrcode {
                margin: 20px auto;
                width: 200px;
                height: 200px;
            }
            
            .qr-value {
                word-break: break-all;
                margin-top: 10px;
                font-size: 12px;
                color: #666;
            }
            
            .qr-button {
                margin-left: 5px !important;
            }
        ';
    }
    
    private function get_admin_scripts() {
        return "
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
                
                // مدیریت مودال QR
                var modal = document.getElementById('qrCodeModal');
                var qrcodeDiv = document.getElementById('qrcode');
                var qrValueText = modal.querySelector('.qr-value');
                var span = document.getElementsByClassName('cpg-close')[0];
                
                $('.qr-button').on('click', function() {
                    var value = $(this).data('value');
                    
                    // پاک کردن QR قبلی
                    qrcodeDiv.innerHTML = '';
                    
                    // ایجاد QR جدید
                    var qr = qrcode(0, 'M');
                    qr.addData(value);
                    qr.make();
                    qrcodeDiv.innerHTML = qr.createImgTag(5);
                    
                    // نمایش متن
                    qrValueText.textContent = value;
                    
                    // نمایش مودال
                    modal.style.display = 'block';
                });
                
                // بستن مودال
                span.onclick = function() {
                    modal.style.display = 'none';
                }
                
                window.onclick = function(event) {
                    if (event.target == modal) {
                        modal.style.display = 'none';
                    }
                }
            });
        ";
    }
} 