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

if (isset($_POST['add_lesson'])) {
    $title = sanitize($_POST['title']);
    $description = sanitize($_POST['description']);
    $class_id = sanitize($_POST['class_id']);
    $subject_id = sanitize($_POST['subject_id']);
    
    $fileName = '';
    $filePath = '';
    $fileType = '';
    
    if (isset($_FILES['lesson_file']) && $_FILES['lesson_file']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['lesson_file'];
        $fileType = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $fileName = time() . '_' . basename($file['name']);
        $uploadDir = '../assets/uploads/lessons/';
        
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $targetPath = $uploadDir . $fileName;
        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            $filePath = $fileName;
        }
    }
    
    $stmt = $pdo->prepare("INSERT INTO lessons (title, description, class_id, subject_id, file_name, file_type, file_path) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$title, $description, $class_id, $subject_id, $fileName, $fileType, $filePath]);
    $message = '<div class="success-msg">Lesson uploaded successfully!</div>';
}

if (isset($_GET['delete'])) {
    $id = sanitize($_GET['delete']);
    $stmt = $pdo->prepare("SELECT * FROM lessons WHERE id = ?");
    $stmt->execute([$id]);
    $lesson = $stmt->fetch();
    
    if ($lesson && $lesson['file_path']) {
        $filePath = '../assets/uploads/lessons/' . $lesson['file_path'];
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }
    
    $stmt = $pdo->prepare("DELETE FROM lessons WHERE id = ?");
    $stmt->execute([$id]);
    $message = '<div class="success-msg">Lesson deleted successfully!</div>';
}

$lessons = $pdo->query("SELECT l.*, c.class_name, c.section, s.subject_name 
                         FROM lessons l 
                         LEFT JOIN classes c ON l.class_id = c.id 
                         LEFT JOIN subjects s ON l.subject_id = s.id 
                         ORDER BY l.id DESC")->fetchAll();

$classes = $pdo->query("SELECT * FROM classes ORDER BY class_name, section")->fetchAll();
$subjects = $pdo->query("SELECT * FROM subjects ORDER BY subject_name")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lessons & Materials - School Management System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        .lesson-cards {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }
        .lesson-card {
            background: var(--white);
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 20px var(--shadow);
            transition: transform 0.3s;
        }
        .lesson-card:hover {
            transform: translateY(-5px);
        }
        .lesson-header {
            padding: 20px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: var(--white);
        }
        .lesson-header h3 {
            font-size: 18px;
            margin-bottom: 5px;
        }
        .lesson-header span {
            font-size: 12px;
            opacity: 0.9;
        }
        .lesson-body {
            padding: 20px;
        }
        .lesson-body p {
            color: var(--gray);
            font-size: 14px;
            margin-bottom: 15px;
        }
        .lesson-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }
        .lesson-type {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 12px;
            color: var(--gray);
        }
        .lesson-actions {
            display: flex;
            gap: 10px;
        }
        .lesson-actions a {
            padding: 8px 15px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 12px;
            transition: all 0.3s;
        }
        .lesson-actions .download {
            background: var(--primary);
            color: var(--white);
        }
        .lesson-actions .delete {
            background: var(--danger);
            color: var(--white);
        }
        .upload-zone {
            border: 2px dashed var(--primary);
            border-radius: 15px;
            padding: 40px;
            text-align: center;
            margin-bottom: 20px;
            transition: all 0.3s;
        }
        .upload-zone:hover {
            background: rgba(74, 144, 226, 0.05);
        }
        .upload-zone i {
            font-size: 50px;
            color: var(--primary);
            margin-bottom: 15px;
        }
        .upload-zone p {
            color: var(--gray);
            margin-bottom: 15px;
        }
        .filter-bar {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .filter-bar select {
            padding: 10px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            min-width: 150px;
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
            <li><a href="results.php"><i class="fas fa-chart-line"></i> Results</a></li>
            <li><a href="reportcards.php"><i class="fas fa-file-alt"></i> Report Cards</a></li>
            <li><a href="calendar.php"><i class="fas fa-calendar-alt"></i> Calendar</a></li>
            <li><a href="announcements.php"><i class="fas fa-bullhorn"></i> Announcements</a></li>
            <li><a href="fees.php"><i class="fas fa-money-bill"></i> School Fees</a></li>
            <li><a href="chatbot.php"><i class="fas fa-robot"></i> AI Chatbot</a></li>
            <li><a href="chatbot_qa.php"><i class="fas fa-database"></i> Chatbot Q&A</a></li>
            <li><a href="lessons.php" class="active"><i class="fas fa-book-open"></i> Lessons</a></li>
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
            <h1><i class="fas fa-book-open"></i> Lessons & Materials</h1>
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
            
            <div class="table-container">
                <div class="table-header">
                    <h2>Upload New Lesson</h2>
                </div>
                <form method="POST" enctype="multipart/form-data" style="padding: 20px;">
                    <div class="upload-zone">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <p>Drag & drop files here or click to browse</p>
                        <input type="file" name="lesson_file" accept=".pdf,.doc,.docx,.ppt,.pptx,.png,.jpg" style="display: none;" id="fileInput" onchange="updateFileName(this)">
                        <button type="button" class="btn btn-primary" onclick="document.getElementById('fileInput').click()">
                            <i class="fas fa-folder-open"></i> Browse Files
                        </button>
                        <p id="fileName" style="margin-top: 10px; font-size: 12px;"></p>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div class="form-group">
                            <label>Lesson Title</label>
                            <input type="text" name="title" required placeholder="e.g. Introduction to Fractions">
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
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div class="form-group">
                            <label>Subject</label>
                            <select name="subject_id" required>
                                <option value="">Select Subject</option>
                                <?php foreach ($subjects as $s): ?>
                                <option value="<?php echo $s['id']; ?>"><?php echo $s['subject_name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Description (Optional)</label>
                            <input type="text" name="description" placeholder="Brief description of the lesson">
                        </div>
                    </div>
                    <button type="submit" name="add_lesson" class="btn btn-success" style="width: 100%;">
                        <i class="fas fa-upload"></i> Upload Lesson
                    </button>
                </form>
            </div>
            
            <div class="filter-bar">
                <select onchange="filterLessons(this.value)">
                    <option value="">All Classes</option>
                    <?php foreach ($classes as $c): ?>
                    <option value="<?php echo $c['id']; ?>"><?php echo $c['class_name'] . ' - ' . $c['section']; ?></option>
                    <?php endforeach; ?>
                </select>
                <select onchange="filterBySubject(this.value)">
                    <option value="">All Subjects</option>
                    <?php foreach ($subjects as $s): ?>
                    <option value="<?php echo $s['id']; ?>"><?php echo $s['subject_name']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <h2 style="margin: 30px 0 20px;">All Lessons</h2>
            
            <?php if (empty($lessons)): ?>
            <div class="table-container" style="text-align: center; padding: 40px;">
                <i class="fas fa-book-open" style="font-size: 50px; color: #ddd; margin-bottom: 15px;"></i>
                <p style="color: var(--gray);">No lessons uploaded yet. Upload your first lesson above.</p>
            </div>
            <?php else: ?>
            <div class="lesson-cards">
                <?php foreach ($lessons as $lesson): ?>
                <div class="lesson-card">
                    <div class="lesson-header">
                        <h3><?php echo $lesson['title']; ?></h3>
                        <span><?php echo $lesson['class_name'] . ' - ' . $lesson['section']; ?></span>
                    </div>
                    <div class="lesson-body">
                        <p><?php echo $lesson['description'] ?: 'No description provided.'; ?></p>
                        <div class="lesson-meta">
                            <div class="lesson-type">
                                <i class="fas fa-<?php 
                                    echo $lesson['file_type'] == 'pdf' ? 'file-pdf' : 
                                        ($lesson['file_type'] == 'ppt' || $lesson['file_type'] == 'pptx' ? 'presentation' : 
                                        ($lesson['file_type'] == 'doc' || $lesson['file_type'] == 'docx' ? 'file-word' : 'file')); 
                                ?>"></i>
                                <?php echo strtoupper($lesson['file_type'] ?: 'No file'); ?>
                            </div>
                            <div class="lesson-actions">
                                <?php if ($lesson['file_path']): ?>
                                <a href="../assets/uploads/lessons/<?php echo $lesson['file_path']; ?>" class="download" download>
                                    <i class="fas fa-download"></i> Download
                                </a>
                                <?php endif; ?>
                                <a href="?delete=<?php echo $lesson['id']; ?>" class="delete delete-btn">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </main>
    
    <script src="../assets/js/script.js"></script>
    <script>
        function updateFileName(input) {
            document.getElementById('fileName').textContent = input.files[0] ? input.files[0].name : '';
        }
        
        function filterLessons(classId) {
            const cards = document.querySelectorAll('.lesson-card');
            cards.forEach(card => {
                if (!classId) {
                    card.style.display = 'block';
                } else {
                    const lessonClass = card.querySelector('.lesson-header span').textContent;
                    if (lessonClass.includes(classId)) {
                        card.style.display = 'block';
                    } else {
                        card.style.display = 'none';
                    }
                }
            });
        }
        
        function filterBySubject(subjectId) {
            // Simple filter implementation
        }
    </script>
</body>
</html>
