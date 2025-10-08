<?php
// Functions/getConversationContext.php
include_once __DIR__ . '/detectOrder.php';
include_once __DIR__ . '/getStaticContext.php';

/**
 * @param mysqli $mysqli
 * @param int $customer_id
 * @param string $conversation_token
 * @param string $siteUrl optional (for static context)
 * @param int $limit messages to fetch
 * @return array ['messages'=>[], 'order_intent'=>[], 'confirmed'=>bool]
 */
function getConversationContext($mysqli, int $customer_id, string $conversation_token, string $siteUrl = '', int $limit = 20): array
{
    $stmt = $mysqli->prepare("
        SELECT conversation_role, message, conversation_created_at
        FROM conversations
        WHERE conversation_customer_id = ? AND conversation_token = ?
        ORDER BY conversation_id DESC
        LIMIT ?
    ");
    if (!$stmt) {
        error_log("getConversationContext prepare failed: " . mysqli_error($mysqli));
        return ['messages' => [], 'order_intent' => [], 'confirmed' => false];
    }

    $stmt->bind_param('isi', $customer_id, $conversation_token, $limit);
    $stmt->execute();
    $result = $stmt->get_result();

    $messages = [];
    while ($row = $result->fetch_assoc()) {
        $messages[] = [
            'role' => ($row['conversation_role'] === 'user') ? 'user' : 'assistant',
            'text' => $row['message'],
            'created_at' => $row['conversation_created_at']
        ];
    }
    $stmt->close();

    // reverse to chronological order (oldest first)
    $messages = array_reverse($messages);

    // remove last message if it's the "current" message saved separately upstream (your flow may already handle this)
    // if you already insert the current user message before calling, then this keeps the previously-saved last message behavior
    // Comment out the next line if you DO NOT store the current message before calling this function.
    if (!empty($messages)) array_pop($messages);

    // load static context (menu)
    $static = getStaticContext($mysqli, $siteUrl);
    $menu = $static['menu_array'] ?? [];

    // Extract order items from past user messages
    $orderIntent = extractOrderWithMenu($messages, $menu);

    // Detect confirmation: last user message only
    $lastUserText = '';
    for ($i = count($messages) - 1; $i >= 0; $i--) {
        if ($messages[$i]['role'] === 'user') {
            $lastUserText = (string)$messages[$i]['text'];
            break;
        }
    }
    $confirmed = false;
    if ($lastUserText !== '') {
        $confirmed = (bool) preg_match('/\b(that\'s all|that all|just that|that\'s it|done|no more|confirm|yes please|confirm order)\b/i', $lastUserText);
    }

    return [
        'messages' => $messages,
        'order_intent' => $orderIntent,
        'confirmed' => $confirmed
    ];
}
