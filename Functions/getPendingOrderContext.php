<?php

function getPendingOrderContext($mysqli, $customer_id)
{
    $stmt = $mysqli->prepare("
        SELECT *
        FROM orders
        WHERE order_customer_id = ?
        AND order_status = 'pending_confirmation' OR order_status = 'awaiting_payment'
        ORDER BY order_id DESC LIMIT 1
    ");
    $stmt->bind_param('i', $customer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $pendingOrder = $result->fetch_assoc();
    $stmt->close();

    if ($pendingOrder) {
        $decodedText = json_decode($pendingOrder['order_text'], true);

        if ($decodedText) {
            $pendingOrder['order_text'] = $decodedText;
        }

        return [
            'has_pending' => true,
            'order_data'  => [
                'order_id'       => $pendingOrder['order_id'],
                'items'          => $decodedText['items'] ?? [],
                'order_subtotal' => $pendingOrder['order_subtotal'],
                'order_tax'      => $pendingOrder['order_tax'],
                'delivery_fee'   => $pendingOrder['order_delivery_fee'],
                'order_total'    => $pendingOrder['order_total'],
                'order_status'   => $pendingOrder['order_status'],
                'order_currency' => $pendingOrder['order_currency'],
                'created_at'     => $pendingOrder['created_at']
            ],
            'context_message' => "\n\nIMPORTANT: Customer has pending order #" . $pendingOrder['order_id'] .
                " for KSH " . number_format($pendingOrder['order_total'], 2) .
                ". Status: " . $pendingOrder['order_status']
        ];
    }

    return ['has_pending' => false, 'order_data' => null, 'context_message' => ""];
}
