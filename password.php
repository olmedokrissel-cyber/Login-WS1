<?php
session_start();
require 'db.php'; 

$message = '';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $target_username = trim($_POST['username']);
    $new_password = $_POST['new_password'];

    if (!empty($target_username) && !empty($new_password)) {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE username = ?");
        $stmt->bind_param("ss", $hashed_password, $target_username);

        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                $message = "<p style='color: #2ecc71; font-weight: bold; text-align: center;'>Password updated successfully for '$target_username'!</p>";
            } else {
                $message = "<p style='color: #e74c3c; font-weight: bold; text-align: center;'>Username not found!</p>";
            }
        } else {
            $message = "<p style='color: #e74c3c; font-weight: bold; text-align: center;'>Error updating password: " . $conn->error . "</p>";
        }
        $stmt->close();
    } else {
        $message = "<p style='color: #e74c3c; font-weight: bold; text-align: center;'>Please fill in all fields.</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Reset User Password</title>
    <link rel="stylesheet" href="pastyle.css">
</head>
<body>

<div class="container">

    <div class="login-box">

        <div class="header-text">
            <h1>Reset User Password</h1>
            <p>Admin Tool: Enter target username and new password</p>
        </div>

        <?php if (!empty($message)) { echo $message; } ?>

        <form action="password.php" method="POST">

            <div class="input-box">
                <label>Target Username</label>
                <input type="text" name="username" placeholder="Enter username" required>
            </div>

            <div class="input-box">
                <label>New Password</label>
                <input type="password" name="new_password" placeholder="Enter new password" required minlength="6">
            </div>

            <button type="submit">Reset Password</button>

        </form>

        <div class="links">
            <a href="admin_dash.php">← Back to Admin Dashboard</a>
        </div>

    </div>

</div>

</body>
</html>