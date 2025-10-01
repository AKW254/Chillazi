<?php
function processOrder($mysqli, $customer_id, $orderData)
{
if (!isset($orderData['order_data'])) {
return null;
}

$order = $orderData['order_data'];
$orderJson = json_encode($order, JSON_UNESCAPED_UNICODE);

$stmt = $mysqli->prepare("
INSERT INTO orders (
order_customer_id, order_text, order_subtotal, order_tax,
order_delivery_fee, order_total, order_status, order_currency
) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
");
$stmt->bind_param(
'isddddss',
$customer_id,
$orderJson,
$order['order_subtotal'],
$order['order_tax'],
$order['delivery_fee'],
$order['order_total'],
$order['order_currency'],
$order['order_status']
);
$stmt->execute();
$order_id = $stmt->insert_id;
$stmt->close();

// return normalized
$order['order_id'] = $order_id;
return ['order_data' => $order];
}