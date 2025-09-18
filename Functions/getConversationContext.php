<?php
function getConversationContext($mysqli, $customer_id, $conversationToken) {
// Strategy 1: Load only recent messages from current session
$stmt = $mysqli->prepare("
SELECT conversation_role,message,conversation_created_at FROM conversations
WHERE conversation_customer_id = ? AND conversation_token = ?
ORDER BY conversation_id DESC
LIMIT 6
");
$stmt->bind_param('is', $customer_id, $conversationToken);
$stmt->execute();
$result = $stmt->get_result();

$messages = [];
$messageCount = 0;
while ($row = $result->fetch_assoc()) {
$messages[] = [
'role' => $row['conversation_role'] === 'user' ? 'user' : 'assistant',
'text' => $row['message']
];
$messageCount++;
}
$stmt->close();

// Reverse to chronological order
$messages = array_reverse($messages);

// Remove the last message (current user message we just saved)
if (!empty($messages)) {
array_pop($messages);
}

return [
'messages' => $messages,
'is_first_message' => $messageCount <= 1, 'message_count'=> $messageCount
    ];
    }