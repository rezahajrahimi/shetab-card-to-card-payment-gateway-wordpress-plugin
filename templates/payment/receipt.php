<?php defined('ABSPATH') || exit; ?>

<?php
// بررسی وضعیت تراکنش
global $wpdb;
$transaction = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}cpg_transactions WHERE order_id = %d ORDER BY id DESC LIMIT 1",
    $order_id
));

if (!$transaction) {
    return;
}

// اگر تراکنش تایید شده است، ریدایرکت به صفحه تشکر
if ($transaction->status === 'completed') {
    wp_redirect($order->get_checkout_order_received_url());
    exit;
}

$order = wc_get_order($order_id);
$card_number = get_option('cpg_card_number');
$card_holder = get_option('cpg_card_holder');
$telegram_id = get_option('cpg_telegram_id');
$whatsapp_number = get_option('cpg_whatsapp_number');

// محاسبه زمان باقی‌مانده
$expires_time = strtotime($transaction->expires_at);
$current_time = time();
$remaining_time = $expires_time - $current_time;

// حذف هوک‌های اضافی که ممکن است باعث تکرار شوند
remove_all_actions('woocommerce_receipt_' . $order->get_payment_method());
remove_all_actions('woocommerce_thankyou_' . $order->get_payment_method());

// اگر زمان تمام شده، پیام مناسب نمایش داده شود
if ($remaining_time <= 0) {
    echo '<div class="cpg-payment-info expired">';
    echo '<p class="cpg-expired-message">' . __('مهلت پرداخت به پایان رسیده است. لطفاً سفارش جدیدی ثبت کنید.', 'shetab-card-to-card-payment-gateway') . '</p>';
    echo '</div>';
    return;
}

wp_enqueue_style('cpg-payment-style');
wp_enqueue_script('cpg-payment-script');
?>

<div class="cpg-payment-info" id="cpg-payment-container">
    <div class="cpg-timer-container">
        <div class="cpg-timer" data-expires="<?php echo esc_attr(date('c', $expires_time)); ?>">
            <svg class="cpg-timer-circle" viewBox="0 0 100 100">
                <circle class="cpg-timer-path-elapsed" cx="50" cy="50" r="45" stroke="#f0f0f0" stroke-width="7" fill="none"/>
                <circle class="cpg-timer-path-remaining" cx="50" cy="50" r="45" stroke="#4CAF50" stroke-width="7" fill="none" transform="rotate(-90 50 50)"/>
            </svg>
            <div class="cpg-timer-label">10:00</div>
        </div>
        <p class="cpg-expires-text">
            <?php echo sprintf(
                __('مهلت پرداخت تا %s', 'shetab-card-to-card-payment-gateway'),
                wp_date('H:i', $expires_time)
            ); ?>
        </p>
    </div>
    
    <div class="cpg-payment-details">
        <h3><?php _e('اطلاعات پرداخت کارت به کارت', 'shetab-card-to-card-payment-gateway'); ?></h3>
        
        <div class="cpg-amount">
            <span class="cpg-label"><?php _e('مبلغ قابل پرداخت:', 'shetab-card-to-card-payment-gateway'); ?></span>
            <span class="cpg-value"><?php echo number_format($transaction->unique_amount, 0, '', ','); ?> <?php _e('تومان', 'shetab-card-to-card-payment-gateway'); ?></span>
        </div>
        
        <p class="cpg-amount-text">
            <?php printf(
                __('لطفاً مبلغ دقیق %s را به شماره کارت زیر واریز نمایید:', 'shetab-card-to-card-payment-gateway'),
                '<strong>' . wc_price($transaction->unique_amount) . '</strong>'
            ); ?>
        </p>
        
        <div class="cpg-card-info">
            <div class="cpg-card-number" style="direction: ltr; text-align: left; font-family: monospace;" title="<?php _e('برای کپی کلیک کنید', 'shetab-card-to-card-payment-gateway'); ?>">
                <?php echo chunk_split($card_number, 4, ' '); ?>
            </div>
            <div class="cpg-card-holder"><?php echo esc_html($card_holder); ?></div>
        </div>
        
        <div class="cpg-amount-warning">
            <?php _e('توجه: برای تایید خودکار، لطفاً دقیقاً مبلغ اعلام شده را واریز نمایید.', 'shetab-card-to-card-payment-gateway'); ?>
        </div>
        
        <?php if ($telegram_id || $whatsapp_number): ?>
            <div class="cpg-social-media">
                <p class="cpg-support-text">
                    <?php _e('در صورت نیاز به پشتیبانی:', 'shetab-card-to-card-payment-gateway'); ?>
                </p>
                
                <?php if ($telegram_id): ?>
                    <a href="https://t.me/<?php echo esc_attr(ltrim($telegram_id, '@')); ?>" 
                       class="cpg-social-button cpg-telegram" target="_blank" rel="noopener">
                        <img src="<?php echo CPG_PLUGIN_URL; ?>assets/images/telegram.svg" alt="Telegram">
                        <?php _e('پشتیبانی تلگرام', 'shetab-card-to-card-payment-gateway'); ?>
                    </a>
                <?php endif; ?>
                
                <?php if ($whatsapp_number): ?>
                    <a href="https://wa.me/<?php echo esc_attr($whatsapp_number); ?>" 
                       class="cpg-social-button cpg-whatsapp" target="_blank" rel="noopener">
                        <img src="<?php echo CPG_PLUGIN_URL; ?>assets/images/whatsapp.svg" alt="WhatsApp">
                        <?php _e('پشتیبانی واتساپ', 'shetab-card-to-card-payment-gateway'); ?>
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
// اضافه کردن کد بررسی وضعیت تراکنش
jQuery(document).ready(function($) {
    function checkTransactionStatus() {
        $.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            type: 'POST',
            data: {
                action: 'check_transaction_status',
                order_id: <?php echo $order_id; ?>,
                nonce: '<?php echo wp_create_nonce('check_transaction_status'); ?>'
            },
            success: function(response) {
                if (response.success && response.data.status === 'completed') {
                    window.location.href = '<?php echo $order->get_checkout_order_received_url(); ?>';
                }
            }
        });
    }
    
    // بررسی هر 10 ثانیه
    setInterval(checkTransactionStatus, 10000);
});
</script> 