<?php
// Functions/menu_parse.php
require __DIR__ . '/../vendor/autoload.php';

use Smalot\PdfParser\Parser;

/**
 * Parse menu PDF and return formatted text
 *
 * @param string $filePath Path to the menu PDF
 * @return string Formatted menu text or error message
 */
function getMenuText(string $filePath): string
{
    // Input validation
    if (empty($filePath)) {
        return "❌ Error: File path cannot be empty";
    }

    if (!file_exists($filePath)) {
        return "❌ Error: Menu file not found";
    }

    if (!is_readable($filePath)) {
        return "❌ Error: Menu file is not readable";
    }

    // Check file extension
    $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
    if ($extension !== 'pdf') {
        return "❌ Error: Only PDF files are supported";
    }

    try {
        // Parse PDF
        $parser = new Parser();
        $pdf = $parser->parseFile($filePath);
        $text = $pdf->getText();

        if (empty(trim($text))) {
            return "❌ Error: PDF appears to be empty or contains only images";
        }

        // Extract menu items
        $lines = preg_split("/\r\n|\n|\r/", $text);
        $menu = [];
        $categories = [];
        $currentCategory = 'General';

        // Category detection patterns
        $categoryPatterns = [
            '/^(APPETIZERS?|STARTERS?|DRINKS?|BEVERAGES?|MAIN\s*COURSES?|ENTREES?|DESSERTS?|SALADS?|SOUPS?|PIZZA|BURGERS?|SANDWICHES?|PASTA|SEAFOOD|MEAT|VEGETARIAN|SIDES?)\s*:?\s*$/i'
        ];

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;

            // Check for category headers
            foreach ($categoryPatterns as $pattern) {
                if (preg_match($pattern, $line, $matches)) {
                    $currentCategory = ucwords(strtolower(trim($matches[1])));
                    if (!in_array($currentCategory, $categories)) {
                        $categories[] = $currentCategory;
                    }
                    continue 2; // Skip to next line
                }
            }

            // Enhanced price matching patterns
            $pricePatterns = [
                // "Meal Name .... 250" or "Meal Name - 250"
                '/^(.*?)\s+[-:.]{1,}\s*(\d+(?:\.\d{1,2})?)$/',
                // "Meal Name 250" (direct)
                '/^(.*?)\s+(\d+(?:\.\d{1,2})?)$/',
                // "Meal Name KSH 250"
                '/^(.*?)\s+(?:KSH?\s*)?(\d+(?:\.\d{1,2})?)$/i'
            ];

            foreach ($pricePatterns as $pattern) {
                if (preg_match($pattern, $line, $matches)) {
                    $meal = cleanMealName($matches[1]);
                    $price = floatval($matches[2]);

                    // Skip if meal name is too short or price is 0
                    if (strlen($meal) < 3 || $price <= 0) {
                        continue 2;
                    }

                    // Skip common non-menu items
                    if (isNonMenuItem($meal)) {
                        continue 2;
                    }

                    $menu[] = [
                        'name' => $meal,
                        'price' => $price,
                        
                    ];
                    break; // Found match, skip other patterns
                }
            }
        }

        // Generate formatted output
        if (empty($menu)) {
            return "📋 Menu is empty or could not be parsed";
        }

        $output = "📋 **CHILLAZI FOODMART MENU**\n";
        $output .= "🍽️ Total Items: " . count($menu) . "\n\n";

        // Group by category if multiple categories found
        if (count($categories) > 1) {
            $currentCat = '';
            foreach ($menu as $item) {
                if ($item['category'] !== $currentCat) {
                    $currentCat = $item['category'];
                    $output .= "\n🏷️ **" . strtoupper($currentCat) . "**\n";
                    $output .= str_repeat("─", 25) . "\n";
                }
                $output .= "🍴 {$item['name']} - KSH " . number_format($item['price'], 0) . "\n";
            }
        } else {
            // Simple list without categories
            foreach ($menu as $item) {
                $output .= "🍴 {$item['name']} - KSH " . number_format($item['price'], 0) . "\n";
            }
        }

        return $output;
    } catch (Exception $e) {
        error_log("Menu parsing error: " . $e->getMessage());
        return "❌ Error: Failed to parse menu PDF - " . $e->getMessage();
    }
}

/**
 * Helper function to clean meal names
 *
 * @param string $meal Raw meal name
 * @return string Cleaned meal name
 */
function cleanMealName(string $meal): string
{
    // Remove dots, dashes at the end, extra whitespace
    $meal = preg_replace('/[.\-_]+$/', '', $meal);
    $meal = preg_replace('/\s+/', ' ', $meal);
    return trim($meal);
}

/**
 * Helper function to check if text is likely not a menu item
 *
 * @param string $text Text to check
 * @return bool True if not a menu item
 */
function isNonMenuItem(string $text): bool
{
    $nonMenuPatterns = [
        '/^(page|total|subtotal|tax|service|delivery|minimum|maximum|tel|phone|email|address|open|closed|hours)/i',
        '/^(terms|conditions|note|notice|copyright|chillazi|foodmart)/i',
        '/^\d+$/', // Just numbers
        '/^[a-z]$/i' // Single letters
    ];

    foreach ($nonMenuPatterns as $pattern) {
        if (preg_match($pattern, $text)) {
            return true;
        }
    }

    return false;
}

// Legacy function for backward compatibility
function parseMenu(string $filePath): array
{
    $menuText = getMenuText($filePath);

    if (strpos($menuText, '❌ Error:') === 0) {
        return ['error' => substr($menuText, 10)]; // Remove "❌ Error: " prefix
    }

    // Extract items from formatted text (simple regex)
    $menu = [];
    if (preg_match_all('/🍴\s+(.*?)\s+-\s+KSH\s+(\d+(?:,\d{3})*(?:\.\d{2})?)/i', $menuText, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
            $meal = trim($match[1]);
            $price = str_replace(',', '', $match[2]); // Remove comma formatting
            $menu[$meal] = $price;
        }
    }

    return $menu;
}
