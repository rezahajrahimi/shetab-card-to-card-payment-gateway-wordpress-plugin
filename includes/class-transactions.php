<?php
namespace CPG;

defined('ABSPATH') || exit;

class Transactions {
    private $table;
    
    public function __construct() {
        global $wpdb;
        $this->table = $wpdb->prefix . 'cpg_transactions';
    }
    
    public function create($data) {
        global $wpdb;
        
        $unique_amount = $data['amount'] + rand(1, 999) / 1000;
        
        $result = $wpdb->insert(
            $this->table,
            array(
                'order_id' => $data['order_id'],
                'amount' => $data['amount'],
                'unique_amount' => $unique_amount,
                'status' => 'pending',
                'created_at' => current_time('mysql'),
                'expires_at' => isset($data['expires_at']) ? $data['expires_at'] : date('Y-m-d H:i:s', strtotime('+10 minutes')),
            ),
            array('%d', '%f', '%f', '%s', '%s', '%s')
        );
        
        return $result ? $wpdb->insert_id : false;
    }
    
    public function verify($order_id, $amount) {
        global $wpdb;
        
        $transaction = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table} WHERE order_id = %d AND unique_amount = %f AND status = 'pending' AND expires_at > NOW()",
            $order_id,
            $amount
        ));
        
        if (!$transaction) {
            return new \WP_Error(
                'invalid_transaction',
                __('تراکنش نامعتبر است یا منقضی شده است', 'shetab-card-to-card-payment-gateway'),
                array('status' => 400)
            );
        }
        
        $this->update_status($transaction->id, 'completed');
        $this->add_log($transaction->id, __('پرداخت با موفقیت تایید شد', 'shetab-card-to-card-payment-gateway'));
        
        $order = wc_get_order($order_id);
        $order->payment_complete();
        $order->add_order_note(__('پرداخت از طریق کارت به کارت تایید شد.', 'shetab-card-to-card-payment-gateway'));
        
        return true;
    }
    
    private function update_status($id, $status) {
        global $wpdb;
        
        return $wpdb->update(
            $this->table,
            array(
                'status' => $status,
                'updated_at' => current_time('mysql')
            ),
            array('id' => $id),
            array('%s', '%s'),
            array('%d')
        );
    }
    
    private function add_log($id, $message) {
        global $wpdb;
        
        $transaction = $wpdb->get_row($wpdb->prepare(
            "SELECT log FROM {$this->table} WHERE id = %d",
            $id
        ));
        
        $current_log = $transaction->log ? json_decode($transaction->log, true) : array();
        $current_log[] = array(
            'time' => current_time('mysql'),
            'message' => $message
        );
        
        return $wpdb->update(
            $this->table,
            array(
                'log' => json_encode($current_log),
                'updated_at' => current_time('mysql')
            ),
            array('id' => $id),
            array('%s', '%s'),
            array('%d')
        );
    }
} 