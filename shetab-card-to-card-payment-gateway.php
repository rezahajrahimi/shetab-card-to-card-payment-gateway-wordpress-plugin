<?php
/**
 * Plugin Name: Shetab Card to Card Payment Gateway
 * Plugin URI: 
 * Description: درگاه پرداخت کارت به کارت با تایید خودکار برای ووکامرس
 * Version: 1.0.0
 * Author: Reza HajRahimi
 * Author URI: mailto:RezaHajRahimi@gmail.com
 * Text Domain: shetab-card-to-card-payment-gateway
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * WC requires at least: 6.0
 * WC tested up to: 8.0
 */

defined('ABSPATH') || exit;

// تعریف ثابت‌ها
define('CPG_VERSION', '1.0.0');
define('CPG_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CPG_PLUGIN_URL', plugin_dir_url(__FILE__));

// Autoloader
spl_autoload_register(function ($class) {
    $prefix = 'CPG\\';
    $base_dir = CPG_PLUGIN_DIR . 'includes/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative_class = substr($class, $len);
    $file = $base_dir . 'class-' . str_replace('\\', '/', strtolower($relative_class)) . '.php';
    
    if (file_exists($file)) {
        require $file;
    }
});

// بررسی وجود ووکامرس
add_action('plugins_loaded', function() {
    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', function() {
            echo '<div class="error"><p>';
            echo esc_html__('درگاه پرداخت کارت به کارت نیاز به نصب و فعال‌سازی ووکامرس دارد.', 'shetab-card-to-card-payment-gateway');
            echo '</p></div>';
        });
        return;
    }

    // راه‌اندازی کلاس‌های اصلی
    new CPG\Core();
});

// فعال‌سازی پلاگین
register_activation_hook(__FILE__, function() {
    if (!class_exists('WooCommerce')) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die(
            esc_html__('این پلاگین نیاز به نصب و فعال‌سازی ووکامرس دارد.', 'shetab-card-to-card-payment-gateway'),
            'Plugin dependency check',
            array('back_link' => true)
        );
    }

    // ایجاد جداول دیتابیس
    CPG\Database::install();
    
    // تنظیم مقادیر پیش‌فرض
    if (!get_option('cpg_api_key')) {
        update_option('cpg_api_key', bin2hex(random_bytes(32)));
    }
});

// غیرفعال‌سازی پلاگین
register_deactivation_hook(__FILE__, function() {
    // در صورت نیاز اقدامات لازم انجام شود
}); 