<?php

function generateOrderSummary($orderData) {
$summary = "🧾 Order Summary (" . $orderData['created_at'] . ")\n";
$summary .= "----------------------------------------\n";

foreach ($orderData['items'] as $item) {
$summary .= sprintf(
"%d x %s @ Ksh %s = Ksh %s\n",
$item['quantity'],
$item['name'],
number_format($item['price'], 0),
number_format($item['total'], 0)
);
}

$summary .= "----------------------------------------\n";
$summary .= "Subtotal: Ksh " . number_format($orderData['subtotal'], 2) . "\n";
$summary .= "Tax (16%): Ksh " . number_format($orderData['tax'], 2) . "\n";
$summary .= "Delivery: Ksh " . number_format($orderData['delivery_fee'], 2) . "\n";
$summary .= "TOTAL: Ksh " . number_format($orderData['total'], 2) . "\n";
$summary .= "How would you like to pay?";

return $summary;
}