
<?php
require_once "db.php";

$sql = "SELECT * FROM students ORDER BY id DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Student Management</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>

<div class="container">

    <h2>Student Management System</h2>

    <?php if (isset($_GET["success"])): ?>
        <p class="success">Operation completed successfully!</p>
    <?php endif; ?>

    <a href="create.php" class="add-btn">+ Add Student</a>

    <table>
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Email</th>
            <th>Age</th>
            <th>Course</th>
            <th>Actions</th>
        </tr>

        <?php while ($row = $result->fetch_assoc()): ?>

        <tr>
            <td><?= htmlspecialchars($row["id"]) ?></td>
            <td><?= htmlspecialchars($row["name"]) ?></td>
            <td><?= htmlspecialchars($row["email"]) ?></td>
            <td><?= htmlspecialchars($row["age"]) ?></td>
            <td><?= htmlspecialchars($row["course"]) ?></td>

            <td>
                <a href="edit.php?id=<?= (int)$row["id"] ?>"
                   class="edit">Edit</a>

                <a href="delete.php?id=<?= (int)$row["id"] ?>"
                   class="delete"
                   onclick="return confirm('Delete this student?')">
                   Delete
                </a>
            </td>
        </tr>

        <?php endwhile; ?>

    </table>

</div>

</body>
</html>
