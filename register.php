<?php
session_start();
require 'db.php'; 

$msg_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstname = trim($_POST['firstname']);
    $lastname = trim($_POST['lastname']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = trim($_POST['role']);

    if ($password !== $confirm_password) {
        $msg_error = "Passwords do not match!";
    } else {
        $check_stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $check_stmt->bind_param("ss", $username, $email);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows > 0) {
            $msg_error = "Username or Email already exists!";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (firstname, lastname, username, email, password, role) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssss", $firstname, $lastname, $username, $email, $hashed_password, $role);

            if ($stmt->execute()) {
                $_SESSION['msg'] = "Registration successful! You can now log in.";
                header("Location: login.php");
                exit();
            } else {
                $msg_error = "Registration failed. Please try again.";
            }
            $stmt->close();
        }
        $check_stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Form</title>
    <link rel="stylesheet" href="register.css?v=<?php echo time(); ?>">
    <style>
        .alert-error { color: #ee6e6e; font-weight: bold; margin-bottom: 15px; text-align: center; }
    </style>
</head>
<body>

<div class="container">

    <div class="header-text">
        <h2>Registration Form</h2>
        <p>Fill in the details to register</p>
    </div>
    <?php if (!empty($msg_error)): ?>
        <p class="alert-error"><?= htmlspecialchars($msg_error) ?></p>
    <?php endif; ?>

    <form action="register.php" method="POST">

        <div class="input-box">
            <label>First Name</label>
            <input type="text" name="firstname" placeholder="Enter First Name" required value="<?= htmlspecialchars($_POST['firstname'] ?? '') ?>">
        </div>

        <div class="input-box">
            <label>Last Name</label>
            <input type="text" name="lastname" placeholder="Enter Last Name" required value="<?= htmlspecialchars($_POST['lastname'] ?? '') ?>">
        </div>

        <div class="input-box">
            <label>Username</label>
            <input type="text" name="username" placeholder="Enter Username" required value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
        </div>

        <div class="input-box">
            <label>Email</label>
            <input type="email" name="email" placeholder="Enter email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
        </div>

        <div class="input-box">
            <label>Password</label>
            <input type="password" name="password" placeholder="Create password" required>
        </div>

        <div class="input-box">
            <label>Confirm Password</label>
            <input type="password" name="confirm_password" placeholder="Confirm password" required>
        </div>

        <div class="input-box">
            <label>Account Role</label>
            <input type="text" name="role" value="Student" required>
        </div>

        <div class="input-box">
            <label>Grade Level</label>
            <input type="text" name="grade_level" placeholder="Enter Grade Level" required>
        </div>

        <div class="input-box">
            <label>Section</label>
            <input type="text" name="section" placeholder="Enter Section" required>
        </div>

        <button type="submit">Register</button>

    </form>

    <div class="links">
        <p>Already have an account? <a href="login.php">Login here</a></p>
    </div>

</div>

</body>
</html>