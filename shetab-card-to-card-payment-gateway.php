<?php
/**
 * Plugin Name: Shetab Card to Card Payment Gateway
 * Plugin URI: 
 * Description: یک درگاه پرداخت سفارشی برای تاییید خودکار کارت به کارت
 * Version: 1.0.0
 * Author: Reza HajRahimi (RezaHajRahimi@gmail.com)
 * Text Domain: shetab-card-to-card-payment-gateway
 */

if (!defined('ABSPATH')) {
    exit;
}

// بررسی وجود ووکامرس
if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    add_action('admin_notices', function() {
        echo '<div class="error"><p>برای استفاده از درگاه پرداخت شتاب کارت به کارت، نیاز به نصب و فعال‌سازی ووکامرس دارید.</p></div>';
    });
    return;
}

// تعریف ثابت‌های پلاگین
define('CPG_VERSION', '1.0.0');
define('CPG_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CPG_PLUGIN_URL', plugin_dir_url(__FILE__));

// فعال‌سازی پلاگین
register_activation_hook(__FILE__, 'cpg_activate');
function cpg_activate() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}cpg_transactions (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        order_id bigint(20) NOT NULL,
        amount decimal(10,2) NOT NULL,
        unique_amount decimal(10,2) NOT NULL,
        status varchar(20) NOT NULL,
        card_holder varchar(100) NULL,
        description text NULL,
        log text NULL,
        created_at datetime NOT NULL,
        expires_at datetime NOT NULL,
        updated_at datetime NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

// غیرفعال‌سازی پلاگین
register_deactivation_hook(__FILE__, 'cpg_deactivate');
function cpg_deactivate() {
    // پاک کردن جداول در صورت نیاز
}

// اضافه کردن منوی تنظیمات
add_action('admin_menu', 'cpg_add_admin_menu');
function cpg_add_admin_menu() {
    add_menu_page(
        'تنظیمات درگاه پرداخت',
        'درگاه پرداخت',
        'manage_options',
        'shetab-card-to-card-payment-gateway',
        'cpg_settings_page',
        'dashicons-money-alt',
        56
    );

    add_submenu_page(
        'shetab-card-to-card-payment-gateway',
        'تراکنش‌ها',
        'تراکنش‌ها',
        'manage_options',
        'shetab-transactions',
        'cpg_transactions_page'
    );

    // اضافه کردن استایل و اسکریپت به صفحه تنظیمات
    add_action('admin_enqueue_scripts', function($hook) {
        if ('toplevel_page_shetab-card-to-card-payment-gateway' !== $hook) {
            return;
        }
        
        wp_enqueue_style('dashicons');
        
        wp_add_inline_style('admin-bar', '
            .copy-field-container {
                display: flex;
                gap: 5px;
                margin-bottom: 5px;
            }
            .copy-button {
                display: flex !important;
                align-items: center;
                gap: 3px;
            }
            .copy-button .dashicons {
                font-size: 16px;
                width: 16px;
                height: 16px;
            }
        ');
        
        wp_enqueue_script('cpg-admin-script', '', array(), false, true);
        wp_add_inline_script('cpg-admin-script', '
            jQuery(document).ready(function($) {
                $(".copy-button").on("click", function() {
                    var targetId = $(this).data("clipboard-target");
                    var $input = $(targetId);
                    
                    $input.select();
                    document.execCommand("copy");
                    
                    var $button = $(this);
                    var originalText = $button.html();
                    $button.html("<span class=\"dashicons dashicons-yes\"></span> کپی شد!");
                    
                    setTimeout(function() {
                        $button.html(originalText);
                    }, 2000);
                });
            });
        ');
    });
}

// تابع تولید کلید API
function cpg_generate_api_key() {
    return bin2hex(random_bytes(32));
}

// صفحه تنظیمات
function cpg_settings_page() {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    if (isset($_POST['cpg_save_settings'])) {
        check_admin_referer('cpg_settings_nonce');
        
        $card_number = sanitize_text_field($_POST['cpg_card_number']);
        $card_holder = sanitize_text_field($_POST['cpg_card_holder']);
        $telegram_id = sanitize_text_field($_POST['cpg_telegram_id']);
        $whatsapp_number = sanitize_text_field($_POST['cpg_whatsapp_number']);
        
        // اعتبارسنجی شماره کارت
        if (strlen($card_number) !== 16 || !is_numeric($card_number)) {
            echo '<div class="notice notice-error"><p>شماره کارت باید 16 رقم باشد.</p></div>';
        } else {
            update_option('cpg_card_number', $card_number);
            update_option('cpg_card_holder', $card_holder);
            update_option('cpg_telegram_id', $telegram_id);
            update_option('cpg_whatsapp_number', $whatsapp_number);
            echo '<div class="notice notice-success"><p>تنظیمات با موفقیت ذخیره شد.</p></div>';
        }
    }

    if (isset($_POST['cpg_refresh_api_key'])) {
        check_admin_referer('cpg_settings_nonce');
        $new_api_key = cpg_generate_api_key();
        update_option('cpg_api_key', $new_api_key);
        echo '<div class="notice notice-success"><p>کلید API با موفقیت بروزرسانی شد.</p></div>';
    }
    
    // اگر کلید API وجود نداشت، یک کلید جدید بساز
    $api_key = get_option('cpg_api_key', '');
    if (empty($api_key)) {
        $api_key = cpg_generate_api_key();
        update_option('cpg_api_key', $api_key);
    }

    $card_number = get_option('cpg_card_number', '');
    $card_holder = get_option('cpg_card_holder', '');
    $telegram_id = get_option('cpg_telegram_id', '');
    $whatsapp_number = get_option('cpg_whatsapp_number', '');
    $endpoint_url = home_url('/wp-json/shetab-card-to-card-payment-gateway/v1/verify-payment');
    ?>
    <div class="wrap">
        <h1>تنظیمات درگاه پرداخت</h1>
        <form method="post" action="">
            <?php wp_nonce_field('cpg_settings_nonce'); ?>
            <table class="form-table">
                <tr>
                    <th scope="row">شماره کارت</th>
                    <td>
                        <input type="text" name="cpg_card_number" value="<?php echo esc_attr($card_number); ?>" 
                               class="regular-text" pattern="[0-9]{16}" maxlength="16" required>
                        <p class="description">شماره کارت باید 16 رقم باشد</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">نام دارنده کارت</th>
                    <td>
                        <input type="text" name="cpg_card_holder" value="<?php echo esc_attr($card_holder); ?>" 
                               class="regular-text" required>
                    </td>
                </tr>
                <tr>
                    <th scope="row">آیدی تلگرام</th>
                    <td>
                        <input type="text" name="cpg_telegram_id" value="<?php echo esc_attr($telegram_id); ?>" 
                               class="regular-text" placeholder="مثال: @username">
                        <p class="description">آیدی تلگرام خود را وارد کنید (با @ شروع شود)</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">شماره واتساپ</th>
                    <td>
                        <input type="text" name="cpg_whatsapp_number" value="<?php echo esc_attr($whatsapp_number); ?>" 
                               class="regular-text" placeholder="مثال: 09123456789">
                        <p class="description">شماره واتساپ خود را بدون صفر اول وارد کنید</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">کلید API</th>
                    <td>
                        <div class="copy-field-container">
                            <input type="text" id="api_key_field" value="<?php echo esc_attr($api_key); ?>" class="regular-text" readonly>
                            <button type="button" class="button button-secondary copy-button" data-clipboard-target="#api_key_field">
                                <span class="dashicons dashicons-clipboard"></span>
                                کپی
                            </button>
                        </div>
                        <input type="submit" name="cpg_refresh_api_key" class="button button-secondary" value="تولید کلید جدید" 
                            onclick="return confirm('آیا مطمئن هستید؟ کلید قبلی دیگر کار نخواهد کرد.');">
                        <p class="description">این کلید باید در هدر درخواست‌ها به عنوان Authorization استفاده شود.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">آدرس API</th>
                    <td>
                        <div class="copy-field-container">
                            <input type="text" id="api_url_field" value="<?php echo esc_url($endpoint_url); ?>" class="regular-text" readonly>
                            <button type="button" class="button button-secondary copy-button" data-clipboard-target="#api_url_field">
                                <span class="dashicons dashicons-clipboard"></span>
                                کپی
                            </button>
                        </div>
                        <p class="description">
                            برای تایید پرداخت، یک درخواست POST به این آدرس با پارامترهای زیر ارسال کنید:<br>
                            - هدر: <code>Authorization: [کلید API]</code><br>
                            - بدنه: <code>{"amount": "مبلغ", "order_id": "شناسه سفارش"}</code>
                        </p>
                    </td>
                </tr>
            </table>
            <p class="submit">
                <input type="submit" name="cpg_save_settings" class="button button-primary" value="ذخیره تنظیمات">
            </p>
        </form>
    </div>
    <?php
}

// ثبت درگاه پرداخت جدید
add_action('woocommerce_blocks_loaded', 'cpg_init_payment_gateway');
function cpg_init_payment_gateway() {
    if (!class_exists('Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType')) {
        return;
    }

    // ثبت درگاه پرداخت برای بلوک‌های ووکامرس
    add_action('woocommerce_payment_gateways', 'cpg_add_gateway_class');
    
    class WC_Shetab_Card_To_Card_Payment_Gateway extends WC_Payment_Gateway {
        public function __construct() {
            $this->id = 'shetab_card_to_card';
            $this->icon = plugin_dir_url(__FILE__) . 'logo.jpeg'; 
            $this->has_fields = false;
            $this->method_title = 'درگاه پرداخت کارت به کارت';
            $this->method_description = 'پرداخت از طریق کارت به کارت با تایید خودکار';
            
            $this->supports = array(
                'products',
                'refunds',
                'pre-orders',
                'payment_form'
            );
            
            $this->init_form_fields();
            $this->init_settings();
            
            $this->title = $this->get_option('title');
            $this->description = $this->get_option('description');
            $this->enabled = $this->get_option('enabled');
            
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        }
    }
}

// ثبت درگاه پرداخت برای بلوک‌های ووکامرس
class WC_Shetab_Gateway_Blocks_Support extends Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType {
    private $gateway;
    
    public function __construct() {
        $this->gateway = new WC_Shetab_Card_To_Card_Payment_Gateway();
    }
    
    public function initialize() {
        $this->settings = get_option('woocommerce_shetab_card_to_card_settings', array());
    }
    
    public function is_active() {
        return $this->gateway->is_available();
    }
    
    public function get_payment_method_script_handles() {
        wp_register_script(
            'shetab-blocks-integration',
            plugins_url('js/blocks-integration.js', __FILE__),
            array('wc-blocks-registry', 'wc-settings', 'wp-element', 'wp-html-entities', 'wp-i18n'),
            null,
            true
        );
        return array('shetab-blocks-integration');
    }
    
    public function get_payment_method_data() {
        return array(
            'title' => $this->gateway->title,
            'description' => $this->gateway->description,
            'supports' => $this->gateway->supports,
        );
    }
}

// ثبت اسکریپت‌های مورد نیاز
add_action('init', function() {
    if (!class_exists('Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry')) {
        return;
    }
    
    add_action(
        'woocommerce_blocks_payment_method_type_registration',
        function(Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry) {
            $payment_method_registry->register(new WC_Shetab_Gateway_Blocks_Support());
        }
    );
});

// اضافه کردن درگاه پرداخت به ووکامرس
add_action('woocommerce_payment_gateways', 'cpg_add_gateway_class');
function cpg_add_gateway_class($gateways) {
    $gateways[] = 'WC_Shetab_Card_To_Card_Payment_Gateway';
    return $gateways;
}

// تعریف کلاس درگاه پرداخت
add_action('plugins_loaded', function() {
    // بررسی وجود ووکامرس
    if (!class_exists('WooCommerce') || !class_exists('WC_Payment_Gateway')) {
        return;
    }
    
class WC_Shetab_Card_To_Card_Payment_Gateway extends WC_Payment_Gateway {
    public function __construct() {
            $this->id = 'shetab_card_to_card';
            $this->icon = plugin_dir_url(__FILE__) . 'logo.jpeg'; 
        $this->has_fields = false;
            $this->method_title = 'درگاه پرداخت کارت به کارت';
            $this->method_description = 'پرداخت از طریق کارت به کارت با تایید خودکار';
        
            // پشتیبانی از ویژگی‌های ووکامرس
        $this->supports = array(
                'products',
                'refunds',
                'pre-orders'
        );
        
            // بارگذاری تنظیمات
        $this->init_form_fields();
        $this->init_settings();
        
            // تنظیم متغیرها
        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');
            $this->enabled = $this->get_option('enabled');
        
            // Actions
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
            add_action('woocommerce_receipt_' . $this->id, array($this, 'receipt_page'));
            add_action('woocommerce_api_' . $this->id, array($this, 'verify_payment'));
    }
    
    public function init_form_fields() {
        $this->form_fields = array(
            'enabled' => array(
                'title'       => 'فعال/غیرفعال',
                'type'        => 'checkbox',
                    'label'       => 'فعال کردن درگاه پرداخت کارت به کارت',
                    'default'     => 'yes'
            ),
            'title' => array(
                'title'       => 'عنوان',
                'type'        => 'text',
                'description' => 'عنوانی که کاربر در صفحه پرداخت می‌بیند',
                    'default'     => 'پرداخت کارت به کارت',
                'desc_tip'    => true,
            ),
            'description' => array(
                'title'       => 'توضیحات',
                'type'        => 'textarea',
                'description' => 'توضیحاتی که کاربر در صفحه پرداخت می‌بیند',
                    'default'     => 'پرداخت مبلغ سفارش از طریق کارت به کارت با تایید خودکار'
                )
        );
    }
    
    public function process_payment($order_id) {
            $order = wc_get_order($order_id);
            
            // ایجاد تراکنش در دیتابیس
        global $wpdb;
            $unique_amount = $order->get_total() + rand(1, 999) / 1000;
        
        $wpdb->insert(
            $wpdb->prefix . 'cpg_transactions',
            array(
                'order_id' => $order_id,
                    'amount' => $order->get_total(),
                'unique_amount' => $unique_amount,
                'status' => 'pending',
                'created_at' => current_time('mysql'),
                    'expires_at' => date('Y-m-d H:i:s', strtotime('+24 hours')),
            ),
            array('%d', '%f', '%f', '%s', '%s', '%s')
        );
        
            // تغییر وضعیت سفارش به در انتظار پرداخت
            $order->update_status('pending', __('در انتظار پرداخت کارت به کارت', 'woocommerce'));
            
            // خالی کردن سبد خرید
            WC()->cart->empty_cart();
            
            // انتقال به صفحه پرداخت
            return array(
                'result'   => 'success',
                'redirect' => $order->get_checkout_payment_url(true)
            );
        }
        
        public function receipt_page($order_id) {
            $order = wc_get_order($order_id);
            $card_number = get_option('cpg_card_number');
            $card_holder = get_option('cpg_card_holder');
            
            global $wpdb;
            $transaction = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}cpg_transactions WHERE order_id = %d ORDER BY id DESC LIMIT 1",
                $order_id
            ));
            
            echo '<div class="card-payment-info">';
            echo '<h3>اطلاعات پرداخت کارت به کارت</h3>';
            echo '<p>لطفاً مبلغ دقیق ' . wc_price($transaction->unique_amount) . ' را به شماره کارت زیر واریز نمایید:</p>';
            echo '<div class="card-details">';
            echo '<p><strong>شماره کارت:</strong> ' . chunk_split($card_number, 4, ' ') . '</p>';
            echo '<p><strong>به نام:</strong> ' . esc_html($card_holder) . '</p>';
            echo '</div>';
            echo '<p class="warning">توجه: پرداخت دقیقاً با مبلغ اعلام شده انجام شود.</p>';
            echo '</div>';
        }
        
        public function can_refund_order($order) {
            return $order && $order->get_transaction_id();
        }
    }
});

// اضافه کردن استایل به صفحه پرداخت
add_action('wp_enqueue_scripts', function() {
    if (is_checkout_pay_page()) {
        wp_add_inline_style('woocommerce-layout', '
            .card-payment-info {
                background: #f8f8f8;
                padding: 20px;
                border-radius: 5px;
                margin-bottom: 20px;
            }
            .card-details {
                background: #fff;
                padding: 15px;
                border-radius: 4px;
                margin: 10px 0;
            }
            .warning {
                color: #721c24;
                background-color: #f8d7da;
                border: 1px solid #f5c6cb;
                padding: 10px;
                border-radius: 4px;
                margin-top: 15px;
            }
        ');
    }
});

// اضافه کردن endpoint برای تایید پرداخت
add_action('rest_api_init', function () {
    register_rest_route('shetab-card-to-card-payment-gateway/v1', '/verify-payment', array(
        'methods' => 'POST',
        'callback' => 'cpg_verify_payment',
        'permission_callback' => function (WP_REST_Request $request) {
            $api_key = get_option('cpg_api_key');
            $headers = $request->get_headers();
            
            // بررسی کلید API
            if (!isset($headers['authorization'][0]) || $headers['authorization'][0] !== $api_key) {
                return false;
            }
            
            // بررسی CSRF token
            $nonce = $request->get_header('X-WP-Nonce');
            if (!wp_verify_nonce($nonce, 'wp_rest')) {
                return false;
            }
            
            return true;
        }
    ));
});

function cpg_verify_payment($request) {
    global $wpdb;
    
    $params = $request->get_params();
    
    if (!isset($params['amount']) || !isset($params['order_id'])) {
        return new WP_Error('missing_params', 'پارامترهای مورد نیاز ارسال نشده‌اند', array('status' => 400));
    }
    
    $transaction = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}cpg_transactions WHERE order_id = %d AND unique_amount = %f AND status = 'pending' AND expires_at > NOW()",
        $params['order_id'],
        $params['amount']
    ));
    
    if (!$transaction) {
        return new WP_Error('invalid_transaction', 'تراکنش نامعتبر است یا منقضی شده است', array('status' => 400));
    }
    
    // به‌روزرسانی وضعیت تراکنش
    $wpdb->update(
        $wpdb->prefix . 'cpg_transactions',
        array(
            'status' => 'completed',
            'updated_at' => current_time('mysql')
        ),
        array('id' => $transaction->id),
        array('%s', '%s'),
        array('%d')
    );
    
    // افزودن لاگ
    cpg_add_log($transaction->id, 'پرداخت با موفقیت تایید شد');
    
    // به‌روزرسانی وضعیت سفارش
    $order = wc_get_order($params['order_id']);
    $order->payment_complete();
    $order->add_order_note('پرداخت از طریق کارت به کارت تایید شد.');
    
    return array(
        'success' => true,
        'message' => 'پرداخت با موفقیت تایید شد'
    );
}

function cpg_transactions_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

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
    <div class="wrap">
        <h1>تراکنش‌ها</h1>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>شناسه</th>
                    <th>شماره سفارش</th>
                    <th>مبلغ اصلی</th>
                    <th>مبلغ یکتا</th>
                    <th>وضعیت</th>
                    <th>تاریخ ایجاد</th>
                    <th>مهلت پرداخت</th>
                    <th>توضیحات</th>
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
                        <td><?php echo number_format($transaction->amount); ?> تومان</td>
                        <td><?php echo number_format($transaction->unique_amount); ?> تومان</td>
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
    <?php
}

function cpg_add_log($transaction_id, $message) {
    global $wpdb;
    
    $transaction = $wpdb->get_row($wpdb->prepare(
        "SELECT log FROM {$wpdb->prefix}cpg_transactions WHERE id = %d",
        $transaction_id
    ));
    
    $current_log = $transaction->log ? json_decode($transaction->log, true) : array();
    $current_log[] = array(
        'time' => current_time('mysql'),
        'message' => $message
    );
    
    $wpdb->update(
        $wpdb->prefix . 'cpg_transactions',
        array(
            'log' => json_encode($current_log),
            'updated_at' => current_time('mysql')
        ),
        array('id' => $transaction_id),
        array('%s', '%s'),
        array('%d')
    );
}

// اضافه کردن استایل و اسکریپت به هدر
add_action('wp_enqueue_scripts', 'cpg_enqueue_scripts');
function cpg_enqueue_scripts() {
    if (is_checkout() && !is_checkout_pay_page()) {
        wp_enqueue_style('cpg-payment-style', CPG_PLUGIN_URL . 'assets/css/payment.css', array(), CPG_VERSION);
        wp_enqueue_script('cpg-payment-script', CPG_PLUGIN_URL . 'assets/js/payment.js', array('jquery'), CPG_VERSION, true);
    }
} 