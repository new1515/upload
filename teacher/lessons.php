<?php 
require_once '../config/database.php'; 

if (!isset($_SESSION['teacher_id'])) { 
    redirect('../login.php'); 
}

$teacherId = $_SESSION['teacher_id'];
$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['upload_lesson'])) {
    $title = sanitize($_POST['title']);
    $description = sanitize($_POST['description']);
    $class_id = (int)$_POST['class_id'];
    $subject_id = (int)$_POST['subject_id'];
    
    $fileName = '';
    $filePath = '';
    $fileType = '';
    
    if (isset($_FILES['lesson_file']) && $_FILES['lesson_file']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['lesson_file'];
        $allowedTypes = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png', 'mp4', 'mp3'];
        $fileType = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($fileType, $allowedTypes)) {
            $message = '<div class="error-msg">Invalid file type!</div>';
        } elseif ($file['size'] > 50 * 1024 * 1024) {
            $message = '<div class="error-msg">File too large! Max 50MB.</div>';
        } else {
            $fileName = time() . '_' . basename($file['name']);
            $uploadDir = '../assets/uploads/lessons/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
            $targetPath = $uploadDir . $fileName;
            if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                $filePath = $fileName;
                $stmt = $pdo->prepare("INSERT INTO lessons (title, description, class_id, subject_id, file_name, file_type, file_path, uploaded_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$title, $description, $class_id, $subject_id, $fileName, $fileType, $filePath, $teacherId]);
                $message = '<div class="success-msg"><i class="fas fa-check-circle"></i> Lesson uploaded!</div>';
            }
        }
    } else {
        $stmt = $pdo->prepare("INSERT INTO lessons (title, description, class_id, subject_id, uploaded_by) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$title, $description, $class_id, $subject_id, $teacherId]);
        $message = '<div class="success-msg"><i class="fas fa-check-circle"></i> Lesson added!</div>';
    }
}

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("SELECT * FROM lessons WHERE id = ? AND uploaded_by = ?");
    $stmt->execute([$id, $teacherId]);
    if ($stmt->fetch()) {
        $pdo->prepare("DELETE FROM lessons WHERE id = ? AND uploaded_by = ?")->execute([$id, $teacherId]);
        $message = '<div class="success-msg"><i class="fas fa-check-circle"></i> Lesson deleted!</div>';
    }
}

$teacherClasses = [];
$teacherSubjects = [];

$stmt = $pdo->prepare("SELECT c.* FROM teacher_classes tc JOIN classes c ON tc.class_id = c.id WHERE tc.teacher_id = ? ORDER BY c.level, c.class_name");
$stmt->execute([$teacherId]);
$teacherClasses = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT DISTINCT s.* FROM teacher_subjects ts JOIN subjects s ON ts.subject_id = s.id WHERE ts.teacher_id = ? ORDER BY s.subject_name");
$stmt->execute([$teacherId]);
$teacherSubjects = $stmt->fetchAll();

$viewMode = isset($_GET['view']) ? sanitize($_GET['view']) : 'my_lessons';

$gesPlans = [];
$gesTests = [];
$myLessons = [];

if ($viewMode == 'lesson_plans') {
    $gesPlans = $pdo->query("SELECT lp.*, s.subject_name, c.class_name FROM lesson_plans lp JOIN subjects s ON lp.subject_id = s.id LEFT JOIN classes c ON lp.class_id = c.id ORDER BY lp.id DESC")->fetchAll();
} elseif ($viewMode == 'test_books') {
    $gesTests = $pdo->query("SELECT tb.*, s.subject_name, c.class_name FROM test_books tb JOIN subjects s ON tb.subject_id = s.id LEFT JOIN classes c ON tb.class_id = c.id ORDER BY tb.id DESC")->fetchAll();
} else {
    $myLessons = $pdo->query("SELECT l.*, c.class_name, s.subject_name FROM lessons l LEFT JOIN classes c ON l.class_id = c.id LEFT JOIN subjects s ON l.subject_id = s.id WHERE l.uploaded_by = $teacherId ORDER BY l.id DESC")->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lessons - Teacher Portal</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        .view-tabs { display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap; }
        .view-tab { padding: 12px 20px; border-radius: 10px; text-decoration: none; font-weight: 600; transition: all 0.3s; display: flex; align-items: center; gap: 8px; }
        .view-tab:hover { transform: translateY(-2px); }
        .view-tab.my_lessons { background: #27ae60; color: white; }
        .view-tab.lesson_plans { background: #3498db; color: white; }
        .view-tab.test_books { background: #e74c3c; color: white; }
        .view-tab.active { box-shadow: 0 4px 15px rgba(0,0,0,0.3); }
        .upload-section { background: white; border-radius: 15px; padding: 25px; margin-bottom: 25px; border-left: 4px solid #27ae60; }
        .lesson-card, .plan-card, .test-card { background: white; border-radius: 15px; padding: 20px; margin-bottom: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .plan-card { border-left: 4px solid #3498db; }
        .test-card { border-left: 4px solid #e74c3c; }
        .badge { padding: 5px 15px; border-radius: 20px; font-size: 12px; font-weight: 600; }
        .badge-nursery { background: #ffeaa7; color: #d63031; }
        .badge-kg { background: #fab1a0; color: #e17055; }
        .badge-primary { background: #74b9ff; color: #0984e3; }
        .badge-jhs { background: #55efc4; color: #00b894; }
        .test-section { background: var(--light); padding: 10px 15px; border-radius: 8px; margin-bottom: 10px; }
        .test-section h4 { margin: 0 0 8px 0; }
        .test-section p { margin: 0; font-size: 13px; }
        .ges-notice { background: #fff3cd; padding: 15px; border-radius: 10px; border-left: 4px solid #f39c12; margin-bottom: 20px; }
        .ges-notice h4 { margin: 0 0 10px; }
        .ges-notice p { margin: 0; color: #666; font-size: 13px; }
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
            <li><a href="lessons.php" class="active"><i class="fas fa-book-open"></i> Lessons</a></li>
            <li><a href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
            <li><a href="change_password.php"><i class="fas fa-key"></i> Change Password</a></li>
            <li><a href="../logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </nav>
    
    <main class="main-content">
        <div class="header"><h1><i class="fas fa-book-open"></i> Lessons & GES Resources</h1></div>
        
        <div class="content">
            <?php echo $message; ?>
            
            <div class="view-tabs">
                <a href="?view=my_lessons" class="view-tab my_lessons <?php echo $viewMode == 'my_lessons' ? 'active' : ''; ?>">
                    <i class="fas fa-upload"></i> My Lessons
                </a>
                <a href="?view=lesson_plans" class="view-tab lesson_plans <?php echo $viewMode == 'lesson_plans' ? 'active' : ''; ?>">
                    <i class="fas fa-clipboard-list"></i> GES Lesson Plans
                </a>
                <a href="?view=test_books" class="view-tab test_books <?php echo $viewMode == 'test_books' ? 'active' : ''; ?>">
                    <i class="fas fa-file-alt"></i> GES Test Books
                </a>
            </div>
            
            <?php if ($viewMode == 'my_lessons'): ?>
            
            <div class="upload-section">
                <h3 style="margin-top: 0;"><i class="fas fa-cloud-upload-alt"></i> Upload New Lesson</h3>
                <form method="POST" enctype="multipart/form-data">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div class="form-group">
                            <label>Title *</label>
                            <input type="text" name="title" required placeholder="Lesson title">
                        </div>
                        <div class="form-group">
                            <label>Subject *</label>
                            <select name="subject_id" required>
                                <option value="">Select Subject</option>
                                <?php foreach ($teacherSubjects as $s): ?>
                                <option value="<?php echo $s['id']; ?>"><?php echo $s['subject_name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Class *</label>
                            <select name="class_id" required>
                                <option value="">Select Class</option>
                                <?php foreach ($teacherClasses as $c): ?>
                                <option value="<?php echo $c['id']; ?>"><?php echo $c['class_name'] . ' - Section ' . $c['section']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Description</label>
                            <input type="text" name="description" placeholder="Brief description">
                        </div>
                    </div>
                    <div class="form-group" style="margin-top: 15px;">
                        <label>File (Optional)</label>
                        <input type="file" name="lesson_file" accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.jpg,.jpeg,.png,.mp4,.mp3">
                        <small style="color: #888;">PDF, Word, PPT, Excel, Images, Videos (Max 50MB)</small>
                    </div>
                    <button type="submit" name="upload_lesson" class="btn btn-success" style="margin-top: 15px;">
                        <i class="fas fa-upload"></i> Upload Lesson
                    </button>
                </form>
            </div>
            
            <h3>My Uploaded Lessons (<?php echo count($myLessons); ?>)</h3>
            <?php if (empty($myLessons)): ?>
            <div style="text-align: center; padding: 40px; background: white; border-radius: 15px;">
                <i class="fas fa-book-open" style="font-size: 50px; color: #ddd;"></i>
                <p style="color: #888;">No lessons uploaded yet.</p>
            </div>
            <?php else: foreach ($myLessons as $l): ?>
            <div class="lesson-card" style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h4 style="margin: 0 0 5px;"><?php echo htmlspecialchars($l['title']); ?></h4>
                    <p style="margin: 0; color: #666; font-size: 13px;">
                        <i class="fas fa-book"></i> <?php echo $l['subject_name']; ?> | 
                        <i class="fas fa-school"></i> <?php echo $l['class_name']; ?> | 
                        <i class="fas fa-clock"></i> <?php echo date('d M Y', strtotime($l['created_at'])); ?>
                    </p>
                </div>
                <div style="display: flex; gap: 10px;">
                    <?php if ($l['file_path']): ?>
                    <a href="../assets/uploads/lessons/<?php echo $l['file_path']; ?>" class="btn btn-sm btn-primary" target="_blank"><i class="fas fa-download"></i></a>
                    <?php endif; ?>
                    <a href="?delete=<?php echo $l['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete?')"><i class="fas fa-trash"></i></a>
                </div>
            </div>
            <?php endforeach; endif; ?>
            
            <?php elseif ($viewMode == 'lesson_plans'): ?>
            
            <div class="ges-notice">
                <h4><i class="fas fa-info-circle"></i> GES Lesson Plans</h4>
                <p>These are official GES lesson plans for all subjects and class levels. Use them as a guide for your weekly teaching.</p>
            </div>
            
            <h3>GES Lesson Plans (<?php echo count($gesPlans); ?>)</h3>
            <?php if (empty($gesPlans)): ?>
            <div style="text-align: center; padding: 40px; background: white; border-radius: 15px;">
                <i class="fas fa-clipboard-list" style="font-size: 50px; color: #ddd;"></i>
                <p style="color: #888;">No lesson plans available yet. Admin will add them.</p>
            </div>
            <?php else: foreach ($gesPlans as $p): ?>
            <div class="plan-card">
                <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                    <div>
                        <h4 style="margin: 0 0 10px;"><?php echo $p['week']; ?>: <?php echo htmlspecialchars($p['topic']); ?></h4>
                        <p style="margin: 0 0 10px; color: #666;">
                            <i class="fas fa-book"></i> <?php echo $p['subject_name']; ?> | 
                            <i class="fas fa-calendar"></i> <?php echo $p['term']; ?>
                        </p>
                    </div>
                </div>
                <?php if ($p['sub_topic']): ?>
                <div class="test-section">
                    <h4><i class="fas fa-arrow-right"></i> Sub-Topic</h4>
                    <p><?php echo htmlspecialchars($p['sub_topic']); ?></p>
                </div>
                <?php endif; ?>
                <?php if ($p['objectives']): ?>
                <div class="test-section">
                    <h4><i class="fas fa-bullseye"></i> Objectives</h4>
                    <p><?php echo nl2br(htmlspecialchars($p['objectives'])); ?></p>
                </div>
                <?php endif; ?>
                <?php if ($p['activities'] || $p['materials']): ?>
                <div style="display: flex; gap: 15px; flex-wrap: wrap; margin-top: 10px;">
                    <?php if ($p['activities']): ?>
                    <span style="background: #e8f4fd; padding: 5px 10px; border-radius: 5px; font-size: 12px;">
                        <i class="fas fa-chalkboard-teacher"></i> <?php echo htmlspecialchars($p['activities']); ?>
                    </span>
                    <?php endif; ?>
                    <?php if ($p['materials']): ?>
                    <span style="background: #ffeaa7; padding: 5px 10px; border-radius: 5px; font-size: 12px;">
                        <i class="fas fa-tools"></i> <?php echo htmlspecialchars($p['materials']); ?>
                    </span>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; endif; ?>
            
            <?php elseif ($viewMode == 'test_books'): ?>
            
            <div class="ges-notice">
                <h4><i class="fas fa-info-circle"></i> GES Test Books</h4>
                <p>Access test papers and exam questions for all subjects. These follow GES assessment guidelines.</p>
            </div>
            
            <h3>GES Test Books (<?php echo count($gesTests); ?>)</h3>
            <?php if (empty($gesTests)): ?>
            <div style="text-align: center; padding: 40px; background: white; border-radius: 15px;">
                <i class="fas fa-file-alt" style="font-size: 50px; color: #ddd;"></i>
                <p style="color: #888;">No test books available yet. Admin will add them.</p>
            </div>
            <?php else: foreach ($gesTests as $t): ?>
            <div class="test-card">
                <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                    <div>
                        <h4 style="margin: 0 0 10px;"><?php echo htmlspecialchars($t['subject_name']); ?></h4>
                        <p style="margin: 0 0 10px; color: #666;">
                            <strong style="color: #e74c3c;"><?php echo $t['test_type']; ?></strong> | 
                            <i class="fas fa-calendar"></i> <?php echo date('d M Y', strtotime($t['test_date'])); ?> | 
                            <i class="fas fa-star"></i> <?php echo $t['total_marks']; ?> marks
                        </p>
                        <?php if ($t['topic']): ?>
                        <p style="margin: 0 0 10px; font-size: 13px;"><i class="fas fa-book"></i> <?php echo htmlspecialchars($t['topic']); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                <?php if ($t['remarks']): ?>
                <div class="test-section">
                    <h4><i class="fas fa-comment"></i> Remarks</h4>
                    <p><?php echo htmlspecialchars($t['remarks']); ?></p>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; endif; ?>
            
            <?php endif; ?>
        </div>
    </main>
    <script src="../assets/js/script.js"></script>
</body>
</html>
