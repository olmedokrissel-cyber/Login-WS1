<?php
session_start();
require 'db.php'; 

if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role']) !== 'admin') {
    header("Location: login.php");
    exit();
}
if (isset($_POST['create_user'])) {
    $firstname = trim($_POST['firstname']);
    $lastname = trim($_POST['lastname']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $role = strtolower($_POST['role']);

    $check_stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $check_stmt->bind_param("ss", $username, $email);
    $check_stmt->execute();
    
    if ($check_stmt->get_result()->num_rows > 0) {
        $_SESSION['msg_error'] = "Username or Email already exists!";
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $conn->prepare("INSERT INTO users (firstname, lastname, username, email, password, role) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $firstname, $lastname, $username, $email, $hashed_password, $role);
        
        if ($stmt->execute()) {
            $_SESSION['msg'] = ucfirst($role) . " account created successfully!";
        } else {
            $_SESSION['msg_error'] = "Failed to create account.";
        }
        $stmt->close();
    }
    $check_stmt->close();
    header("Location: admin_dash.php");
    exit();
}
if (isset($_POST['reset_password'])) {
    $target_id = intval($_POST['user_id']);
    $new_pass = $_POST['new_password'];
    $hashed = password_hash($new_pass, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
    $stmt->bind_param("si", $hashed, $target_id);
    if ($stmt->execute()) {
        $_SESSION['msg'] = "Password reset successfully!";
    } else {
        $_SESSION['msg_error'] = "Failed to reset password.";
    }
    $stmt->close();
    header("Location: admin_dash.php");
    exit();
}
if (isset($_POST['update_account'])) {
    $target_id = intval($_POST['user_id']);
    $uname = trim($_POST['username']);
    $email = trim($_POST['email']);

    $stmt = $conn->prepare("UPDATE users SET username = ?, email = ? WHERE id = ?");
    $stmt->bind_param("ssi", $uname, $email, $target_id);
    if ($stmt->execute()) {
        $_SESSION['msg'] = "User details updated!";
    } else {
        $_SESSION['msg_error'] = "Failed to update user.";
    }
    $stmt->close();
    header("Location: admin_dash.php");
    exit();
}

if (isset($_POST['delete_user'])) {
    $target_id = intval($_POST['user_id']);

    if ($target_id === $_SESSION['user_id']) {
        $_SESSION['msg_error'] = "You cannot delete your own account!";
    } else {
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $target_id);
        if ($stmt->execute()) {
            $_SESSION['msg'] = "User deleted successfully!";
        } else {
            $_SESSION['msg_error'] = "Failed to delete user.";
        }
        $stmt->close();
    }
    header("Location: admin_dash.php");
    exit();
}

// Logged-in nga Admin Profile
$stmt = $conn->prepare("SELECT username, email FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$profile = $stmt->get_result()->fetch_assoc();
$stmt->close();

$all_users = $conn->query("SELECT id, username, email, role FROM users ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="dashstyle.css?v=<?php echo time(); ?>">
</head>
<body>
    <div class="sidebar-container">
        <div class="sidebar">
            <h2>Admin Panel</h2>
            <ul class="sidebar-menu">
                <li><a href="index.php" class="active">Welcome Portal</a></li>
                <li><a href="admin_dash.php">Manage Users</a></li>
                <li><a href="#settings">System Settings</a></li>
                <li><a href="logout.php" class="logout-btn">Logout</a></li>
            </ul>
        </div>
    </div>
    <div class="main-content">
        
        <?php 
        if (isset($_SESSION['msg'])) {
            echo "<div class='alert-success'>" . $_SESSION['msg'] . "</div>";
            unset($_SESSION['msg']);
        }
        if (isset($_SESSION['msg_error'])) {
            echo "<div class='alert-error'>" . $_SESSION['msg_error'] . "</div>";
            unset($_SESSION['msg_error']);
        }
        ?>
        <div class="card">
            <h1>System Administrator Dashboard</h1>
            <?php if ($profile): ?>
                <p style="text-align:center;">Logged in as: <strong><?= htmlspecialchars($profile['username']) ?></strong></p>
            <?php endif; ?>
        </div>

    <div class="card">
        <h2>Add Staff Account</h2>
        <form action="admin_dash.php" method="POST" class="create-form">
            <input type="text" name="firstname" placeholder="First Name" required>
            <input type="text" name="lastname" placeholder="Last Name" required>
            <input type="text" name="username" placeholder="Username" required>
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="password" placeholder="Password" required minlength="6">
                
            <select name="role" required>
                <option value="teacher">Teacher</option>
                <option value="principal">Principal</option>
                <option value="admin">Admin</option>
                </select>
                
                <button type="submit" name="create_user" class="btn-create">+ Create Account</button>
            </form>
        </div>
        <div class="card">
            <h2>User Management</h2>
            <table class="admin-table">
            <thead>
            <tr>
                <th>ID</th>
                <th>Role</th>
                <th>Username / Email</th>
                <th>Reset Password</th>
                <th>Actions</th>
            </tr>
                </thead>
                <tbody>
                    <?php while ($row = $all_users->fetch_assoc()): ?>
                    <tr>
                        <td><?= $row['id'] ?></td>
                        <td><strong><?= strtoupper(htmlspecialchars($row['role'])) ?></strong></td>
                    
            <td>
                <form action="admin_dash.php" method="POST" class="inline-form">
                    <input type="hidden" name="user_id" value="<?= $row['id'] ?>">
                    <input type="text" name="username" value="<?= htmlspecialchars($row['username']) ?>" required>
                    <input type="email" name="email" value="<?= htmlspecialchars($row['email'] ?? '') ?>" placeholder="Email">
                    <button type="submit" name="update_account" class="btn-action btn-update">Save</button>
                </form>
            </td>

            <td>
                <form action="admin_dash.php" method="POST" class="inline-form">
                    <input type="hidden" name="user_id" value="<?= $row['id'] ?>">
                    <input type="password" name="new_password" placeholder="New Pass" required minlength="6">
                    <button type="submit" name="reset_password" class="btn-action btn-reset">Reset</button>
                </form>
            </td>

            <td>
                <form action="admin_dash.php" method="POST" class="inline-form" onsubmit="return confirm('Are you sure you want to delete this user?');">
                <input type="hidden" name="user_id" value="<?= $row['id'] ?>">
                <button type="submit" name="delete_user" class="btn-action btn-delete">Delete</button>
                </form>
            </td>
            </tr>
            <?php endwhile; ?>
            </tbody>
            </table>
        </div>

    </div>

</body>
</html>