<?php
namespace CPG;

defined('ABSPATH') || exit;

class Database {
    public static function install() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}cpg_transactions (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            order_id bigint(20) NOT NULL,
            amount bigint(20) NOT NULL,
            unique_amount bigint(20) NOT NULL,
            status varchar(20) NOT NULL,
            card_holder varchar(100) NULL,
            description text NULL,
            log text NULL,
            created_at datetime NOT NULL,
            expires_at datetime NOT NULL,
            updated_at datetime NULL,
            PRIMARY KEY  (id),
            KEY order_id (order_id),
            KEY status (status)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
} 