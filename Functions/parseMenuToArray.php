<?php
function parseMenuToArray($menuText)
{
    // Implement menu parsing logic here
    // Example basic implementation:
    $menuArray = [];
    $lines = explode("\n", $menuText);

    foreach ($lines as $line) {
        if (preg_match('/(.+?)\s+(?:KSH|Ksh|ksh)?\s*(\d+)/i', $line, $matches)) {
            $itemName = trim($matches[1]);
            $price = (float) $matches[2];
            $menuArray[$itemName] = $price;
        }
    }

    return $menuArray;
}