<?php

/**
 * Get the last N messages for a customer in a given conversation
 */
function getConversationContext($mysqli, $customer_id, $conversation_token, $limit = 20)
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
            'role'       => $row['conversation_role'] === 'user' ? 'user' : 'model',
            'text'       => $row['message'],
            'created_at' => $row['conversation_created_at']
        ];
    }
    $stmt->close();

    // Reverse to chronological order
    $messages = array_reverse($messages);

    // Remove last message (the current one was just saved separately)
    if (!empty($messages)) {
        array_pop($messages);
    }

    $messageCount = count($messages);

    return [
        'messages'         => $messages,
        'summary'         => createConversationSummary($messages),
        'message_count'    => $messageCount,
        'is_first_message' => $messageCount === 0
    ];
}

/**
 * Create a summary of the conversation
 */
function createConversationSummary($messages)
{
    if (empty($messages)) {
        return "";
    }

    $summary = "RECENT CONTEXT (for strict ordering):\n";
    $orderItems = [];
    $lastUserMessage = "";

    foreach ($messages as $msg) {
        if ($msg['role'] === 'user') {
            $lastUserMessage = $msg['text'];

            // Detect items like "2 burgers", "1 juice"
            if (preg_match_all('/(\d+)\s+([a-zA-Z ]+)/i', $msg['text'], $matches, PREG_SET_ORDER)) {
                foreach ($matches as $m) {
                    $orderItems[] = [
                        'item'     => trim($m[2]),
                        'quantity' => (int) $m[1]
                    ];
                }
            }

            // Detect confirmation
            if (preg_match('/\b(that\'s all|just that|only that|nothing else|done|no more)\b/i', $msg['text'])) {
                $summary .= "CUSTOMER CONFIRMED ORDER.\n";
            }
        }
    }

    if (!empty($orderItems)) {
        $summary .= "CONFIRMED ITEMS: " . json_encode($orderItems, JSON_UNESCAPED_UNICODE) . "\n";
    }

    if ($lastUserMessage) {
        $summary .= "LAST USER MESSAGE: \"" . $lastUserMessage . "\"\n";
    }

    $summary .= "⚠️ DO NOT invent new items. Only use CONFIRMED ITEMS above unless user explicitly adds more.\n";

    return $summary;
}
