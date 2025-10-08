<?php
// Functions/detectOrder.php

function extractOrderWithMenu(array $messages, array $menu): array
{
    $items = [];
    $menuKeys = array_keys($menu);

    foreach ($messages as $msg) {
        if (!isset($msg['role'], $msg['text']) || strtolower($msg['role']) !== 'user') continue;

        $text = trim($msg['text']);
        if ($text === '') continue;

        // normalize text: remove punctuation but keep words/spaces
        $clean = mb_strtolower($text, 'UTF-8');
        $clean = preg_replace('/[^\p{L}\p{N}\s-]/u', ' ', $clean);
        $clean = preg_replace('/\s+/', ' ', $clean);
        // remove filler words that commonly break detection
        $clean = preg_replace('/\b(of|please|get|me|some|a|an|the|for|cup|cups|x)\b/u', ' ', $clean);
        $clean = trim($clean);

        if ($clean === '') continue;

        // Patterns: quantity-first OR item-first
        $patterns = [
            '/\b(\d+|[a-zA-Z\s-]+)\s+(?:x|pcs|pieces|cups|cup|plates|plate|bottles|bottle|orders|order)?\s*([a-zA-Z][a-zA-Z0-9\s\-]*)\b/u',
            '/\b([a-zA-Z][a-zA-Z0-9\s\-]*)\s+(?:x|of|for)?\s*(\d+|[a-zA-Z\s-]+)\b/u'
        ];

        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $clean, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $m) {
                    // choose which group is qty vs item
                    $part1 = trim($m[1]);
                    $part2 = trim($m[2]);

                    // determine qty
                    $qty = 0;
                    if (is_numeric($part1)) {
                        $qty = (int)$part1;
                        $rawItem = $part2;
                    } elseif (is_numeric($part2)) {
                        $qty = (int)$part2;
                        $rawItem = $part1;
                    } else {
                        // words -> number attempt
                        $n1 = wordsToNumber($part1);
                        $n2 = wordsToNumber($part2);
                        if ($n1 > 0) {
                            $qty = $n1;
                            $rawItem = $part2;
                        } elseif ($n2 > 0) {
                            $qty = $n2;
                            $rawItem = $part1;
                        } else {
                            // neither side is a number — skip
                            continue;
                        }
                    }

                    if ($qty <= 0) continue;

                    // normalize item: singularize simple plural by trimming trailing 's' if present
                    $rawItem = normalizeItemName($rawItem);

                    // fuzzy match to menu keys
                    $matched = fuzzyMatchMenuItem($rawItem, $menuKeys);
                    if ($matched === '') continue; // unknown item — skip

                    $price = $menu[$matched] ?? 0;
                    if ($price <= 0) continue;

                    // consolidate duplicates (similar items)
                    $found = false;
                    foreach ($items as &$it) {
                        if (mb_strtolower($it['item'], 'UTF-8') === mb_strtolower($matched, 'UTF-8')) {
                            $it['quantity'] += $qty;
                            $it['total'] = $it['quantity'] * $it['price'];
                            $found = true;
                            break;
                        }
                    }
                    if (!$found) {
                        $items[] = [
                            'item' => $matched,
                            'quantity' => $qty,
                            'price' => (float)$price,
                            'total' => (float)$price * $qty
                        ];
                    }
                }
            }
        }
    }

    return $items;
}

/**
 * Simple normalization: collapse extra spaces, remove trailing 's' for naive plural handling.
 */
function normalizeItemName(string $name): string
{
    $n = preg_replace('/\s+/', ' ', trim(mb_strtolower($name, 'UTF-8')));
    // remove 'cup' or 'cups' that may remain
    $n = preg_replace('/\b(cup|cups|piece|pieces|plate|plates|bottle|bottles)\b/', '', $n);
    $n = trim($n);
    // naive singularize
    if (mb_substr($n, -1) === 's') {
        $n2 = mb_substr($n, 0, mb_strlen($n) - 1);
        if (mb_strlen($n2) > 1) $n = $n2;
    }
    return trim($n);
}

/**
 * Fuzzy matching of input to menu keys. Returns matched menu key exactly as in $menuKeys (case preserved).
 * Accepts exact, substring, or Levenshtein/ similarity based match.
 */
function fuzzyMatchMenuItem(string $input, array $menuKeys): string
{
    $input = mb_strtolower(trim($input), 'UTF-8');
    if ($input === '') return '';

    $best = '';
    $bestScore = 0;

    foreach ($menuKeys as $menuItem) {
        $menuLower = mb_strtolower($menuItem, 'UTF-8');

        // direct equality or substring
        if ($input === $menuLower) return $menuItem;
        if (mb_stripos($menuLower, $input) !== false || mb_stripos($input, $menuLower) !== false) {
            return $menuItem;
        }

        // similarity (percentage)
        similar_text($input, $menuLower, $perc);
        if ($perc > $bestScore) {
            $bestScore = $perc;
            $best = $menuItem;
        }

        // alternative: normalized levenshtein distance (only for short strings)
        $distance = levenshtein($input, $menuLower);
        $len = max(mb_strlen($input), mb_strlen($menuLower));
        if ($len > 0) {
            $levScore = (1 - ($distance / $len)) * 100;
            if ($levScore > $bestScore) {
                $bestScore = $levScore;
                $best = $menuItem;
            }
        }
    }

    return ($bestScore >= 70) ? $best : '';
}

/**
 * Convert words like "twenty five" -> 25, "one hundred" -> 100
 */
function wordsToNumber(string $words): int
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

    $words = preg_split('/[\s-]+/', mb_strtolower(trim($words), 'UTF-8'));
    $total = 0;
    $current = 0;
    foreach ($words as $w) {
        if (!isset($map[$w])) continue;
        $val = $map[$w];
        if ($val >= 100) {
            $current = ($current === 0 ? 1 : $current) * $val;
            if ($val === 1000) {
                $total += $current;
                $current = 0;
            }
        } else {
            $current += $val;
        }
    }
    return $total + $current;
}
