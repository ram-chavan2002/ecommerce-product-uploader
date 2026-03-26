<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

define('DB_HOST', 'localhost');
define('DB_USER', 'sai7755_blog');
define('DB_PASS', ' RamSai@2026#FK');
define('DB_NAME', 'sai7755_blog');

$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if (!$conn) {
    die("<h2 style='color:red;font-family:sans-serif'>❌ DB Error: " . mysqli_connect_error() . "</h2>");
}
mysqli_set_charset($conn, 'utf8mb4');

function clean($conn, $data) {
    return mysqli_real_escape_string($conn, trim($data));
}
?>


