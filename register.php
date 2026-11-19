<?php
require_once __DIR__ . "/connection.php";

$fields = array(
    "product_code" => "",
    "product_name" => "",
    "category" => "",
    "brand" => "",
    "description" => "",
    "quantity" => "",
    "unit_price" => "",
    "supplier" => "",
    "date_added" => ""
);
$successMessage = "";
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

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    foreach ($fields as $name => $value) {
        $fields[$name] = trim($_POST[$name] ?? "");
        if ($fields[$name] === "") {
            $fieldErrors[$name] = $fieldLabels[$name] . " is required.";
        }
    }

    $quantity = filter_var($fields["quantity"], FILTER_VALIDATE_INT);
    $unitPrice = $fields["unit_price"];

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
        $dateAdded = DateTime::createFromFormat("!Y-m-d", $fields["date_added"]);
        if ($dateAdded === false || $dateAdded->format("Y-m-d") !== $fields["date_added"]) {
            $fieldErrors["date_added"] = "Enter a valid date.";
        }
    }

    if (count($fieldErrors) > 0) {
        $errorMessage = implode(" ", array_values($fieldErrors));
    } else {
        try {
            $duplicateCheck = $conn->prepare("SELECT id FROM products WHERE product_code = ? LIMIT 1");
            $duplicateCheck->bind_param("s", $fields["product_code"]);
            $duplicateCheck->execute();
            $duplicate = $duplicateCheck->get_result()->fetch_assoc();
            $duplicateCheck->close();

            if ($duplicate !== null) {
                $errorMessage = "This product code is already in use. Enter a different product code.";
            } else {
                $stmt = $conn->prepare(
                    "INSERT INTO products
                        (product_code, product_name, category, brand, description, quantity, unit_price, supplier, date_added)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
                );
                $stmt->bind_param(
                    "sssssidss",
                    $fields["product_code"],
                    $fields["product_name"],
                    $fields["category"],
                    $fields["brand"],
                    $fields["description"],
                    $quantity,
                    $unitPrice,
                    $fields["supplier"],
                    $fields["date_added"]
                );
                $stmt->execute();
                $stmt->close();

                $successMessage = "Product registered successfully.";
                foreach ($fields as $name => $value) {
                    $fields[$name] = "";
                }
            }
        } catch (mysqli_sql_exception $exception) {
            error_log("Product registration failed: " . $exception->getMessage());
            $errorMessage = "Could not register the product. Please try again.";
        }
    }
}

function escapeHtml($value)
{
    return htmlspecialchars($value, ENT_QUOTES, "UTF-8");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Product</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="page-register">
    <h1>Product Registration</h1>
    <p><a href="view_product.php">View products</a></p>

    <?php if ($successMessage !== ""): ?>
        <p role="status"><?php echo escapeHtml($successMessage); ?></p>
    <?php endif; ?>
    <?php if ($errorMessage !== ""): ?>
        <p role="alert"><?php echo escapeHtml($errorMessage); ?></p>
    <?php endif; ?>

    <form method="post" action="">
        <p>
            <label for="product_code">Product code</label>
            <input id="product_code" name="product_code" type="text" maxlength="20" required value="<?php echo escapeHtml($fields["product_code"]); ?>">
        </p>
        <p>
            <label for="product_name">Product name</label>
            <input id="product_name" name="product_name" type="text" maxlength="100" required value="<?php echo escapeHtml($fields["product_name"]); ?>">
        </p>
        <p>
            <label for="category">Category</label>
            <input id="category" name="category" type="text" maxlength="50" required value="<?php echo escapeHtml($fields["category"]); ?>">
        </p>
        <p>
            <label for="brand">Brand</label>
            <input id="brand" name="brand" type="text" maxlength="50" required value="<?php echo escapeHtml($fields["brand"]); ?>">
        </p>
        <p>
            <label for="description">Description</label>
            <textarea id="description" name="description" maxlength="250" required><?php echo escapeHtml($fields["description"]); ?></textarea>
        </p>
        <p>
            <label for="quantity">Quantity in stock</label>
            <input id="quantity" name="quantity" type="number" min="0" max="2147483647" step="1" required value="<?php echo escapeHtml($fields["quantity"]); ?>">
        </p>
        <p>
            <label for="unit_price">Unit price</label>
            <input id="unit_price" name="unit_price" type="number" min="0" max="99999999.99" step="0.01" required value="<?php echo escapeHtml($fields["unit_price"]); ?>">
        </p>
        <p>
            <label for="supplier">Supplier</label>
            <input id="supplier" name="supplier" type="text" maxlength="100" required value="<?php echo escapeHtml($fields["supplier"]); ?>">
        </p>
        <p>
            <label for="date_added">Date added</label>
            <input id="date_added" name="date_added" type="date" required value="<?php echo escapeHtml($fields["date_added"]); ?>">
        </p>
        <button type="submit">Register product</button>
    </form>
</body>
</html>