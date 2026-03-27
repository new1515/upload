<?php 
require_once '../config/database.php'; 

if (!isset($_SESSION['teacher_id'])) { 
    redirect('../login.php'); 
}

$teacherId = $_SESSION['teacher_id'];
$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_test'])) {
    $class_id = (int)$_POST['class_id'];
    $subject_id = (int)$_POST['subject_id'];
    $test_type = sanitize($_POST['test_type']);
    $test_date = sanitize($_POST['test_date']);
    $total_marks = (int)$_POST['total_marks'];
    $topic = sanitize($_POST['topic']);
    $remarks = sanitize($_POST['remarks']);
    
    $scores = $_POST['scores'] ?? [];
    $scoresJson = json_encode($scores);
    
    $stmt = $pdo->prepare("INSERT INTO test_books (teacher_id, class_id, subject_id, test_type, test_date, total_marks, topic, scores_data, remarks) 
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$teacherId, $class_id, $subject_id, $test_type, $test_date, $total_marks, $topic, $scoresJson, $remarks]);
    
    $message = '<div class="success-msg"><i class="fas fa-check-circle"></i> Test recorded successfully!</div>';
    try {
        logActivity($pdo, 'create', "Teacher recorded test: $test_type for class", $_SESSION['teacher_username'] ?? 'teacher', 'test_book', $pdo->lastInsertId());
    } catch(Exception $e) {}
}

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM test_books WHERE id = ? AND teacher_id = ?");
    $stmt->execute([$id, $teacherId]);
    $message = '<div class="success-msg"><i class="fas fa-check-circle"></i> Test record deleted!</div>';
}

$teacherClasses = $pdo->prepare("
    SELECT c.id, c.class_name, c.section, c.level 
    FROM teacher_classes tc 
    JOIN classes c ON tc.class_id = c.id 
    WHERE tc.teacher_id = ?
    ORDER BY c.level, c.class_name
");
$teacherClasses->execute([$teacherId]);
$teacherClasses = $teacherClasses->fetchAll();

$teacherSubjects = $pdo->prepare("
    SELECT DISTINCT s.id, s.subject_name, s.level 
    FROM teacher_subjects ts 
    JOIN subjects s ON ts.subject_id = s.id 
    WHERE ts.teacher_id = ?
    ORDER BY s.subject_name
");
$teacherSubjects->execute([$teacherId]);
$teacherSubjects = $teacherSubjects->fetchAll();

$classId = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;
$subjectId = isset($_GET['subject_id']) ? (int)$_GET['subject_id'] : 0;

$students = [];
if ($classId) {
    $stmt = $pdo->prepare("SELECT * FROM students WHERE class_id = ? ORDER BY name");
    $stmt->execute([$classId]);
    $students = $stmt->fetchAll();
}

$myTests = $pdo->prepare("
    SELECT tb.*, c.class_name, c.section, s.subject_name 
    FROM test_books tb 
    LEFT JOIN classes c ON tb.class_id = c.id 
    LEFT JOIN subjects s ON tb.subject_id = s.id 
    WHERE tb.teacher_id = ?
    ORDER BY tb.test_date DESC
");
$myTests->execute([$teacherId]);
$myTests = $myTests->fetchAll();

$testTypes = [
    'Class Test' => 'Class Test',
    'Quiz' => 'Quiz',
    'Homework' => 'Homework',
    'Pop Quiz' => 'Pop Quiz',
    'Mid-Term Test' => 'Mid-Term Test',
    'End of Week Test' => 'End of Week Test',
    'End of Unit Test' => 'End of Unit Test',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Books - Teacher Portal</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        .ges-header {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            color: white;
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 25px;
        }
        .ges-header h2 {
            margin: 0 0 10px 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .ges-header p {
            margin: 0;
            opacity: 0.9;
        }
        .test-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border-left: 4px solid #11998e;
        }
        .test-card:hover {
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        .test-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }
        .test-title {
            font-size: 18px;
            font-weight: 600;
            color: #11998e;
            margin: 0;
        }
        .test-meta {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            font-size: 13px;
            color: #666;
        }
        .test-meta span {
            display: flex;
            align-items: center;
            gap: 5px;
            background: #f0fff4;
            padding: 5px 12px;
            border-radius: 20px;
        }
        .score-badge {
            display: inline-block;
            background: linear-gradient(135deg, #11998e, #38ef7d);
            color: white;
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: 600;
            margin-top: 10px;
        }
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 15px;
        }
        .form-grid .full-width {
            grid-column: span 3;
        }
        .score-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .score-table th, .score-table td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: left;
        }
        .score-table th {
            background: #f0fff4;
            font-weight: 600;
            color: #11998e;
        }
        .score-table input {
            width: 80px;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 5px;
            text-align: center;
        }
        .score-table tr:nth-child(even) {
            background: #f9fff9;
        }
        .class-filter {
            background: white;
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 20px;
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: flex-end;
        }
        .class-filter .form-group {
            flex: 1;
            min-width: 150px;
            margin: 0;
        }
        @media (max-width: 768px) {
            .form-grid { grid-template-columns: 1fr; }
            .form-grid .full-width { grid-column: span 1; }
            .test-header { flex-direction: column; gap: 10px; }
            .score-table { font-size: 12px; }
            .score-table input { width: 60px; }
        }
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
            <li><a href="results.php"><i class="fas fa-chart-line"></i> View Results</a></li>
            <li><a href="lessons.php"><i class="fas fa-book-open"></i> Lessons</a></li>
            <li><a href="lesson_plans.php"><i class="fas fa-calendar-alt"></i> Lesson Plans</a></li>
            <li><a href="test_books.php" class="active"><i class="fas fa-file-alt"></i> Test Books</a></li>
            <li><a href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
            <li><a href="change_password.php"><i class="fas fa-key"></i> Change Password</a></li>
            <li><a href="../logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </nav>
    
    <main class="main-content">
        <div class="header">
            <h1><i class="fas fa-file-alt"></i> GES Test Books</h1>
        </div>
        
        <div class="content">
            <?php echo $message; ?>
            
            <div class="ges-header">
                <h2><i class="fas fa-clipboard-list"></i> Ghana Education Service (GES) Test Book</h2>
                <p>Record class tests, quizzes, and assessments. Track student performance over time.</p>
            </div>
            
            <div class="table-container" style="margin-bottom: 25px;">
                <div class="table-header">
                    <h3><i class="fas fa-plus-circle"></i> Record New Test</h3>
                </div>
                <form method="POST" style="padding: 20px;" id="testForm">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Class *</label>
                            <select name="class_id" id="classSelect" required onchange="loadStudents()">
                                <option value="">Select Class</option>
                                <?php foreach ($teacherClasses as $c): ?>
                                <option value="<?php echo $c['id']; ?>" <?php echo $classId == $c['id'] ? 'selected' : ''; ?>><?php echo $c['class_name'] . ' - Section ' . $c['section']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Subject *</label>
                            <select name="subject_id" id="subjectSelect" required>
                                <option value="">Select Subject</option>
                                <?php foreach ($teacherSubjects as $s): ?>
                                <option value="<?php echo $s['id']; ?>" <?php echo $subjectId == $s['id'] ? 'selected' : ''; ?>><?php echo $s['subject_name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Test Type *</label>
                            <select name="test_type" required>
                                <option value="">Select Type</option>
                                <?php foreach ($testTypes as $type): ?>
                                <option value="<?php echo $type; ?>"><?php echo $type; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Test Date *</label>
                            <input type="date" name="test_date" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Total Marks *</label>
                            <input type="number" name="total_marks" value="20" min="1" required>
                        </div>
                        <div class="form-group">
                            <label>Topic/Content</label>
                            <input type="text" name="topic" placeholder="e.g., Fractions - Week 3">
                        </div>
                        <div class="form-group full-width">
                            <label>Remarks/Notes</label>
                            <textarea name="remarks" rows="2" placeholder="Any observations or notes about the test"></textarea>
                        </div>
                    </div>
                    
                    <div id="studentsSection" style="<?php echo empty($students) ? 'display:none;' : ''; ?>">
                        <h4 style="margin: 20px 0 15px; color: #11998e;"><i class="fas fa-list"></i> Enter Scores</h4>
                        <?php if (!empty($students)): ?>
                        <table class="score-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Student Name</th>
                                    <th>Score (/<?php echo isset($_POST['total_marks']) ? $_POST['total_marks'] : 20; ?>)</th>
                                    <th>Remark</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($students as $i => $student): ?>
                                <tr>
                                    <td><?php echo $i + 1; ?></td>
                                    <td><?php echo htmlspecialchars($student['name']); ?></td>
                                    <td>
                                        <input type="number" name="scores[<?php echo $student['id']; ?>][score]" min="0" placeholder="Score">
                                    </td>
                                    <td>
                                        <input type="text" name="scores[<?php echo $student['id']; ?>][remark]" placeholder="Optional remark">
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <button type="submit" name="save_test" class="btn btn-success" style="width: 100%; margin-top: 20px; padding: 15px;">
                            <i class="fas fa-save"></i> Save Test Results
                        </button>
                        <?php else: ?>
                        <p style="color: #999; text-align: center;">Select a class to see students</p>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
            
            <div class="table-container">
                <div class="table-header">
                    <h3>My Test Records (<?php echo count($myTests); ?>)</h3>
                </div>
                
                <?php if (empty($myTests)): ?>
                <div style="text-align: center; padding: 40px; color: #999;">
                    <i class="fas fa-file-alt" style="font-size: 50px; margin-bottom: 15px;"></i>
                    <p>No test records yet. Use the form above to record your first test.</p>
                </div>
                <?php else: ?>
                <?php foreach ($myTests as $test): ?>
                <?php 
                    $scoresData = json_decode($test['scores_data'] ?? '{}', true);
                    $scoresCount = is_array($scoresData) ? count($scoresData) : 0;
                ?>
                <div class="test-card">
                    <div class="test-header">
                        <div>
                            <h3 class="test-title"><?php echo htmlspecialchars($test['test_type']); ?></h3>
                            <div class="test-meta">
                                <span><i class="fas fa-book"></i> <?php echo $test['subject_name'] ?? 'N/A'; ?></span>
                                <span><i class="fas fa-school"></i> <?php echo ($test['class_name'] ?? 'N/A') . ' - Section ' . ($test['section'] ?? ''); ?></span>
                                <span><i class="fas fa-calendar"></i> <?php echo date('d M Y', strtotime($test['test_date'])); ?></span>
                                <span><i class="fas fa-star"></i> Out of: <?php echo $test['total_marks']; ?></span>
                            </div>
                            <?php if ($test['topic']): ?>
                            <p style="margin: 10px 0 0; color: #666; font-size: 13px;"><strong>Topic:</strong> <?php echo htmlspecialchars($test['topic']); ?></p>
                            <?php endif; ?>
                            <span class="score-badge">
                                <i class="fas fa-users"></i> <?php echo $scoresCount; ?> students recorded
                            </span>
                        </div>
                        <div>
                            <a href="?delete=<?php echo $test['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this test record?')">
                                <i class="fas fa-trash"></i> Delete
                            </a>
                        </div>
                    </div>
                    <?php if ($test['remarks']): ?>
                    <p style="margin: 0; color: #666; font-size: 13px; font-style: italic;"><?php echo htmlspecialchars($test['remarks']); ?></p>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>
    
    <script src="../assets/js/script.js"></script>
    <script>
        function loadStudents() {
            const classId = document.getElementById('classSelect').value;
            const studentsSection = document.getElementById('studentsSection');
            
            if (classId) {
                studentsSection.style.display = 'block';
                studentsSection.innerHTML = '<p style="text-align: center; padding: 40px;"><i class="fas fa-spinner fa-spin" style="font-size: 30px; color: #11998e;"></i><br>Loading students...</p>';
                
                fetch('load_students.php?class_id=' + classId)
                    .then(response => response.text())
                    .then(html => {
                        studentsSection.innerHTML = html;
                    })
                    .catch(error => {
                        studentsSection.innerHTML = '<p style="color: red; text-align: center;">Error loading students</p>';
                    });
            } else {
                studentsSection.style.display = 'none';
            }
        }
    </script>
</body>
</html>
