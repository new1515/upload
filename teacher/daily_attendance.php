<?php
require_once '../config/database.php';

if (!isset($_SESSION['teacher_id'])) {
    redirect('../login.php');
}

$teacherId = $_SESSION['teacher_id'];
$teacherName = $_SESSION['teacher_name'];

$pdo->exec("CREATE TABLE IF NOT EXISTS daily_attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    attendance_date DATE NOT NULL,
    status ENUM('present', 'absent', 'late') DEFAULT 'present',
    marked_by INT,
    remarks VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_daily (student_id, attendance_date),
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
)");

$settings = [];
$stmt = $pdo->query("SELECT * FROM school_settings");
while ($row = $stmt->fetch()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
$schoolName = $settings['school_name'] ?? 'School Management System';
$schoolLogo = $settings['school_logo'] ?? '';

$message = '';

$selectedClass = isset($_POST['class_id']) ? (int)$_POST['class_id'] : (isset($_GET['class']) ? (int)$_GET['class'] : 0);
$selectedDate = isset($_POST['attendance_date']) ? sanitize($_POST['attendance_date']) : (isset($_GET['date']) ? sanitize($_GET['date']) : date('Y-m-d'));

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_daily_attendance'])) {
    $studentIds = $_POST['student_id'];
    $attendanceStatuses = $_POST['status'];
    $remarks = $_POST['remarks'] ?? [];
    
    $saved = 0;
    foreach ($studentIds as $studentId) {
        $status = $attendanceStatuses[$studentId] ?? 'present';
        $remark = sanitize($remarks[$studentId] ?? '');
        
        $stmt = $pdo->prepare("INSERT INTO daily_attendance (student_id, attendance_date, status, remarks, marked_by) 
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE status = VALUES(status), remarks = VALUES(remarks), marked_by = VALUES(marked_by)");
        $stmt->execute([$studentId, $selectedDate, $status, $remark, $teacherId]);
        $saved++;
    }
    
    logActivity($pdo, 'update', "Teacher $teacherName marked daily attendance for $saved students on $selectedDate", $_SESSION['teacher_username'] ?? 'teacher', 'daily_attendance', null);
    $message = '<div class="success-msg">Daily attendance saved successfully for ' . date('M d, Y', strtotime($selectedDate)) . '!</div>';
}

if (isset($_GET['mark_all'])) {
    $allStatus = sanitize($_GET['mark_all']);
    if (in_array($allStatus, ['present', 'absent', 'late'])) {
        $stmt = $pdo->prepare("SELECT id FROM students WHERE class_id = ?");
        $stmt->execute([$selectedClass]);
        $students = $stmt->fetchAll();
        
        foreach ($students as $student) {
            $stmt = $pdo->prepare("INSERT INTO daily_attendance (student_id, attendance_date, status, marked_by) 
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE status = VALUES(status), marked_by = VALUES(marked_by)");
            $stmt->execute([$student['id'], $selectedDate, $allStatus, $teacherId]);
        }
        
        logActivity($pdo, 'update', "Teacher $teacherName marked all students as $allStatus on $selectedDate", $_SESSION['teacher_username'] ?? 'teacher', 'daily_attendance', null);
        $message = '<div class="success-msg">All students marked as ' . ucfirst($allStatus) . '!</div>';
    }
}

$teacherClasses = $pdo->query("SELECT c.* FROM classes c 
    INNER JOIN teacher_classes tc ON c.id = tc.class_id 
    WHERE tc.teacher_id = $teacherId ORDER BY c.level, c.class_name, c.section")->fetchAll();

$students = [];
$existingAttendance = [];
if ($selectedClass) {
    $stmt = $pdo->prepare("SELECT s.*, da.status, da.remarks 
        FROM students s 
        LEFT JOIN daily_attendance da ON s.id = da.student_id AND da.attendance_date = ?
        WHERE s.class_id = ? 
        ORDER BY s.name");
    $stmt->execute([$selectedDate, $selectedClass]);
    $students = $stmt->fetchAll();
    
    $stmt = $pdo->prepare("SELECT student_id, status, remarks FROM daily_attendance WHERE attendance_date = ? AND student_id IN (SELECT id FROM students WHERE class_id = ?)");
    $stmt->execute([$selectedDate, $selectedClass]);
    while ($row = $stmt->fetch()) {
        $existingAttendance[$row['student_id']] = $row;
    }
}

$stats = ['present' => 0, 'absent' => 0, 'late' => 0];
foreach ($students as $s) {
    if (isset($existingAttendance[$s['id']])) {
        $stats[$existingAttendance[$s['id']]['status']]++;
    } else {
        $stats['present']++;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daily Attendance Register - Teacher Portal</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        .register-container { background: white; padding: 20px; border-radius: 15px; margin-bottom: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .stats-box { display: flex; gap: 15px; margin-bottom: 20px; }
        .stat-card { flex: 1; padding: 15px; border-radius: 10px; text-align: center; background: #f8f9fa; }
        .stat-card.present { background: #d4edda; }
        .stat-card.absent { background: #f8d7da; }
        .stat-card.late { background: #fff3cd; }
        .stat-number { font-size: 32px; font-weight: bold; }
        .quick-mark { display: flex; gap: 10px; margin-bottom: 15px; flex-wrap: wrap; }
        .info-box { background: linear-gradient(135deg, #3498db, #2980b9); color: white; padding: 15px 20px; border-radius: 10px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <button class="mobile-menu-toggle"><i class="fas fa-bars"></i></button>
    <div class="sidebar-overlay"></div>
    <nav class="sidebar">
        <div class="sidebar-brand"><i class="fas fa-chalkboard-teacher"></i> Teacher Portal</div>
        <ul class="sidebar-menu">
            <li><a href="index.php"><i class="fas fa-home"></i> Dashboard</a></li>
            <li><a href="announcements.php"><i class="fas fa-bullhorn"></i> Announcements</a></li>
            <li><a href="my_classes.php"><i class="fas fa-school"></i> My Classes</a></li>
            <li><a href="enter_scores.php"><i class="fas fa-edit"></i> Enter Scores</a></li>
            <li><a href="attendance.php"><i class="fas fa-calendar-check"></i> Attendance</a></li>
            <li><a href="daily_attendance.php" class="active"><i class="fas fa-clipboard-list"></i> Daily Register</a></li>
            <li><a href="results.php"><i class="fas fa-chart-line"></i> View Results</a></li>
            <li><a href="lessons.php"><i class="fas fa-book-open"></i> Lessons</a></li>
            <li><a href="lesson_plans.php"><i class="fas fa-calendar-alt"></i> Lesson Plans</a></li>
            <li><a href="test_books.php"><i class="fas fa-file-alt"></i> Test Books</a></li>
            <li><a href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
            <li><a href="../logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </nav>
    
    <main class="main-content">
        <div class="header">
            <h1><i class="fas fa-clipboard-list"></i> Daily Attendance Register</h1>
            <div class="header-right">
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
                <h1><?php echo htmlspecialchars($schoolName); ?></h1>
                <p><?php echo htmlspecialchars($settings['school_tagline'] ?? ''); ?></p>
            </div>
        </div>
        
        <div class="content">
            <?php echo $message; ?>
            
            <div class="info-box">
                <i class="fas fa-info-circle"></i> Mark daily attendance for your students. Select a class and date, then mark each student as Present, Absent, or Late.
            </div>
            
            <div class="register-container">
                <h2 style="margin-bottom: 15px;"><i class="fas fa-filter"></i> Select Class & Date</h2>
                <form method="POST" id="filterForm" style="display: flex; gap: 15px; flex-wrap: wrap; align-items: flex-end;">
                    <div class="form-group" style="flex: 1; min-width: 180px; margin: 0;">
                        <label>My Class</label>
                        <select name="class_id" required onchange="document.getElementById('filterForm').submit()">
                            <option value="">-- Select Class --</option>
                            <?php foreach ($teacherClasses as $c): ?>
                            <option value="<?php echo $c['id']; ?>" <?php echo $selectedClass == $c['id'] ? 'selected' : ''; ?>>
                                <?php echo $c['class_name'] . ' - Section ' . $c['section']; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group" style="flex: 1; min-width: 150px; margin: 0;">
                        <label>Date</label>
                        <input type="date" name="attendance_date" value="<?php echo $selectedDate; ?>" onchange="document.getElementById('filterForm').submit()">
                    </div>
                </form>
                
                <?php if ($selectedClass): ?>
                <div class="quick-mark">
                    <span style="font-weight: 500; margin-right: 10px;">Quick Mark All:</span>
                    <a href="?class=<?php echo $selectedClass; ?>&date=<?php echo $selectedDate; ?>&mark_all=present" class="btn btn-success btn-sm"><i class="fas fa-check"></i> All Present</a>
                    <a href="?class=<?php echo $selectedClass; ?>&date=<?php echo $selectedDate; ?>&mark_all=absent" class="btn btn-danger btn-sm"><i class="fas fa-times"></i> All Absent</a>
                    <a href="?class=<?php echo $selectedClass; ?>&date=<?php echo $selectedDate; ?>&mark_all=late" class="btn btn-warning btn-sm"><i class="fas fa-clock"></i> All Late</a>
                </div>
                <?php endif; ?>
            </div>
            
            <?php if ($selectedClass && !empty($students)): ?>
            <div class="stats-box">
                <div class="stat-card present">
                    <div class="stat-number"><?php echo $stats['present']; ?></div>
                    <div><i class="fas fa-check-circle"></i> Present</div>
                </div>
                <div class="stat-card absent">
                    <div class="stat-number"><?php echo $stats['absent']; ?></div>
                    <div><i class="fas fa-times-circle"></i> Absent</div>
                </div>
                <div class="stat-card late">
                    <div class="stat-number"><?php echo $stats['late']; ?></div>
                    <div><i class="fas fa-clock"></i> Late</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo count($students); ?></div>
                    <div><i class="fas fa-users"></i> Total</div>
                </div>
            </div>
            
            <form method="POST">
                <input type="hidden" name="class_id" value="<?php echo $selectedClass; ?>">
                <input type="hidden" name="attendance_date" value="<?php echo $selectedDate; ?>">
                
                <div class="table-container">
                    <div class="table-header">
                        <h3><i class="fas fa-users"></i> Attendance Register - <?php echo date('l, M d, Y', strtotime($selectedDate)); ?></h3>
                        <button type="submit" name="save_daily_attendance" class="btn btn-success">
                            <i class="fas fa-save"></i> Save Attendance
                        </button>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th style="text-align: left;">Student Name</th>
                                <th>Gender</th>
                                <th style="text-align: center;">Present</th>
                                <th style="text-align: center;">Absent</th>
                                <th style="text-align: center;">Late</th>
                                <th>Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $index => $student): 
                                $currentStatus = $existingAttendance[$student['id']]['status'] ?? 'present';
                                $currentRemark = $existingAttendance[$student['id']]['remarks'] ?? '';
                            ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td style="text-align: left; font-weight: 500;"><?php echo htmlspecialchars($student['name']); ?></td>
                                <td><?php echo $student['gender']; ?></td>
                                <td style="text-align: center;">
                                    <input type="hidden" name="student_id[]" value="<?php echo $student['id']; ?>">
                                    <input type="radio" name="status[<?php echo $student['id']; ?>]" value="present" <?php echo $currentStatus == 'present' ? 'checked' : ''; ?> class="status-radio" data-student="<?php echo $student['id']; ?>">
                                </td>
                                <td style="text-align: center;">
                                    <input type="radio" name="status[<?php echo $student['id']; ?>]" value="absent" <?php echo $currentStatus == 'absent' ? 'checked' : ''; ?> class="status-radio" data-student="<?php echo $student['id']; ?>">
                                </td>
                                <td style="text-align: center;">
                                    <input type="radio" name="status[<?php echo $student['id']; ?>]" value="late" <?php echo $currentStatus == 'late' ? 'checked' : ''; ?> class="status-radio" data-student="<?php echo $student['id']; ?>">
                                </td>
                                <td>
                                    <input type="text" name="remarks[<?php echo $student['id']; ?>]" value="<?php echo htmlspecialchars($currentRemark); ?>" placeholder="Optional..." style="width: 100%; padding: 5px; border: 1px solid #ddd; border-radius: 5px;">
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
                <i class="fas fa-clipboard-list" style="font-size: 50px; color: #ddd;"></i>
                <p style="color: #666; margin-top: 15px;">Select a class and date above to open the attendance register.</p>
            </div>
            <?php endif; ?>
        </div>
    </main>
    
    <script src="../assets/js/script.js"></script>
    <script>
        document.querySelectorAll('.status-radio').forEach(function(radio) {
            radio.addEventListener('change', function() {
                var row = this.closest('tr');
                row.classList.remove('present-row', 'absent-row', 'late-row');
                if (this.value === 'present') row.classList.add('present-row');
                else if (this.value === 'absent') row.classList.add('absent-row');
                else if (this.value === 'late') row.classList.add('late-row');
            });
            var event = new Event('change');
            radio.dispatchEvent(event);
        });
    </script>
    <style>
        .present-row { background-color: #d4edda !important; }
        .absent-row { background-color: #f8d7da !important; }
        .late-row { background-color: #fff3cd !important; }
        .btn-sm { padding: 5px 12px; font-size: 12px; }
        .status-radio { width: 20px; height: 20px; cursor: pointer; }
    </style>
</body>
</html>
