<?php
namespace CPG;

defined('ABSPATH') || exit;

class PaymentGateway extends \WC_Payment_Gateway {
    public function __construct() {
        $this->id = 'shetab_card_to_card';
        $this->icon = CPG_PLUGIN_URL . 'assets/images/logo.png';
        $this->has_fields = false;
        $this->method_title = __('درگاه پرداخت کارت به کارت', 'shetab-card-to-card-payment-gateway');
        $this->method_description = __('پرداخت از طریق کارت به کارت با تایید خودکار', 'shetab-card-to-card-payment-gateway');
        
        $this->supports = array(
            'products',
            'refunds'
        );
        
        $this->init_form_fields();
        $this->init_settings();
        
        $this->title = $this->get_option('title', __('پرداخت کارت به کارت', 'shetab-card-to-card-payment-gateway'));
        $this->description = $this->get_option('description', __('پرداخت از طریق کارت به کارت', 'shetab-card-to-card-payment-gateway'));
        $this->enabled = $this->get_option('enabled', 'yes');
        
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        add_action('woocommerce_receipt_' . $this->id, array($this, 'receipt_page'));
    }
    
    public function init_form_fields() {
        $this->form_fields = array(
            'enabled' => array(
                'title'       => __('فعال/غیرفعال', 'shetab-card-to-card-payment-gateway'),
                'type'        => 'checkbox',
                'label'       => __('فعال کردن درگاه پرداخت کارت به کارت', 'shetab-card-to-card-payment-gateway'),
                'default'     => 'yes'
            ),
            'title' => array(
                'title'       => __('عنوان', 'shetab-card-to-card-payment-gateway'),
                'type'        => 'text',
                'description' => __('عنوانی که کاربر در صفحه پرداخت می‌بیند', 'shetab-card-to-card-payment-gateway'),
                'default'     => __('پرداخت کارت به کارت', 'shetab-card-to-card-payment-gateway'),
                'desc_tip'    => true,
            ),
            'description' => array(
                'title'       => __('توضیحات', 'shetab-card-to-card-payment-gateway'),
                'type'        => 'textarea',
                'description' => __('توضیحاتی که کاربر در صفحه پرداخت می‌بیند', 'shetab-card-to-card-payment-gateway'),
                'default'     => __('پرداخت مبلغ سفارش از طریق کارت به کارت با تایید خودکار', 'shetab-card-to-card-payment-gateway')
            )
        );
    }
    
    public function process_payment($order_id) {
        $order = wc_get_order($order_id);
        
        if (!$order) {
            return array(
                'result' => 'failure',
                'messages' => __('سفارش یافت نشد', 'shetab-card-to-card-payment-gateway')
            );
        }
        
        $transaction = new Transactions();
        
        try {
            // تنظیم زمان انقضا به 10 دقیقه بعد
            $expires_at = date('Y-m-d H:i:s', strtotime('+10 minutes'));
            
            $transaction_id = $transaction->create(array(
                'order_id' => $order->get_id(),
                'amount' => $order->get_total(),
                'expires_at' => $expires_at
            ));
            
            if (!$transaction_id) {
                throw new \Exception(__('خطا در ایجاد تراکنش', 'shetab-card-to-card-payment-gateway'));
            }
            
            $order->update_status('pending', __('در انتظار پرداخت کارت به کارت', 'shetab-card-to-card-payment-gateway'));
            WC()->cart->empty_cart();
            
            return array(
                'result' => 'success',
                'redirect' => $order->get_checkout_payment_url(true)
            );
            
        } catch (\Exception $e) {
            wc_add_notice($e->getMessage(), 'error');
            return array(
                'result' => 'failure',
                'messages' => $e->getMessage()
            );
        }
    }
    
    public function receipt_page($order_id) {
        // جلوگیری از اجرای چندباره
        static $already_called = false;
        if ($already_called) {
            return;
        }
        $already_called = true;
        
        // جلوگیری از لود چندباره فایل‌های CSS و JS
        if (!wp_style_is('cpg-payment-style', 'enqueued')) {
            wp_enqueue_style('cpg-payment-style', CPG_PLUGIN_URL . 'assets/css/payment.css', array(), CPG_VERSION);
        }
        if (!wp_script_is('cpg-payment-script', 'enqueued')) {
            wp_enqueue_script('cpg-payment-script', CPG_PLUGIN_URL . 'assets/js/payment.js', array('jquery'), CPG_VERSION, true);
        }
        
        include CPG_PLUGIN_DIR . 'templates/payment/receipt.php';
    }
    
    public function process_refund($order_id, $amount = null, $reason = '') {
        $order = wc_get_order($order_id);
        
        if (!$order) {
            return new \WP_Error('invalid_order', __('سفارش نامعتبر است', 'shetab-card-to-card-payment-gateway'));
        }
        
        global $wpdb;
        $transaction = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}cpg_transactions WHERE order_id = %d AND status = 'completed'",
            $order_id
        ));
        
        if (!$transaction) {
            return new \WP_Error('invalid_transaction', __('تراکنش معتبری برای این سفارش یافت نشد', 'shetab-card-to-card-payment-gateway'));
        }
        
        $wpdb->update(
            $wpdb->prefix . 'cpg_transactions',
            array(
                'status' => 'refunded',
                'description' => $reason,
                'updated_at' => current_time('mysql')
            ),
            array('id' => $transaction->id),
            array('%s', '%s', '%s'),
            array('%d')
        );
        
        $order->add_order_note(
            sprintf(__('مبلغ %s استرداد شد. دلیل: %s', 'shetab-card-to-card-payment-gateway'),
                wc_price($amount),
                $reason
            )
        );
        
        return true;
    }
} 