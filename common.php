<?php

include '../forum/config.php';

function connectDB() {
    $conn_string = "host=" . $dbhost . " port=DB_PORT" . " dbname=DB_NAME" . 
    " user=" . $dbuser . " password=" .$dbpasswd;
    
    $conn = pg_connect($conn_string);
    
    if (!$conn) {
        echo "no conn";
      $message = "Não foi possível ligar à base de dados.";
      error_log("CREATE: Could not connect to database.");
      return NULL;
    }
    
    unset($dbpasswd);

    return $conn;
}

function closeDBConnection($conn) {
    pg_close($conn);
}

function getUserDataFromToken($token) {

}