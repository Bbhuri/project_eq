<?php
$mysqli = new mysqli("localhost", "root", "", "public");
if ($mysqli->connect_errno) {
    die("DB Connection failed: " . $mysqli->connect_error);
}
$mysqli->set_charset("utf8mb4");
?>
