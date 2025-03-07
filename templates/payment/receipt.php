<?php defined('ABSPATH') || exit; ?>

<?php
$order = wc_get_order($order_id);
$card_number = get_option('cpg_card_number');
$card_holder = get_option('cpg_card_holder');
$telegram_id = get_option('cpg_telegram_id');
$whatsapp_number = get_option('cpg_whatsapp_number');

global $wpdb;
$transaction = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}cpg_transactions WHERE order_id = %d ORDER BY id DESC LIMIT 1",
    $order_id
));

if (!$transaction) {
    return;
}

$expires_time = strtotime($transaction->expires_at);
?>

<div class="cpg-payment-info">
    <div class="cpg-timer-container">
        <div class="cpg-timer" data-expires="<?php echo esc_attr($transaction->expires_at); ?>">
            <svg class="cpg-timer-circle" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
                <circle class="cpg-timer-path-elapsed" cx="50" cy="50" r="45" />
                <circle class="cpg-timer-path-remaining" cx="50" cy="50" r="45" />
            </svg>
            <div class="cpg-timer-label"></div>
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
        
        <p><?php printf(
            __('لطفاً مبلغ دقیق %s را به شماره کارت زیر واریز نمایید:', 'shetab-card-to-card-payment-gateway'),
            wc_price($transaction->unique_amount)
        ); ?></p>
        
        <div class="cpg-card-info">
            <div class="cpg-card-number" title="<?php _e('برای کپی کلیک کنید', 'shetab-card-to-card-payment-gateway'); ?>">
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