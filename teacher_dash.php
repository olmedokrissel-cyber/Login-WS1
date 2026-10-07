<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'teacher') {
    header("Location: login.php");
    exit();
}

$stmt = $conn->prepare("
    SELECT users.username, users.email, teachers.department, teachers.subject 
    FROM users 
    JOIN teachers ON users.id = teachers.user_id 
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
    <title>Teacher Dashboard</title>
    <link rel="stylesheet" href="dashstyle.css?v=<?php echo time(); ?>">
</head>
<body>

    <div class="sidebar-container">
        <aside class="sidebar">
            <h2>Teacher Portal</h2>
            <ul class="sidebar-menu">
                <li><a href="index.php" class="active">Welcome Portal</a></li>
                <li><a href="#classes">My Classes</a></li>
                <li><a href="#students">Students</a></li>
                <li><a href="#grades">Grades</a></li>
                <li><a href="logout.php" class="logout-btn">Logout</a></li>
            </ul>
        </aside>
    </div>

    <main class="main-content">
        <div class="card">
            <div class="dashboard-header">
                <div class="welcome-text">
                    <h1>Teacher Dashboard</h1>
                    <p>Welcome, <strong><?= htmlspecialchars($profile['username'] ?? '') ?></strong>!</p>
                </div>
            </div>

            <?php if ($profile): ?>
                <div class="stats-grid">
                    <div class="stat-card teachers">
                        <h3>Email</h3>
                        <div class="stat-number"><?= htmlspecialchars($profile['email'] ?? 'N/A') ?></div>
                    </div>
                    <div class="stat-card classes">
                        <h3>Subject</h3>
                        <div class="stat-number"><?= htmlspecialchars($profile['subject'] ?? 'N/A') ?></div>
                    </div>
                    <div class="stat-card students">
                        <h3>Area / Branch</h3>
                        <div class="stat-number"><?= htmlspecialchars($profile['department'] ?? 'N/A') ?></div>
                    </div>
                </div>
            <?php else: ?>
                <p class="alert-error">Teacher record not found.</p>
            <?php endif; ?>
        </div>
    </main>

</body>
</html>