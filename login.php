<?php
session_start();
require_once 'db.php';

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login_input'], $_POST['password'])) {
    $login_input = trim($_POST['login_input']);
    $password    = $_POST['password']; 

    $stmt = $conn->prepare("SELECT id, username, password, role FROM users WHERE username = ? OR email = ?");
    $stmt->bind_param("ss", $login_input, $login_input);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        process_user_login($conn, $row, $password, $error);
    } else {
        $stmt->close();

        $admin_stmt = $conn->prepare("SELECT id, username, password, 'admin' AS role FROM admins WHERE username = ? OR email = ?");
        $admin_stmt->bind_param("ss", $login_input, $login_input);
        $admin_stmt->execute();
        $admin_result = $admin_stmt->get_result();

        if ($admin_row = $admin_result->fetch_assoc()) {
            process_user_login($conn, $admin_row, $password, $error, 'admins');
        } else {
            $error = "No account found with that Username or Email!";
        }
        $admin_stmt->close();
    }

    $conn->close();
}
function process_user_login($conn, $row, $password, &$error, $table = 'users') {
    $db_password = $row['password'];

    if (password_verify($password, $db_password) || $password === $db_password) {

        if (password_needs_rehash($db_password, PASSWORD_DEFAULT)) {
            $new_hash = password_hash($password, PASSWORD_DEFAULT);
            $update_stmt = $conn->prepare("UPDATE {$table} SET password = ? WHERE id = ?");
            $update_stmt->bind_param("si", $new_hash, $row['id']);
            $update_stmt->execute();
            $update_stmt->close();
        }

        $_SESSION['user_id']  = $row['id'];
        $_SESSION['username'] = $row['username'];
        $_SESSION['role']     = strtolower(trim($row['role']));

        switch ($_SESSION['role']) {
            case 'admin':
                header("Location: admin_dash.php");
                exit();
            case 'principal':
                header("Location: principal_dash.php");
                exit();
            case 'teacher':
                header("Location: teacher_dash.php");
                exit();
            case 'student':
                header("Location: student_dash.php");
                exit();
            default:
                $error = "Unauthorized user role: " . htmlspecialchars($row['role']);
                break;
        }
    } else {
        $error = "Invalid password!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="loginstyle.css?v=<?php echo time(); ?>">
</head>
<body>

<div class="container">

    <div class="login-box">

        <div class="header-text">
            <p>Please login to your account.</p>
        </div>
        
        <?php if (isset($_SESSION['msg'])): ?>
            <p style="color: #4ade80; text-align: center; margin-bottom: 15px; font-weight: bold;"><?php echo $_SESSION['msg']; unset($_SESSION['msg']); ?></p>
        <?php endif; ?>

        <?php if (isset($_GET['signup']) && $_GET['signup'] === 'success'): ?>
            <p style="color: white; text-align: center; margin-bottom: 15px;">Registration successful! Please log in.</p>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <p style="color: #f87171; text-align: center; margin-bottom: 15px; font-weight: bold;"><?php echo $error; ?></p>
        <?php endif; ?>

        <form action="login.php" method="POST">

            <div class="input-box">
                <label>Username or Email</label>
                <input type="text" name="login_input" placeholder="Enter username or email" required value="<?= htmlspecialchars($_POST['login_input'] ?? '') ?>">
            </div>

            <div class="input-box">
                <label>Password</label>
                <input type="password" name="password" placeholder="Enter password" required>
            </div>

            <button type="submit">Login</button>

        </form>

        <div class="links">
            <a href="password.php">Forgot Password?</a>
        </div>

    </div>

</div>
</body>
</html>