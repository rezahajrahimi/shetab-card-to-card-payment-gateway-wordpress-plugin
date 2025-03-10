# راهنمای استفاده از API درگاه پرداخت کارت به کارت

این پلاگین دارای API‌های RESTful است که به شما امکان می‌دهد از طریق برنامه‌های خارجی با آن ارتباط برقرار کنید.

## نکات مهم

1. برای دسترسی به API، باید از آدرس پایه وردپرس به همراه مسیر API استفاده کنید:
   ```
   https://your-site.com/wp-json/shetab-card-to-card-payment-gateway/v1/
   ```

2. برای برخی از درخواست‌ها، نیاز به کلید API دارید که در تنظیمات پلاگین قابل مشاهده است.

## مسیرهای API

### 1. بررسی وضعیت تراکنش

این API به شما امکان می‌دهد وضعیت یک تراکنش را بررسی کنید.

- **URL**: `/check-status/{order_id}`
- **روش**: GET
- **نیاز به احراز هویت**: خیر

#### پارامترها:

- `order_id`: شناسه سفارش (عدد)

#### پاسخ موفق:

```json
{
  "success": true,
  "status": "pending",
  "amount": 1000000,
  "expires_at": "2023-05-01 12:30:00"
}
```

#### پاسخ خطا:

```json
{
  "code": "transaction_not_found",
  "message": "تراکنشی برای این سفارش یافت نشد",
  "data": {
    "status": 404
  }
}
```

### 2. تایید پرداخت

این API به شما امکان می‌دهد یک پرداخت را تایید کنید.

- **URL**: `/verify-payment`
- **روش**: POST
- **نیاز به احراز هویت**: بله (کلید API)

#### هدرها:

- `Authorization`: کلید API

#### پارامترهای درخواست:

```json
{
  "order_id": 123,
  "amount": 1000000
}
```

#### پاسخ موفق:

```json
{
  "success": true,
  "message": "پرداخت با موفقیت تایید شد"
}
```

#### پاسخ خطا:

```json
{
  "code": "missing_params",
  "message": "پارامترهای مورد نیاز ارسال نشده‌اند",
  "data": {
    "status": 400
  }
}
```

## نمونه کد PHP

```php
// بررسی وضعیت تراکنش
$response = wp_remote_get('https://your-site.com/wp-json/shetab-card-to-card-payment-gateway/v1/check-status/123');
$body = json_decode(wp_remote_retrieve_body($response), true);

// تایید پرداخت
$api_key = 'YOUR_API_KEY';
$response = wp_remote_post('https://your-site.com/wp-json/shetab-card-to-card-payment-gateway/v1/verify-payment', [
    'headers' => [
        'Authorization' => $api_key
    ],
    'body' => [
        'order_id' => 123,
        'amount' => 1000000
    ]
]);
$body = json_decode(wp_remote_retrieve_body($response), true);
```

## نمونه کد JavaScript

```javascript
// بررسی وضعیت تراکنش
fetch('https://your-site.com/wp-json/shetab-card-to-card-payment-gateway/v1/check-status/123')
  .then(response => response.json())
  .then(data => console.log(data));

// تایید پرداخت
const apiKey = 'YOUR_API_KEY';
fetch('https://your-site.com/wp-json/shetab-card-to-card-payment-gateway/v1/verify-payment', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'Authorization': apiKey
  },
  body: JSON.stringify({
    order_id: 123,
    amount: 1000000
  })
})
  .then(response => response.json())
  .then(data => console.log(data));
``` 