<?php
// Functions/getStaticContext.php
// returns ['scraped'=>string, 'menu_array'=>[ 'Milkshake'=>250, ... ]]

include_once __DIR__ . '/scraper_summary.php'; // optional - only if you have it

function getStaticContext($mysqli, string $siteUrl = ''): array
{
    static $cache = null;
    if ($cache !== null) return $cache;

    // 1) scraped text (optional)
    $scraped = '';
    if (!empty($siteUrl) && function_exists('scrapeAndSummarize')) {
        try {
            $scraped = (string) scrapeAndSummarize($siteUrl);
        } catch (Throwable $e) {
            error_log("Scrape error: " . $e->getMessage());
            $scraped = '';
        }
    }

    // 2) menu from DB
    $menu_array = [];
    $sql = "SELECT menu_name, menu_price FROM menus ORDER BY menu_name ASC";
    $res = mysqli_query($mysqli, $sql);
    if ($res) {
        //In
        while ($row = mysqli_fetch_assoc($res)) {
            $name = trim($row['menu_name']);
            if ($name === '') continue;
            // normalize name exactly as DB (keep original casing)
            $menu_array[$name] = (float)$row['menu_price'];
        }
        mysqli_free_result($res);
        // Insert Header in the menu array(meals,categories and prices) 
        $menu_array = array_merge(['Meals', 'Categories', 'Prices'], $menu_array);
    }
    $cache = [
        'scraped' => $scraped,
        'menu_array' => $menu_array
    ];
    return $cache;
}
