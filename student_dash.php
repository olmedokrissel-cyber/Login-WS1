<?php
session_start();
require 'db.php';


if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || strtolower(trim($_SESSION['role'])) !== 'student') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("
    SELECT users.username, users.email, students.id AS student_table_id, students.grade_level, students.section 
    FROM users 
    LEFT JOIN students ON users.id = students.user_id 
    WHERE users.id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$profile = $stmt->get_result()->fetch_assoc();
$stmt->close();

$student_id = $profile['student_table_id'] ?? null;

$grades = [];
if ($student_id) {
    $stmt_grades = $conn->prepare("
        SELECT subject_name, grade, teacher_name, remarks 
        FROM grades 
        WHERE student_id = ?
    ");
    if ($stmt_grades) {
        $stmt_grades->bind_param("i", $student_id);
        $stmt_grades->execute();
        $grades_result = $stmt_grades->get_result();
        while ($row = $grades_result->fetch_assoc()) {
            $grades[] = $row;
        }
        $stmt_grades->close();
    }
}

$schedule = [];
if (!empty($profile['grade_level']) && !empty($profile['section'])) {
    $stmt_sched = $conn->prepare("
        SELECT subject_name, day_of_week, start_time, end_time, room 
        FROM schedules 
        WHERE grade_level = ? AND section = ?
        ORDER BY FIELD(day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'), start_time
    ");
    if ($stmt_sched) {
        $stmt_sched->bind_param("ss", $profile['grade_level'], $profile['section']);
        $stmt_sched->execute();
        $sched_result = $stmt_sched->get_result();
        while ($row = $sched_result->fetch_assoc()) {
            $schedule[] = $row;
        }
        $stmt_sched->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard</title>
    <link rel="stylesheet" href="dashstyle.css?v=<?php echo time(); ?>">
    <style>
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        .sidebar-menu li a.active { font-weight: bold; }
        table.data-table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
        table.data-table th, table.data-table td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        table.data-table th { background-color: #f4f4f4; }
    </style>
</head>
<body>

    <div class="sidebar-container">
        <aside class="sidebar">
            <h2>Student Portal</h2>
            <ul class="sidebar-menu">
                <li><a href="#dashboard" class="nav-link active" onclick="switchTab(event, 'dashboard')">Dashboard</a></li>
                <li><a href="#myprofile" class="nav-link" onclick="switchTab(event, 'myprofile')">My Profile</a></li>
                <li><a href="#grades" class="nav-link" onclick="switchTab(event, 'grades')">Grades</a></li>
                <li><a href="#classschedule" class="nav-link" onclick="switchTab(event, 'classschedule')">Class Schedule</a></li>
                <li><a href="logout.php" class="logout-btn">Logout</a></li>
            </ul>
        </aside>
    </div>

    <main class="main-content">
        <section id="dashboard" class="tab-content active card">
            <div class="dashboard-header">
                <div class="welcome-text">
                    <h1>Student Dashboard</h1>
                    <p>Welcome back, <strong><?= htmlspecialchars($profile['username'] ?? 'Student') ?></strong>!</p>
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
        </section>

        <section id="myprofile" class="tab-content card">
            <h2>My Profile</h2>
            <p><strong>Username:</strong> <?= htmlspecialchars($profile['username'] ?? 'N/A') ?></p>
            <p><strong>Email:</strong> <?= htmlspecialchars($profile['email'] ?? 'N/A') ?></p>
            <p><strong>Grade Level:</strong> <?= htmlspecialchars($profile['grade_level'] ?? 'N/A') ?></p>
            <p><strong>Section:</strong> <?= htmlspecialchars($profile['section'] ?? 'N/A') ?></p>
        </section>

        <section id="grades" class="tab-content card">
            <h2>Academic Grades</h2>
            <?php if (!empty($grades)): ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Subject</th>
                            <th>Teacher</th>
                            <th>Grade</th>
                            <th>Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($grades as $g): ?>
                            <tr>
                                <td><?= htmlspecialchars($g['subject_name']) ?></td>
                                <td><?= htmlspecialchars($g['teacher_name'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($g['grade']) ?></td>
                                <td><?= htmlspecialchars($g['remarks'] ?? 'N/A') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No grade records available yet.</p>
            <?php endif; ?>
        </section>

        <section id="classschedule" class="tab-content card">
            <h2>Class Schedule</h2>
            <?php if (!empty($schedule)): ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Day</th>
                            <th>Subject</th>
                            <th>Time</th>
                            <th>Room</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($schedule as $s): ?>
                            <tr>
                                <td><?= htmlspecialchars($s['day_of_week']) ?></td>
                                <td><?= htmlspecialchars($s['subject_name']) ?></td>
                                <td>
                                    <?= date("g:i A", strtotime($s['start_time'])) ?> - 
                                    <?= date("g:i A", strtotime($s['end_time'])) ?>
                                </td>
                                <td><?= htmlspecialchars($s['room'] ?? 'TBA') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No class schedule found for your grade level and section.</p>
            <?php endif; ?>
        </section>
    </main>

    <script>
        function switchTab(event, tabId) {
            event.preventDefault();
            
            const tabs = document.querySelectorAll('.tab-content');
            tabs.forEach(tab => tab.classList.remove('active'));

            const navLinks = document.querySelectorAll('.nav-link');
            navLinks.forEach(link => link.classList.remove('active'));

            document.getElementById(tabId).classList.add('active');
            event.currentTarget.classList.add('active');
        }
    </script>
</body>
</html>
