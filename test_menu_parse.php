<?php

require __DIR__ . '/Functions/menu_parser.php';
//require __DIR__ . '/Config/config.php'; // must define $apiKey

$menuFile  = __DIR__ . "/Storage/menu/menu.pdf";
$menuArray = function_exists('parseMenu') ? parseMenu($menuFile) : [];
$menuText  = function_exists('getMenuText') ? getMenuText($menuFile) : "";

header('Content-Type: application/json');
echo json_encode(['menu' => $menuText], JSON_PRETTY_PRINT);
