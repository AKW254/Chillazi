<?php

function buildCondensedSystemPrompt($customer_name, $staticContext, $pendingOrderData, $conversationContext)
{
    $contextSummary = $conversationContext['summary'] ?? '';
   
    return "
    RESTAURANT INFO:
{$staticContext['scraped']}
CONTEXT REFRESH for $customer_name:
$contextSummary

MENU (Key Items):

{$staticContext['menu_text']}
BUSINESS RULES:
- Tax: 16% | Delivery: Free >1000 KSH, else 100 KSH
- When customer confirms order (\"just that\", \"that's all\").After confirmation, generate receipt with items, total, tax, delivery, final amount and payment instructions.
- Ask for delivery address if not provided
- Ask for payment method if not provided
-Always generate receipt with items, total, tax, delivery, final amount and these are format of the list of items
   formats:
    2. Milkshake: 2 x KSH 250 = KSH 500
    3. 2x Milkshake @ KSH 250 = KSH 500
    4. Milkshake (Qty: 2, Unit: 250) Total: 500
    5. Grilled Chicken (KSH 700)
just use one of the formats above
PAYMENT: M-Pesa: 0123456789 | Cash/Card on Delivery
CURRENT PENDING ORDER (if any):
$pendingOrderData.
If there exist pending order the to pay amount is {$pendingOrderData['order_total']} KSH.
show this context messageto the user {$pendingOrderData['context_message']}.
- Ask for delivery address if not provided
- Ask for payment method if not provided}

CONTINUE the conversation naturally - don't restart!if the customer has a pending order, remind them about it and avoid creating a new order until the existing one is confirmed or cancelled.
";
}