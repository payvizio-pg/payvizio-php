# Payvizio PHP SDK

```bash
composer require payvizio/sdk
```

```php
<?php
require __DIR__ . '/vendor/autoload.php';

$pv = new \Payvizio\Payvizio(['apiKey' => getenv('PAYVIZIO_API_KEY')]);

$session = $pv->payments->create([
    'orderId'  => 'ord_42',
    'amount'   => 1499.00,
    'currency' => 'INR',
], idempotencyKey: 'create-ord_42');

$pv->payments->capture($session['sessionId'], 500.00);

$pv->refunds->create([
    'sessionId' => $session['sessionId'],
    'amount'    => 200.00,
    'reason'    => 'requested_by_customer',
]);

// Webhook — pass the raw request body
$rawBody  = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_PAYVIZIO_SIGNATURE'] ?? null;
$ok = $pv->webhooks->verify($rawBody, $signature, getenv('PAYVIZIO_WEBHOOK_SECRET'));
```

PHP 8.1+. Uses ext-curl, ext-json, ext-hash. Errors throw `\Payvizio\PayvizioError`.
