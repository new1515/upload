<?php
require_once '../config/database.php';

if (!isLoggedIn()) {
    redirect('../login.php');
}

$settings = [];
$stmt = $pdo->query("SELECT * FROM school_settings");
while ($row = $stmt->fetch()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
$schoolName = $settings['school_name'] ?? 'School Management System';
$schoolLogo = $settings['school_logo'] ?? '';

$message = '';
$selectedClass = isset($_POST['class_id']) ? sanitize($_POST['class_id']) : '';
$selectedTerm = isset($_POST['term']) ? sanitize($_POST['term']) : 'Term 1';
$selectedYear = isset($_POST['academic_year']) ? sanitize($_POST['academic_year']) : '2025-2026';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_attendance'])) {
    $studentIds = $_POST['student_id'];
    $daysOpened = (int)$_POST['days_school_opened'];
    $daysPresent = $_POST['days_present'];
    
    foreach ($studentIds as $studentId) {
        $present = isset($daysPresent[$studentId]) ? (int)$daysPresent[$studentId] : 0;
        $absent = max(0, $daysOpened - $present);
        
        $stmt = $pdo->prepare("INSERT INTO student_attendance (student_id, class_id, term, academic_year, days_school_opened, days_present, days_absent) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE days_school_opened = VALUES(days_school_opened), days_present = VALUES(days_present), days_absent = VALUES(days_absent)");
        $stmt->execute([$studentId, $selectedClass, $selectedTerm, $selectedYear, $daysOpened, $present, $absent]);
    }
    
    logActivity($pdo, 'update', "Updated attendance for class ID: $selectedClass, $selectedTerm $selectedYear", $_SESSION['admin_username'] ?? 'admin', 'attendance', null);
    $message = '<div class="success-msg">Attendance saved successfully!</div>';
}

$classes = $pdo->query("SELECT * FROM classes ORDER BY class_name, section")->fetchAll();
$terms = ['Term 1', 'Term 2', 'Term 3'];
$years = ['2025-2026', '2024-2025'];

$students = [];
if ($selectedClass) {
    $stmt = $pdo->prepare("SELECT s.*, sa.days_school_opened, sa.days_present, sa.days_absent 
        FROM students s 
        LEFT JOIN student_attendance sa ON s.id = sa.student_id AND sa.term = ? AND sa.academic_year = ?
        WHERE s.class_id = ? 
        ORDER BY s.name");
    $stmt->execute([$selectedTerm, $selectedYear, $selectedClass]);
    $students = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance - School Management System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        .attendance-form { background: white; padding: 20px; border-radius: 15px; margin-bottom: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .attendance-table input { width: 80px; padding: 8px; border: 1px solid #ddd; border-radius: 5px; text-align: center; }
        .attendance-table .present { background: #d4edda; }
        .attendance-table .absent { background: #f8d7da; }
    </style>
</head>
<body>
    <button class="mobile-menu-toggle">
        <i class="fas fa-bars"></i>
    </button>
    <div class="sidebar-overlay"></div>
    <nav class="sidebar">
        <div class="sidebar-brand"><i class="fas fa-graduation-cap"></i> School Admin</div>
        <ul class="sidebar-menu">
            <li><a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
            <li><a href="students.php"><i class="fas fa-user-graduate"></i> Students</a></li>
            <li><a href="parents.php"><i class="fas fa-users"></i> Parents</a></li>
            <li><a href="teachers.php"><i class="fas fa-chalkboard-teacher"></i> Teachers</a></li>
            <li><a href="subjects.php"><i class="fas fa-book"></i> Subjects</a></li>
            <li><a href="classes.php"><i class="fas fa-school"></i> Classes</a></li>
            <li><a href="attendance.php"><i class="fas fa-calendar-check"></i> Attendance (Term)</a></li>
            <li><a href="daily_attendance.php"><i class="fas fa-clipboard-list"></i> Daily Register</a></li>
            <li><a href="timetable.php"><i class="fas fa-clock"></i> Timetable</a></li>
            <li><a href="results.php"><i class="fas fa-chart-line"></i> Results</a></li>
            <li><a href="reportcards.php"><i class="fas fa-file-alt"></i> Report Cards</a></li>
            <li><a href="calendar.php"><i class="fas fa-calendar-alt"></i> Calendar</a></li>
            <li><a href="announcements.php"><i class="fas fa-bullhorn"></i> Announcements</a></li>
            <li><a href="fees.php"><i class="fas fa-money-bill"></i> School Fees</a></li>
            <li><a href="chatbot.php"><i class="fas fa-robot"></i> AI Chatbot</a></li>
            <li><a href="chatbot_qa.php"><i class="fas fa-database"></i> Chatbot Q&A</a></li>
            <li><a href="lessons.php"><i class="fas fa-book-open"></i> Lessons</a></li>
            <li><a href="history.php"><i class="fas fa-history"></i> History</a></li>
            <li><a href="admins.php"><i class="fas fa-user-shield"></i> Manage Admins</a></li>
            <li><a href="validate.php"><i class="fas fa-clipboard-check"></i> Validate System</a></li>
            <li><a href="user_access.php"><i class="fas fa-key"></i> User Access</a></li>
            <li><a href="reset_password.php"><i class="fas fa-key"></i> Reset Password</a></li>
            <li><a href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
            <li><a href="../logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </nav>
    
    <main class="main-content">
        <div class="header">
            <h1><i class="fas fa-calendar-check"></i> Student Attendance</h1>
            <div class="header-right">
                <div class="theme-switcher">
                    <button class="theme-btn active" data-theme="blue" title="Blue"></button>
                    <button class="theme-btn" data-theme="green" title="Green"></button>
                    <button class="theme-btn" data-theme="purple" title="Purple"></button>
                    <button class="theme-btn" data-theme="red" title="Red"></button>
                    <button class="theme-btn" data-theme="orange" title="Orange"></button>
                    <button class="theme-btn" data-theme="teal" title="Teal"></button>
                    <button class="theme-btn" data-theme="pink" title="Pink"></button>
                    <button class="theme-btn" data-theme="gold" title="Gold"></button>
                    <button class="theme-btn" data-theme="dark" title="Dark"></button>
                    <button class="theme-btn" data-theme="ocean" title="Ocean"></button>
                    <button class="theme-btn" data-theme="sky" title="Sky"></button>
                    <button class="theme-btn" data-theme="emerald" title="Emerald"></button>
                    <button class="theme-btn" data-theme="violet" title="Violet"></button>
                </div>
                <a href="settings.php"><i class="fas fa-cog"></i></a>
                <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
        
        <div class="school-header">
            <div class="school-logo">
                <?php if ($schoolLogo && file_exists('../assets/images/' . $schoolLogo)): ?>
                    <img src="../assets/images/<?php echo $schoolLogo; ?>" alt="School Logo">
                <?php else: ?>
                    <i class="fas fa-graduation-cap"></i>
                <?php endif; ?>
            </div>
            <div class="school-info">
                <h1><?php echo $schoolName; ?></h1>
                <p><?php echo $settings['school_tagline'] ?? ''; ?></p>
            </div>
        </div>
        
        <div class="content">
            <?php echo $message; ?>
            
            <div class="attendance-form">
                <h2 style="margin-bottom: 15px;"><i class="fas fa-filter"></i> Select Class & Term</h2>
                <form method="POST" style="display: flex; gap: 15px; flex-wrap: wrap; align-items: flex-end;">
                    <div class="form-group" style="flex: 1; min-width: 180px; margin: 0;">
                        <label>Class</label>
                        <select name="class_id" required onchange="this.form.submit()">
                            <option value="">-- Select Class --</option>
                            <?php foreach ($classes as $c): ?>
                            <option value="<?php echo $c['id']; ?>" <?php echo $selectedClass == $c['id'] ? 'selected' : ''; ?>>
                                <?php echo $c['class_name'] . ' - Section ' . $c['section']; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group" style="flex: 1; min-width: 120px; margin: 0;">
                        <label>Term</label>
                        <select name="term" onchange="this.form.submit()">
                            <?php foreach ($terms as $t): ?>
                            <option value="<?php echo $t; ?>" <?php echo $selectedTerm == $t ? 'selected' : ''; ?>><?php echo $t; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group" style="flex: 1; min-width: 120px; margin: 0;">
                        <label>Academic Year</label>
                        <select name="academic_year" onchange="this.form.submit()">
                            <?php foreach ($years as $y): ?>
                            <option value="<?php echo $y; ?>" <?php echo $selectedYear == $y ? 'selected' : ''; ?>><?php echo $y; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </form>
            </div>
            
            <?php if ($selectedClass && !empty($students)): ?>
            <form method="POST">
                <input type="hidden" name="class_id" value="<?php echo $selectedClass; ?>">
                <input type="hidden" name="term" value="<?php echo $selectedTerm; ?>">
                <input type="hidden" name="academic_year" value="<?php echo $selectedYear; ?>">
                
                <div class="table-container">
                    <div class="table-header">
                        <h3><i class="fas fa-users"></i> Students in Class - <?php echo $selectedTerm; ?>, <?php echo $selectedYear; ?></h3>
                        <div style="display: flex; gap: 15px; align-items: center;">
                            <label style="font-size: 13px;">Days School Opened: 
                                <input type="number" name="days_school_opened" id="daysOpened" value="60" min="0" max="100" style="width: 70px; padding: 5px; border: 1px solid #ddd; border-radius: 5px; text-align: center;" required>
                            </label>
                            <button type="submit" name="save_attendance" class="btn btn-success">
                                <i class="fas fa-save"></i> Save Attendance
                            </button>
                        </div>
                    </div>
                    <table class="attendance-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th style="text-align: left;">Student Name</th>
                                <th>Gender</th>
                                <th>Days Present</th>
                                <th>Days Absent</th>
                                <th>Attendance %</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $index => $student): ?>
                            <input type="hidden" name="student_id[]" value="<?php echo $student['id']; ?>">
                            <?php 
                            $daysOpened = $student['days_school_opended'] ?? 60;
                            $daysPresent = $student['days_present'] ?? 0;
                            $daysAbsent = $student['days_absent'] ?? 0;
                            $percentage = $daysOpened > 0 ? round(($daysPresent / $daysOpened) * 100, 1) : 0;
                            ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td style="text-align: left; font-weight: 500;"><?php echo htmlspecialchars($student['name']); ?></td>
                                <td><?php echo $student['gender']; ?></td>
                                <td>
                                    <input type="number" name="days_present[<?php echo $student['id']; ?>]" 
                                        value="<?php echo $daysPresent; ?>" min="0" max="100" 
                                        onchange="updateAbsent(this)" class="present">
                                </td>
                                <td>
                                    <span id="absent_<?php echo $student['id']; ?>"><?php echo $daysAbsent; ?></span>
                                </td>
                                <td>
                                    <span class="badge <?php echo $percentage >= 75 ? 'badge-success' : ($percentage >= 50 ? 'badge-warning' : 'badge-danger'); ?>">
                                        <?php echo $percentage; ?>%
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </form>
            <?php elseif ($selectedClass): ?>
            <div class="table-container" style="text-align: center; padding: 40px;">
                <i class="fas fa-users" style="font-size: 50px; color: #ddd;"></i>
                <p style="color: #666; margin-top: 15px;">No students in this class.</p>
            </div>
            <?php else: ?>
            <div class="table-container" style="text-align: center; padding: 40px;">
                <i class="fas fa-calendar-check" style="font-size: 50px; color: #ddd;"></i>
                <p style="color: #666; margin-top: 15px;">Select a class above to manage attendance.</p>
            </div>
            <?php endif; ?>
        </div>
    </main>
    
    <script src="../assets/js/script.js"></script>
    <script>
        function updateAbsent(input) {
            var row = input.closest('tr');
            var present = parseInt(input.value) || 0;
            var opened = parseInt(document.getElementById('daysOpened').value) || 0;
            var absent = Math.max(0, opened - present);
            var absentSpan = row.querySelector('[id^="absent_"]');
            absentSpan.textContent = absent;
            
            var percentage = opened > 0 ? ((present / opened) * 100).toFixed(1) : 0;
            var badge = row.querySelector('.badge');
            badge.textContent = percentage + '%';
            badge.className = 'badge ' + (percentage >= 75 ? 'badge-success' : (percentage >= 50 ? 'badge-warning' : 'badge-danger'));
        }
        
        document.getElementById('daysOpened').addEventListener('change', function() {
            document.querySelectorAll('[id^="absent_"]').forEach(function(span) {
                var row = span.closest('tr');
                var presentInput = row.querySelector('input[name^="days_present"]');
                var present = parseInt(presentInput.value) || 0;
                var opened = parseInt(this.value) || 0;
                span.textContent = Math.max(0, opened - present);
            });
        });
    </script>
</body>
</html>
