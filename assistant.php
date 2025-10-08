<?php
// index.php (main controller)
header('Content-Type: application/json; charset=utf-8');
ob_start();

require_once 'Config/config.php';         // must define $mysqli, $geminiApiKey, optionally $siteUrl
require_once 'Functions/getConversationContext.php';
require_once 'Functions/getStaticContext.php';
require_once 'Functions/buildCondensedSystemPrompt.php'; // your prompt builder
require_once 'Functions/processOrder.php';               // your existing processOrder
require_once 'Functions/Gemini.php';                     // your Gemini wrapper
require_once 'Functions/detectOrder.php';

if (!isset($mysqli) || !($mysqli instanceof mysqli)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database not configured']);
    exit;
}

$customer_name  = trim($_POST['customer_name'] ?? '');
$customer_email = trim($_POST['customer_email'] ?? '');
$customer_phone = trim($_POST['customer_phone'] ?? '');
$message        = trim($_POST['message'] ?? '');
$conversationToken = trim($_POST['conversation_token'] ?? '');
$siteUrl = $siteUrl ?? ''; // optional from config

if ($message === '') {
    echo json_encode(['success' => false, 'error' => 'Message required']);
    exit;
}

if ($customer_email === '' && $customer_phone === '') {
    echo json_encode(['success' => false, 'error' => 'Either email or phone is required']);
    exit;
}

// find or create customer
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

// conversation token
if (empty($conversationToken) || $conversationToken === 'null') {
    $conversationToken = 'conv_' . $customer_id . '_' . time() . '_' . bin2hex(random_bytes(4));
}

// save current user message (important so context includes it)
$stmt = $mysqli->prepare("INSERT INTO conversations (conversation_customer_id, conversation_role, message, conversation_token) VALUES (?,?,?,?)");
$role = 'user';
$stmt->bind_param('isss', $customer_id, $role, $message, $conversationToken);
$stmt->execute();
$conversation_id = $stmt->insert_id;
$stmt->close();

// get conversation context (messages, order intent so far, whether already confirmed)
$conversationContext = getConversationContext($mysqli, $customer_id, $conversationToken, $siteUrl, 20);


// Add current message's extracted intent (we already saved message above, but some flows add again — safe to merge)
$static = getStaticContext($mysqli, $siteUrl);
$currentIntent = extractOrderWithMenu([['role' => 'user', 'text' => $message]], $static['menu_array']);
if (!empty($currentIntent)) {
    // merge: if existing items are same, combine quantities
    $existing = $conversationContext['order_intent'] ?? [];
    foreach ($currentIntent as $ci) {
        $merged = false;
        foreach ($existing as &$ex) {
            if (mb_strtolower($ex['item']) === mb_strtolower($ci['item'])) {
                $ex['quantity'] += $ci['quantity'];
                $ex['total'] = $ex['quantity'] * $ex['price'];
                $merged = true;
                break;
            }
        }
        if (!$merged) $existing[] = $ci;
    }
    $conversationContext['order_intent'] = $existing;
}

// detect confirmation from the current message explicitly (in case context didn't pick up yet)
if (preg_match('/\b(that\'s all|just that|that\'s it|done|no more|confirm|yes please)\b/i', $message)) {
    $conversationContext['confirmed'] = true;
}

try {
    // If confirmed and there is an order intent -> finalize
    if (!empty($conversationContext['confirmed']) && !empty($conversationContext['order_intent'])) {
        // processOrder should store order in DB and return normalized order data
        $orderData = processOrder($mysqli, $customer_id, $conversationContext['order_intent'], $static);

        // build receipt text
        $receipt = "Receipt\nItems:\n";
        foreach ($orderData['items'] as $it) {
            $receipt .= "{$it['quantity']} x {$it['item']} @ KSH {$it['price']} = KSH {$it['total']}\n";
        }
        $receipt .= "Subtotal: KSH {$orderData['order_subtotal']}\n";
        $receipt .= "Tax: KSH {$orderData['order_tax']}\n";
        $receipt .= "Delivery Fee: KSH {$orderData['delivery_fee']}\n";
        $receipt .= "Total Amount : KSH {$orderData['order_total']}\n";
        $receipt .= "PAYMENT: Paybill 90800 | Cash/Card on Delivery\n";

        // save assistant response
        $stmt = $mysqli->prepare("INSERT INTO conversations (conversation_customer_id, conversation_role, message, conversation_token) VALUES (?,?,?,?)");
        $assistantRole = 'assistant';
        $stmt->bind_param('isss', $customer_id, $assistantRole, $receipt, $conversationToken);
        $stmt->execute();
        $stmt->close();

        echo json_encode(['success' => true, 'reply' => $receipt, 'conversation_token' => $conversationToken]);
        exit;
    }

    // Otherwise continue conversation -> call Gemini
    $systemPrompt = buildCondensedSystemPrompt($customer_name, $static, $conversationContext, /*pendingOrderData*/ []);
    $messagesForModel = [
        ['role' => 'user', 'text' => $systemPrompt],
        ['role' => 'user', 'text' => $message]
    ];
    if (empty($geminiApiKey)) {
        // fallback if not configured
        $reply = "AI not configured (missing API key).";
    } else {
        $reply = (string) Gemini($messagesForModel, $geminiApiKey);
        if ($reply === '') $reply = "Sorry, I’m having trouble responding right now. Could you repeat your last request?";
    }

    // save assistant response
    $stmt = $mysqli->prepare("INSERT INTO conversations (conversation_customer_id, conversation_role, message, conversation_token) VALUES (?,?,?,?)");
    $assistantRole = 'assistant';
    $stmt->bind_param('isss', $customer_id, $assistantRole, $reply, $conversationToken);
    $stmt->execute();
    $stmt->close();

    echo json_encode(['success' => true, 'reply' => $reply, 'conversation_token' => $conversationToken], JSON_UNESCAPED_UNICODE);
    exit;
} catch (Exception $ex) {
    error_log("Main handler error: " . $ex->getMessage());
    echo json_encode(['success' => false, 'reply' => "Please Try the request again."]);
    exit;
}
