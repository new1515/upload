<?php
require_once '../config/database.php';

if (!isset($_SESSION['parent_id'])) {
    redirect('../login.php');
}

$studentId = $_SESSION['parent_student_id'];

$settings = [];
$stmt = $pdo->query("SELECT * FROM school_settings");
while ($row = $stmt->fetch()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
$schoolName = $settings['school_name'] ?? 'School Management System';
$schoolLogo = $settings['school_logo'] ?? '';
$headmasterName = $settings['headmaster_name'] ?? 'Mr. Emmanuel Kofi Asante';

$student = null;
if ($studentId) {
    $stmt = $pdo->prepare("SELECT s.*, c.class_name, c.section, c.id as class_id, t.name as class_teacher_name 
        FROM students s 
        LEFT JOIN classes c ON s.class_id = c.id 
        LEFT JOIN teachers t ON c.class_teacher_id = t.id 
        WHERE s.id = ?");
    $stmt->execute([$studentId]);
    $student = $stmt->fetch();
}

$selectedTerm = isset($_GET['term']) ? sanitize($_GET['term']) : 'Term 1';
$selectedYear = isset($_GET['year']) ? sanitize($_GET['year']) : '2025-2026';

$assessments = [];
if ($student) {
    $stmt = $pdo->prepare("SELECT a.*, sub.subject_name, sub.id as sub_id FROM student_assessments a 
        LEFT JOIN subjects sub ON a.subject_id = sub.id 
        WHERE a.student_id = ? AND a.term = ? AND a.academic_year = ? 
        ORDER BY sub.subject_name");
    $stmt->execute([$studentId, $selectedTerm, $selectedYear]);
    $assessments = $stmt->fetchAll();
}

$subjectTeachers = [];
foreach ($assessments as $a) {
    $teacherStmt = $pdo->prepare("SELECT t.name FROM teachers t INNER JOIN teacher_subjects ts ON t.id = ts.teacher_id WHERE ts.subject_id = ? LIMIT 1");
    $teacherStmt->execute([$a['sub_id']]);
    $teacherName = $teacherStmt->fetchColumn();
    if ($teacherName) {
        $subjectTeachers[$a['sub_id']] = $teacherName;
    }
}

$attStmt = $pdo->prepare("SELECT * FROM student_attendance WHERE student_id = ? AND term = ? AND academic_year = ?");
$attStmt->execute([$studentId, $selectedTerm, $selectedYear]);
$attendance = $attStmt->fetch();

$daysOpened = $attendance['days_school_opened'] ?? 0;
$daysPresent = $attendance['days_present'] ?? 0;
$daysAbsent = $attendance['days_absent'] ?? 0;
$attPercentage = $daysOpened > 0 ? round(($daysPresent / $daysOpened) * 100, 1) : 0;

function getGrade($marks) {
    if ($marks >= 80) return ['A', '#27ae60'];
    if ($marks >= 70) return ['B', '#2ecc71'];
    if ($marks >= 60) return ['C', '#f39c12'];
    if ($marks >= 50) return ['D', '#e67e22'];
    if ($marks >= 40) return ['E', '#e74c3c'];
    return ['F', '#c0392b'];
}

$totalMarks = 0;
$subjectCount = count($assessments);
foreach ($assessments as $a) {
    $totalMarks += ((float)($a['test1'] ?? 0) + (float)($a['test2'] ?? 0) + (float)($a['test3'] ?? 0) + (float)($a['project'] ?? 0) + (float)($a['class_assessment'] ?? 0) + (float)($a['exam'] ?? 0));
}
$average = $subjectCount > 0 ? $totalMarks / $subjectCount : 0;
$gradeInfo = getGrade($average);

$terms = ['Term 1', 'Term 2', 'Term 3'];
$years = ['2025-2026', '2024-2025'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Card - Parent Portal</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        @media print {
            .sidebar, .header, nav, .no-print, .mobile-menu-toggle { display: none !important; }
            .main-content { margin: 0 !important; padding: 10px !important; }
            .content { padding: 0 !important; }
        }
        .report-card { background: white; border: 2px solid #f39c12; border-radius: 15px; padding: 20px; margin: 20px auto; page-break-after: always; }
        .report-header { text-align: center; border-bottom: 2px solid #f39c12; padding-bottom: 15px; margin-bottom: 20px; }
        .report-header h1 { color: #f39c12; font-size: 20px; margin: 10px 0; }
        .report-header p { color: #888; font-size: 12px; margin: 5px 0; }
        .student-info { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; margin-bottom: 20px; padding: 15px; background: #f8f9fa; border-radius: 10px; font-size: 12px; }
        .student-info div { text-align: center; }
        .student-info strong { display: block; font-size: 11px; color: #888; margin-bottom: 5px; }
        .scores-table { width: 100%; border-collapse: collapse; font-size: 11px; }
        .scores-table th { background: #f39c12; color: white; padding: 8px 5px; font-size: 10px; }
        .scores-table td { padding: 8px 5px; border-bottom: 1px solid #eee; text-align: center; }
        .scores-table td:first-child { text-align: left; }
        .grade-badge { padding: 2px 8px; border-radius: 10px; color: white; font-weight: bold; font-size: 10px; }
        .summary-row { display: grid; grid-template-columns: repeat(5, 1fr); gap: 10px; margin-top: 20px; }
        .summary-box { text-align: center; padding: 12px; background: #f8f9fa; border-radius: 10px; }
        .summary-box h4 { font-size: 18px; margin: 0; color: #f39c12; }
        .summary-box p { margin: 5px 0 0 0; font-size: 10px; color: #888; }
        .signature-row { display: flex; justify-content: space-between; margin-top: 25px; padding-top: 15px; border-top: 1px solid #eee; font-size: 11px; }
        .term-select { background: white; padding: 15px; border-radius: 10px; margin-bottom: 20px; display: flex; gap: 15px; flex-wrap: wrap; }
        .term-select .form-group { margin: 0; flex: 1; min-width: 120px; }
    </style>
</head>
<body>
    <button class="mobile-menu-toggle">
        <i class="fas fa-bars"></i>
    </button>
    <div class="sidebar-overlay"></div>
    <nav class="sidebar">
        <div class="sidebar-brand"><i class="fas fa-users"></i> Parent Portal</div>
        <ul class="sidebar-menu">
            <li><a href="index.php"><i class="fas fa-home"></i> Dashboard</a></li>
            <li><a href="announcements.php"><i class="fas fa-bullhorn"></i> Announcements</a></li>
            <li><a href="results.php"><i class="fas fa-chart-line"></i> Results</a></li>
            <li><a href="reportcard.php" class="active"><i class="fas fa-file-alt"></i> Report Card</a></li>
            <li><a href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
            <li><a href="../logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </nav>
    
    <main class="main-content">
        <div class="header">
            <h1><i class="fas fa-file-alt"></i> Report Card</h1>
            <div class="header-right no-print">
                <?php if (empty($assessments)): ?>
                <span style="color: #888;"><i class="fas fa-info-circle"></i> No results for this term</span>
                <?php else: ?>
                <button onclick="window.print()" class="btn btn-primary"><i class="fas fa-print"></i> Print</button>
                <?php endif; ?>
                <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
        
        <div class="school-header no-print">
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
            <form method="GET" class="term-select no-print">
                <div class="form-group">
                    <label>Term</label>
                    <select name="term" onchange="this.form.submit()">
                        <?php foreach ($terms as $t): ?>
                        <option value="<?php echo $t; ?>" <?php echo $selectedTerm == $t ? 'selected' : ''; ?>><?php echo $t; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Academic Year</label>
                    <select name="year" onchange="this.form.submit()">
                        <?php foreach ($years as $y): ?>
                        <option value="<?php echo $y; ?>" <?php echo $selectedYear == $y ? 'selected' : ''; ?>><?php echo $y; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>
            
            <?php if ($student): ?>
            <div class="report-card">
                <div class="report-header">
                    <h1><?php echo htmlspecialchars($schoolName); ?></h1>
                    <p>Academic Report Card - <?php echo $selectedTerm; ?>, <?php echo $selectedYear; ?></p>
                </div>
                
                <div class="student-info">
                    <div><strong>Student Name</strong><?php echo htmlspecialchars($student['name']); ?></div>
                    <div><strong>Class</strong><?php echo htmlspecialchars(($student['class_name'] ?? '') . ' - Section ' . ($student['section'] ?? '')); ?></div>
                    <div><strong>Gender</strong><?php echo htmlspecialchars($student['gender']); ?></div>
                    <div><strong>Class Teacher</strong><?php echo htmlspecialchars($student['class_teacher_name'] ?? 'Not assigned'); ?></div>
                </div>
                
                <div style="background: #f8f9fa; padding: 12px; border-radius: 8px; margin-bottom: 15px; display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; text-align: center; font-size: 12px;">
                    <div><strong>Days Opened</strong><br><?php echo $daysOpened; ?></div>
                    <div><strong>Days Present</strong><br><?php echo $daysPresent; ?></div>
                    <div><strong>Days Absent</strong><br><?php echo $daysAbsent; ?></div>
                    <div><strong>Attendance %</strong><br><span style="padding: 2px 8px; border-radius: 10px; color: white; font-weight: bold; font-size: 11px; background: <?php echo $attPercentage >= 75 ? '#27ae60' : ($attPercentage >= 50 ? '#f39c12' : '#e74c3c'); ?>"><?php echo $attPercentage; ?>%</span></div>
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
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($assessments)): ?>
                        <tr><td colspan="11" style="text-align: center; color: #999;">No results available for this term.</td></tr>
                        <?php else: ?>
                        <?php $subNum = 0; foreach ($assessments as $a): $subNum++; 
                            $total = ((float)($a['test1'] ?? 0) + (float)($a['test2'] ?? 0) + (float)($a['test3'] ?? 0) + (float)($a['project'] ?? 0) + (float)($a['class_assessment'] ?? 0) + (float)($a['exam'] ?? 0));
                            $gradeInfo = getGrade($total);
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
                            <td><span class="grade-badge" style="background: <?php echo $gradeInfo[1]; ?>"><?php echo $gradeInfo[0]; ?></span></td>
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
                        <h4><?php echo number_format($totalMarks, 0); ?></h4>
                        <p>Total Marks</p>
                    </div>
                    <div class="summary-box">
                        <h4><?php echo number_format($average, 1); ?>%</h4>
                        <p>Average</p>
                    </div>
                    <div class="summary-box">
                        <h4 style="color: <?php echo $gradeInfo[1]; ?>"><?php echo $gradeInfo[0]; ?></h4>
                        <p>Grade</p>
                    </div>
                    <div class="summary-box">
                        <h4><?php echo $selectedTerm; ?></h4>
                        <p>Term</p>
                    </div>
                </div>
                
                <div class="signature-row">
                    <div><strong>Class Teacher:</strong> <?php echo htmlspecialchars($student['class_teacher_name'] ?? '_________________'); ?></div>
                    <div><strong>Headmaster:</strong> <?php echo htmlspecialchars($headmasterName); ?></div>
                </div>
                <div style="margin-top: 10px; font-size: 11px;">
                    <div><strong>Headmaster/Headmistress Signature:</strong> _________________</div>
                    <div style="margin-top: 5px;"><strong>Date:</strong> <?php echo date('d M Y'); ?></div>
                </div>
            </div>
            <?php else: ?>
            <div style="background: white; padding: 60px; border-radius: 15px; text-align: center;">
                <p style="color: #888;">No student linked to this account.</p>
            </div>
            <?php endif; ?>
        </div>
    </main>
    <script src="../assets/js/script.js"></script>
</body>
</html>
