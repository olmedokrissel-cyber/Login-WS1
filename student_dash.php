<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'student') {
    header("Location: login.php");
    exit();
}

$stmt = $conn->prepare("
    SELECT users.username, users.email, students.grade_level, students.section 
    FROM users 
    LEFT JOIN students ON users.id = students.user_id 
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
    <title>Student Dashboard</title>
    <link rel="stylesheet" href="dashstyle.css?v=<?php echo time(); ?>">
</head>
<body>

    <div class="sidebar-container">
        <aside class="sidebar">
            <h2>Student Portal</h2>
            <ul class="sidebar-menu">
                <li><a href="index.php" class="active">Welcome Portal</a></li>
                <li><a href="#myprofile">My Profile</a></li>
                <li><a href="#grades">Grades</a></li>
                <li><a href="#classschedule">Class Schedule</a></li>
                <li><a href="logout.php" class="logout-btn">Logout</a></li>
            </ul>
        </aside>
    </div>

    <main class="main-content">
        <div class="card">
            <div class="dashboard-header">
                <div class="welcome-text">
                    <h1>Student Dashboard</h1>
                    <p>Welcome, <strong><?= htmlspecialchars($profile['username'] ?? '') ?></strong>!</p>
                </div>
            </div>

            <?php if ($profile): ?>
                <div class="stats-grid">
                    <div class="stat-card teachers">
                        <h3>Email Address</h3>
                        <div class="stat-number"><?= htmlspecialchars($profile['email'] ?? 'N/A') ?></div>
                    </div>
                    <div class="stat-card classes">
                        <h3>Grade Level</h3>
                        <div class="stat-number"><?= htmlspecialchars($profile['grade_level'] ?? 'N/A') ?></div>
                    </div>
                    <div class="stat-card students">
                        <h3>Section</h3>
                        <div class="stat-number"><?= htmlspecialchars($profile['section'] ?? 'N/A') ?></div>
                    </div>
                </div>
            <?php else: ?>
                <p class="alert-error">Student record not found.</p>
            <?php endif; ?>
        </div>
    </main>

</body>
</html>