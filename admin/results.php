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

$message = '';

if (isset($_POST['add_result'])) {
    $student_id = sanitize($_POST['student_id']);
    $class_id = sanitize($_POST['class_id']);
    $subject_id = sanitize($_POST['subject_id']);
    $term = sanitize($_POST['term']);
    $academic_year = sanitize($_POST['academic_year']);
    $test1 = sanitize($_POST['test1']);
    $test2 = sanitize($_POST['test2']);
    $test3 = sanitize($_POST['test3']);
    $project = sanitize($_POST['project']);
    $class_assessment = sanitize($_POST['class_assessment']);
    $exam = sanitize($_POST['exam']);
    
    $total = (float)$test1 + (float)$test2 + (float)$test3 + (float)$project + (float)$class_assessment + (float)$exam;
    
    if ($total >= 80) { $grade = 'A'; $remarks = 'Excellent'; }
    elseif ($total >= 70) { $grade = 'B'; $remarks = 'Very Good'; }
    elseif ($total >= 60) { $grade = 'C'; $remarks = 'Good'; }
    elseif ($total >= 50) { $grade = 'D'; $remarks = 'Pass'; }
    elseif ($total >= 40) { $grade = 'E'; $remarks = 'Below Pass'; }
    else { $grade = 'F'; $remarks = 'Fail'; }
    
    $stmt = $pdo->prepare("INSERT INTO student_assessments (student_id, class_id, subject_id, term, academic_year, test1, test2, test3, project, class_assessment, exam, grade, remarks) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$student_id, $class_id, $subject_id, $term, $academic_year, $test1, $test2, $test3, $project, $class_assessment, $exam, $grade, $remarks]);
    $message = '<div class="success-msg">Result added successfully!</div>';
}

if (isset($_POST['update_result'])) {
    $id = sanitize($_POST['id']);
    $test1 = sanitize($_POST['test1']);
    $test2 = sanitize($_POST['test2']);
    $test3 = sanitize($_POST['test3']);
    $project = sanitize($_POST['project']);
    $class_assessment = sanitize($_POST['class_assessment']);
    $exam = sanitize($_POST['exam']);
    
    $total = (float)$test1 + (float)$test2 + (float)$test3 + (float)$project + (float)$class_assessment + (float)$exam;
    
    if ($total >= 80) { $grade = 'A'; $remarks = 'Excellent'; }
    elseif ($total >= 70) { $grade = 'B'; $remarks = 'Very Good'; }
    elseif ($total >= 60) { $grade = 'C'; $remarks = 'Good'; }
    elseif ($total >= 50) { $grade = 'D'; $remarks = 'Pass'; }
    elseif ($total >= 40) { $grade = 'E'; $remarks = 'Below Pass'; }
    else { $grade = 'F'; $remarks = 'Fail'; }
    
    $stmt = $pdo->prepare("UPDATE student_assessments SET test1=?, test2=?, test3=?, project=?, class_assessment=?, exam=?, grade=?, remarks=? WHERE id=?");
    $stmt->execute([$test1, $test2, $test3, $project, $class_assessment, $exam, $grade, $remarks, $id]);
    $message = '<div class="success-msg">Result updated successfully!</div>';
}

if (isset($_GET['delete'])) {
    $id = sanitize($_GET['delete']);
    $stmt = $pdo->prepare("DELETE FROM student_assessments WHERE id = ?");
    $stmt->execute([$id]);
    $message = '<div class="success-msg">Result deleted successfully!</div>';
}

$results = $pdo->query("SELECT a.*, s.name as student_name, sub.subject_name, c.class_name, c.section 
                        FROM student_assessments a 
                        LEFT JOIN students s ON a.student_id = s.id 
                        LEFT JOIN subjects sub ON a.subject_id = sub.id 
                        LEFT JOIN classes c ON a.class_id = c.id 
                        ORDER BY a.id DESC")->fetchAll();

$students = $pdo->query("SELECT s.*, c.class_name, c.section FROM students s LEFT JOIN classes c ON s.class_id = c.id ORDER BY c.class_name, s.name")->fetchAll();
$subjects = $pdo->query("SELECT * FROM subjects ORDER BY subject_name")->fetchAll();
$classes = $pdo->query("SELECT * FROM classes ORDER BY class_name, section")->fetchAll();
$terms = ['Term 1', 'Term 2', 'Term 3'];
$years = ['2025-2026', '2024-2025'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Results - School Management System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        .score-info-bar {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }
        .score-info-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .score-info-item strong {
            color: #4a90e2;
        }
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
            <li><a href="classes.php"><i class="fas fa-school"></i> Classes</a></li>
            <li><a href="attendance.php"><i class="fas fa-calendar-check"></i> Attendance</a></li>
            <li><a href="timetable.php"><i class="fas fa-clock"></i> Timetable</a></li>
            <li><a href="results.php" class="active"><i class="fas fa-chart-line"></i> Results</a></li>
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
            <h1><i class="fas fa-chart-line"></i> Manage Results</h1>
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
            
            <div class="score-info-bar">
                <div class="score-info-item">
                    <i class="fas fa-info-circle"></i>
                    <span><strong>Test 1:</strong> 10 marks</span>
                </div>
                <div class="score-info-item">
                    <i class="fas fa-info-circle"></i>
                    <span><strong>Test 2:</strong> 10 marks</span>
                </div>
                <div class="score-info-item">
                    <i class="fas fa-info-circle"></i>
                    <span><strong>Test 3:</strong> 10 marks</span>
                </div>
                <div class="score-info-item">
                    <i class="fas fa-info-circle"></i>
                    <span><strong>Project:</strong> 10 marks</span>
                </div>
                <div class="score-info-item">
                    <i class="fas fa-info-circle"></i>
                    <span><strong>Class Assessment:</strong> 10 marks</span>
                </div>
                <div class="score-info-item">
                    <i class="fas fa-info-circle"></i>
                    <span><strong>Exam:</strong> 50 marks</span>
                </div>
                <div class="score-info-item">
                    <i class="fas fa-trophy"></i>
                    <span><strong>Total:</strong> 100 marks</span>
                </div>
            </div>
            
            <div class="table-container">
                <div class="table-header">
                    <h2>All Results</h2>
                    <button class="btn btn-primary modal-trigger" data-modal="addModal">
                        <i class="fas fa-plus"></i> Add Result
                    </button>
                </div>
                
                <?php if (empty($results)): ?>
                <div style="text-align: center; padding: 40px; color: #999;">
                    <i class="fas fa-chart-line" style="font-size: 50px; margin-bottom: 15px;"></i>
                    <p>No results found. Use the Score Entry feature from Classes page for easier entry.</p>
                </div>
                <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Student</th>
                            <th>Class</th>
                            <th>Subject</th>
                            <th>Term</th>
                            <th>Total</th>
                            <th>Grade</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($results as $result): 
                            $total = (float)($result['test1'] ?? 0) + (float)($result['test2'] ?? 0) + (float)($result['test3'] ?? 0) + 
                                     (float)($result['project'] ?? 0) + (float)($result['class_assessment'] ?? 0) + (float)($result['exam'] ?? 0);
                        ?>
                        <tr>
                            <td><?php echo $result['id']; ?></td>
                            <td><?php echo $result['student_name']; ?></td>
                            <td><?php echo $result['class_name'] . ' - ' . $result['section']; ?></td>
                            <td><?php echo $result['subject_name']; ?></td>
                            <td><?php echo $result['term'] . ' (' . $result['academic_year'] . ')'; ?></td>
                            <td><strong><?php echo number_format($total, 1); ?>/100</strong></td>
                            <td><span class="badge <?php echo strtolower($result['grade'] ?? 'f'); ?>"><?php echo $result['grade'] ?? '-'; ?></span></td>
                            <td class="action-btns">
                                <button class="edit modal-trigger" data-modal="editModal<?php echo $result['id']; ?>">Edit</button>
                                <a href="?delete=<?php echo $result['id']; ?>" class="delete delete-btn">Delete</a>
                            </td>
                        </tr>
                        
                        <div id="editModal<?php echo $result['id']; ?>" class="modal">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h3>Edit Result</h3>
                                    <button class="modal-close">&times;</button>
                                </div>
                                <form method="POST">
                                    <input type="hidden" name="id" value="<?php echo $result['id']; ?>">
                                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                                        <div class="form-group">
                                            <label>Test 1 (/10)</label>
                                            <input type="number" name="test1" value="<?php echo $result['test1']; ?>" min="0" max="10" step="0.5">
                                        </div>
                                        <div class="form-group">
                                            <label>Test 2 (/10)</label>
                                            <input type="number" name="test2" value="<?php echo $result['test2']; ?>" min="0" max="10" step="0.5">
                                        </div>
                                        <div class="form-group">
                                            <label>Test 3 (/10)</label>
                                            <input type="number" name="test3" value="<?php echo $result['test3']; ?>" min="0" max="10" step="0.5">
                                        </div>
                                        <div class="form-group">
                                            <label>Project (/10)</label>
                                            <input type="number" name="project" value="<?php echo $result['project']; ?>" min="0" max="10" step="0.5">
                                        </div>
                                        <div class="form-group">
                                            <label>Class Assessment (/10)</label>
                                            <input type="number" name="class_assessment" value="<?php echo $result['class_assessment']; ?>" min="0" max="10" step="0.5">
                                        </div>
                                        <div class="form-group">
                                            <label>Exam (/50)</label>
                                            <input type="number" name="exam" value="<?php echo $result['exam']; ?>" min="0" max="50" step="0.5">
                                        </div>
                                    </div>
                                    <button type="submit" name="update_result" class="btn btn-primary" style="width: 100%; margin-top: 10px;">Update Result</button>
                                </form>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>
    </main>
    
    <div id="addModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Add New Result</h3>
                <button class="modal-close">&times;</button>
            </div>
            <form method="POST">
                <div class="form-group">
                    <label>Student</label>
                    <select name="student_id" required>
                        <option value="">Select Student</option>
                        <?php foreach ($students as $stu): ?>
                        <option value="<?php echo $stu['id']; ?>"><?php echo $stu['name'] . ' (' . $stu['class_name'] . ')'; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Class</label>
                    <select name="class_id" required>
                        <option value="">Select Class</option>
                        <?php foreach ($classes as $c): ?>
                        <option value="<?php echo $c['id']; ?>"><?php echo $c['class_name'] . ' - ' . $c['section']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Subject</label>
                    <select name="subject_id" required>
                        <option value="">Select Subject</option>
                        <?php foreach ($subjects as $sub): ?>
                        <option value="<?php echo $sub['id']; ?>"><?php echo $sub['subject_name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Term</label>
                    <select name="term" required>
                        <?php foreach ($terms as $t): ?>
                        <option value="<?php echo $t; ?>"><?php echo $t; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Academic Year</label>
                    <select name="academic_year" required>
                        <?php foreach ($years as $y): ?>
                        <option value="<?php echo $y; ?>"><?php echo $y; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                    <div class="form-group">
                        <label>Test 1 (/10)</label>
                        <input type="number" name="test1" value="0" min="0" max="10" step="0.5" required>
                    </div>
                    <div class="form-group">
                        <label>Test 2 (/10)</label>
                        <input type="number" name="test2" value="0" min="0" max="10" step="0.5" required>
                    </div>
                    <div class="form-group">
                        <label>Test 3 (/10)</label>
                        <input type="number" name="test3" value="0" min="0" max="10" step="0.5" required>
                    </div>
                    <div class="form-group">
                        <label>Project (/10)</label>
                        <input type="number" name="project" value="0" min="0" max="10" step="0.5" required>
                    </div>
                    <div class="form-group">
                        <label>Class Assessment (/10)</label>
                        <input type="number" name="class_assessment" value="0" min="0" max="10" step="0.5" required>
                    </div>
                    <div class="form-group">
                        <label>Exam (/50)</label>
                        <input type="number" name="exam" value="0" min="0" max="50" step="0.5" required>
                    </div>
                </div>
                <button type="submit" name="add_result" class="btn btn-primary" style="width: 100%; margin-top: 10px;">Add Result</button>
            </form>
        </div>
    </div>
    
    <style>
        .badge.a, .badge.a\+ { background: rgba(39, 174, 96, 0.2); color: #27ae60; }
        .badge.b { background: rgba(46, 204, 113, 0.2); color: #2ecc71; }
        .badge.c { background: rgba(243, 156, 18, 0.2); color: #f39c12; }
        .badge.d { background: rgba(230, 126, 34, 0.2); color: #e67e22; }
        .badge.e { background: rgba(231, 76, 60, 0.2); color: #e74c3c; }
        .badge.f { background: rgba(192, 57, 43, 0.2); color: #c0392b; }
    </style>
    
    <script src="../assets/js/script.js"></script>
</body>
</html>
