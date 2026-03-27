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

if (isset($_POST['add_plan'])) {
    $class_id = (int)$_POST['class_id'];
    $subject_id = (int)$_POST['subject_id'];
    $term = sanitize($_POST['term']);
    $week = sanitize($_POST['week']);
    $title = sanitize($_POST['title']);
    $topic = sanitize($_POST['topic']);
    $objectives = sanitize($_POST['objectives']);
    $activities = sanitize($_POST['activities']);
    $materials = sanitize($_POST['materials']);
    $duration = sanitize($_POST['duration']);
    $evaluation = sanitize($_POST['evaluation']);
    
    $fileName = '';
    $filePath = '';
    
    if (isset($_FILES['plan_file']) && $_FILES['plan_file']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['plan_file'];
        $allowedTypes = ['pdf', 'doc', 'docx', 'ppt', 'pptx'];
        $fileType = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (in_array($fileType, $allowedTypes) && $file['size'] <= 20 * 1024 * 1024) {
            $fileName = time() . '_' . basename($file['name']);
            $uploadDir = '../assets/uploads/lesson_plans/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
            if (move_uploaded_file($file['tmp_name'], $uploadDir . $fileName)) {
                $filePath = $fileName;
            }
        }
    }
    
    $stmt = $pdo->prepare("INSERT INTO lesson_plans (class_id, subject_id, term, week, title, topic, objectives, activities, materials, duration, evaluation, file_name, file_path, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$class_id, $subject_id, $term, $week, $title, $topic, $objectives, $activities, $materials, $duration, $evaluation, $fileName, $filePath, $_SESSION['admin_id']]);
    
    logActivity($pdo, 'create', "Added lesson plan: $title", $_SESSION['admin_username'] ?? 'admin', 'lesson_plan', $pdo->lastInsertId());
    $message = '<div class="success-msg"><i class="fas fa-check-circle"></i> Lesson plan added successfully!</div>';
}

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("SELECT file_path FROM lesson_plans WHERE id = ?");
    $stmt->execute([$id]);
    $plan = $stmt->fetch();
    if ($plan && $plan['file_path']) {
        @unlink('../assets/uploads/lesson_plans/' . $plan['file_path']);
    }
    $pdo->prepare("DELETE FROM lesson_plans WHERE id = ?")->execute([$id]);
    logActivity($pdo, 'delete', "Deleted lesson plan ID: $id", $_SESSION['admin_username'] ?? 'admin', 'lesson_plan', $id);
    $message = '<div class="success-msg"><i class="fas fa-check-circle"></i> Lesson plan deleted!</div>';
}

$filterSubject = isset($_GET['subject']) ? (int)$_GET['subject'] : 0;
$filterTerm = isset($_GET['term']) ? sanitize($_GET['term']) : '';

$query = "SELECT lp.*, s.subject_name, c.class_name, c.section 
          FROM lesson_plans lp 
          LEFT JOIN subjects s ON lp.subject_id = s.id 
          LEFT JOIN classes c ON lp.class_id = c.id 
          WHERE 1=1";
$params = [];

if ($filterSubject) {
    $query .= " AND lp.subject_id = ?";
    $params[] = $filterSubject;
}
if ($filterTerm) {
    $query .= " AND lp.term = ?";
    $params[] = $filterTerm;
}

$query .= " ORDER BY lp.id DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$plans = $stmt->fetchAll();

$subjects = $pdo->query("SELECT * FROM subjects ORDER BY subject_name")->fetchAll();
$classes = $pdo->query("SELECT * FROM classes ORDER BY class_name, section")->fetchAll();
$terms = ['Term 1', 'Term 2', 'Term 3'];
$weeks = ['Week 1', 'Week 2', 'Week 3', 'Week 4', 'Week 5', 'Week 6', 'Week 7', 'Week 8', 'Week 9', 'Week 10', 'Week 11', 'Week 12', 'Week 13'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lesson Plans - <?php echo $schoolName; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        .form-grid { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px; }
        .form-grid .full-width { grid-column: span 3; }
        .filter-bar { background: white; padding: 15px; border-radius: 10px; margin-bottom: 20px; display: flex; gap: 15px; flex-wrap: wrap; align-items: flex-end; }
        .filter-bar .form-group { margin: 0; flex: 1; min-width: 150px; }
        .ges-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 25px; border-radius: 15px; margin-bottom: 25px; }
        .ges-header h2 { margin: 0 0 10px; display: flex; align-items: center; gap: 10px; }
        .ges-header p { margin: 0; opacity: 0.9; }
        .plan-card { background: white; border-radius: 15px; padding: 20px; margin-bottom: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-left: 4px solid #667eea; }
        .plan-card:hover { box-shadow: 0 5px 20px rgba(0,0,0,0.1); }
        .plan-meta { display: flex; gap: 15px; flex-wrap: wrap; font-size: 13px; color: #666; margin-top: 10px; }
        .plan-meta span { background: #f0f4ff; padding: 5px 12px; border-radius: 20px; }
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
            <li><a href="lesson_plans.php" class="active"><i class="fas fa-calendar-alt"></i> Lesson Plans</a></li>
            <li><a href="test_books.php"><i class="fas fa-file-alt"></i> Test Books</a></li>
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
        <div class="header"><h1><i class="fas fa-calendar-alt"></i> Lesson Plans</h1></div>
        
        <div class="content">
            <?php echo $message; ?>
            
            <div class="ges-header">
                <h2><i class="fas fa-clipboard-list"></i> GES Lesson Plans</h2>
                <p>Create and manage lesson plans with file attachments (PDF, Word, PowerPoint).</p>
            </div>
            
            <div class="table-container" style="margin-bottom: 25px;">
                <div class="table-header"><h3><i class="fas fa-plus-circle"></i> Add New Lesson Plan</h3></div>
                <form method="POST" enctype="multipart/form-data" style="padding: 20px;">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Title *</label>
                            <input type="text" name="title" required placeholder="e.g., Introduction to Fractions">
                        </div>
                        <div class="form-group">
                            <label>Week *</label>
                            <select name="week" required>
                                <option value="">Select Week</option>
                                <?php foreach ($weeks as $w): ?>
                                <option value="<?php echo $w; ?>"><?php echo $w; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Term *</label>
                            <select name="term" required>
                                <option value="">Select Term</option>
                                <?php foreach ($terms as $t): ?>
                                <option value="<?php echo $t; ?>"><?php echo $t; ?></option>
                                <?php endforeach; ?>
                            </select>
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
                        <div class="form-group">
                            <label>Duration (min)</label>
                            <input type="text" name="duration" value="40" placeholder="e.g., 40">
                        </div>
                        <div class="form-group full-width">
                            <label>Topic *</label>
                            <input type="text" name="topic" required placeholder="Main topic for this lesson">
                        </div>
                        <div class="form-group" style="grid-column: span 2;">
                            <label>Objectives</label>
                            <textarea name="objectives" rows="2" placeholder="What students will learn..."></textarea>
                        </div>
                        <div class="form-group" style="grid-column: span 2;">
                            <label>Activities</label>
                            <textarea name="activities" rows="2" placeholder="Teaching activities..."></textarea>
                        </div>
                        <div class="form-group">
                            <label>Materials</label>
                            <input type="text" name="materials" placeholder="e.g., Charts, Books">
                        </div>
                        <div class="form-group">
                            <label>Evaluation</label>
                            <input type="text" name="evaluation" placeholder="How to assess...">
                        </div>
                        <div class="form-group full-width">
                            <label>Upload File (Optional)</label>
                            <input type="file" name="plan_file" accept=".pdf,.doc,.docx,.ppt,.pptx">
                            <small style="color: #888;">PDF, Word, or PowerPoint (Max 20MB)</small>
                        </div>
                    </div>
                    <button type="submit" name="add_plan" class="btn btn-primary" style="margin-top: 15px;">
                        <i class="fas fa-save"></i> Save Lesson Plan
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
                <div class="form-group">
                    <label>Filter by Term</label>
                    <select onchange="window.location.href='?term='+this.value">
                        <option value="">All Terms</option>
                        <?php foreach ($terms as $t): ?>
                        <option value="<?php echo $t; ?>" <?php echo $filterTerm == $t ? 'selected' : ''; ?>><?php echo $t; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="table-container">
                <div class="table-header"><h3>All Lesson Plans (<?php echo count($plans); ?>)</h3></div>
                
                <?php if (empty($plans)): ?>
                <div style="text-align: center; padding: 40px; color: #999;">
                    <i class="fas fa-calendar-alt" style="font-size: 50px; margin-bottom: 15px;"></i>
                    <p>No lesson plans found.</p>
                </div>
                <?php else: ?>
                <?php foreach ($plans as $p): ?>
                <div class="plan-card">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                        <div>
                            <h3 style="margin: 0 0 10px; color: #667eea;"><?php echo htmlspecialchars($p['title']); ?></h3>
                            <div class="plan-meta">
                                <span><i class="fas fa-book"></i> <?php echo $p['subject_name'] ?? 'N/A'; ?></span>
                                <span><i class="fas fa-school"></i> <?php echo ($p['class_name'] ?? 'N/A') . ' - ' . ($p['section'] ?? ''); ?></span>
                                <span><i class="fas fa-calendar"></i> <?php echo $p['week']; ?></span>
                                <span><i class="fas fa-clock"></i> <?php echo $p['term']; ?></span>
                            </div>
                            <?php if ($p['topic']): ?>
                            <p style="margin: 10px 0 0; color: #666;"><strong>Topic:</strong> <?php echo htmlspecialchars($p['topic']); ?></p>
                            <?php endif; ?>
                            <?php if ($p['file_path']): ?>
                            <div class="file-attachment">
                                <i class="fas fa-paperclip"></i>
                                <a href="../assets/uploads/lesson_plans/<?php echo $p['file_path']; ?>" target="_blank">
                                    <i class="fas fa-download"></i> Download Attachment
                                </a>
                            </div>
                            <?php endif; ?>
                        </div>
                        <a href="?delete=<?php echo $p['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this lesson plan?')">
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
