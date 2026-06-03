<?php

// payFast calls on  this URL silently to confirm payment.


require_once 'includes/db.php';

// payfast sandbox credentials 
$merchant_id  = '10048639';
$merchant_key = 'yj45y8yq0lmx1';
$passphrase   = 'SAIIGOTPRICED2GO';


// get the posted data from payfast
$pfData = $_POST;

// Verify the signature
$pfParamString = '';
foreach ($pfData as $key => $val) {
    if ($key !== 'signature') {
        $pfParamString .= $key . '=' . urlencode(stripslashes($val)) . '&';
    }
}
$pfParamString = rtrim($pfParamString, '&');
$pfParamString .= '&passphrase=' . urlencode($passphrase);
$signature = md5($pfParamString);

if ($signature !== $pfData['signature']) {
    //  ignore signature
    http_response_code(400);
    exit;
}

//check the payment status
if ($pfData['payment_status'] === 'COMPLETE') {
    $m_payment_id = $pfData['m_payment_id'] ?? '';
    $amount_gross = $pfData['amount_gross'] ?? 0;

    if ($m_payment_id) {
        // update the payment status to paid
        $pdo->prepare("UPDATE orders SET status = 'paid' WHERE payment_ref = ?")
            ->execute([$m_payment_id]);

        // gwet the listing_id from the order and mark it sold
        $order = $pdo->prepare("SELECT listing_id FROM orders WHERE payment_ref = ?");
        $order->execute([$m_payment_id]);
        $row = $order->fetch();
        if ($row) {
            $pdo->prepare("UPDATE listings SET status = 'sold' WHERE listing_id = ?")
                ->execute([$row['listing_id']]);
        }

        // insert into payments table
        $pdo->prepare("INSERT INTO payments (order_id, amount, method, status, transaction_ref, paid_at)
                       SELECT order_id, ?, 'payfast', 'confirmed', ?, NOW()
                       FROM orders WHERE payment_ref = ?")
            ->execute([$amount_gross, $pfData['pf_payment_id'] ?? '', $m_payment_id]);
    }
}

http_response_code(200);
echo 'OK';
