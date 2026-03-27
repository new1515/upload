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

$selectedClass = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;
$selectedTerm = isset($_GET['term']) ? sanitize($_GET['term']) : 'Term 1';
$selectedYear = isset($_GET['year']) ? sanitize($_GET['year']) : '2025-2026';

$classes = $pdo->query("SELECT * FROM classes ORDER BY class_name, section")->fetchAll();
$terms = ['Term 1', 'Term 2', 'Term 3'];
$years = ['2025-2026', '2024-2025'];

$validationResults = [];

if ($selectedClass) {
    $stmt = $pdo->prepare("SELECT * FROM classes WHERE id = ?");
    $stmt->execute([$selectedClass]);
    $classData = $stmt->fetch();
    
    $stmt = $pdo->prepare("SELECT * FROM students WHERE class_id = ?");
    $stmt->execute([$selectedClass]);
    $students = $stmt->fetchAll();
    
    $totalStudents = count($students);
    $studentsWithScores = 0;
    $studentsWithAttendance = 0;
    $studentsWithAllScores = 0;
    $missingSubjects = [];
    
    foreach ($students as $student) {
        $scoreCheck = $pdo->prepare("SELECT COUNT(*) FROM student_assessments WHERE student_id = ? AND class_id = ? AND term = ? AND academic_year = ? AND exam IS NOT NULL");
        $scoreCheck->execute([$student['id'], $selectedClass, $selectedTerm, $selectedYear]);
        if ($scoreCheck->fetchColumn() > 0) $studentsWithScores++;
        
        $attCheck = $pdo->prepare("SELECT * FROM student_attendance WHERE student_id = ? AND term = ? AND academic_year = ?");
        $attCheck->execute([$student['id'], $selectedTerm, $selectedYear]);
        $att = $attCheck->fetch();
        if ($att && $att['days_school_opened'] > 0) $studentsWithAttendance++;
        
        $subjectCheck = $pdo->prepare("SELECT COUNT(*) FROM student_assessments WHERE student_id = ? AND class_id = ? AND term = ? AND academic_year = ?");
        $subjectCheck->execute([$student['id'], $selectedClass, $selectedTerm, $selectedYear]);
        $subjectCount = $subjectCheck->fetchColumn();
        if ($subjectCount >= 5) $studentsWithAllScores++;
    }
    
    $validationResults = [
        'total_students' => $totalStudents,
        'students_with_scores' => $studentsWithScores,
        'students_with_attendance' => $studentsWithAttendance,
        'students_complete' => $studentsWithAllScores,
        'score_percentage' => $totalStudents > 0 ? round(($studentsWithScores / $totalStudents) * 100, 1) : 0,
        'attendance_percentage' => $totalStudents > 0 ? round(($studentsWithAttendance / $totalStudents) * 100, 1) : 0,
        'complete_percentage' => $totalStudents > 0 ? round(($studentsWithAllScores / $totalStudents) * 100, 1) : 0,
    ];
    
    $isReady = ($validationResults['score_percentage'] >= 100 && $validationResults['attendance_percentage'] >= 100);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Validate System - School Management System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        .validation-card { background: white; border-radius: 15px; padding: 25px; margin-bottom: 20px; box-shadow: 0 5px 20px rgba(0,0,0,0.1); }
        .validation-card h3 { margin-bottom: 20px; color: #2c3e50; }
        .progress-bar { background: #e9ecef; border-radius: 10px; height: 30px; overflow: hidden; margin: 10px 0; }
        .progress-bar .fill { height: 100%; border-radius: 10px; display: flex; align-items: center; justify-content: center; color: white; font-size: 12px; font-weight: 600; transition: width 0.5s ease; }
        .progress-bar .fill.green { background: linear-gradient(135deg, #27ae60, #2ecc71); }
        .progress-bar .fill.yellow { background: linear-gradient(135deg, #f39c12, #e67e22); }
        .progress-bar .fill.red { background: linear-gradient(135deg, #e74c3c, #c0392b); }
        .status-badge { display: inline-block; padding: 8px 20px; border-radius: 25px; font-weight: 600; font-size: 14px; }
        .status-badge.ready { background: #d4edda; color: #155724; }
        .status-badge.not-ready { background: #fff3cd; color: #856404; }
        .checklist { list-style: none; padding: 0; margin: 0; }
        .checklist li { padding: 12px 15px; border-bottom: 1px solid #eee; display: flex; align-items: center; gap: 10px; }
        .checklist li:last-child { border-bottom: none; }
        .checklist li i.fa-check { color: #27ae60; }
        .checklist li i.fa-times { color: #e74c3c; }
        .checklist li i.fa-spinner { color: #f39c12; }
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
            <li><a href="attendance.php"><i class="fas fa-calendar-check"></i> Attendance</a></li>
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
            <li><a href="validate.php" class="active"><i class="fas fa-clipboard-check"></i> Validate System</a></li>
            <li><a href="user_access.php"><i class="fas fa-key"></i> User Access</a></li>
            <li><a href="reset_password.php"><i class="fas fa-key"></i> Reset Password</a></li>
            <li><a href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
            <li><a href="../logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </nav>
    
    <main class="main-content">
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
        
        <div class="header">
            <h1><i class="fas fa-clipboard-check"></i> Validate System</h1>
            <div class="header-right">
                <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
        
        <div class="content">
            <div class="validation-card">
                <h3><i class="fas fa-filter"></i> Select Class & Term</h3>
                <form method="GET" style="display: flex; gap: 15px; flex-wrap: wrap; align-items: flex-end;">
                    <div class="form-group" style="flex: 1; min-width: 200px; margin: 0;">
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
                        <label>Year</label>
                        <select name="year" onchange="this.form.submit()">
                            <?php foreach ($years as $y): ?>
                            <option value="<?php echo $y; ?>" <?php echo $selectedYear == $y ? 'selected' : ''; ?>><?php echo $y; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </form>
            </div>
            
            <?php if ($selectedClass && !empty($validationResults)): ?>
            
            <div class="validation-card">
                <h3><i class="fas fa-clipboard-list"></i> Validation Status - <?php echo htmlspecialchars($classData['class_name'] . ' - Section ' . $classData['section']); ?></h3>
                
                <?php if ($isReady): ?>
                <div style="text-align: center; padding: 20px;">
                    <span class="status-badge ready">
                        <i class="fas fa-check-circle"></i> SYSTEM READY FOR REPORT CARDS
                    </span>
                    <p style="color: #155724; margin-top: 15px;">All data has been validated. You can now print report cards.</p>
                    <a href="reportcards.php" class="btn btn-success" style="margin-top: 15px;">
                        <i class="fas fa-file-alt"></i> Go to Report Cards
                    </a>
                </div>
                <?php else: ?>
                <div style="text-align: center; padding: 20px;">
                    <span class="status-badge not-ready">
                        <i class="fas fa-exclamation-triangle"></i> SYSTEM NOT READY - DATA INCOMPLETE
                    </span>
                    <p style="color: #856404; margin-top: 15px;">Please complete the data below before printing report cards.</p>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="validation-card">
                <h3><i class="fas fa-chart-pie"></i> Score Completion</h3>
                <p>Students with at least one exam score: <strong><?php echo $validationResults['students_with_scores']; ?> / <?php echo $validationResults['total_students']; ?></strong></p>
                <div class="progress-bar">
                    <div class="fill <?php echo $validationResults['score_percentage'] >= 100 ? 'green' : ($validationResults['score_percentage'] >= 50 ? 'yellow' : 'red'); ?>" style="width: <?php echo $validationResults['score_percentage']; ?>%">
                        <?php echo $validationResults['score_percentage']; ?>%
                    </div>
                </div>
                <?php if ($validationResults['score_percentage'] < 100): ?>
                <p style="color: #856404; margin-top: 10px;">
                    <i class="fas fa-info-circle"></i> <?php echo $validationResults['total_students'] - $validationResults['students_with_scores']; ?> student(s) still missing scores. 
                    <a href="class_scores.php?id=<?php echo $selectedClass; ?>">Enter scores here</a>
                </p>
                <?php endif; ?>
            </div>
            
            <div class="validation-card">
                <h3><i class="fas fa-calendar-check"></i> Attendance Completion</h3>
                <p>Students with attendance recorded: <strong><?php echo $validationResults['students_with_attendance']; ?> / <?php echo $validationResults['total_students']; ?></strong></p>
                <div class="progress-bar">
                    <div class="fill <?php echo $validationResults['attendance_percentage'] >= 100 ? 'green' : ($validationResults['attendance_percentage'] >= 50 ? 'yellow' : 'red'); ?>" style="width: <?php echo $validationResults['attendance_percentage']; ?>%">
                        <?php echo $validationResults['attendance_percentage']; ?>%
                    </div>
                </div>
                <?php if ($validationResults['attendance_percentage'] < 100): ?>
                <p style="color: #856404; margin-top: 10px;">
                    <i class="fas fa-info-circle"></i> <?php echo $validationResults['total_students'] - $validationResults['students_with_attendance']; ?> student(s) still missing attendance. 
                    <a href="attendance.php">Record attendance here</a>
                </p>
                <?php endif; ?>
            </div>
            
            <div class="validation-card">
                <h3><i class="fas fa-tasks"></i> Pre-Printing Checklist</h3>
                <ul class="checklist">
                    <li>
                        <?php if ($validationResults['score_percentage'] >= 100): ?>
                        <i class="fas fa-check-circle"></i>
                        <?php else: ?>
                        <i class="fas fa-times-circle"></i>
                        <?php endif; ?>
                        All students have exam scores entered
                    </li>
                    <li>
                        <?php if ($validationResults['attendance_percentage'] >= 100): ?>
                        <i class="fas fa-check-circle"></i>
                        <?php else: ?>
                        <i class="fas fa-times-circle"></i>
                        <?php endif; ?>
                        All students have attendance recorded
                    </li>
                    <li>
                        <?php if (!empty($classData['class_teacher_id'])): ?>
                        <i class="fas fa-check-circle"></i>
                        <?php else: ?>
                        <i class="fas fa-times-circle"></i>
                        <?php endif; ?>
                        Class teacher assigned
                    </li>
                    <li>
                        <?php if (!empty($settings['headmaster_name'])): ?>
                        <i class="fas fa-check-circle"></i>
                        <?php else: ?>
                        <i class="fas fa-times-circle"></i>
                        <?php endif; ?>
                        Headmaster name set in settings
                    </li>
                    <li>
                        <?php if (!empty($schoolLogo)): ?>
                        <i class="fas fa-check-circle"></i>
                        <?php else: ?>
                        <i class="fas fa-exclamation-circle" style="color: #f39c12;"></i>
                        <?php endif; ?>
                        School logo uploaded (recommended)
                    </li>
                </ul>
            </div>
            
            <?php elseif (!$selectedClass): ?>
            <div class="validation-card" style="text-align: center; padding: 60px;">
                <i class="fas fa-clipboard-check" style="font-size: 50px; color: #ddd; margin-bottom: 20px;"></i>
                <p style="color: #888;">Select a class above to validate data before printing report cards.</p>
            </div>
            <?php endif; ?>
        </div>
    </main>
    
    <script src="../assets/js/script.js"></script>
</body>
</html>
