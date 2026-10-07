<?php
session_start();
require 'db.php';


if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'principal') {
    header("Location: login.php");
    exit();
}

$stmt = $conn->prepare("
    SELECT users.username, users.email, principals.office_location 
    FROM users 
    LEFT JOIN principals ON users.id = principals.user_id 
    WHERE users.id = ?
");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$profile = $result->fetch_assoc();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Principal Dashboard</title>
    <link rel="stylesheet" href="dashstyle.css?v=<?php echo time(); ?>">
</head>
<body>

    <div class="sidebar-container">
        <aside class="sidebar">
            <h2>Principal Portal</h2>
            <ul class="sidebar-menu">
                <li><a href="index.php" class="active">Welcome Portal</a></li>
                <li><a href="#School Overview">School Overview</a></li>
                <li><a href="#Faculty List">Faculty List</a></li>
                <li><a href="#Reports">Reports</a></li>
                <li><a href="logout.php" class="logout-btn">Logout</a></li>
            </ul>
        </aside>
    </div>

    <main class="main-content">
        
        <div class="card">
            <div class="dashboard-header">
                <div class="welcome-text">
                    <h1>Principal Dashboard</h1>
                    <p>Welcome, <strong><?= htmlspecialchars($profile['username'] ?? 'User') ?></strong>!</p>
                </div>
            </div>

            <div class="stats-grid">
                <div class="stat-card teachers">
                    <h3>Account Email</h3>
                    <div class="stat-number" style="font-size: 1.1rem; word-break: break-all;">
                        <?= htmlspecialchars($profile['email'] ?? 'N/A') ?>
                    </div>
                </div>

                <div class="stat-card classes">
                    <h3>Office Location</h3>
                    <div class="stat-number" style="font-size: 1.2rem;">
                        <?= htmlspecialchars($profile['office_location'] ?? 'Not Assigned') ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <h2>School Overview</h2>
            <p style="color: #475569; line-height: 1.6; text-align: center;">
                This is your principal dashboard. 
                Only system administrators can manage user accounts and reset passwords.
            </p>
        </div>

    </main>

</body>
</html>