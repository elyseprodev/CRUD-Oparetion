<?php
require_once __DIR__ . "/connection.php";

function escapeHtml($value)
{
    return htmlspecialchars((string) ($value ?? ""), ENT_QUOTES, "UTF-8");
}

$fieldNames = array(
    "product_code",
    "product_name",
    "category",
    "brand",
    "description",
    "quantity",
    "unit_price",
    "supplier",
    "date_added"
);
$product = array_fill_keys($fieldNames, "");
$errorMessage = "";
$fieldErrors = array();
$fieldLabels = array(
    "product_code" => "Product code",
    "product_name" => "Product name",
    "category" => "Category",
    "brand" => "Brand",
    "description" => "Description",
    "quantity" => "Quantity",
    "unit_price" => "Unit price",
    "supplier" => "Supplier",
    "date_added" => "Date added"
);
$idSource = $_SERVER["REQUEST_METHOD"] === "POST" ? ($_POST["id"] ?? "") : ($_GET["id"] ?? "");
$id = filter_var($idSource, FILTER_VALIDATE_INT);

if ($id === false || $id < 1) {
    http_response_code(400);
    $errorMessage = "A valid product ID is required.";
} else {
    try {
        $select = $conn->prepare("SELECT " . implode(", ", $fieldNames) . " FROM products WHERE id = ?");
        $select->bind_param("i", $id);
        $select->execute();
        $result = $select->get_result();
        $storedProduct = $result->fetch_assoc();
        $select->close();

        if ($storedProduct === null) {
            http_response_code(404);
            $errorMessage = "The requested product was not found.";
        } else {
            $product = $storedProduct;
        }
    } catch (mysqli_sql_exception $exception) {
        error_log("Product retrieval for editing failed: " . $exception->getMessage());
        http_response_code(500);
        $errorMessage = "Unable to load the product. Please try again later.";
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && $errorMessage === "") {
    foreach ($fieldNames as $name) {
        $product[$name] = trim($_POST[$name] ?? "");
        if ($product[$name] === "") {
            $fieldErrors[$name] = $fieldLabels[$name] . " is required.";
        }
    }

    $quantity = filter_var($product["quantity"], FILTER_VALIDATE_INT);
    $unitPrice = $product["unit_price"];

    if (!isset($fieldErrors["quantity"]) && ($quantity === false || $quantity < 0 || $quantity > 2147483647)) {
        $fieldErrors["quantity"] = "Quantity must be a whole number from 0 to 2,147,483,647.";
    }
    if (
        !isset($fieldErrors["unit_price"])
        && (!preg_match('/\A(?:\d+(?:\.\d{1,2})?|\.\d{1,2})\z/', $unitPrice)
            || (float) $unitPrice < 0
            || (float) $unitPrice > 99999999.99)
    ) {
        $fieldErrors["unit_price"] = "Unit price must be between 0 and 99,999,999.99, with no more than 2 decimal places.";
    }
    if (!isset($fieldErrors["date_added"])) {
        $dateAdded = DateTime::createFromFormat("!Y-m-d", $product["date_added"]);
        if ($dateAdded === false || $dateAdded->format("Y-m-d") !== $product["date_added"]) {
            $fieldErrors["date_added"] = "Enter a valid date.";
        }
    }

    if (count($fieldErrors) > 0) {
        $errorMessage = implode(" ", array_values($fieldErrors));
    } else {
        try {
            $duplicateCheck = $conn->prepare(
                "SELECT id FROM products WHERE product_code = ? AND id <> ? LIMIT 1"
            );
            $duplicateCheck->bind_param("si", $product["product_code"], $id);
            $duplicateCheck->execute();
            $duplicate = $duplicateCheck->get_result()->fetch_assoc();
            $duplicateCheck->close();

            if ($duplicate !== null) {
                $errorMessage = "This product code is already in use. Enter a different product code.";
            } else {
                $update = $conn->prepare(
                    "UPDATE products
                     SET product_code = ?, product_name = ?, category = ?, brand = ?, description = ?,
                         quantity = ?, unit_price = ?, supplier = ?, date_added = ?
                     WHERE id = ?"
                );
                $update->bind_param(
                    "sssssidssi",
                    $product["product_code"],
                    $product["product_name"],
                    $product["category"],
                    $product["brand"],
                    $product["description"],
                    $quantity,
                    $unitPrice,
                    $product["supplier"],
                    $product["date_added"],
                    $id
                );
                $update->execute();
                $update->close();

                header("Location: view_product.php?status=updated");
                exit;
            }
        } catch (mysqli_sql_exception $exception) {
            error_log("Product update failed: " . $exception->getMessage());
            $errorMessage = "Unable to update the product. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="page-edit">
    <h1>Edit Product</h1>
    <p><a href="view_product.php">Back to products</a></p>

    <?php if ($errorMessage !== ""): ?>
        <p role="alert"><?php echo escapeHtml($errorMessage); ?></p>
    <?php endif; ?>

    <?php if ($errorMessage === "" || $id !== false && $id > 0 && isset($storedProduct)): ?>
        <form method="post" action="edit_product.php">
            <input type="hidden" name="id" value="<?php echo escapeHtml($id); ?>">
            <p>
                <label for="product_code">Product code</label>
                <input id="product_code" name="product_code" type="text" maxlength="20" required value="<?php echo escapeHtml($product["product_code"]); ?>">
            </p>
            <p>
                <label for="product_name">Product name</label>
                <input id="product_name" name="product_name" type="text" maxlength="100" required value="<?php echo escapeHtml($product["product_name"]); ?>">
            </p>
            <p>
                <label for="category">Category</label>
                <input id="category" name="category" type="text" maxlength="50" required value="<?php echo escapeHtml($product["category"]); ?>">
            </p>
            <p>
                <label for="brand">Brand</label>
                <input id="brand" name="brand" type="text" maxlength="50" required value="<?php echo escapeHtml($product["brand"]); ?>">
            </p>
            <p>
                <label for="description">Description</label>
                <textarea id="description" name="description" maxlength="250" required><?php echo escapeHtml($product["description"]); ?></textarea>
            </p>
            <p>
                <label for="quantity">Quantity in stock</label>
                <input id="quantity" name="quantity" type="number" min="0" max="2147483647" step="1" required value="<?php echo escapeHtml($product["quantity"]); ?>">
            </p>
            <p>
                <label for="unit_price">Unit price</label>
                <input id="unit_price" name="unit_price" type="number" min="0" max="99999999.99" step="0.01" required value="<?php echo escapeHtml($product["unit_price"]); ?>">
            </p>
            <p>
                <label for="supplier">Supplier</label>
                <input id="supplier" name="supplier" type="text" maxlength="100" required value="<?php echo escapeHtml($product["supplier"]); ?>">
            </p>
            <p>
                <label for="date_added">Date added</label>
                <input id="date_added" name="date_added" type="date" required value="<?php echo escapeHtml($product["date_added"]); ?>">
            </p>
            <button type="submit">Save changes</button>
        </form>
    <?php endif; ?>
</body>
</html>
