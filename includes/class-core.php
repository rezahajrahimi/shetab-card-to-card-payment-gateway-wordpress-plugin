<?php
namespace CPG;

defined('ABSPATH') || exit;

class Core {
    private static $instance = null;
    
    public function __construct() {
        $this->init_hooks();
        $this->load_dependencies();
    }
    
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function init_hooks() {
        add_action('init', array($this, 'load_textdomain'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_filter('woocommerce_payment_gateways', array($this, 'add_gateway'));
    }
    
    private function load_dependencies() {
        // اطمینان از لود شدن فایل‌ها به ترتیب صحیح
        require_once CPG_PLUGIN_DIR . 'includes/class-database.php';
        require_once CPG_PLUGIN_DIR . 'includes/class-transactions.php';
        require_once CPG_PLUGIN_DIR . 'includes/class-payment-gateway.php';
        require_once CPG_PLUGIN_DIR . 'includes/class-admin.php';
        require_once CPG_PLUGIN_DIR . 'includes/class-api.php';
        
        new Admin();
        new API();
        new PaymentGateway();
    }
    
    public function load_textdomain() {
        load_plugin_textdomain(
            'shetab-card-to-card-payment-gateway',
            false,
            dirname(plugin_basename(CPG_PLUGIN_DIR)) . '/languages/'
        );
    }
    
    public function enqueue_scripts() {
        if (is_checkout() && !is_checkout_pay_page()) {
            wp_enqueue_style(
                'cpg-payment-style',
                CPG_PLUGIN_URL . 'assets/css/payment.css',
                array(),
                CPG_VERSION
            );
            
            wp_enqueue_script(
                'cpg-payment-script',
                CPG_PLUGIN_URL . 'assets/js/payment.js',
                array('jquery'),
                CPG_VERSION,
                true
            );
        }
    }
    
    public function add_gateway($gateways) {
        $gateways[] = 'CPG\PaymentGateway';
        return $gateways;
    }
} 