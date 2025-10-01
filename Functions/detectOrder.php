<?php
function detectOrder($response)
{
    $items = [];

    // Match multiple formats of items
    if (preg_match_all(
        '/
        (?: (\d+)\s*x?\s*([a-zA-Z ]+)\s*-\s*(?:KSH|KES|Sh)\s*([\d\.]+) )                             # format 1: 2 Milkshakes - KSH 500
        | (?: ([a-zA-Z ]+):\s*(\d+)\s*x\s*(?:KSH|KES|Sh)\s*([\d\.]+)\s*=\s*(?:KSH|KES|Sh)\s*([\d\.]+) ) # format 2: Milkshake: 2 x KSH 250 = KSH 500
        | (?: (\d+)\s*x\s*([a-zA-Z ]+)\s*@\s*(?:KSH|KES|Sh)\s*([\d\.]+)\s*=\s*(?:KSH|KES|Sh)\s*([\d\.]+) ) # format 3: 2 x Milkshake @ KSH 250 = KSH 500 (fixed to allow space)
        | (?: ([a-zA-Z ]+)\s*\(Qty:\s*(\d+),\s*Unit:\s*([\d\.]+)\)\s*Total:\s*([\d\.]+) )              # format 4: Milkshake (Qty: 2, Unit: 250) Total: 500
        | (?: ([a-zA-Z ]+)\s*\((?:KSH|KES|Sh)\s*([\d\.]+)\) )                                          # format 5: Grilled Chicken (KSH 700)
        /ix',
        $response,
        $matches,
        PREG_SET_ORDER
    )) {
        foreach ($matches as $match) {
            if (!empty($match[1])) {
                // Format 1
                $quantity  = (int) $match[1];
                $itemName  = trim($match[2]);
                $total     = (float) $match[3];
                $unitPrice = $quantity > 0 ? $total / $quantity : $total;
            } elseif (!empty($match[4])) {
                // Format 2
                $itemName  = trim($match[4]);
                $quantity  = (int) $match[5];
                $unitPrice = (float) $match[6];
                $total     = (float) $match[7];
            } elseif (!empty($match[8])) {
                // Format 3 (fixed to allow `1 x` and `1x`)
                $quantity  = (int) $match[8];
                $itemName  = trim($match[9]);
                $unitPrice = (float) $match[10];
                $total     = (float) $match[11];
            } elseif (!empty($match[12])) {
                // Format 4
                $itemName  = trim($match[12]);
                $quantity  = (int) $match[13];
                $unitPrice = (float) $match[14];
                $total     = (float) $match[15];
            } elseif (!empty($match[16])) {
                // Format 5
                $itemName  = trim($match[16]);
                $total     = (float) $match[17];
                $quantity  = 1; // assume 1 if not specified
                $unitPrice = $total;
            } else {
                continue;
            }

            $items[] = [
                'name'     => $itemName,
                'quantity' => $quantity,
                'price'    => $unitPrice,
                'total'    => $total
            ];
        }
    }

    // ✅ Match totals with flexible patterns
    preg_match('/Sub\s*Total:\s*(?:KSH|KES|Sh)\s*([\d\.]+)/i', $response, $subtotalMatch);   // matches Subtotal: or Sub Total:
    preg_match('/Tax(?: \(.*?\))?:\s*(?:KSH|KES|Sh)\s*([\d\.]+)/i', $response, $taxMatch);
    preg_match('/Delivery(?: Fee| Charge)?:\s*(?:KSH|KES|Sh)\s*([\d\.]+)/i', $response, $deliveryMatch);
    preg_match('/Total(?: Amount)?:\s*(?:KSH|KES|Sh)\s*([\d\.]+)/i', $response, $totalMatch);

    if (!empty($items)) {
        return [
            'order_data' => [
                'order_id'       => null,
                'items'          => $items,
                'order_subtotal' => isset($subtotalMatch[1]) ? (float)$subtotalMatch[1] : array_sum(array_column($items, 'total')),
                'order_tax'      => isset($taxMatch[1]) ? (float)$taxMatch[1] : 0,
                'delivery_fee'   => isset($deliveryMatch[1]) ? (float)$deliveryMatch[1] : 0,
                'order_total'    => isset($totalMatch[1]) ? (float)$totalMatch[1] : (
                    (isset($subtotalMatch[1]) ? (float)$subtotalMatch[1] : array_sum(array_column($items, 'total')))
                    + (isset($taxMatch[1]) ? (float)$taxMatch[1] : 0)
                    + (isset($deliveryMatch[1]) ? (float)$deliveryMatch[1] : 0)
                ),
                'order_currency' => 'KES',
                'order_status'   => 'pending_confirmation',
                'created_at'     => date('Y-m-d H:i:s')
            ]
        ];
    }

    return null;
}
