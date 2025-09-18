<?php

//Mysql connection
$host='localhost';
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
//Open AI Key
$geminiApiKey = "";
