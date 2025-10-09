<?php

//Mysql connection
$host='127.0.0.1';
$user='root';
$pass='';
$db= 'chillazi';

$mysqli=new mysqli($host,$user,$pass,$db);
try {
    $mysqli->connect($host, $user, $pass, $db);
} catch (Exception $e) {
    echo $e->getMessage();
    exit;
}
//Gemini AI Key
$geminiApiKey = "";
//URL to scrape
$siteUrl = "http://chillazi.devlan.co.ke/index.php";
