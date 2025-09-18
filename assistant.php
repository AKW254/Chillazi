<?php
header('Content-Type: application/json');
include 'Config/config.php';
include 'Functions/getConversationContext.php';
include 'Functions/getStaticContext.php';
include 'Functions/getPendingOrderContext.php';
include 'Functions/buildSystemPrompt.php';
include 'Functions/processOrder.php';
include 'Functions/Gemini.php';
include 'Functions/detectOrder.php';
include 'Functions/generateOrderSummary.php';
include 'vendor/autoload.php';


    // Input validation
    $customer_name = trim($_POST['customer_name'] ?? '');
    $customer_email = trim($_POST['customer_email'] ?? '');
    $customer_phone = trim($_POST['customer_phone'] ?? '');
    $message = trim($_POST['message'] ?? '');
    $conversationToken = trim($_POST['conversationToken'] ?? '');

    // Basic validation
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
    if (empty($conversationToken)) {
        $conversationToken = 'conv_' . $customer_id . '_' . time() . '_' . bin2hex(random_bytes(4));
    }

    // 🔹 Save user message
    $stmt = $mysqli->prepare("INSERT INTO conversations (conversation_customer_id, conversation_role, message,conversation_token) VALUES (?,?,?,?)");
    $role = 'user';
    $stmt->bind_param('isss', $customer_id, $role, $message,$conversationToken);
    $stmt->execute();
    $conversation_id = $stmt->insert_id;
    $stmt->close();
   
    // 🔹 Get conversation context using different strategies
    $conversationContext = getConversationContext($mysqli, $customer_id, $conversationToken);

    // 🔹 Static info (load once and cache)
    $staticContext = getStaticContext();

    // 🔹 Check for pending orders
   $pendingOrderContext = getPendingOrderContext($mysqli, $customer_id);


    // 🔹 Build system prompt (only for first message or context reset)
    $isFirstMessage = $conversationContext['is_first_message'];
    $messages = [];

    if ($isFirstMessage) {
        // Full context for new conversation
        $systemPrompt = buildSystemPrompt($customer_name, $customer_email, $customer_phone, $staticContext, $pendingOrderContext);
        $messages[] = [
            'role' => 'user',
            'text' => $systemPrompt . "\n\nCustomer says: " . $message
        ];
    } else {
        // Continue existing conversation - only add recent context if needed
        $messages = $conversationContext['messages'];
        $messages[] = [
            'role' => 'user',
            'text' => $message
        ];
    }
    echo json_encode([
        'success' => true,
        'messages' => $messages,
        'conversation_token' => $conversationToken
    ], JSON_UNESCAPED_UNICODE);
    // try {
    //     $response = Gemini($messages, $geminiApiKey);

    //     // 🔹 Save assistant response
    //     $stmt = $mysqli->prepare("INSERT INTO conversations (conversation_customer_id, conversation_role, message,conversation_token) VALUES (?,?,?,?)");
    //     $assistantRole = 'assistant';
    //     $stmt->bind_param('isss', $customer_id, $assistantRole, $response, $conversationToken);
    //     $stmt->execute();
    //     $stmt->close();
        

    //     // 🔹 Process AI Response for Orders
    //     $orderData = detectOrder($response, $staticContext['menu_array']);

    //     // 🔹 Process Order if Found
    //     if ($orderData) {
    //         //Convert order items to JSON string for storage
    //         $orderDataJson = json_encode($orderData, JSON_UNESCAPED_UNICODE);
            
    //         $stmt = $mysqli->prepare("
    //     INSERT INTO orders (
    //         order_customer_id, 
    //         order_text, 
    //         order_subtotal,
    //         order_tax,
    //         order_delivery_fee,
    //         order_total,
    //         order_status,
    //         order_currency
    //     ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    // ");

    //         $stmt->bind_param(
    //             'isddddss',
    //             $customer_id,
    //             $orderDataJson,
    //             $orderData['subtotal'],
    //             $orderData['tax'],
    //             $orderData['delivery_fee'],
    //             $orderData['total'],
    //             $orderData['status'],
    //             $orderData['currency']
    //         );

    //         $stmt->execute();
    //         $order_id = $stmt->insert_id;
    //         $stmt->close();

    //         // Attach order ID to orderData
    //         $orderData['order_id'] = $order_id;
    //         // Generate order summary
    //         $orderSummary = generateOrderSummary($orderData);

          
    //         // Append confirmation to response
    //         $response .= "\n\n" . $orderSummary;
    //         $response .= "\n\n✅ ORDER CONFIRMED! Order ID: #" . $order_id;
    //         $response .= "\n💰 Total: KSH " . number_format($orderData['total'], 2);
    //         $response .= "\n\n📱 PAYMENT OPTIONS:";
    //         $response .= "\n1️⃣ M-Pesa: Send KSH " . number_format($orderData['total'], 2) . " to 0123456789";
    //         $response .= "\n2️⃣ Cash on Delivery";
    //         $response .= "\n3️⃣ Card on Delivery";
    //         // Respond to frontend with order data
    //         echo json_encode([
    //             'success' => true,
    //             'reply' => $response,
    //             'order' => $orderData,
    //             'order_id' => $order_id,
    //             'conversation_token' => $conversationToken
    //         ], JSON_UNESCAPED_UNICODE);
    //     }else {
    //         echo json_encode([
    //             'success' => true,
    //             'reply' => $response,
    //             'conversationToken' => $conversationToken
    //         ], JSON_UNESCAPED_UNICODE);
    //     }

       

    // } catch (Exception $e) {
    //     error_log("Gemini API Error: " . $e->getMessage());
    //     echo json_encode([
    //         'success' => false,
    //         'error' => 'Failed to get response from AI service.'
    //     ]);
    // }
   
