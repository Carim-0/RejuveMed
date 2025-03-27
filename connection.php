<?php
    $serverName = "bjdgli1y1vghfnu3p75x-mysql.services.clever-cloud.com";
    $databaseName = "bjdgli1y1vghfnu3p75x";
    $username = "ua65hpg0pdl8upxh";
    $password = "m97GGgIuFyMUsSplCjxD";

    try {
        $con = new mysqli($serverName, $username, $password, $databaseName);

        // Check connection
        if ($con->connect_error) {
            throw new Exception("Connection failed: " . $con->connect_error);
        } 
    } catch (Exception $e) {
        die("Database connection failed: " . $e->getMessage());
    }
?>