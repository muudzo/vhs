<?php

//DATABASE CONNECTION
$servername = "localhost";
$username = "root";
$password ="Mudzo2608";
$dbname = "tatenda_db";

try{
    //connect to the database
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    //query to fetch  a random riddle 
    $query = "SELECT question , answer FROM riddles ORDER BY RAND() LIMIT 1";
    $stmt = $pdo->prepare($query);

    //fetch the riddle
    $riddle = $stmt->fetch(PDO::FETCH_ASSOC);