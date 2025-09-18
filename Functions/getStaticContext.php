<?php
include 'scraper_summary.php';
include 'menu_parser.php';
function getStaticContext()
{
    // Cache static content to avoid reloading every time
    static $staticCache = null;

    if ($staticCache === null) {
        // Load scraped info
        $scraped = scrapeAndSummarize(["http://127.0.0.1/Chillazi/index.php"], 2000);

        // Load menu
        $menuFile = "/../Storage/menu/menu.pdf";
        $menuText = getMenuText(__DIR__ . $menuFile);

        // Parse menu into array for order detection
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

        $staticCache = [
            'scraped' => $scraped,
            'menu_text' => $menuText,
            'menu_array' => parseMenuToArray($menuText)
           
        ];
    }

    return $staticCache;
}