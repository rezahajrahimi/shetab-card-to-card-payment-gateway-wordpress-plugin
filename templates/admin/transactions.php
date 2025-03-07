<?php defined('ABSPATH') || exit; ?>

<div class="wrap">
    <h1><?php _e('تراکنش‌ها', 'shetab-card-to-card-payment-gateway'); ?></h1>
    
    <?php
    global $wpdb;
    
    // تعداد آیتم در هر صفحه
    $per_page = 20;
    $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
    $offset = ($current_page - 1) * $per_page;
    
    // دریافت تراکنش‌ها
    $transactions = $wpdb->get_results($wpdb->prepare(
        "SELECT t.*, o.post_status as order_status 
         FROM {$wpdb->prefix}cpg_transactions t
         LEFT JOIN {$wpdb->prefix}posts o ON t.order_id = o.ID
         ORDER BY t.created_at DESC
         LIMIT %d OFFSET %d",
        $per_page,
        $offset
    ));
    
    // تعداد کل تراکنش‌ها
    $total_items = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}cpg_transactions");
    $total_pages = ceil($total_items / $per_page);
    ?>
    
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th><?php _e('شناسه', 'shetab-card-to-card-payment-gateway'); ?></th>
                <th><?php _e('شماره سفارش', 'shetab-card-to-card-payment-gateway'); ?></th>
                <th><?php _e('مبلغ اصلی', 'shetab-card-to-card-payment-gateway'); ?></th>
                <th><?php _e('مبلغ یکتا', 'shetab-card-to-card-payment-gateway'); ?></th>
                <th><?php _e('وضعیت', 'shetab-card-to-card-payment-gateway'); ?></th>
                <th><?php _e('تاریخ ایجاد', 'shetab-card-to-card-payment-gateway'); ?></th>
                <th><?php _e('مهلت پرداخت', 'shetab-card-to-card-payment-gateway'); ?></th>
                <th><?php _e('توضیحات', 'shetab-card-to-card-payment-gateway'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($transactions as $transaction): ?>
                <tr>
                    <td><?php echo esc_html($transaction->id); ?></td>
                    <td>
                        <a href="<?php echo admin_url('post.php?post=' . $transaction->order_id . '&action=edit'); ?>">
                            <?php echo esc_html($transaction->order_id); ?>
                        </a>
                        (<?php echo esc_html($transaction->order_status); ?>)
                    </td>
                    <td><?php echo number_format($transaction->amount); ?> <?php _e('تومان', 'shetab-card-to-card-payment-gateway'); ?></td>
                    <td><?php echo number_format($transaction->unique_amount); ?> <?php _e('تومان', 'shetab-card-to-card-payment-gateway'); ?></td>
                    <td><?php echo esc_html($transaction->status); ?></td>
                    <td><?php echo wp_date('Y-m-d H:i:s', strtotime($transaction->created_at)); ?></td>
                    <td><?php echo wp_date('Y-m-d H:i:s', strtotime($transaction->expires_at)); ?></td>
                    <td><?php echo esc_html($transaction->description); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <?php if ($total_pages > 1): ?>
        <div class="tablenav bottom">
            <div class="tablenav-pages">
                <?php
                echo paginate_links(array(
                    'base' => add_query_arg('paged', '%#%'),
                    'format' => '',
                    'prev_text' => __('&laquo;'),
                    'next_text' => __('&raquo;'),
                    'total' => $total_pages,
                    'current' => $current_page
                ));
                ?>
            </div>
        </div>
    <?php endif; ?>
</div> 