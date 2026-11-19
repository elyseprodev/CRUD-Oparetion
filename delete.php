<?php
require_once __DIR__ . "/connection.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    header("Allow: POST");
    exit("Use the delete button on the product list to delete a product.");
}

$id = filter_var($_POST["id"] ?? "", FILTER_VALIDATE_INT);
if ($id === false || $id < 1) {
    http_response_code(400);
    exit("Invalid product ID.");
}

try {
    $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $deleted = $stmt->affected_rows > 0;
    $stmt->close();

    header("Location: view_product.php?status=" . ($deleted ? "deleted" : "missing"));
    exit;
} catch (mysqli_sql_exception $exception) {
    error_log("Product deletion failed: " . $exception->getMessage());
    http_response_code(500);
    exit("Unable to delete the product. Please try again later.");
}
?>