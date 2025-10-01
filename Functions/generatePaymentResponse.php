<?php
function generatePaymentResponse($paymentData, $paymentResult = null)
{
    if (!$paymentData) {
        return "";
    }

    $orderId = $paymentData['payment_order_id'];
    $amount = number_format($paymentData['payment_amount'], 2);

    switch ($paymentData['payment_status']) {
        case 'confirmed':
            if ($paymentData['payment_method'] === 'M-Pesa') {
                return "\n\n✅ PAYMENT CONFIRMED!\n" .
                    "Order #$orderId - KSH $amount\n" .
                    "M-Pesa Code: " . $paymentData['payment_confirmation_code'] . "\n" .
                    "Your order is now being prepared! 🍕\n" .
                    "Estimated delivery: 30-45 minutes\n" .
                    "We'll call you when it's ready for delivery.";
            } else {
                return "\n\n✅ PAYMENT METHOD CONFIRMED!\n" .
                    "Order #$orderId - KSH $amount\n" .
                    "Payment: " . $paymentData['payment_method'] . "\n" .
                    "Your order is now being prepared! 🍕\n" .
                    "Estimated delivery: 30-45 minutes";
            }

        case 'awaiting_confirmation':
            return "\n\n⏳ M-PESA PAYMENT SELECTED\n" .
                "Order #$orderId - KSH $amount\n" .
                "Please send KSH $amount to: 0123456789 (Chillazi Foodmart)\n" .
                "Then reply with your M-Pesa confirmation code (e.g., QH12XYZ789)";

        case 'awaiting_method_selection':
            return "\n\n💰 HOW WOULD YOU LIKE TO PAY?\n" .
                "Order #$orderId - KSH $amount\n\n" .
                "1️⃣ M-Pesa: Send to 0123456789\n" .
                "2️⃣ Cash on Delivery\n" .
                "3️⃣ Card on Delivery\n\n" .
                "Just type your preferred method!";

        default:
            return "";
    }
}