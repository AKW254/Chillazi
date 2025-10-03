<?php
function buildCondensedSystemPrompt($customer_name, $staticContext, $conversationContext, $pendingOrderData)
{
    $orderItems = $conversationContext['order_intent'];
    $confirmed  = $conversationContext['confirmed'];

    $summary = "Customer so far: ";
    foreach ($orderItems as $it) {
        $summary .= "{$it['quantity']} {$it['item']}, ";
    }

    return "
You are Chillazi Foodmart's AI assistant.
- Acknowledge items naturally when mentioned.
- Example: 'Got it! 2 Milkshakes for KSH 500. Anything else?'
- Do NOT generate receipts until customer confirms.
- If user says 'just that', 'that's all', or 'done' → STOP adding and wait for server to finalize receipt.
- Never invent menu items.


Customer: {$customer_name}
Order intent: " . json_encode($orderItems) . "
Confirmed? " . ($confirmed ? "Yes" : "No") . "
Menu:
{$staticContext['menu_text']}
Basic info:
{$staticContext['scraped']}
Order History: {$pendingOrderData['order_data']}
";

}
