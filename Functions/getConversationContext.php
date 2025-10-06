<?php
function getConversationContext($mysqli, $customer_id, $conversation_token, $limit = 5)
{
    $stmt = $mysqli->prepare("
        SELECT conversation_role, message, conversation_created_at
        FROM conversations
        WHERE conversation_customer_id = ? AND conversation_token = ?
        ORDER BY conversation_id DESC
        LIMIT ?
    ");
    $stmt->bind_param('isi', $customer_id, $conversation_token, $limit);
    $stmt->execute();
    $result = $stmt->get_result();

    $messages = [];
    while ($row = $result->fetch_assoc()) {
        $messages[] = [
            'role'       => $row['conversation_role'] === 'user' ? 'user' : 'assistant',
            'text'       => $row['message'],
            'created_at' => $row['conversation_created_at']
        ];
    }
    $stmt->close();

    $messages = array_reverse($messages);
    if (!empty($messages)) array_pop($messages);

    // Extract latest intent with prices
    $orderIntent = extractOrderWithMenu($messages, getStaticContext()['menu_array']);

    return [
        'messages'     => $messages,
        'order_intent' => $orderIntent,
        'confirmed'    => detectConfirmation($messages)
    ];
}

/**
 * Extract items + map directly to menu prices
 */
function extractOrderWithMenu($messages, $menu)
{
    $items = [];
    foreach ($messages as $msg) {
        if ($msg['role'] !== 'user') continue;

        if (preg_match_all('/(\d+|[a-zA-Z\s-]+)\s+([a-zA-Z ]+)/i', $msg['text'], $matches, PREG_SET_ORDER)) {
            foreach ($matches as $m) {
                $qtyWord  = strtolower(trim($m[1]));
                $quantity = is_numeric($qtyWord) ? (int)$qtyWord : wordsToNumber($qtyWord);
                if ($quantity <= 0) continue;

                $rawItem = strtolower(trim($m[2]));
                $price   = matchMenuItem($rawItem, $menu);

                $items[] = [
                    'item'     => $rawItem,
                    'quantity' => $quantity,
                    'price'    => $price,
                    'total'    => $price * $quantity
                ];
            }
        }
    }
    return $items;
}

/**
 * Fuzzy match against menu
 */
function matchMenuItem($name, $menu)
{
    $name = strtolower(trim($name));
    foreach ($menu as $menuItem => $price) {
        $menuItem = strtolower($menuItem);
        if ($name === $menuItem) return $price;
        if (strpos($menuItem, $name) !== false || strpos($name, $menuItem) !== false) {
            return $price;
        }
    }
    return 0; // unknown item
}

/**
 * Convert textual numbers (e.g. "twenty five") → integer
 */
function wordsToNumber($words)
{
    $map = [
        'zero' => 0,
        'one' => 1,
        'two' => 2,
        'three' => 3,
        'four' => 4,
        'five' => 5,
        'six' => 6,
        'seven' => 7,
        'eight' => 8,
        'nine' => 9,
        'ten' => 10,
        'eleven' => 11,
        'twelve' => 12,
        'thirteen' => 13,
        'fourteen' => 14,
        'fifteen' => 15,
        'sixteen' => 16,
        'seventeen' => 17,
        'eighteen' => 18,
        'nineteen' => 19,
        'twenty' => 20,
        'thirty' => 30,
        'forty' => 40,
        'fifty' => 50,
        'sixty' => 60,
        'seventy' => 70,
        'eighty' => 80,
        'ninety' => 90,
        'hundred' => 100,
        'thousand' => 1000
    ];

    $words = preg_split('/[\s-]+/', strtolower(trim($words)));
    $total = 0;
    $current = 0;

    foreach ($words as $w) {
        if (!isset($map[$w])) continue;
        $val = $map[$w];

        if ($val == 100 || $val == 1000) {
            $current = ($current == 0 ? 1 : $current) * $val;
            if ($val == 1000) {
                $total += $current;
                $current = 0;
            }
        } else {
            $current += $val;
        }
    }
    return $total + $current;
}

function detectConfirmation($messages)
{
    $lastUser = end($messages)['text'] ?? '';
    return preg_match('/\b(that\'s all|just that|done|no more|that all)\b/i', $lastUser);
}
