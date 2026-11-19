
<?php
require_once "db.php";

// Get student ID from URL
$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    die("Invalid student ID.");
}

// Retrieve student information
$stmt = $conn->prepare("SELECT * FROM students WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();

$result = $stmt->get_result();
$student = $result->fetch_assoc();
$stmt->close();

if (!$student) {
    die("Student not found.");
}

$name = $student["name"];
$email = $student["email"];
$age = $student["age"];
$course = $student["course"];

$errors = [];

// Process update form
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $name = trim($_POST["name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $age = trim($_POST["age"] ?? "");
    $course = trim($_POST["course"] ?? "");

    // Validate name
    if (empty($name)) {
        $errors[] = "Name is required.";
    } elseif (!preg_match("/^[a-zA-Z ]+$/", $name)) {
        $errors[] = "Name must contain letters only.";
    }

    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Enter a valid email.";
    }

    // Validate age
    if (filter_var($age, FILTER_VALIDATE_INT) === false ||
        $age < 1 || $age > 120) {
        $errors[] = "Age must be between 1 and 120.";
    }

    // Validate course
    if (empty($course)) {
        $errors[] = "Course is required.";
    }

    if (empty($errors)) {

        $sql = "UPDATE students
                SET name = ?, email = ?, age = ?, course = ?
                WHERE id = ?";

        $stmt = $conn->prepare($sql);

        $stmt->bind_param(
            "ssisi",
            $name,
            $email,
            $age,
            $course,
            $id
        );

        if ($stmt->execute()) {
            header("Location: index.php?success=updated");
            exit();
        } else {
            if ($stmt->errno == 1062) {
                $errors[] = "Email already belongs to another student.";
            } else {
                $errors[] = "Update failed.";
            }
        }

        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Student</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>

<div class="container">

    <h2>Update Student</h2>

    <?php foreach ($errors as $error): ?>
        <p class="error">
            <?= htmlspecialchars($error) ?>
        </p>
    <?php endforeach; ?>

    <form method="POST">

        <label>Name</label>
        <input type="text" name="name"
               value="<?= htmlspecialchars($name) ?>">

        <label>Email</label>
        <input type="email" name="email"
               value="<?= htmlspecialchars($email) ?>">

        <label>Age</label>
        <input type="number" name="age"
               value="<?= htmlspecialchars($age) ?>">

        <label>Course</label>
        <input type="text" name="course"
               value="<?= htmlspecialchars($course) ?>">

        <button type="submit">Update Student</button>

    </form>

    <a href="index.php">Back to Students</a>

</div>

</body>
</html>
