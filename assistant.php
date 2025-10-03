<?php

header('Content-Type: application/json; charset=utf-8');

include 'Config/config.php';
include 'Functions/getConversationContext.php';
include 'Functions/getStaticContext.php';
include 'Functions/getPendingOrderContext.php';
include 'Functions/buildSystemPrompt.php';
include 'Functions/buildCondensedSystemPrompt.php';
include 'Functions/processOrder.php';
include 'Functions/Gemini.php';
include 'Functions/detectOrder.php';
include 'vendor/autoload.php';

// Error handling
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

register_shutdown_function(function () {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        ob_clean();
        http_response_code(500);
        echo json_encode(['error' => 'Server error occurred']);
    }
});

// Input validation
$customer_name = trim($_POST['customer_name'] ?? '');
$customer_email = trim($_POST['customer_email'] ?? '');
$customer_phone = trim($_POST['customer_phone'] ?? '');
$message = trim($_POST['message'] ?? '');
$conversationToken = trim($_POST['conversation_token'] ?? '');

if (empty($message)) {
    echo json_encode(['error' => 'Message is required']);
    exit;
}

if (empty($customer_email) && empty($customer_phone)) {
    echo json_encode(['error' => 'Either email or phone is required']);
    exit;
}

// 🔹 Find or create customer
$customer_id = null;
$stmt = $mysqli->prepare("SELECT customer_id FROM customers WHERE customer_email = ? OR customer_phone = ? LIMIT 1");
$stmt->bind_param('ss', $customer_email, $customer_phone);
$stmt->execute();
$stmt->bind_result($customer_id);
$stmt->fetch();
$stmt->close();

if (!$customer_id) {
    $stmt = $mysqli->prepare("INSERT INTO customers (customer_name, customer_email, customer_phone) VALUES (?,?,?)");
    $stmt->bind_param('sss', $customer_name, $customer_email, $customer_phone);
    $stmt->execute();
    $customer_id = $stmt->insert_id;
    $stmt->close();
}

// ✅ If no token, create new one
if (empty($conversationToken) || $conversationToken === 'null') {
    $conversationToken = 'conv_' . $customer_id . '_' . time() . '_' . bin2hex(random_bytes(4));
}

// 🔹 Save user message
$stmt = $mysqli->prepare("INSERT INTO conversations (conversation_customer_id, conversation_role, message, conversation_token) VALUES (?,?,?,?)");
$role = 'user';
$stmt->bind_param('isss', $customer_id, $role, $message, $conversationToken);
$stmt->execute();
$conversation_id = $stmt->insert_id;
$stmt->close();

// 🔹 Get conversation context
$conversationContext = getConversationContext($mysqli, $customer_id, $conversationToken);

// 🔹 Add current message into order + confirmation detection
$currentOrderIntent = extractOrderWithMenu([['role' => 'user', 'text' => $message]], getStaticContext()['menu_array']);
if (!empty($currentOrderIntent)) {
    $conversationContext['order_intent'] = array_merge($conversationContext['order_intent'], $currentOrderIntent);
}

if (detectConfirmation([['role' => 'user', 'text' => $message]])) {
    $conversationContext['confirmed'] = true;
}

// 🔹 Static info (menu, scraped data, etc.)
$staticContext = getStaticContext();

// 🔹 Check for pending orders
$pendingOrderData = getPendingOrderContext($mysqli, $customer_id);


try {
    // ✅ Case 1: Customer confirmed → finalize order
    if ($conversationContext['confirmed'] && !empty($conversationContext['order_intent'])) {
        $orderData = processOrder($mysqli, $customer_id, $conversationContext['order_intent'], $staticContext);

        $receipt = "Receipt\nItems:\n";
        foreach ($orderData['items'] as $it) {
            $receipt .= "{$it['quantity']} x {$it['item']} @ KSH {$it['price']} = KSH {$it['total']}\n";
        }
        $receipt .= "Subtotal: KSH {$orderData['order_subtotal']}\n";
        $receipt .= "Tax: KSH {$orderData['order_tax']}\n";
        $receipt .= "Delivery Fee: KSH {$orderData['delivery_fee']}\n";
        $receipt .= "Total Amount : KSH {$orderData['order_total']}\n";
        $receipt .= "PAYMENT: Paybill 90800 | Cash/Card on Delivery\n";

        // Save assistant response
        $stmt = $mysqli->prepare("
            INSERT INTO conversations (conversation_customer_id, conversation_role, message, conversation_token) 
            VALUES (?,?,?,?)
        ");
        $assistantRole = 'assistant';
        $stmt->bind_param('isss', $customer_id, $assistantRole, $receipt, $conversationToken);
        $stmt->execute();
        $stmt->close();

        echo json_encode([
            'success' => true,
            'reply' => $receipt,
            'conversation_token' => $conversationToken
        ]);
        exit;
    }

    // ✅ Case 2: Not confirmed yet → continue conversation
    $systemPrompt = buildCondensedSystemPrompt($customer_name, $staticContext, $conversationContext, $pendingOrderData);
    $messages = [
        ['role' => 'user', 'text' => $systemPrompt],
        ['role' => 'user', 'text' => $message]
    ];

    $response = Gemini($messages, $geminiApiKey);
    $response = (string)$response;

    // Save assistant response
    $stmt = $mysqli->prepare("
        INSERT INTO conversations (conversation_customer_id, conversation_role, message, conversation_token) 
        VALUES (?,?,?,?)
    ");
    $assistantRole = 'assistant';
    $stmt->bind_param('isss', $customer_id, $assistantRole, $response, $conversationToken);
    $stmt->execute();
    $stmt->close();

    echo json_encode([
        'success' => true,
        'reply' => $response,
        'conversation_token' => $conversationToken
    ]);
    exit;
} catch (Exception $e) {
    error_log("Gemini API Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'reply' => "Sorry, I’m having trouble responding right now. Could you repeat your last request?",
        'conversation_token' => $conversationToken
    ]);
    exit;
}
