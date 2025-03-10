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
        
        register_rest_route('shetab-card-to-card-payment-gateway/v1', '/check-status/(?P<order_id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'check_status'),
            'permission_callback' => '__return_true',
            'args' => array(
                'order_id' => array(
                    'validate_callback' => function($param) {
                        return is_numeric($param);
                    }
                )
            )
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
        global $wpdb;
        $params = $request->get_params();
        
        if (!isset($params['amount'])) {
            return new \WP_Error(
                'missing_params',
                __('پارامترهای مورد نیاز ارسال نشده‌اند', 'shetab-card-to-card-payment-gateway'),
                array('status' => 400)
            );
        }
        if (!isset($params['order_id'])) {
            // get order_id where amount is equal to params['amount'] and status is pending
            $order_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}cpg_transactions WHERE unique_amount = %d AND status = 'pending'", $params['amount']));
            if (!$order_id) {
                return new \WP_Error(
                    'order_not_found',
                    __('سفارشی برای این مبلغ و وضعیت یافت نشد', 'shetab-card-to-card-payment-gateway'),
                    array('status' => 404)
                );
            }
            $params['order_id'] = $order_id;
            // $params['amount'] = $wpdb->get_var($wpdb->prepare("SELECT amount FROM {$wpdb->prefix}cpg_transactions WHERE id = %d", $order_id));
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
    
    public function check_status($request) {
        $order_id = $request->get_param('order_id');
        
        global $wpdb;
        $transaction = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}cpg_transactions WHERE order_id = %d ORDER BY id DESC LIMIT 1",
            $order_id
        ));
        
        if (!$transaction) {
            return new \WP_Error(
                'transaction_not_found',
                __('تراکنشی برای این سفارش یافت نشد', 'shetab-card-to-card-payment-gateway'),
                array('status' => 404)
            );
        }
        
        return array(
            'success' => true,
            'status' => $transaction->status,
            'amount' => $transaction->unique_amount,
            'expires_at' => $transaction->expires_at
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