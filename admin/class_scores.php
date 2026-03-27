<?php
require_once '../config/database.php';

$settings = [];
$stmt = $pdo->query("SELECT * FROM school_settings");
while ($row = $stmt->fetch()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
$schoolName = $settings['school_name'] ?? 'School Management System';
$schoolLogo = $settings['school_logo'] ?? '';

if (!isLoggedIn()) {
    redirect('../login.php');
}

$classId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$subjectId = isset($_GET['subject']) ? (int)$_GET['subject'] : 0;
$term = isset($_GET['term']) ? sanitize($_GET['term']) : 'Term 1';
$academicYear = isset($_GET['year']) ? sanitize($_GET['year']) : '2025-2026';

$class = $pdo->prepare("SELECT * FROM classes WHERE id = ?");
$class->execute([$classId]);
$class = $class->fetch();

if (!$class) {
    header("Location: classes.php");
    exit();
}

$message = '';

$subjects = $pdo->query("SELECT * FROM subjects WHERE level = '" . $class['level'] . "' OR level = 'all' ORDER BY category, subject_name")->fetchAll();
$selectedSubject = $subjectId ? $pdo->prepare("SELECT * FROM subjects WHERE id = ?") : null;
if ($selectedSubject) {
    $selectedSubject->execute([$subjectId]);
    $selectedSubject = $selectedSubject->fetch();
}

$students = $pdo->prepare("SELECT * FROM students WHERE class_id = ? ORDER BY name");
$students->execute([$classId]);
$students = $students->fetchAll();

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
        $remarks = NULL;
        if ($exam !== NULL || $total > 0) {
            if ($total >= 80) { $grade = 'A'; $remarks = 'Excellent'; }
            elseif ($total >= 70) { $grade = 'B'; $remarks = 'Very Good'; }
            elseif ($total >= 60) { $grade = 'C'; $remarks = 'Good'; }
            elseif ($total >= 50) { $grade = 'D'; $remarks = 'Pass'; }
            elseif ($total >= 40) { $grade = 'E'; $remarks = 'Below Pass'; }
            else { $grade = 'F'; $remarks = 'Fail'; }
        }
        
        $stmt = $pdo->prepare("INSERT INTO student_assessments 
            (student_id, class_id, subject_id, term, academic_year, test1, test2, test3, project, class_assessment, exam, grade, remarks) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
            test1 = VALUES(test1), test2 = VALUES(test2), test3 = VALUES(test3), 
            project = VALUES(project), class_assessment = VALUES(class_assessment), 
            exam = VALUES(exam), grade = VALUES(grade), remarks = VALUES(remarks)");
        $stmt->execute([$studentId, $classId, $subjectId, $term, $academicYear, $test1, $test2, $test3, $project, $class_assessment, $exam, $grade, $remarks]);
    }
    $message = '<div class="success-msg">Scores saved successfully!</div>';
}

$assessments = [];
if ($subjectId) {
    $stmt = $pdo->prepare("SELECT * FROM student_assessments WHERE class_id = ? AND subject_id = ? AND term = ? AND academic_year = ?");
    $stmt->execute([$classId, $subjectId, $term, $academicYear]);
    $assessmentsRaw = $stmt->fetchAll();
    foreach ($assessmentsRaw as $a) {
        $assessments[$a['student_id']] = $a;
    }
}

$terms = ['Term 1', 'Term 2', 'Term 3'];
$years = ['2025-2026', '2024-2025', '2023-2024'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enter Scores - <?php echo $class['class_name']; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        .class-banner {
            background: linear-gradient(135deg, <?php 
                if ($class['level'] === 'nursery') echo '#e74c3c, #c0392b';
                elseif ($class['level'] === 'kg') echo '#f39c12, #d35400';
                elseif ($class['level'] === 'primary') echo '#4a90e2, #357abd';
                else echo '#27ae60, #2ecc71';
            ?>);
            color: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 25px;
        }
        .class-banner h2 { font-size: 24px; margin-bottom: 5px; }
        .filters {
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: flex-end;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .filters .form-group { flex: 1; min-width: 150px; margin-bottom: 0; }
        .score-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .score-info h4 { margin-bottom: 10px; color: #2c3e50; }
        .score-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
            gap: 10px;
        }
        .score-info-item {
            text-align: center;
            padding: 10px;
            background: #fff;
            border-radius: 8px;
        }
        .score-info-item span { display: block; font-size: 12px; color: #666; }
        .score-info-item strong { font-size: 16px; color: #4a90e2; }
        .scores-table { width: 100%; overflow-x: auto; }
        .scores-table input {
            width: 60px;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 5px;
            text-align: center;
        }
        .scores-table input:focus { border-color: #4a90e2; outline: none; }
        .total-col { background: #f0f4f8; font-weight: bold; }
        .grade-col { 
            min-width: 50px; 
            text-align: center;
            padding: 8px;
            border-radius: 5px;
            color: #fff;
            font-weight: bold;
        }
        .grade-a { background: #27ae60; }
        .grade-b { background: #2ecc71; }
        .grade-c { background: #f39c12; }
        .grade-d { background: #e67e22; }
        .grade-e { background: #e74c3c; }
        .grade-f { background: #c0392b; }
        .no-subject {
            text-align: center;
            padding: 60px;
            background: #fff;
            border-radius: 15px;
        }
        .no-subject i { font-size: 60px; color: #ddd; margin-bottom: 20px; }
    </style>
</head>
<body>
    <button class="mobile-menu-toggle">
        <i class="fas fa-bars"></i>
    </button>
    <div class="sidebar-overlay"></div>
    <nav class="sidebar">
        <div class="sidebar-brand">
            <i class="fas fa-graduation-cap"></i>
            School Admin
        </div>
        <ul class="sidebar-menu">
            <li><a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
            <li><a href="students.php"><i class="fas fa-user-graduate"></i> Students</a></li>
            <li><a href="parents.php"><i class="fas fa-users"></i> Parents</a></li>
            <li><a href="teachers.php"><i class="fas fa-chalkboard-teacher"></i> Teachers</a></li>
            <li><a href="subjects.php"><i class="fas fa-book"></i> Subjects</a></li>
            <li><a href="classes.php" class="active"><i class="fas fa-school"></i> Classes</a></li>
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
            <h1><i class="fas fa-edit"></i> Enter Scores</h1>
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
                <a href="settings.php" title="Settings"><i class="fas fa-cog"></i></a>
                <a href="class_students.php?id=<?php echo $classId; ?>"><i class="fas fa-users"></i> View Students</a>
                <a href="classes.php"><i class="fas fa-arrow-left"></i> Back</a>
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
            <div class="class-banner">
                <div>
                    <h2><i class="fas fa-school"></i> <?php echo $class['class_name']; ?> - Section <?php echo $class['section']; ?></h2>
                    <p><?php 
                        if ($class['level'] === 'nursery') echo 'Nursery';
                        elseif ($class['level'] === 'kg') echo 'Kindergarten';
                        elseif ($class['level'] === 'primary') echo 'Primary School';
                        else echo 'Junior High School';
                    ?></p>
                </div>
            </div>
            
            <?php echo $message; ?>
            
            <div class="filters">
                <form method="GET" style="display: flex; gap: 15px; flex-wrap: wrap; align-items: flex-end; width: 100%;">
                    <input type="hidden" name="id" value="<?php echo $classId; ?>">
                    <div class="form-group">
                        <label>Subject</label>
                        <select name="subject" required onchange="this.form.submit()">
                            <option value="">Select Subject</option>
                            <?php foreach ($subjects as $s): ?>
                            <option value="<?php echo $s['id']; ?>" <?php echo $subjectId == $s['id'] ? 'selected' : ''; ?>>
                                <?php echo $s['subject_name']; ?> (<?php echo ucfirst($s['category']); ?>)
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
                        <label>Academic Year</label>
                        <select name="year" onchange="this.form.submit()">
                            <?php foreach ($years as $y): ?>
                            <option value="<?php echo $y; ?>" <?php echo $academicYear == $y ? 'selected' : ''; ?>><?php echo $y; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </form>
            </div>
            
            <?php if (!$subjectId): ?>
            <div class="no-subject">
                <i class="fas fa-book-open"></i>
                <h3>Select a Subject</h3>
                <p>Please select a subject above to enter scores.</p>
            </div>
            <?php elseif (empty($students)): ?>
            <div class="no-subject">
                <i class="fas fa-users"></i>
                <h3>No Students in This Class</h3>
                <p>Add students to this class first before entering scores.</p>
                <a href="class_students.php?id=<?php echo $classId; ?>" class="btn btn-primary">Add Students</a>
            </div>
            <?php else: ?>
            
            <div class="score-info">
                <h4><i class="fas fa-info-circle"></i> Score Distribution (Total: 100 Marks)</h4>
                <div class="score-info-grid">
                    <div class="score-info-item">
                        <strong>10</strong>
                        <span>Test 1</span>
                    </div>
                    <div class="score-info-item">
                        <strong>10</strong>
                        <span>Test 2</span>
                    </div>
                    <div class="score-info-item">
                        <strong>10</strong>
                        <span>Test 3</span>
                    </div>
                    <div class="score-info-item">
                        <strong>10</strong>
                        <span>Project</span>
                    </div>
                    <div class="score-info-item">
                        <strong>10</strong>
                        <span>Class Assessment</span>
                    </div>
                    <div class="score-info-item">
                        <strong>50</strong>
                        <span>Exam</span>
                    </div>
                </div>
            </div>
            
            <form method="POST">
                <div class="table-container">
                    <div class="table-header">
                        <h2><i class="fas fa-file-alt"></i> <?php echo $selectedSubject['subject_name']; ?> - <?php echo $term; ?> (<?php echo $academicYear; ?>)</h2>
                        <button type="submit" name="save_scores" class="btn btn-success">
                            <i class="fas fa-save"></i> Save All Scores
                        </button>
                    </div>
                    
                    <div class="scores-table">
                        <table>
                            <thead>
                                <tr>
                                    <th style="min-width: 40px;">#</th>
                                    <th style="min-width: 200px; text-align: left;">Student Name</th>
                                    <th>Test 1<br><small>/10</small></th>
                                    <th>Test 2<br><small>/10</small></th>
                                    <th>Test 3<br><small>/10</small></th>
                                    <th>Project<br><small>/10</small></th>
                                    <th>Class Ass.<br><small>/10</small></th>
                                    <th>Exam<br><small>/50</small></th>
                                    <th class="total-col">Total<br><small>/100</small></th>
                                    <th>Grade</th>
                                    <th>Remarks</th>
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
                                    <td style="text-align: left; font-weight: 500;"><?php echo $student['name']; ?></td>
                                    <td>
                                        <input type="number" name="scores[<?php echo $student['id']; ?>][test1]" 
                                               value="<?php echo $a['test1'] ?? ''; ?>" 
                                               min="0" max="10" step="0.5" placeholder="-">
                                    </td>
                                    <td>
                                        <input type="number" name="scores[<?php echo $student['id']; ?>][test2]" 
                                               value="<?php echo $a['test2'] ?? ''; ?>" 
                                               min="0" max="10" step="0.5" placeholder="-">
                                    </td>
                                    <td>
                                        <input type="number" name="scores[<?php echo $student['id']; ?>][test3]" 
                                               value="<?php echo $a['test3'] ?? ''; ?>" 
                                               min="0" max="10" step="0.5" placeholder="-">
                                    </td>
                                    <td>
                                        <input type="number" name="scores[<?php echo $student['id']; ?>][project]" 
                                               value="<?php echo $a['project'] ?? ''; ?>" 
                                               min="0" max="10" step="0.5" placeholder="-">
                                    </td>
                                    <td>
                                        <input type="number" name="scores[<?php echo $student['id']; ?>][class_assessment]" 
                                               value="<?php echo $a['class_assessment'] ?? ''; ?>" 
                                               min="0" max="10" step="0.5" placeholder="-">
                                    </td>
                                    <td>
                                        <input type="number" name="scores[<?php echo $student['id']; ?>][exam]" 
                                               value="<?php echo $a['exam'] ?? ''; ?>" 
                                               min="0" max="50" step="0.5" placeholder="-">
                                    </td>
                                    <td class="total-col" id="total_<?php echo $student['id']; ?>"><?php echo $total > 0 ? number_format($total, 1) : '-'; ?></td>
                                    <td>
                                        <?php if ($a && $a['grade']): ?>
                                        <span class="grade-col <?php echo 'grade-' . strtolower($a['grade'][0]); ?>">
                                            <?php echo $a['grade']; ?>
                                        </span>
                                        <?php else: ?>
                                        <span style="color: #ccc;">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $a['remarks'] ?? '-'; ?></td>
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
    
    <script>
        document.querySelectorAll('input[type="number"]').forEach(function(input) {
            input.addEventListener('change', function() {
                var row = this.closest('tr');
                var inputs = row.querySelectorAll('input[type="number"]');
                var total = 0;
                inputs.forEach(function(inp) {
                    var val = parseFloat(inp.value) || 0;
                    total += val;
                });
                var totalCell = row.querySelector('[id^="total_"]');
                if (totalCell) {
                    totalCell.textContent = total > 0 ? total.toFixed(1) : '-';
                }
            });
        });
    </script>
    <script src="../assets/js/script.js"></script>
</body>
</html>
