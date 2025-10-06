<?php
function processOrder($mysqli, $customer_id, $orderItems)
{
    if (empty($orderItems)) return null;

    // Calculate totals directly from orderItems (they already have price & total)
    $subtotal = 0;
    foreach ($orderItems as $item) {
        $subtotal += $item['total'];
    }

    $tax = $subtotal * 0.16;
    $delivery = $subtotal > 1000 ? 0 : 100;
    $total = $subtotal + $tax + $delivery;

    $orderData = [
        'items' => $orderItems,
        'order_subtotal' => $subtotal,
        'order_tax' => $tax,
        'delivery_fee' => $delivery,
        'order_total' => $total,
        'order_status' => 'pending_confirmation',
        'order_currency' => 'KES'
    ];

    // Save single order intent to DB
    $stmt = $mysqli->prepare("
        INSERT INTO orders
        (order_customer_id, order_text, order_subtotal, order_tax, order_delivery_fee, order_total, order_status, order_currency)
        VALUES (?,?,?,?,?,?,?,?)
    ");
    $orderJson = json_encode($orderData, JSON_UNESCAPED_UNICODE);
    $stmt->bind_param(
        "isddddss",
        $customer_id,
        $orderJson,
        $subtotal,
        $tax,
        $delivery,
        $total,
        $orderData['order_status'],
        $orderData['order_currency']
    );
    $stmt->execute();
    $orderData['order_id'] = $stmt->insert_id;
    $stmt->close();

    return $orderData;
}
