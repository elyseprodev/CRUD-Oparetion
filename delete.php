
<?php

require_once "db.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    exit("Method not allowed.");
}

$id = filter_input(INPUT_POST, "id", FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    die("Invalid student ID.");
}

$sql = "DELETE FROM students WHERE id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    header("Location: index.php?success=deleted");
    exit();
} else {
    echo "Unable to delete student.";
}

$stmt->close();
?>
<form method="POST" action="delete.php"
      onsubmit="return confirm('Are you sure?')">

    <input type="hidden" name="id"
           value="<?= (int)$row["id"] ?>">

    <button type="submit" class="delete">
        Delete
    </button>

</form>