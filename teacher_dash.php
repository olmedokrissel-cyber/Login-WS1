<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || strtolower(trim($_SESSION['role'])) !== 'teacher') {
    header("Location: login.php");
    exit();
}

$teacher_user_id = $_SESSION['user_id'];
$message = "";
$error = "";

$stmt = $conn->prepare("
    SELECT users.username, users.email, teachers.id AS teacher_table_id, teachers.department, teachers.subject 
    FROM users 
    JOIN teachers ON users.id = teachers.user_id 
    WHERE users.id = ?
");
$stmt->bind_param("i", $teacher_user_id);
$stmt->execute();
$profile = $stmt->get_result()->fetch_assoc();
$stmt->close();

$teacher_name = $profile['username'] ?? 'Teacher';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
  
    if (isset($_POST['action']) && $_POST['action'] === 'update_profile') {
        $new_email = trim($_POST['email']);
        $new_subject = trim($_POST['subject']);
        $new_department = trim($_POST['department']);

        $conn->begin_transaction();
        try {
           
            $u_stmt = $conn->prepare("UPDATE users SET email = ? WHERE id = ?");
            $u_stmt->bind_param("si", $new_email, $teacher_user_id);
            $u_stmt->execute();
            $u_stmt->close();

            $t_stmt = $conn->prepare("UPDATE teachers SET subject = ?, department = ? WHERE user_id = ?");
            $t_stmt->bind_param("ssi", $new_subject, $new_department, $teacher_user_id);
            $t_stmt->execute();
            $t_stmt->close();

            $conn->commit();
            $message = "Profile updated successfully!";
       
            $profile['email'] = $new_email;
            $profile['subject'] = $new_subject;
            $profile['department'] = $new_department;
        } catch (Exception $e) {
            $conn->rollback();
            $error = "Error updating profile: " . $e->getMessage();
        }
    }

    if (isset($_POST['action']) && $_POST['action'] === 'save_grade') {
        $student_id = intval($_POST['student_id']);
        $subject_name = trim($_POST['subject_name']);
        $grade = trim($_POST['grade']);
        $remarks = trim($_POST['remarks']);

        if ($student_id > 0 && !empty($subject_name) && !empty($grade)) {
            $check = $conn->prepare("SELECT id FROM grades WHERE student_id = ? AND subject_name = ?");
            $check->bind_param("is", $student_id, $subject_name);
            $check->execute();
            $res = $check->get_result();

            if ($res->num_rows > 0) {
                $g_id = $res->fetch_assoc()['id'];
                $g_stmt = $conn->prepare("UPDATE grades SET grade = ?, teacher_name = ?, remarks = ? WHERE id = ?");
                $g_stmt->bind_param("sssi", $grade, $teacher_name, $remarks, $g_id);
                $g_stmt->execute();
                $g_stmt->close();
            } else {
                $g_stmt = $conn->prepare("INSERT INTO grades (student_id, subject_name, grade, teacher_name, remarks) VALUES (?, ?, ?, ?, ?)");
                $g_stmt->bind_param("issss", $student_id, $subject_name, $grade, $teacher_name, $remarks);
                $g_stmt->execute();
                $g_stmt->close();
            }
            $check->close();
            $message = "Grade saved successfully!";
        } else {
            $error = "Please fill in all required fields for grades.";
        }
    }

    if (isset($_POST['action']) && $_POST['action'] === 'save_schedule') {
        $grade_level = trim($_POST['grade_level']);
        $section = trim($_POST['section']);
        $subject_name = trim($_POST['subject_name']);
        $day_of_week = trim($_POST['day_of_week']);
        $start_time = trim($_POST['start_time']);
        $end_time = trim($_POST['end_time']);
        $room = trim($_POST['room']);

        if (!empty($grade_level) && !empty($section) && !empty($subject_name) && !empty($day_of_week)) {
            $s_stmt = $conn->prepare("INSERT INTO schedules (grade_level, section, subject_name, day_of_week, start_time, end_time, room) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $s_stmt->bind_param("sssssss", $grade_level, $section, $subject_name, $day_of_week, $start_time, $end_time, $room);
            $s_stmt->execute();
            $s_stmt->close();
            $message = "Schedule added successfully!";
        } else {
            $error = "Please fill in all required schedule fields.";
        }
    }
}

$students_list = [];
$st_query = $conn->query("
    SELECT students.id AS student_id, users.username, students.grade_level, students.section 
    FROM students 
    JOIN users ON students.user_id = users.id 
    ORDER BY users.username ASC
");
if ($st_query) {
    while ($row = $st_query->fetch_assoc()) {
        $students_list[] = $row;
    }
}

$grades_list = [];
$gr_query = $conn->query("
    SELECT grades.*, users.username AS student_name 
    FROM grades 
    JOIN students ON grades.student_id = students.id 
    JOIN users ON students.user_id = users.id 
    ORDER BY users.username ASC
");
if ($gr_query) {
    while ($row = $gr_query->fetch_assoc()) {
        $grades_list[] = $row;
    }
}

$schedules_list = [];
$sc_query = $conn->query("
    SELECT * FROM schedules 
    ORDER BY FIELD(day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'), start_time ASC
");
if ($sc_query) {
    while ($row = $sc_query->fetch_assoc()) {
        $schedules_list[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Dashboard</title>
    <link rel="stylesheet" href="dashstyle.css?v=<?php echo time(); ?>">
    <style>
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        .sidebar-menu li a.active { font-weight: bold; }
        table.data-table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
        table.data-table th, table.data-table td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        table.data-table th { background-color: #f4f4f4; }
        .form-group { margin-bottom: 1rem; }
        .form-group label { display: block; margin-bottom: 0.3rem; font-weight: bold; }
        .form-group input, .form-group select { width: 100%; padding: 8px; box-sizing: border-box; }
        .btn-submit { background-color: #40cc60; color: #fff; padding: 10px 15px; border: none; cursor: pointer; border-radius: 4px; }
        .btn-submit:hover { background-color: #79b349; }
        .msg-success { color: green; font-weight: bold; margin-bottom: 1rem; }
        .msg-error { color: red; font-weight: bold; margin-bottom: 1rem; }
        .form-card { background: #f9f9f9; padding: 15px; border-radius: 5px; margin-bottom: 2rem; border: 1px solid #e0e0e0; }
    </style>
</head>
<body>

    <div class="sidebar-container">
        <aside class="sidebar">
            <h2>Teacher Portal</h2>
            <ul class="sidebar-menu">
                <li><a href="#dashboard" class="nav-link active" onclick="switchTab(event, 'dashboard')">Dashboard</a></li>
                <li><a href="#editprofile" class="nav-link" onclick="switchTab(event, 'editprofile')">My Profile</a></li>
                <li><a href="#managegrades" class="nav-link" onclick="switchTab(event, 'managegrades')">Manage Grades</a></li>
                <li><a href="#manageschedule" class="nav-link" onclick="switchTab(event, 'manageschedule')">Class Schedule</a></li>
                <li><a href="logout.php" class="logout-btn">Logout</a></li>
            </ul>
        </aside>
    </div>

    <main class="main-content">

        <?php if (!empty($message)): ?>
            <p class="msg-success"><?= htmlspecialchars($message) ?></p>
        <?php endif; ?>
        <?php if (!empty($error)): ?>
            <p class="msg-error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <section id="dashboard" class="tab-content active card">
            <div class="dashboard-header">
                <div class="welcome-text">
                    <h1>Teacher Dashboard</h1>
                    <p>Welcome<strong><?= htmlspecialchars($profile['username'] ?? '') ?></strong>!</p>
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
                        <h3>Area / Department</h3>
                        <div class="stat-number"><?= htmlspecialchars($profile['department'] ?? 'N/A') ?></div>
                    </div>
                </div>
            <?php else: ?>
                <p class="alert-error">Teacher record not found.</p>
            <?php endif; ?>
        </section>

        <section id="editprofile" class="tab-content card">
            <h2>My Profile</h2>
            <form method="POST" action="" class="form-card">
                <input type="hidden" name="action" value="update_profile">
                
                <div class="form-group">
                    <label>Username:</label>
                    <input type="text" value="<?= htmlspecialchars($profile['username'] ?? '') ?>" disabled>
                </div>

                <div class="form-group">
                    <label>Email Address:</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($profile['email'] ?? '') ?>" required>
                </div>

                <div class="form-group">
                    <label>Subject Handled:</label>
                    <input type="text" name="subject" value="<?= htmlspecialchars($profile['subject'] ?? '') ?>" required>
                </div>

                <div class="form-group">
                    <label>Department / Area:</label>
                    <input type="text" name="department" value="<?= htmlspecialchars($profile['department'] ?? '') ?>" required>
                </div>

                <button type="submit" class="btn-submit">Save Profile Changes</button>
            </form>
        </section>

        <section id="managegrades" class="tab-content card">
            <h2>Manage Student Grades</h2>

            <form method="POST" action="" class="form-card">
                <h3>Add or Edit Grade</h3>
                <input type="hidden" name="action" value="save_grade">

                <div class="form-group">
                    <label>Select Student:</label>
                    <select name="student_id" required>
                        <option value=""> Choose Student </option>
                        <?php foreach ($students_list as $st): ?>
                            <option value="<?= $st['student_id'] ?>">
                                <?= htmlspecialchars($st['username']) ?> (<?= htmlspecialchars($st['grade_level'] . ' - ' . $st['section']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Subject Name:</label>
                    <input type="text" name="subject_name" value="<?= htmlspecialchars($profile['subject'] ?? '') ?>" required>
                </div>

                <div class="form-group">
                    <label>Grade (e.g. 85, 90, A):</label>
                    <input type="text" name="grade" placeholder="e.g. 88" required>
                </div>

                <div class="form-group">
                    <label>Remarks:</label>
                    <input type="text" name="remarks" placeholder="e.g. Passed, Excellent">
                </div>

                <button type="submit" class="btn-submit">Submit Grade</button>
            </form>

            <h3>Existing Grade Records</h3>
            <?php if (!empty($grades_list)): ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Subject</th>
                            <th>Grade</th>
                            <th>Teacher</th>
                            <th>Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($grades_list as $g): ?>
                            <tr>
                                <td><?= htmlspecialchars($g['student_name']) ?></td>
                                <td><?= htmlspecialchars($g['subject_name']) ?></td>
                                <td><?= htmlspecialchars($g['grade']) ?></td>
                                <td><?= htmlspecialchars($g['teacher_name'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($g['remarks'] ?? 'N/A') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No grades uploaded yet.</p>
            <?php endif; ?>
        </section>

        <section id="manageschedule" class="tab-content card">
            <h2>Manage Class Schedule</h2>

            <form method="POST" action="" class="form-card">
                <h3>Add Class Schedule</h3>
                <input type="hidden" name="action" value="save_schedule">

                <div class="form-group">
                    <label>Grade Level:</label>
                    <input type="text" name="grade_level" placeholder="e.g. Grade 10" required>
                </div>

                <div class="form-group">
                    <label>Section:</label>
                    <input type="text" name="section" placeholder="e.g. Section A" required>
                </div>

                <div class="form-group">
                    <label>Subject Name:</label>
                    <input type="text" name="subject_name" value="<?= htmlspecialchars($profile['subject'] ?? '') ?>" required>
                </div>

                <div class="form-group">
                    <label>Day of Week:</label>
                    <select name="day_of_week" required>
                        <option value="Monday">Monday</option>
                        <option value="Tuesday">Tuesday</option>
                        <option value="Wednesday">Wednesday</option>
                        <option value="Thursday">Thursday</option>
                        <option value="Friday">Friday</option>
                        <option value="Saturday">Saturday</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Start Time:</label>
                    <input type="time" name="start_time" required>
                </div>

                <div class="form-group">
                    <label>End Time:</label>
                    <input type="time" name="end_time" required>
                </div>

                <div class="form-group">
                    <label>Room:</label>
                    <input type="text" name="room" placeholder="e.g. Room 302">
                </div>

                <button type="submit" class="btn-submit">Add Schedule</button>
            </form>

            <h3>Current Class Schedules</h3>
            <?php if (!empty($schedules_list)): ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Grade Level</th>
                            <th>Section</th>
                            <th>Subject</th>
                            <th>Day</th>
                            <th>Time</th>
                            <th>Room</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($schedules_list as $sc): ?>
                            <tr>
                                <td><?= htmlspecialchars($sc['grade_level']) ?></td>
                                <td><?= htmlspecialchars($sc['section']) ?></td>
                                <td><?= htmlspecialchars($sc['subject_name']) ?></td>
                                <td><?= htmlspecialchars($sc['day_of_week']) ?></td>
                                <td>
                                    <?= date("g:i A", strtotime($sc['start_time'])) ?> - 
                                    <?= date("g:i A", strtotime($sc['end_time'])) ?>
                                </td>
                                <td><?= htmlspecialchars($sc['room'] ?? 'TBA') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No class schedules posted yet.</p>
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
