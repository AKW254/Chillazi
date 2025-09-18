<?php
function processOrder($mysqli, $customer_id, $orderData)
{
    $orderJson = json_encode($orderData, JSON_UNESCAPED_UNICODE);

    // Insert main order
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
        $orderData['subtotal'],
        $orderData['tax'],
        $orderData['delivery_fee'],
        $orderData['total'],
        $orderData['status'],
        $orderData['currency']
    );

    $stmt->execute();
    $order_id = $stmt->insert_id;
    $stmt->close();

    // Insert order items
    $stmt = $mysqli->prepare("INSERT INTO order_items (order_id, item_name, quantity, price, total) VALUES (?, ?, ?, ?, ?)");
    foreach ($orderData['items'] as $item) {
        $stmt->bind_param('isidd', $order_id, $item['name'], $item['quantity'], $item['price'], $item['total']);
        $stmt->execute();
    }
    $stmt->close();

    return $order_id;
}