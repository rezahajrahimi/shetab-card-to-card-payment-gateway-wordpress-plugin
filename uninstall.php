<?php
if (!defined('WP_UNINSTALL_PLUGIN')) {
    die;
}

// حذف جداول
global $wpdb;
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}cpg_transactions");

// حذف تنظیمات
delete_option('cpg_card_number');
delete_option('cpg_card_holder');
delete_option('cpg_telegram_id');
delete_option('cpg_whatsapp_number');
delete_option('cpg_api_key'); 