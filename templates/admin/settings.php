<?php defined('ABSPATH') || exit; ?>

<div class="wrap">
    <h1><?php _e('تنظیمات درگاه پرداخت', 'shetab-card-to-card-payment-gateway'); ?></h1>
    
    <?php if (isset($_POST['cpg_save_settings']) && check_admin_referer('cpg_settings_nonce')): ?>
        <?php
        $card_number = sanitize_text_field($_POST['cpg_card_number']);
        $card_holder = sanitize_text_field($_POST['cpg_card_holder']);
        $telegram_id = sanitize_text_field($_POST['cpg_telegram_id']);
        $whatsapp_number = sanitize_text_field($_POST['cpg_whatsapp_number']);
        
        // اعتبارسنجی شماره کارت
        if (strlen($card_number) !== 16 || !is_numeric($card_number)) {
            echo '<div class="notice notice-error"><p>' . 
                 __('شماره کارت باید 16 رقم باشد.', 'shetab-card-to-card-payment-gateway') . 
                 '</p></div>';
        } else {
            update_option('cpg_card_number', $card_number);
            update_option('cpg_card_holder', $card_holder);
            update_option('cpg_telegram_id', $telegram_id);
            update_option('cpg_whatsapp_number', $whatsapp_number);
            echo '<div class="notice notice-success"><p>' . 
                 __('تنظیمات با موفقیت ذخیره شد.', 'shetab-card-to-card-payment-gateway') . 
                 '</p></div>';
        }
        ?>
    <?php endif; ?>
    
    <?php if (isset($_POST['cpg_refresh_api_key']) && check_admin_referer('cpg_settings_nonce')): ?>
        <?php
        $new_api_key = bin2hex(random_bytes(32));
        update_option('cpg_api_key', $new_api_key);
        echo '<div class="notice notice-success"><p>' . 
             __('کلید API با موفقیت بروزرسانی شد.', 'shetab-card-to-card-payment-gateway') . 
             '</p></div>';
        ?>
    <?php endif; ?>
    
    <?php
    // دریافت مقادیر تنظیمات
    $api_key = get_option('cpg_api_key', '');
    if (empty($api_key)) {
        $api_key = bin2hex(random_bytes(32));
        update_option('cpg_api_key', $api_key);
    }
    
    $card_number = get_option('cpg_card_number', '');
    $card_holder = get_option('cpg_card_holder', '');
    $telegram_id = get_option('cpg_telegram_id', '');
    $whatsapp_number = get_option('cpg_whatsapp_number', '');
    $endpoint_url = home_url('/wp-json/shetab-card-to-card-payment-gateway/v1/verify-payment');
    ?>
    
    <form method="post" action="">
        <?php wp_nonce_field('cpg_settings_nonce'); ?>
        <table class="form-table">
            <tr>
                <th scope="row"><?php _e('شماره کارت', 'shetab-card-to-card-payment-gateway'); ?></th>
                <td>
                    <input type="text" name="cpg_card_number" value="<?php echo esc_attr($card_number); ?>" 
                           class="regular-text" pattern="[0-9]{16}" maxlength="16" required>
                    <p class="description"><?php _e('شماره کارت باید 16 رقم باشد', 'shetab-card-to-card-payment-gateway'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('نام دارنده کارت', 'shetab-card-to-card-payment-gateway'); ?></th>
                <td>
                    <input type="text" name="cpg_card_holder" value="<?php echo esc_attr($card_holder); ?>" 
                           class="regular-text" required>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('آیدی تلگرام', 'shetab-card-to-card-payment-gateway'); ?></th>
                <td>
                    <input type="text" name="cpg_telegram_id" value="<?php echo esc_attr($telegram_id); ?>" 
                           class="regular-text" placeholder="مثال: @username">
                    <p class="description"><?php _e('آیدی تلگرام خود را وارد کنید (با @ شروع شود)', 'shetab-card-to-card-payment-gateway'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('شماره واتساپ', 'shetab-card-to-card-payment-gateway'); ?></th>
                <td>
                    <input type="text" name="cpg_whatsapp_number" value="<?php echo esc_attr($whatsapp_number); ?>" 
                           class="regular-text" placeholder="مثال: 09123456789">
                    <p class="description"><?php _e('شماره واتساپ خود را بدون صفر اول وارد کنید', 'shetab-card-to-card-payment-gateway'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('کلید API', 'shetab-card-to-card-payment-gateway'); ?></th>
                <td>
                    <div class="copy-field-container">
                        <input type="text" id="api_key_field" value="<?php echo esc_attr($api_key); ?>" class="regular-text" readonly>
                        <button type="button" class="button button-secondary copy-button" data-clipboard-target="#api_key_field">
                            <span class="dashicons dashicons-clipboard"></span>
                            <?php _e('کپی', 'shetab-card-to-card-payment-gateway'); ?>
                        </button>
                    </div>
                    <input type="submit" name="cpg_refresh_api_key" class="button button-secondary" 
                           value="<?php _e('تولید کلید جدید', 'shetab-card-to-card-payment-gateway'); ?>" 
                           onclick="return confirm('<?php _e('آیا مطمئن هستید؟ کلید قبلی دیگر کار نخواهد کرد.', 'shetab-card-to-card-payment-gateway'); ?>');">
                    <p class="description"><?php _e('این کلید باید در هدر درخواست‌ها به عنوان Authorization استفاده شود.', 'shetab-card-to-card-payment-gateway'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('آدرس API', 'shetab-card-to-card-payment-gateway'); ?></th>
                <td>
                    <div class="copy-field-container">
                        <input type="text" id="api_url_field" value="<?php echo esc_url($endpoint_url); ?>" class="regular-text" readonly>
                        <button type="button" class="button button-secondary copy-button" data-clipboard-target="#api_url_field">
                            <span class="dashicons dashicons-clipboard"></span>
                            <?php _e('کپی', 'shetab-card-to-card-payment-gateway'); ?>
                        </button>
                    </div>
                    <p class="description">
                        <?php _e('برای تایید پرداخت، یک درخواست POST به این آدرس با پارامترهای زیر ارسال کنید:', 'shetab-card-to-card-payment-gateway'); ?><br>
                        - <?php _e('هدر:', 'shetab-card-to-card-payment-gateway'); ?> <code>Authorization: [کلید API]</code><br>
                        - <?php _e('بدنه:', 'shetab-card-to-card-payment-gateway'); ?> <code>{"amount": "مبلغ", "order_id": "شناسه سفارش"}</code>
                    </p>
                </td>
            </tr>
        </table>
        <p class="submit">
            <input type="submit" name="cpg_save_settings" class="button button-primary" 
                   value="<?php _e('ذخیره تنظیمات', 'shetab-card-to-card-payment-gateway'); ?>">
        </p>
    </form>
</div> 