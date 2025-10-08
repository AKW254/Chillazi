<?php
//Mysqli connection wich are absolute
include __DIR__ . '/Config/config.php'; // must define $mysqli
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
} else {
    error_log('getStaticContext: DB error - ' . mysqli_error($mysqli));
}

header('Content-Type: application/json');
echo json_encode(['menu' => $menu_array], JSON_PRETTY_PRINT);
