<?php
function getPendingOrderContext($mysqli, $customer_id)
{
    $stmt = $mysqli->prepare("
        SELECT order_id, order_total, order_status 
        FROM orders 
        WHERE order_customer_id = ? 
        AND order_status IN ('pending_confirmation', 'pending_payment') 
        ORDER BY order_id DESC 
        
    ");
    $stmt->bind_param('i', $customer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $pendingOrder = $result->fetch_assoc();
    $stmt->close();

    if ($pendingOrder) {
        return "\n\nIMPORTANT: Customer has pending order #" . $pendingOrder['order_id'] .
            " for KSH " . number_format($pendingOrder['order_total'], 2) .
            ". Status: " . $pendingOrder['order_status'];
    }

    return "";
}
