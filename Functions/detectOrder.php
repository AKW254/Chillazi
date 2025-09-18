<?php
function detectOrder($response, $menuArray = [])
{
    $orderData = null;

    // 1. Detect JSON - improved to find all JSON objects
    if (preg_match_all('/\{(?:[^{}]|(?R))*\}/s', $response, $allMatches)) {
        foreach ($allMatches[0] as $jsonStr) {
            $tempData = json_decode($jsonStr, true);
            if ($tempData && (isset($tempData['items']) || isset($tempData['order']['items']))) {
                $rawOrderData = isset($tempData['order']) ? $tempData['order'] : $tempData;

                // Calculate missing fields if not present
                if (isset($rawOrderData['items']) && is_array($rawOrderData['items'])) {
                    $items = [];
                    $subtotal = 0;

                    foreach ($rawOrderData['items'] as $item) {
                        $itemName = $item['item'] ?? $item['name'] ?? '';
                        $quantity = (int)($item['quantity'] ?? 1);
                        $price = (float)($item['price'] ?? ($menuArray[$itemName] ?? 0));
                        $total = $price * $quantity;

                        if ($itemName) {
                            $items[] = [
                                'name' => $itemName,
                                'quantity' => $quantity,
                                'price' => $price,
                                'total' => $total
                            ];
                            $subtotal += $total;
                        }
                    }

                    if (!empty($items)) {
                        $taxRate = 0.16;
                        $tax = $subtotal * $taxRate;
                        $deliveryFee = ($subtotal > 1000) ? 0 : 100;
                        $total = $subtotal + $tax + $deliveryFee;

                        $orderData = [
                            'items' => $items,
                            'subtotal' => $subtotal,
                            'tax' => $tax,
                            'delivery_fee' => $deliveryFee,
                            'total' => $total,
                            'currency' => 'KES',
                            'status' => 'pending_confirmation',
                            'created_at' => date('Y-m-d H:i:s')
                        ];
                        break; // Use the first valid order found
                    }
                }
            }
        }
    }

    // 2. Detect Markdown table (unchanged)
    if (!$orderData && preg_match_all(
        '/\|\s*([^|]+)\s*\|\s*([^|]*)\s*\|\s*([^|]*)\s*\|\s*([^|]*)\s*\|/',
        $response,
        $rows,
        PREG_SET_ORDER
    )) {
        $items = [];
        foreach ($rows as $i => $row) {
            if ($i === 0 || stripos($row[1], 'item') !== false || strpos(trim($row[1]), '-') === 0) continue;
            $name = trim($row[1]);
            $quantity = (int) trim($row[2] ?: 1);
            $price = (float) preg_replace('/[^\d.]/', '', $row[3]);
            $total = (float) preg_replace('/[^\d.]/', '', $row[4]);
            if ($name && isset($menuArray[$name])) {
                $items[] = [
                    'name' => $name,
                    'quantity' => $quantity,
                    'price' => $menuArray[$name],
                    'total' => $menuArray[$name] * $quantity
                ];
            }
        }
        if (!empty($items)) {
            $subtotal = array_sum(array_column($items, 'total'));
            $taxRate = 0.16;
            $tax = $subtotal * $taxRate;
            $deliveryFee = ($subtotal > 1000) ? 0 : 100;
            $total = $subtotal + $tax + $deliveryFee;
            $orderData = [
                'items' => $items,
                'subtotal' => $subtotal,
                'tax' => $tax,
                'delivery_fee' => $deliveryFee,
                'total' => $total,
                'currency' => 'KES',
                'status' => 'pending_confirmation',
                'created_at' => date('Y-m-d H:i:s')
            ];
        }
    }

    // 3. Detect plain text (unchanged)
    if (!$orderData && preg_match_all('/(\d+)\s+([a-zA-Z ]+)/', $response, $matches, PREG_SET_ORDER)) {
        $items = [];
        foreach ($matches as $match) {
            $itemName = ucfirst(trim($match[2]));
            $quantity = (int) $match[1];
            if (!isset($menuArray[$itemName])) continue;
            $price = (float) $menuArray[$itemName];
            $total = $price * $quantity;
            $items[] = [
                'name' => $itemName,
                'quantity' => $quantity,
                'price' => $price,
                'total' => $total
            ];
        }
        if (!empty($items)) {
            $subtotal = array_sum(array_column($items, 'total'));
            $taxRate = 0.16;
            $tax = $subtotal * $taxRate;
            $deliveryFee = ($subtotal > 1000) ? 0 : 100;
            $total = $subtotal + $tax + $deliveryFee;
            $orderData = [
                'items' => $items,
                'subtotal' => $subtotal,
                'tax' => $tax,
                'delivery_fee' => $deliveryFee,
                'total' => $total,
                'currency' => 'KES',
                'status' => 'pending_confirmation',
                'created_at' => date('Y-m-d H:i:s')
            ];
        }
    }

    return $orderData;
}
