
<?php
require_once "db.php";

$name = "";
$email = "";
$age = "";
$course = "";

$errors = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Get form data
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
    if (empty($email)) {
        $errors[] = "Email is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email address.";
    }

    // Validate age
    if (filter_var($age, FILTER_VALIDATE_INT) === false ||
        $age < 1 || $age > 120) {
        $errors[] = "Enter a valid age between 1 and 120.";
    }

    // Validate course
    if (empty($course)) {
        $errors[] = "Course is required.";
    }

    // Insert into database if there are no errors
    if (empty($errors)) {

        $sql = "INSERT INTO students (name, email, age, course)
                VALUES (?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);

        $stmt->bind_param("ssis", $name, $email, $age, $course);

        if ($stmt->execute()) {
            header("Location: index.php?success=created");
            exit();
        } else {
            if ($stmt->errno == 1062) {
                $errors[] = "This email already exists.";
            } else {
                $errors[] = "Error saving student.";
            }
        }

        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Student</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>

<div class="container">

    <h2>Register Student</h2>

    <?php foreach ($errors as $error): ?>
        <p class="error">
            <?= htmlspecialchars($error) ?>
        </p>
    <?php endforeach; ?>

    <form method="POST" action="">

        <label>Student Name</label>
        <input type="text" name="name"
               value="<?= htmlspecialchars($name) ?>"
               placeholder="Enter your name">

        <label>Email Address</label>
        <input type="email" name="email"
               value="<?= htmlspecialchars($email) ?>"
               placeholder="Enter your email">

        <label>Age</label>
        <input type="number" name="age"
               value="<?= htmlspecialchars($age) ?>"
               placeholder="Enter your age">

        <label>Course</label>
        <input type="text" name="course"
               value="<?= htmlspecialchars($course) ?>"
               placeholder="Enter your course">

        <button type="submit">Save Student</button>

    </form>

    <a href="index.php">View Students</a>

</div>

</body>
</html>
