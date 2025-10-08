<?php

require __DIR__ . '/Functions/scraper_summary.php';
require __DIR__ . '/Config/config.php'; // must define $apiKey

$urls = "http://127.0.0.1/Chillazi/index.php";
$result = scrapeAndSummarize($siteUrl);

header('Content-Type: application/json');
echo json_encode(['scraped' => $result], JSON_PRETTY_PRINT);
