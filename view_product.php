<?php
require_once __DIR__ . "/connection.php";

$products = array();
$errorMessage = "";

try {
    $result = $conn->query(
        "SELECT id, product_code, product_name, category, brand, description,
                quantity, unit_price, supplier, date_added
         FROM products
         ORDER BY id DESC"
    );

    while ($product = $result->fetch_assoc()) {
        $products[] = $product;
    }

    $result->free();
} catch (mysqli_sql_exception $exception) {
    error_log("Product listing failed: " . $exception->getMessage());
    http_response_code(500);
    $errorMessage = "Unable to retrieve products. Please try again later.";
}

function escapeHtml($value)
{
    return htmlspecialchars((string) ($value ?? ""), ENT_QUOTES, "UTF-8");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Products</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="page-products">
    <h1>Registered Products</h1>
    <p><a href="register.php">Register a product</a></p>

    <?php
    $statusMessages = array(
        "updated" => "Product updated successfully.",
        "deleted" => "Product deleted successfully.",
        "missing" => "The requested product was not found."
    );
    $status = $_GET["status"] ?? "";
    if (isset($statusMessages[$status])):
    ?>
        <p role="status"><?php echo escapeHtml($statusMessages[$status]); ?></p>
    <?php endif; ?>

    <?php if ($errorMessage !== ""): ?>
        <p role="alert"><?php echo escapeHtml($errorMessage); ?></p>
    <?php elseif (count($products) === 0): ?>
        <p>No products have been registered yet.</p>
    <?php else: ?>
        <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Product code</th>
                    <th>Product name</th>
                    <th>Category</th>
                    <th>Brand</th>
                    <th>Description</th>
                    <th>Quantity in stock</th>
                    <th>Unit price</th>
                    <th>Supplier</th>
                    <th>Date added</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $product): ?>
                    <tr>
                        <td><?php echo escapeHtml($product["id"]); ?></td>
                        <td><?php echo escapeHtml($product["product_code"]); ?></td>
                        <td><?php echo escapeHtml($product["product_name"]); ?></td>
                        <td><?php echo escapeHtml($product["category"]); ?></td>
                        <td><?php echo escapeHtml($product["brand"]); ?></td>
                        <td><?php echo escapeHtml($product["description"]); ?></td>
                        <td><?php echo escapeHtml($product["quantity"]); ?></td>
                        <td><?php echo escapeHtml(number_format((float) $product["unit_price"], 2)); ?></td>
                        <td><?php echo escapeHtml($product["supplier"]); ?></td>
                        <td><?php echo escapeHtml($product["date_added"]); ?></td>
                        <td>
                            <div class="product-actions">
                                <a href="edit_product.php?id=<?php echo rawurlencode((string) $product["id"]); ?>">Edit</a>
                                <form method="post" action="delete.php" onsubmit="return confirm('Delete product ID <?php echo escapeHtml($product["id"]); ?>?');">
                                    <input type="hidden" name="id" value="<?php echo escapeHtml($product["id"]); ?>">
                                    <button type="submit">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    <?php endif; ?>
</body>
</html>