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

$terms = ['Term 1', 'Term 2', 'Term 3'];
$years = ['2025-2026', '2024-2025'];

$results = [];
if ($classId && $subjectId) {
    $stmt = $pdo->prepare("SELECT sa.*, s.name as student_name, s.id as student_number, 
        sub.subject_name, c.class_name, c.section
        FROM student_assessments sa
        INNER JOIN students s ON sa.student_id = s.id
        INNER JOIN subjects sub ON sa.subject_id = sub.id
        INNER JOIN classes c ON sa.class_id = c.id
        WHERE sa.class_id = ? AND sa.subject_id = ? AND sa.term = ? AND sa.academic_year = ?
        ORDER BY s.name");
    $stmt->execute([$classId, $subjectId, $term, $academicYear]);
    $results = $stmt->fetchAll();
}

$class = null;
if ($classId) {
    $stmt = $pdo->prepare("SELECT * FROM classes WHERE id = ?");
    $stmt->execute([$classId]);
    $class = $stmt->fetch();
}

$subject = null;
if ($subjectId) {
    $stmt = $pdo->prepare("SELECT * FROM subjects WHERE id = ?");
    $stmt->execute([$subjectId]);
    $subject = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Results - Teacher Portal</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        .portal-header { background: linear-gradient(135deg, #2980b9, #3498db); color: white; padding: 25px 30px; border-radius: 15px; margin-bottom: 25px; }
        .filters { background: white; padding: 20px; border-radius: 10px; margin-bottom: 20px; display: flex; gap: 15px; flex-wrap: wrap; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .filters .form-group { flex: 1; min-width: 150px; margin-bottom: 0; }
        .result-summary { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 20px; }
        .stat-card { background: white; padding: 20px; border-radius: 10px; text-align: center; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .stat-card h3 { font-size: 28px; margin: 0; color: #2c3e50; }
        .stat-card p { margin: 5px 0 0; color: #7f8c8d; font-size: 14px; }
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
            <li><a href="enter_scores.php"><i class="fas fa-edit"></i> Enter Scores</a></li>
            <li><a href="attendance.php"><i class="fas fa-calendar-check"></i> Attendance</a></li>
            <li><a href="results.php" class="active"><i class="fas fa-chart-line"></i> View Results</a></li>
            <li><a href="lessons.php"><i class="fas fa-book-open"></i> Lessons</a></li>
            <li><a href="lesson_plans.php"><i class="fas fa-calendar-alt"></i> Lesson Plans</a></li>
            <li><a href="test_books.php"><i class="fas fa-file-alt"></i> Test Books</a></li>
            <li><a href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
            <li><a href="../logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </nav>
    
    <main class="main-content">
        <div class="header">
            <h1><i class="fas fa-chart-line"></i> View Results</h1>
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
            <div class="filters">
                <form method="GET" id="filterForm" style="display: flex; gap: 15px; flex-wrap: wrap; align-items: flex-end; width: 100%;">
                    <div class="form-group">
                        <label>Subject</label>
                        <select name="subject" id="subjectSelect" onchange="submitFilter()">
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
                        <select name="class" id="classSelect" onchange="submitFilter()">
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
                        <select name="term" onchange="submitFilter()">
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
            
            <?php if ($classId && $subjectId): ?>
                <?php if (!empty($results)): ?>
                    <?php
                    $totalScores = [];
                    $passCount = 0;
                    foreach ($results as $r) {
                        $test1 = (float)($r['test1'] ?? 0);
                        if ($test1 > 0) {
                            $totalScores[] = $test1;
                        }
                        $total = (float)($r['test1'] ?? 0) + (float)($r['test2'] ?? 0) + (float)($r['test3'] ?? 0) + (float)($r['project'] ?? 0) + (float)($r['class_assessment'] ?? 0) + (float)($r['exam'] ?? 0);
                        if ($total >= 50) {
                            $passCount++;
                        }
                    }
                    $avgScore = count($totalScores) > 0 ? array_sum($totalScores) / count($totalScores) : 0;
                    ?>
                    <div class="result-summary">
                        <div class="stat-card">
                            <h3><?php echo count($results); ?></h3>
                            <p>Total Students</p>
                        </div>
                        <div class="stat-card">
                            <h3><?php echo number_format($avgScore, 1); ?></h3>
                            <p>Average Score</p>
                        </div>
                        <div class="stat-card">
                            <h3><?php echo $passCount; ?></h3>
                            <p>Passed (50+)</p>
                        </div>
                        <div class="stat-card">
                            <h3><?php echo count($results) - $passCount; ?></h3>
                            <p>Failed (&lt;50)</p>
                        </div>
                    </div>
                    
                    <div class="table-container">
                        <div class="table-header">
                            <h3><?php echo htmlspecialchars(($class['class_name'] ?? '') . ' - Section ' . ($class['section'] ?? '')); ?> - <?php echo htmlspecialchars($subject['subject_name'] ?? ''); ?></h3>
                        </div>
                        <table>
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th style="text-align: left;">Student Name</th>
                                    <th>Student ID</th>
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
                                <?php foreach ($results as $index => $r): 
                                    $total = ($r['test1'] ?? 0) + ($r['test2'] ?? 0) + ($r['test3'] ?? 0) + 
                                             ($r['project'] ?? 0) + ($r['class_assessment'] ?? 0) + ($r['exam'] ?? 0);
                                ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td style="text-align: left; font-weight: 500;"><?php echo htmlspecialchars($r['student_name']); ?></td>
                                    <td><?php echo htmlspecialchars($r['student_number'] ?? $r['id']); ?></td>
                                    <td><?php echo $r['test1'] ?? '-'; ?></td>
                                    <td><?php echo $r['test2'] ?? '-'; ?></td>
                                    <td><?php echo $r['test3'] ?? '-'; ?></td>
                                    <td><?php echo $r['project'] ?? '-'; ?></td>
                                    <td><?php echo $r['class_assessment'] ?? '-'; ?></td>
                                    <td><?php echo $r['exam'] ?? '-'; ?></td>
                                    <td><strong><?php echo number_format($total, 1); ?></strong></td>
                                    <td><span class="badge badge-<?php echo ($r['grade'] ?? 'F') >= 'C' ? 'success' : 'danger'; ?>"><?php echo $r['grade'] ?? '-'; ?></span></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div style="background: white; padding: 60px; border-radius: 15px; text-align: center;">
                        <i class="fas fa-chart-line" style="font-size: 50px; color: #ddd; margin-bottom: 20px;"></i>
                        <p style="color: #888;">No results found. Use "Enter Scores" to add student results.</p>
                        <a href="enter_scores.php" class="btn btn-primary" style="margin-top: 15px;"><i class="fas fa-edit"></i> Enter Scores</a>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div style="background: white; padding: 60px; border-radius: 15px; text-align: center;">
                    <i class="fas fa-filter" style="font-size: 50px; color: #ddd; margin-bottom: 20px;"></i>
                    <p style="color: #888;">Select a subject and class above to view results.</p>
                </div>
            <?php endif; ?>
        </div>
    </main>
    <script src="../assets/js/script.js"></script>
    <script>
        function submitFilter() {
            var subject = document.getElementById('subjectSelect').value;
            var classId = document.getElementById('classSelect').value;
            
            var params = [];
            if (subject) params.push('subject=' + subject);
            if (classId) params.push('class=' + classId);
            
            var term = document.querySelector('select[name="term"]').value;
            var year = document.querySelector('select[name="year"]').value;
            
            if (term) params.push('term=' + encodeURIComponent(term));
            if (year) params.push('year=' + encodeURIComponent(year));
            
            var url = 'results.php' + (params.length > 0 ? '?' + params.join('&') : '');
            window.location.href = url;
        }
    </script>
</body>
</html>
