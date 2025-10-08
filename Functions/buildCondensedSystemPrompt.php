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
- for match of ordering is  '2 large fries and a coke', '3 burgers', 'one pizza'
- Do NOT generate receipts until customer confirms.
- If user says 'just that', 'that's all', or 'done' → STOP adding and wait for server to finalize receipt.
- Never invent menu items.



Customer: {$customer_name}
Order intent: " . json_encode($orderItems) . "
Confirmed? " . ($confirmed ? "Yes" : "No") . "
Menu:
" . (
    !empty($staticContext['menu_array'])
        ? implode(", ", array_map(
            fn($k, $v) => "$k (KSH $v)",
            array_keys($staticContext['menu_array']),
            array_values($staticContext['menu_array'])
        ))
        : 'No menu available'
) . "
Basic info:
{$staticContext['scraped']}
Order History: {$pendingOrderData['order_data']}
";

}
