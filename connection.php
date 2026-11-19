<?php
$host = "localhost";
$username = "root";
$password = "";
$database = "product_management";

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli($host, $username, $password, $database);
    $conn->set_charset("utf8mb4");
} catch (mysqli_sql_exception $exception) {
    error_log("Database connection failed: " . $exception->getMessage());
    http_response_code(500);
    exit("Database connection failed. Check that MySQL is running and the database credentials are correct.");
}
?>