<?php
header('Content-Type: application/json');
include 'Config/config.php';
include 'Functions/scraper_summary.php';
include 'Functions/menu_parser.php';
include 'Functions/Gemini.php';
include 'vendor/autoload.php';

try {
    // Input validation
    $customer_name = trim($_POST['customer_name'] ?? '');
    $customer_email = trim($_POST['customer_email'] ?? '');
    $customer_phone = trim($_POST['customer_phone'] ?? '');
    $message = trim($_POST['message'] ?? '');

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

    // 🔹 Save user message
    $stmt = $mysqli->prepare("INSERT INTO conversations (conversation_customer_id, conversation_role, message) VALUES (?,?,?)");
    $role = 'user';
    $stmt->bind_param('iss', $customer_id, $role, $message);
    $stmt->execute();
    $conversation_id = $stmt->insert_id;
    $stmt->close();

    // 🔹 Scraped info
    $scraped = scrapeAndSummarize(["http://127.0.0.1/Chillazi/index.php"], 2000);

    // Menu info
    $menuFile = "/Storage/menu/menu.pdf";
    $menuText = getMenuText(__DIR__ . $menuFile);

    // 🔹 Build system prompt
    $systemPrompt = "You are Chillazi Foodmart's AI assistant (Helpdesk/Waiter).
    - Be friendly and helpful and keep responses concise (max 150 words)
    - Always respond in a single language (English or Swahili) based on customer's message
    - If customer asks for food, provide menu items in a table format with columns: Item, Description, Price
    - If customer asks for order, provide order details in a table format
    - Suggest meals from our menu
    - If customer orders food, extract order details show in table fromat and confirm total
    - Provide information about our restaurant
    -CUSTOMER Name: $customer_name
    -CUSTOMER Email: $customer_email
    -CUSTOMER Phone: $customer_phone
    MENU INFORMATION:
    $menuText
    RESTAURANT INFORMATION:
    $scraped";

    // 🔹 Get conversation history (optional - for context)
    $history = [];
    $stmt = $mysqli->prepare("
        SELECT conversation_role, message 
        FROM conversations 
        WHERE conversation_customer_id = ? 
        ORDER BY conversation_id ASC 
        LIMIT 10
    ");
    $stmt->bind_param('i', $customer_id);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        // Skip the current message we just saved
        if ($row['message'] !== $message) {
            $history[] = [
                'role' => $row['conversation_role'] === 'user' ? 'user' : 'model',
                'text' => $row['message']
            ];
        }
    }
    $stmt->close();

    // 🔹 Prepare messages for Gemini
    // Since Gemini doesn't support system role, prepend system prompt to first user message
    $messages = [];

    // If there's history, add it first
    if (!empty($history)) {
        $messages = $history;
    }

    // Add current message with system context
    $combinedMessage = $systemPrompt . "\n\nCustomer says: " . $message;
    $messages[] = [
        'role' => 'user',
        'text' => $combinedMessage
    ];
    try {
        $response = Gemini($messages, $geminiApiKey);

        // 🔹 Save assistant response
        $stmt = $mysqli->prepare("INSERT INTO conversations (conversation_customer_id, conversation_role, message) VALUES (?,?,?)");
        $assistantRole = 'assistant';
        $stmt->bind_param('iss', $customer_id, $assistantRole, $response);
        $stmt->execute();
        $stmt->close();
        // Respond to frontend
        echo json_encode(['reply' => $response]);
        //Handle order extraction and saving if needed
        // 🔹 Process AI Response for Orders
        $orderData = null;
        $paymentRequest = null;

        // Enhanced regex patterns for different order formats
        $orderPatterns = [
            '/\{[^}]*"order"[^}]*\}/s',  // Standard JSON with "order" key
            '/\{[^}]*"items"[^}]*\}/s',   // JSON with direct "items" key
            '/```json\s*(\{[^}]+\})\s*```/s', // JSON in code blocks
            '/ORDER:\s*(\{[^}]+\})/s'     // ORDER: prefix format
        ];

        // Try multiple patterns to extract order
        foreach ($orderPatterns as $pattern) {
            if (preg_match($pattern, $response, $matches)) {
                $jsonStr = end($matches);
                $tempData = json_decode($jsonStr, true);
                if ($tempData && (isset($tempData['items']) || isset($tempData['order']['items']))) {
                    $orderData = isset($tempData['order']) ? $tempData['order'] : $tempData;
                    break;
                }
            }
        }

        // 🔹 Process Order if Found
        if ($orderData && isset($orderData['items']) && is_array($orderData['items'])) {

            // Calculate totals with proper validation
            $subtotal = 0;
            $processedItems = [];

            foreach ($orderData['items'] as $item) {
                // Clean and validate item data
                $itemName = trim($item['name'] ?? $item['item'] ?? '');
                $quantity = max(1, intval($item['quantity'] ?? 1));

                // Extract price (handle various formats)
                $priceStr = $item['price'] ?? $item['amount'] ?? '0';
                $price = floatval(preg_replace('/[^\d.]/', '', $priceStr));

                // Calculate item total
                $itemTotal = $price * $quantity;
                $subtotal += $itemTotal;

                // Store processed item
                $processedItems[] = [
                    'name' => $itemName,
                    'quantity' => $quantity,
                    'price' => $price,
                    'total' => $itemTotal
                ];
            }

            // Calculate taxes and fees
            $taxRate = 0.16; // 16% VAT (adjust based on location)
            $deliveryFee = isset($orderData['delivery']) && $orderData['delivery'] ? 5.00 : 0;
            $tax = $subtotal * $taxRate;
            $total = $subtotal + $tax + $deliveryFee;

            // Prepare complete order data
            $orderData = [
                'items' => $processedItems,
                'subtotal' => number_format($subtotal, 2, '.', ''),
                'tax' => number_format($tax, 2, '.', ''),
                'delivery_fee' => number_format($deliveryFee, 2, '.', ''),
                'total' => number_format($total, 2, '.', ''),
                'currency' => 'KES', // Kenyan Shillings (adjust as needed)
                'status' => 'pending_confirmation',
                'created_at' => date('Y-m-d H:i:s')
            ];

            // 🔹 Save Order to Database
            $orderJson = json_encode($orderData);
            $stmt = $mysqli->prepare("
        INSERT INTO orders (
            order_customer_id, 
            order_text, 
            order_subtotal,
            order_tax,
            order_delivery_fee,
            order_total,
            order_status,
            order_currency
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");

            $stmt->bind_param(
                'isddddss',
                $customer_id,
                $orderJson,
                $orderData['subtotal'],
                $orderData['tax'],
                $orderData['delivery_fee'],
                $orderData['total'],
                $orderData['status'],
                $orderData['currency']
            );

            $stmt->execute();
            $order_id = $stmt->insert_id;
            $stmt->close();

            // Add order ID to response
            $orderData['order_id'] = $order_id;

            // 🔹 Generate Payment Options
            //$paymentRequest = generatePaymentOptions($order_id, $orderData, $customer_id, $mysqli);
        }

    } catch (Exception $e) {
        error_log("Gemini API Error: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'error' => 'Failed to get response from AI service.'
        ]);
    }
   
} catch (Exception $e) {
    error_log("Application Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'An error occurred. Please try again later.'
    ]);
}
