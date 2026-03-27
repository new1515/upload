<?php
require_once '../config/database.php';

if (!isset($_SESSION['teacher_id'])) {
    redirect('../login.php');
}

$teacherId = $_SESSION['teacher_id'];
$classId = isset($_GET['class']) ? (int)$_GET['class'] : 0;
$subjectId = isset($_GET['subject']) ? (int)$_GET['subject'] : 0;
$term = isset($_GET['term']) ? sanitize($_GET['term']) : 'Term 1';
$academicYear = isset($_GET['year']) ? sanitize($_GET['year']) : '2025-2026';

$settings = [];
$stmt = $pdo->query("SELECT * FROM school_settings");
while ($row = $stmt->fetch()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
$schoolName = $settings['school_name'] ?? 'School Management System';
$schoolLogo = $settings['school_logo'] ?? '';

$teacherSubjects = $pdo->prepare("SELECT sub.* FROM subjects sub 
    INNER JOIN teacher_subjects ts ON sub.id = ts.subject_id 
    WHERE ts.teacher_id = ?");
$teacherSubjects->execute([$teacherId]);
$teacherSubjects = $teacherSubjects->fetchAll();

$teacherClasses = $pdo->query("SELECT c.* FROM classes c 
    INNER JOIN teacher_classes tc ON c.id = tc.class_id 
    WHERE tc.teacher_id = $teacherId ORDER BY c.level, c.class_name, c.section")->fetchAll();

$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_scores'])) {
    $scores = $_POST['scores'];
    foreach ($scores as $studentId => $data) {
        $test1 = isset($data['test1']) && $data['test1'] !== '' ? (float)$data['test1'] : NULL;
        $test2 = isset($data['test2']) && $data['test2'] !== '' ? (float)$data['test2'] : NULL;
        $test3 = isset($data['test3']) && $data['test3'] !== '' ? (float)$data['test3'] : NULL;
        $project = isset($data['project']) && $data['project'] !== '' ? (float)$data['project'] : NULL;
        $class_assessment = isset($data['class_assessment']) && $data['class_assessment'] !== '' ? (float)$data['class_assessment'] : NULL;
        $exam = isset($data['exam']) && $data['exam'] !== '' ? (float)$data['exam'] : NULL;
        
        $total = 0;
        if ($test1 !== NULL) $total += $test1;
        if ($test2 !== NULL) $total += $test2;
        if ($test3 !== NULL) $total += $test3;
        if ($project !== NULL) $total += $project;
        if ($class_assessment !== NULL) $total += $class_assessment;
        if ($exam !== NULL) $total += $exam;
        
        $grade = NULL;
        if ($exam !== NULL || $total > 0) {
            if ($total >= 80) $grade = 'A';
            elseif ($total >= 70) $grade = 'B';
            elseif ($total >= 60) $grade = 'C';
            elseif ($total >= 50) $grade = 'D';
            elseif ($total >= 40) $grade = 'E';
            else $grade = 'F';
        }
        
        $stmt = $pdo->prepare("INSERT INTO student_assessments 
            (student_id, class_id, subject_id, term, academic_year, test1, test2, test3, project, class_assessment, exam, grade) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
            test1 = VALUES(test1), test2 = VALUES(test2), test3 = VALUES(test3), 
            project = VALUES(project), class_assessment = VALUES(class_assessment), 
            exam = VALUES(exam), grade = VALUES(grade)");
        $stmt->execute([$studentId, $classId, $subjectId, $term, $academicYear, $test1, $test2, $test3, $project, $class_assessment, $exam, $grade]);
    }
    $message = '<div class="success-msg">Scores saved successfully!</div>';
}

$class = null;
if ($classId) {
    $stmt = $pdo->prepare("SELECT * FROM classes WHERE id = ?");
    $stmt->execute([$classId]);
    $class = $stmt->fetch();
}

$students = [];
if ($classId) {
    $stmt = $pdo->prepare("SELECT * FROM students WHERE class_id = ? ORDER BY name");
    $stmt->execute([$classId]);
    $students = $stmt->fetchAll();
}

$assessments = [];
if ($subjectId && $classId) {
    $stmt = $pdo->prepare("SELECT * FROM student_assessments WHERE class_id = ? AND subject_id = ? AND term = ? AND academic_year = ?");
    $stmt->execute([$classId, $subjectId, $term, $academicYear]);
    $assessmentsRaw = $stmt->fetchAll();
    foreach ($assessmentsRaw as $a) {
        $assessments[$a['student_id']] = $a;
    }
}

$terms = ['Term 1', 'Term 2', 'Term 3'];
$years = ['2025-2026', '2024-2025'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enter Scores - Teacher Portal</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        .portal-header { background: linear-gradient(135deg, #27ae60, #2ecc71); color: white; padding: 25px 30px; border-radius: 15px; margin-bottom: 25px; }
        .filters { background: white; padding: 20px; border-radius: 10px; margin-bottom: 20px; display: flex; gap: 15px; flex-wrap: wrap; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .filters .form-group { flex: 1; min-width: 150px; margin-bottom: 0; }
        .score-table { width: 100%; overflow-x: auto; }
        .score-table input { width: 60px; padding: 8px; border: 1px solid #ddd; border-radius: 5px; text-align: center; }
        .total-col { background: #f0f4f8; font-weight: bold; }
    </style>
</head>
<body>
    <button class="mobile-menu-toggle">
        <i class="fas fa-bars"></i>
    </button>
    <div class="sidebar-overlay"></div>
    <nav class="sidebar">
        <div class="sidebar-brand"><i class="fas fa-chalkboard-teacher"></i> Teacher Portal</div>
        <ul class="sidebar-menu">
            <li><a href="index.php"><i class="fas fa-home"></i> Dashboard</a></li>
            <li><a href="announcements.php"><i class="fas fa-bullhorn"></i> Announcements</a></li>
            <li><a href="my_classes.php"><i class="fas fa-school"></i> My Classes</a></li>
            <li><a href="enter_scores.php" class="active"><i class="fas fa-edit"></i> Enter Scores</a></li>
            <li><a href="attendance.php"><i class="fas fa-calendar-check"></i> Attendance</a></li>
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
            <h1><i class="fas fa-edit"></i> Enter Scores</h1>
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
            
            <div class="filters">
                <form method="GET" style="display: flex; gap: 15px; flex-wrap: wrap; align-items: flex-end; width: 100%;">
                    <div class="form-group">
                        <label>Subject</label>
                        <select name="subject" required onchange="this.form.submit()">
                            <option value="">Select Subject</option>
                            <?php foreach ($teacherSubjects as $s): ?>
                            <option value="<?php echo $s['id']; ?>" <?php echo $subjectId == $s['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($s['subject_name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Class</label>
                        <select name="class" required onchange="this.form.submit()">
                            <option value="">Select Class</option>
                            <?php foreach ($teacherClasses as $c): ?>
                            <option value="<?php echo $c['id']; ?>" <?php echo $classId == $c['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($c['class_name'] . ' - ' . $c['section']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Term</label>
                        <select name="term" onchange="this.form.submit()">
                            <?php foreach ($terms as $t): ?>
                            <option value="<?php echo $t; ?>" <?php echo $term == $t ? 'selected' : ''; ?>><?php echo $t; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Year</label>
                        <select name="year" onchange="this.form.submit()">
                            <?php foreach ($years as $y): ?>
                            <option value="<?php echo $y; ?>" <?php echo $academicYear == $y ? 'selected' : ''; ?>><?php echo $y; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </form>
            </div>
            
            <?php if (!$subjectId || !$classId): ?>
            <div style="background: white; padding: 60px; border-radius: 15px; text-align: center;">
                <i class="fas fa-edit" style="font-size: 50px; color: #ddd; margin-bottom: 20px;"></i>
                <p style="color: #888;">Select a subject and class above to enter scores.</p>
            </div>
            <?php elseif (empty($students)): ?>
            <div style="background: white; padding: 60px; border-radius: 15px; text-align: center;">
                <i class="fas fa-users" style="font-size: 50px; color: #ddd; margin-bottom: 20px;"></i>
                <p style="color: #888;">No students in this class.</p>
            </div>
            <?php else: ?>
            <form method="POST">
                <div class="table-container">
                    <div class="table-header">
                        <h3><?php echo htmlspecialchars($class['class_name'] . ' - Section ' . $class['section']); ?> - Scores</h3>
                        <button type="submit" name="save_scores" class="btn btn-success">
                            <i class="fas fa-save"></i> Save Scores
                        </button>
                    </div>
                    <div class="score-table">
                        <table>
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th style="text-align: left;">Student Name</th>
                                    <th>T1<br><small>/10</small></th>
                                    <th>T2<br><small>/10</small></th>
                                    <th>T3<br><small>/10</small></th>
                                    <th>Proj<br><small>/10</small></th>
                                    <th>CA<br><small>/10</small></th>
                                    <th>Exam<br><small>/50</small></th>
                                    <th class="total-col">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($students as $index => $student): 
                                    $a = isset($assessments[$student['id']]) ? $assessments[$student['id']] : null;
                                    $total = 0;
                                    if ($a) {
                                        $total = (float)($a['test1'] ?? 0) + (float)($a['test2'] ?? 0) + (float)($a['test3'] ?? 0) + 
                                                 (float)($a['project'] ?? 0) + (float)($a['class_assessment'] ?? 0) + (float)($a['exam'] ?? 0);
                                    }
                                ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td style="text-align: left; font-weight: 500;"><?php echo htmlspecialchars($student['name']); ?></td>
                                    <td><input type="number" name="scores[<?php echo $student['id']; ?>][test1]" value="<?php echo $a['test1'] ?? ''; ?>" min="0" max="10" step="0.5"></td>
                                    <td><input type="number" name="scores[<?php echo $student['id']; ?>][test2]" value="<?php echo $a['test2'] ?? ''; ?>" min="0" max="10" step="0.5"></td>
                                    <td><input type="number" name="scores[<?php echo $student['id']; ?>][test3]" value="<?php echo $a['test3'] ?? ''; ?>" min="0" max="10" step="0.5"></td>
                                    <td><input type="number" name="scores[<?php echo $student['id']; ?>][project]" value="<?php echo $a['project'] ?? ''; ?>" min="0" max="10" step="0.5"></td>
                                    <td><input type="number" name="scores[<?php echo $student['id']; ?>][class_assessment]" value="<?php echo $a['class_assessment'] ?? ''; ?>" min="0" max="10" step="0.5"></td>
                                    <td><input type="number" name="scores[<?php echo $student['id']; ?>][exam]" value="<?php echo $a['exam'] ?? ''; ?>" min="0" max="50" step="0.5"></td>
                                    <td class="total-col" id="total_<?php echo $student['id']; ?>"><?php echo $total > 0 ? number_format($total, 1) : '-'; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </form>
            <?php endif; ?>
        </div>
    </main>
    <script src="../assets/js/script.js"></script>
</body>
</html>
