<?php
namespace CPG;

defined('ABSPATH') || exit;

class API {
    public function __construct() {
        add_action('rest_api_init', array($this, 'register_endpoints'));
    }
    
    public function register_endpoints() {
        register_rest_route('shetab-card-to-card-payment-gateway/v1', '/verify-payment', array(
            'methods' => 'POST',
            'callback' => array($this, 'verify_payment'),
            'permission_callback' => array($this, 'check_permission')
        ));
    }
    
    public function check_permission($request) {
        $api_key = get_option('cpg_api_key');
        $headers = $request->get_headers();
        
        if (!isset($headers['authorization'][0]) || $headers['authorization'][0] !== $api_key) {
            return false;
        }
        
        return true;
    }
    
    public function verify_payment($request) {
        $params = $request->get_params();
        
        if (!isset($params['amount']) || !isset($params['order_id'])) {
            return new \WP_Error(
                'missing_params',
                __('پارامترهای مورد نیاز ارسال نشده‌اند', 'shetab-card-to-card-payment-gateway'),
                array('status' => 400)
            );
        }
        
        $transactions = new Transactions();
        $result = $transactions->verify($params['order_id'], $params['amount']);
        
        if (is_wp_error($result)) {
            return $result;
        }
        
        return array(
            'success' => true,
            'message' => __('پرداخت با موفقیت تایید شد', 'shetab-card-to-card-payment-gateway')
        );
    }

    private function verify_nonce($request) {
        $nonce = $request->get_header('X-WP-Nonce');
        if (!wp_verify_nonce($nonce, 'wp_rest')) {
            return new \WP_Error(
                'invalid_nonce',
                __('توکن امنیتی نامعتبر است', 'shetab-card-to-card-payment-gateway'),
                array('status' => 403)
            );
        }
        return true;
    }
} 