<?php
require_once '../config/database.php';

if (!isLoggedIn()) {
    redirect('../login.php');
}

$message = '';
$selectedClass = isset($_POST['class_id']) ? sanitize($_POST['class_id']) : '';
$selectedTerm = isset($_POST['term']) ? sanitize($_POST['term']) : 'Term 1';
$selectedYear = isset($_POST['academic_year']) ? sanitize($_POST['academic_year']) : '2025-2026';
$students = [];
$classData = null;

if ($selectedClass) {
    $stmt = $pdo->prepare("SELECT * FROM classes WHERE id = ?");
    $stmt->execute([$selectedClass]);
    $classData = $stmt->fetch();
    
    $stmt = $pdo->prepare("SELECT s.* FROM students s WHERE s.class_id = ? ORDER BY s.name");
    $stmt->execute([$selectedClass]);
    $students = $stmt->fetchAll();
}

$classes = $pdo->query("SELECT * FROM classes ORDER BY class_name, section")->fetchAll();
$terms = ['Term 1', 'Term 2', 'Term 3'];
$years = ['2025-2026', '2024-2025'];

$settings = [];
$stmt = $pdo->query("SELECT * FROM school_settings");
while ($row = $stmt->fetch()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
$schoolName = $settings['school_name'] ?? 'Ghana Basic School';
$headmasterName = $settings['headmaster_name'] ?? 'Mr. Emmanuel Kofi Asante';
$headmasterTitle = $settings['headmaster_title'] ?? 'Headmaster';
$classTeacherTitle = $settings['class_teacher_title'] ?? 'Class Teacher';

$classTeacher = null;
if ($selectedClass) {
    $stmt = $pdo->prepare("SELECT t.name FROM teachers t INNER JOIN classes c ON c.class_teacher_id = t.id WHERE c.id = ?");
    $stmt->execute([$selectedClass]);
    $classTeacher = $stmt->fetchColumn();
}

function getGrade($marks) {
    if ($marks >= 80) return 'A';
    if ($marks >= 70) return 'B';
    if ($marks >= 60) return 'C';
    if ($marks >= 50) return 'D';
    if ($marks >= 40) return 'E';
    return 'F';
}

function getGradeColor($grade) {
    $colors = ['A' => '#27ae60', 'B' => '#2ecc71', 'C' => '#f39c12', 'D' => '#e67e22', 'E' => '#e74c3c', 'F' => '#c0392b'];
    return $colors[$grade] ?? '#95a5a6';
}

function getGradeRemark($grade) {
    $remarks = ['A' => 'Excellent', 'B' => 'Very Good', 'C' => 'Good', 'D' => 'Credit', 'E' => 'Pass', 'F' => 'Fail'];
    return $remarks[$grade] ?? '';
}

function getPositionSuffix($pos) {
    if ($pos % 100 >= 11 && $pos % 100 <= 13) return 'th';
    switch ($pos % 10) {
        case 1: return 'st';
        case 2: return 'nd';
        case 3: return 'rd';
        default: return 'th';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Cards - School Management System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        .report-card {
            background: #fff;
            border: 2px solid #4a90e2;
            border-radius: 10px;
            padding: 25px;
            max-width: 850px;
            margin: 20px auto;
            page-break-after: always;
        }
        .report-header {
            text-align: center;
            border-bottom: 2px solid #4a90e2;
            padding-bottom: 10px;
            margin-bottom: 10px;
        }
        .report-header h1 { color: #4a90e2; font-size: 22px; margin-bottom: 5px; }
        .report-header .school-contact { color: #666; font-size: 11px; margin-bottom: 3px; }
        .student-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            background: #f0f7ff;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 15px;
        }
        .student-photo {
            width: 100px;
            height: 120px;
            border: 2px solid #4a90e2;
            border-radius: 8px;
            overflow: hidden;
            background: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .student-photo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .student-photo i {
            font-size: 40px;
            color: #ccc;
        }
        .student-info-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            flex: 1;
            margin-left: 15px;
        }
        .student-info-item {
            background: white;
            padding: 8px 10px;
            border-radius: 6px;
            text-align: center;
        }
        .student-info-item label {
            display: block;
            font-size: 9px;
            color: #888;
            margin-bottom: 2px;
        }
        .student-info-item span {
            font-size: 11px;
            font-weight: 600;
            color: #333;
        }
        .scores-table { width: 100%; border-collapse: collapse; font-size: 11px; }
        .scores-table th { background: #4a90e2; color: white; padding: 6px 4px; text-align: center; font-size: 10px; }
        .scores-table td { padding: 6px 4px; border-bottom: 1px solid #eee; text-align: center; }
        .scores-table td:first-child, .scores-table th:first-child { text-align: left; }
        .summary-row { display: grid; grid-template-columns: repeat(5, 1fr); gap: 8px; margin-top: 12px; }
        .summary-box { text-align: center; padding: 8px; background: #f8f9fa; border-radius: 6px; }
        .summary-box h4 { font-size: 14px; margin-bottom: 2px; }
        .summary-box p { font-size: 10px; color: #666; }
        .grade-badge { display: inline-block; padding: 2px 8px; border-radius: 10px; color: white; font-weight: bold; font-size: 10px; }
        .position-badge { display: inline-block; padding: 3px 10px; border-radius: 10px; color: white; font-weight: bold; font-size: 11px; background: #9b59b6; }
        .class-select-section { background: #fff; padding: 20px; border-radius: 15px; margin-bottom: 20px; }
        @media print {
            .sidebar, .header, .class-select-section, nav, .no-print { display: none !important; }
            .main-content { margin-left: 0 !important; padding: 0 !important; }
            .content { padding: 10px !important; }
            .report-card { border: 1px solid #000; box-shadow: none; max-width: 100%; page-break-after: always; }
        }
        @media (max-width: 600px) {
            .student-header { flex-direction: column; align-items: center; }
            .student-info-grid { grid-template-columns: repeat(2, 1fr); margin-left: 0; margin-top: 15px; width: 100%; }
            .student-photo { width: 80px; height: 100px; }
        }
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
            <li><a href="reportcards.php" class="active"><i class="fas fa-file-alt"></i> Report Cards</a></li>
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
            <h1><i class="fas fa-file-alt"></i> Report Cards</h1>
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
        
        <div class="content">
            <div class="class-select-section">
                <h2 style="margin-bottom: 15px;"><i class="fas fa-search"></i> Select Class to Generate Reports</h2>
                <form method="POST" style="display: flex; gap: 15px; flex-wrap: wrap; align-items: flex-end;">
                    <div class="form-group" style="flex: 1; min-width: 180px; margin: 0;">
                        <label>Select Class</label>
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
            
            <?php if ($selectedClass && $classData): ?>
            
            <?php
            $allStudentTotals = [];
            $subjectPositions = [];
            
            foreach ($students as $student) {
                $stmt = $pdo->prepare("SELECT a.*, sub.subject_name, sub.id as sub_id FROM student_assessments a LEFT JOIN subjects sub ON a.subject_id = sub.id WHERE a.student_id = ? AND a.class_id = ? AND a.term = ? AND a.academic_year = ?");
                $stmt->execute([$student['id'], $selectedClass, $selectedTerm, $selectedYear]);
                $assessments = $stmt->fetchAll();
                
                $totalMarks = 0;
                foreach ($assessments as $a) {
                    $total = ((float)($a['test1'] ?? 0) + (float)($a['test2'] ?? 0) + (float)($a['test3'] ?? 0) + (float)($a['project'] ?? 0) + (float)($a['class_assessment'] ?? 0) + (float)($a['exam'] ?? 0));
                    $totalMarks += $total;
                    
                    if (!isset($subjectPositions[$a['sub_id']])) $subjectPositions[$a['sub_id']] = [];
                    $subjectPositions[$a['sub_id']][] = ['student_id' => $student['id'], 'total' => $total, 'subject_name' => $a['subject_name']];
                }
                
                $allStudentTotals[$student['id']] = [
                    'name' => $student['name'],
                    'total' => $totalMarks,
                    'subjects' => count($assessments)
                ];
            }
            
            arsort($allStudentTotals);
            $rank = 1;
            $prevTotal = null;
            foreach ($allStudentTotals as $id => &$data) {
                if ($prevTotal !== null && $data['total'] < $prevTotal) {
                    $rank++;
                }
                $data['class_position'] = $rank;
                $prevTotal = $data['total'];
            }
            
            foreach ($subjectPositions as $subId => &$studentsInSubject) {
                arsort($studentsInSubject);
                $pos = 1;
                $prevTotal = null;
                foreach ($studentsInSubject as &$record) {
                    if ($prevTotal !== null && $record['total'] < $prevTotal) {
                        $pos++;
                    }
                    $record['position'] = $pos;
                    $prevTotal = $record['total'];
                }
            }
            ?>
            
            <?php
            $validationErrors = [];
            $studentsWithScores = 0;
            $studentsWithAttendance = 0;
            
            foreach ($students as $s) {
                $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM student_assessments WHERE student_id = ? AND class_id = ? AND term = ? AND academic_year = ? AND exam IS NOT NULL");
                $checkStmt->execute([$s['id'], $selectedClass, $selectedTerm, $selectedYear]);
                if ($checkStmt->fetchColumn() > 0) $studentsWithScores++;
                
                $attCheck = $pdo->prepare("SELECT COUNT(*) FROM student_attendance WHERE student_id = ? AND term = ? AND academic_year = ? AND days_school_opened > 0");
                $attCheck->execute([$s['id'], $selectedTerm, $selectedYear]);
                if ($attCheck->fetchColumn() > 0) $studentsWithAttendance++;
            }
            
            if (count($students) > 0 && $studentsWithScores < count($students)) {
                $validationErrors[] = "Only $studentsWithScores of " . count($students) . " students have exam scores entered.";
            }
            if (count($students) > 0 && $studentsWithAttendance < count($students)) {
                $validationErrors[] = "Only $studentsWithAttendance of " . count($students) . " students have attendance recorded.";
            }
            ?>
            
            <?php if (!empty($validationErrors)): ?>
            <div style="background: #fff3cd; border: 1px solid #ffc107; border-radius: 10px; padding: 20px; margin-bottom: 20px;">
                <h3 style="color: #856404; margin-bottom: 15px;"><i class="fas fa-exclamation-triangle"></i> Validation Warnings</h3>
                <ul style="color: #856404; margin: 0; padding-left: 20px;">
                    <?php foreach ($validationErrors as $err): ?>
                    <li><?php echo $err; ?></li>
                    <?php endforeach; ?>
                </ul>
                <p style="color: #856404; margin-top: 15px; margin-bottom: 0;">
                    <strong>Note:</strong> Some report cards may be incomplete. Do you want to continue printing anyway?
                </p>
                <div style="margin-top: 15px; display: flex; gap: 10px;">
                    <button onclick="if(confirm('Print incomplete report cards?')) window.print()" class="btn btn-warning">
                        <i class="fas fa-print"></i> Print Anyway
                    </button>
                    <a href="class_scores.php?id=<?php echo $selectedClass; ?>" class="btn btn-primary">
                        <i class="fas fa-edit"></i> Enter Scores First
                    </a>
                </div>
            </div>
            <?php else: ?>
            <div class="no-print" style="text-align: center; margin-bottom: 15px;">
                <div style="background: #d4edda; border: 1px solid #c3e6cb; border-radius: 10px; padding: 15px; margin-bottom: 15px; color: #155724;">
                    <i class="fas fa-check-circle"></i> All data validated! Report cards are ready to print.
                </div>
                <button onclick="window.print()" class="btn btn-success" style="padding: 12px 30px; font-size: 14px;">
                    <i class="fas fa-print"></i> Print All Report Cards for <?php echo $classData['class_name'] . ' - Section ' . $classData['section']; ?>
                </button>
            </div>
            <?php endif; ?>
            
            <?php if (empty($students)): ?>
            <div class="table-container" style="text-align: center; padding: 40px;">
                <i class="fas fa-users" style="font-size: 50px; color: #ddd;"></i>
                <p style="color: #666; margin-top: 15px;">No students in this class.</p>
            </div>
            <?php else: ?>
            
            <?php foreach ($students as $student): 
                $stmt = $pdo->prepare("SELECT a.*, sub.subject_name, sub.id as sub_id FROM student_assessments a LEFT JOIN subjects sub ON a.subject_id = sub.id WHERE a.student_id = ? AND a.class_id = ? AND a.term = ? AND a.academic_year = ? ORDER BY sub.subject_name");
                $stmt->execute([$student['id'], $selectedClass, $selectedTerm, $selectedYear]);
                $assessments = $stmt->fetchAll();
                
                $attStmt = $pdo->prepare("SELECT * FROM student_attendance WHERE student_id = ? AND class_id = ? AND term = ? AND academic_year = ?");
                $attStmt->execute([$student['id'], $selectedClass, $selectedTerm, $selectedYear]);
                $attendance = $attStmt->fetch();
                
                $subjectTeachers = [];
                foreach ($assessments as $a) {
                    $teacherStmt = $pdo->prepare("SELECT t.name FROM teachers t INNER JOIN teacher_subjects ts ON t.id = ts.teacher_id WHERE ts.subject_id = ? LIMIT 1");
                    $teacherStmt->execute([$a['sub_id']]);
                    $teacherName = $teacherStmt->fetchColumn();
                    if ($teacherName) {
                        $subjectTeachers[$a['sub_id']] = $teacherName;
                    }
                }
                
                $totalMarks = 0;
                $subjectCount = count($assessments);
                foreach ($assessments as $a) {
                    $totalMarks += ((float)($a['test1'] ?? 0) + (float)($a['test2'] ?? 0) + (float)($a['test3'] ?? 0) + (float)($a['project'] ?? 0) + (float)($a['class_assessment'] ?? 0) + (float)($a['exam'] ?? 0));
                }
                
                $classPos = $allStudentTotals[$student['id']]['class_position'] ?? '-';
                $posSuffix = getPositionSuffix($classPos);
                
                $daysOpened = $attendance['days_school_opened'] ?? 0;
                $daysPresent = $attendance['days_present'] ?? 0;
                $daysAbsent = $attendance['days_absent'] ?? 0;
                $attPercentage = $daysOpened > 0 ? round(($daysPresent / $daysOpened) * 100, 1) : 0;
            ?>
            
            <div class="report-card">
                <div class="report-header">
                    <h1><i class="fas fa-graduation-cap"></i> <?php echo $schoolName; ?></h1>
                    <p class="school-contact"><?php echo $settings['school_address'] ?? ''; ?></p>
                    <p class="school-contact">Email: <?php echo $settings['school_email'] ?? ''; ?> | Phone: <?php echo $settings['school_phone'] ?? ''; ?></p>
                    <p style="color: #4a90e2; font-size: 13px; margin-top: 5px;">Academic Report Card - <?php echo $selectedTerm; ?>, <?php echo $selectedYear; ?></p>
                </div>
                
                <div class="student-header">
                    <div class="student-photo">
                        <?php if (!empty($student['photo']) && file_exists('../assets/images/students/' . $student['photo'])): ?>
                            <img src="../assets/images/students/<?php echo $student['photo']; ?>" alt="Student Photo">
                        <?php else: ?>
                            <i class="fas fa-user"></i>
                        <?php endif; ?>
                    </div>
                    <div class="student-info-grid">
                        <div class="student-info-item">
                            <label>Student Name</label>
                            <span><?php echo $student['name']; ?></span>
                        </div>
                        <div class="student-info-item">
                            <label>Class</label>
                            <span><?php echo $classData['class_name'] . ' - Section ' . $classData['section']; ?></span>
                        </div>
                        <div class="student-info-item">
                            <label>Gender</label>
                            <span><?php echo $student['gender']; ?></span>
                        </div>
                        <div class="student-info-item">
                            <label>Class Position</label>
                            <span class="position-badge" style="padding: 2px 8px;"><?php echo $classPos . $posSuffix; ?>/<?php echo count($students); ?></span>
                        </div>
                    </div>
                </div>
                
                <div style="background: #f8f9fa; padding: 10px; border-radius: 8px; margin-bottom: 15px; display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; text-align: center; font-size: 12px;">
                    <div><strong>Days School Opened</strong><br><?php echo $daysOpened; ?></div>
                    <div><strong>Days Present</strong><br><?php echo $daysPresent; ?></div>
                    <div><strong>Days Absent</strong><br><?php echo $daysAbsent; ?></div>
                    <div><strong>Attendance %</strong><br><span class="badge <?php echo $attPercentage >= 75 ? 'badge-success' : ($attPercentage >= 50 ? 'badge-warning' : 'badge-danger'); ?>"><?php echo $attPercentage; ?>%</span></div>
                </div>
                
                <table class="scores-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Subject</th>
                            <th>Subject Teacher</th>
                            <th>T1</th>
                            <th>T2</th>
                            <th>T3</th>
                            <th>Proj</th>
                            <th>CA</th>
                            <th>Exam</th>
                            <th>Total</th>
                            <th>Grade</th>
                            <th>Remark</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($assessments)): ?>
                        <tr><td colspan="12" style="text-align: center; color: #999;">No results found</td></tr>
                        <?php else: ?>
                        <?php 
                        $subNum = 0;
                        foreach ($assessments as $a): 
                            $subNum++;
                            $ca = ((float)($a['test1'] ?? 0) + (float)($a['test2'] ?? 0) + (float)($a['test3'] ?? 0) + (float)($a['project'] ?? 0) + (float)($a['class_assessment'] ?? 0));
                            $total = $ca + (float)($a['exam'] ?? 0);
                            $grade = getGrade($total);
                            $remark = getGradeRemark($grade);
                            $subjectTeacher = $subjectTeachers[$a['sub_id']] ?? '-';
                        ?>
                        <tr>
                            <td><?php echo $subNum; ?></td>
                            <td style="text-align: left;"><?php echo $a['subject_name']; ?></td>
                            <td style="font-size: 9px;"><?php echo $subjectTeacher; ?></td>
                            <td><?php echo $a['test1'] !== null ? number_format($a['test1'], 0) : '-'; ?></td>
                            <td><?php echo $a['test2'] !== null ? number_format($a['test2'], 0) : '-'; ?></td>
                            <td><?php echo $a['test3'] !== null ? number_format($a['test3'], 0) : '-'; ?></td>
                            <td><?php echo $a['project'] !== null ? number_format($a['project'], 0) : '-'; ?></td>
                            <td><?php echo $a['class_assessment'] !== null ? number_format($a['class_assessment'], 0) : '-'; ?></td>
                            <td><?php echo $a['exam'] !== null ? number_format($a['exam'], 0) : '-'; ?></td>
                            <td style="font-weight: bold;"><?php echo number_format($total, 0); ?></td>
                            <td><span class="grade-badge" style="background: <?php echo getGradeColor($grade); ?>"><?php echo $grade; ?></span></td>
                            <td style="font-size: 10px;"><?php echo $remark; ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
                
                <div class="summary-row">
                    <div class="summary-box">
                        <h4><?php echo $subjectCount; ?></h4>
                        <p>Subjects</p>
                    </div>
                    <div class="summary-box">
                        <h4><?php echo $subjectCount > 0 ? number_format($totalMarks, 0) : '0'; ?>/<?php echo $subjectCount * 100; ?></h4>
                        <p>Total Marks</p>
                    </div>
                    <div class="summary-box">
                        <h4><?php echo $subjectCount > 0 ? number_format($totalMarks / $subjectCount, 1) : '0'; ?>%</h4>
                        <p>Average</p>
                    </div>
                    <div class="summary-box">
                        <h4 style="color: <?php echo getGradeColor(getGrade($subjectCount > 0 ? $totalMarks / $subjectCount : 0)); ?>">
                            <?php echo $subjectCount > 0 ? getGrade($totalMarks / $subjectCount) : '-'; ?>
                        </h4>
                        <p>Overall</p>
                    </div>
                    <div class="summary-box">
                        <h4><?php echo $classPos . $posSuffix; ?></h4>
                        <p>Class Position</p>
                    </div>
                </div>
                
                <div style="margin-top: 15px; padding-top: 12px; border-top: 2px solid #4a90e2; display: flex; justify-content: space-between; font-size: 11px;">
                    <div>
                        <strong><?php echo $classTeacherTitle; ?>:</strong> <?php echo htmlspecialchars($classTeacher ?? '_________________'); ?><br>
                        <span style="font-size: 10px; color: #888;">Signature: _________________</span>
                    </div>
                    <div style="text-align: right;">
                        <strong><?php echo $headmasterTitle; ?>:</strong> <?php echo htmlspecialchars($headmasterName); ?><br>
                        <span style="font-size: 10px; color: #888;">Signature: _________________</span>
                    </div>
                </div>
                <div style="margin-top: 10px; display: flex; justify-content: space-between; font-size: 11px;">
                    <div><strong>Date:</strong> <?php echo date('d M Y'); ?></div>
                    <div><strong>Stamp:</strong> __________</div>
                </div>
            </div>
            
            <?php endforeach; ?>
            
            <div class="no-print" style="text-align: center; margin-top: 15px;">
                <button onclick="window.print()" class="btn btn-primary" style="padding: 12px 30px;">
                    <i class="fas fa-print"></i> Print All Report Cards
                </button>
            </div>
            
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </main>
    
    <script src="../assets/js/script.js"></script>
</body>
</html>
