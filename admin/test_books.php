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

$message = '';

if (isset($_POST['add_test'])) {
    $class_id = (int)$_POST['class_id'];
    $subject_id = (int)$_POST['subject_id'];
    $test_type = sanitize($_POST['test_type']);
    $test_date = sanitize($_POST['test_date']);
    $total_marks = (int)$_POST['total_marks'];
    $topic = sanitize($_POST['topic']);
    $remarks = sanitize($_POST['remarks']);
    
    $fileName = '';
    $filePath = '';
    
    if (isset($_FILES['test_file']) && $_FILES['test_file']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['test_file'];
        $allowedTypes = ['pdf', 'doc', 'docx', 'ppt', 'pptx'];
        $fileType = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (in_array($fileType, $allowedTypes) && $file['size'] <= 20 * 1024 * 1024) {
            $fileName = time() . '_' . basename($file['name']);
            $uploadDir = '../assets/uploads/test_books/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
            if (move_uploaded_file($file['tmp_name'], $uploadDir . $fileName)) {
                $filePath = $fileName;
            }
        }
    }
    
    $stmt = $pdo->prepare("INSERT INTO test_books (class_id, subject_id, test_type, test_date, total_marks, topic, remarks, file_name, file_path, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$class_id, $subject_id, $test_type, $test_date, $total_marks, $topic, $remarks, $fileName, $filePath, $_SESSION['admin_id']]);
    
    logActivity($pdo, 'create', "Added test book: $test_type", $_SESSION['admin_username'] ?? 'admin', 'test_book', $pdo->lastInsertId());
    $message = '<div class="success-msg"><i class="fas fa-check-circle"></i> Test book added successfully!</div>';
}

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("SELECT file_path FROM test_books WHERE id = ?");
    $stmt->execute([$id]);
    $test = $stmt->fetch();
    if ($test && $test['file_path']) {
        @unlink('../assets/uploads/test_books/' . $test['file_path']);
    }
    $pdo->prepare("DELETE FROM test_books WHERE id = ?")->execute([$id]);
    logActivity($pdo, 'delete', "Deleted test book ID: $id", $_SESSION['admin_username'] ?? 'admin', 'test_book', $id);
    $message = '<div class="success-msg"><i class="fas fa-check-circle"></i> Test book deleted!</div>';
}

$filterSubject = isset($_GET['subject']) ? (int)$_GET['subject'] : 0;

$query = "SELECT tb.*, s.subject_name, c.class_name, c.section 
          FROM test_books tb 
          LEFT JOIN subjects s ON tb.subject_id = s.id 
          LEFT JOIN classes c ON tb.class_id = c.id 
          WHERE 1=1";
$params = [];

if ($filterSubject) {
    $query .= " AND tb.subject_id = ?";
    $params[] = $filterSubject;
}

$query .= " ORDER BY tb.test_date DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$tests = $stmt->fetchAll();

$subjects = $pdo->query("SELECT * FROM subjects ORDER BY subject_name")->fetchAll();
$classes = $pdo->query("SELECT * FROM classes ORDER BY class_name, section")->fetchAll();
$testTypes = ['Class Test', 'Quiz', 'Homework', 'Pop Quiz', 'Mid-Term Test', 'End of Week Test', 'End of Unit Test'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Books - <?php echo $schoolName; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        .form-grid { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px; }
        .form-grid .full-width { grid-column: span 3; }
        .filter-bar { background: white; padding: 15px; border-radius: 10px; margin-bottom: 20px; display: flex; gap: 15px; flex-wrap: wrap; align-items: flex-end; }
        .filter-bar .form-group { margin: 0; flex: 1; min-width: 150px; }
        .ges-header { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); color: white; padding: 25px; border-radius: 15px; margin-bottom: 25px; }
        .ges-header h2 { margin: 0 0 10px; display: flex; align-items: center; gap: 10px; }
        .ges-header p { margin: 0; opacity: 0.9; }
        .test-card { background: white; border-radius: 15px; padding: 20px; margin-bottom: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-left: 4px solid #11998e; }
        .test-card:hover { box-shadow: 0 5px 20px rgba(0,0,0,0.1); }
        .test-meta { display: flex; gap: 15px; flex-wrap: wrap; font-size: 13px; color: #666; margin-top: 10px; }
        .test-meta span { background: #f0fff4; padding: 5px 12px; border-radius: 20px; }
        .file-attachment { background: #e8f5e9; padding: 10px 15px; border-radius: 8px; margin-top: 10px; display: flex; align-items: center; gap: 10px; }
        .file-attachment a { color: #2e7d32; font-weight: 600; text-decoration: none; }
        .file-attachment a:hover { text-decoration: underline; }
        @media (max-width: 768px) {
            .form-grid { grid-template-columns: 1fr; }
            .form-grid .full-width { grid-column: span 1; }
        }
    </style>
</head>
<body>
    <button class="mobile-menu-toggle"><i class="fas fa-bars"></i></button>
    <div class="sidebar-overlay"></div>
    <nav class="sidebar">
        <div class="sidebar-brand"><i class="fas fa-school"></i> <?php echo $schoolName; ?></div>
        <ul class="sidebar-menu">
            <li><a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
            <li><a href="students.php"><i class="fas fa-user-graduate"></i> Students</a></li>
            <li><a href="teachers.php"><i class="fas fa-chalkboard-teacher"></i> Teachers</a></li>
            <li><a href="parents.php"><i class="fas fa-users"></i> Parents</a></li>
            <li><a href="subjects.php"><i class="fas fa-book"></i> Subjects</a></li>
            <li><a href="classes.php"><i class="fas fa-school"></i> Classes</a></li>
            <li><a href="attendance.php"><i class="fas fa-calendar-check"></i> Attendance</a></li>
            <li><a href="timetable.php"><i class="fas fa-table"></i> Timetable</a></li>
            <li><a href="results.php"><i class="fas fa-chart-line"></i> Results</a></li>
            <li><a href="reportcards.php"><i class="fas fa-file-alt"></i> Report Cards</a></li>
            <li><a href="announcements.php"><i class="fas fa-bullhorn"></i> Announcements</a></li>
            <li><a href="fees.php"><i class="fas fa-money-bill"></i> School Fees</a></li>
            <li><a href="lessons.php"><i class="fas fa-book-open"></i> Lessons</a></li>
            <li><a href="lesson_plans.php"><i class="fas fa-calendar-alt"></i> Lesson Plans</a></li>
            <li><a href="test_books.php" class="active"><i class="fas fa-file-alt"></i> Test Books</a></li>
            <li><a href="notes.php"><i class="fas fa-sticky-note"></i> Notes</a></li>
            <li><a href="chatbot.php"><i class="fas fa-robot"></i> Chat Bot</a></li>
            <li><a href="validate.php"><i class="fas fa-check-circle"></i> Validate System</a></li>
            <li><a href="user_access.php"><i class="fas fa-key"></i> User Access</a></li>
            <li><a href="admins.php"><i class="fas fa-user-shield"></i> Manage Admins</a></li>
            <li><a href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
            <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </nav>
    
    <main class="main-content">
        <div class="header"><h1><i class="fas fa-file-alt"></i> Test Books</h1></div>
        
        <div class="content">
            <?php echo $message; ?>
            
            <div class="ges-header">
                <h2><i class="fas fa-clipboard-list"></i> GES Test Books</h2>
                <p>Create and manage test books with file attachments (PDF, Word, PowerPoint).</p>
            </div>
            
            <div class="table-container" style="margin-bottom: 25px;">
                <div class="table-header"><h3><i class="fas fa-plus-circle"></i> Add New Test Book</h3></div>
                <form method="POST" enctype="multipart/form-data" style="padding: 20px;">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Test Type *</label>
                            <select name="test_type" required>
                                <option value="">Select Type</option>
                                <?php foreach ($testTypes as $t): ?>
                                <option value="<?php echo $t; ?>"><?php echo $t; ?></option>
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
                            <label>Subject *</label>
                            <select name="subject_id" required>
                                <option value="">Select Subject</option>
                                <?php foreach ($subjects as $s): ?>
                                <option value="<?php echo $s['id']; ?>"><?php echo $s['subject_name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Class *</label>
                            <select name="class_id" required>
                                <option value="">Select Class</option>
                                <?php foreach ($classes as $c): ?>
                                <option value="<?php echo $c['id']; ?>"><?php echo $c['class_name'] . ' - Section ' . $c['section']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group full-width">
                            <label>Topic</label>
                            <input type="text" name="topic" placeholder="e.g., Fractions - Week 3">
                        </div>
                        <div class="form-group" style="grid-column: span 2;">
                            <label>Remarks</label>
                            <textarea name="remarks" rows="2" placeholder="Any observations or notes..."></textarea>
                        </div>
                        <div class="form-group full-width">
                            <label>Upload Test File (Optional)</label>
                            <input type="file" name="test_file" accept=".pdf,.doc,.docx,.ppt,.pptx">
                            <small style="color: #888;">PDF, Word, or PowerPoint (Max 20MB)</small>
                        </div>
                    </div>
                    <button type="submit" name="add_test" class="btn btn-success" style="margin-top: 15px;">
                        <i class="fas fa-save"></i> Save Test Book
                    </button>
                </form>
            </div>
            
            <div class="filter-bar">
                <div class="form-group">
                    <label>Filter by Subject</label>
                    <select onchange="window.location.href='?subject='+this.value">
                        <option value="">All Subjects</option>
                        <?php foreach ($subjects as $s): ?>
                        <option value="<?php echo $s['id']; ?>" <?php echo $filterSubject == $s['id'] ? 'selected' : ''; ?>><?php echo $s['subject_name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="table-container">
                <div class="table-header"><h3>All Test Books (<?php echo count($tests); ?>)</h3></div>
                
                <?php if (empty($tests)): ?>
                <div style="text-align: center; padding: 40px; color: #999;">
                    <i class="fas fa-file-alt" style="font-size: 50px; margin-bottom: 15px;"></i>
                    <p>No test books found.</p>
                </div>
                <?php else: ?>
                <?php foreach ($tests as $t): ?>
                <div class="test-card">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                        <div>
                            <h3 style="margin: 0 0 10px; color: #11998e;"><?php echo htmlspecialchars($t['test_type']); ?></h3>
                            <div class="test-meta">
                                <span><i class="fas fa-book"></i> <?php echo $t['subject_name'] ?? 'N/A'; ?></span>
                                <span><i class="fas fa-school"></i> <?php echo ($t['class_name'] ?? 'N/A') . ' - ' . ($t['section'] ?? ''); ?></span>
                                <span><i class="fas fa-calendar"></i> <?php echo date('d M Y', strtotime($t['test_date'])); ?></span>
                                <span><i class="fas fa-star"></i> Out of: <?php echo $t['total_marks']; ?></span>
                            </div>
                            <?php if ($t['topic']): ?>
                            <p style="margin: 10px 0 0; color: #666;"><strong>Topic:</strong> <?php echo htmlspecialchars($t['topic']); ?></p>
                            <?php endif; ?>
                            <?php if ($t['remarks']): ?>
                            <p style="margin: 5px 0 0; color: #888; font-style: italic;"><strong>Remarks:</strong> <?php echo htmlspecialchars($t['remarks']); ?></p>
                            <?php endif; ?>
                            <?php if ($t['file_path']): ?>
                            <div class="file-attachment">
                                <i class="fas fa-paperclip"></i>
                                <a href="../assets/uploads/test_books/<?php echo $t['file_path']; ?>" target="_blank">
                                    <i class="fas fa-download"></i> Download Test File
                                </a>
                            </div>
                            <?php endif; ?>
                        </div>
                        <a href="?delete=<?php echo $t['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this test book?')">
                            <i class="fas fa-trash"></i>
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>
    <script src="../assets/js/script.js"></script>
</body>
</html>
