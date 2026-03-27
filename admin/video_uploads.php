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

if (isset($_POST['upload_video'])) {
    $title = sanitize($_POST['title']);
    $description = sanitize($_POST['description']);
    $class_id = sanitize($_POST['class_id']);
    $subject_id = sanitize($_POST['subject_id']);
    $teacher_id = !empty($_POST['teacher_id']) ? sanitize($_POST['teacher_id']) : null;
    
    $fileName = '';
    $filePath = '';
    
    if (isset($_FILES['video_file']) && $_FILES['video_file']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['video_file'];
        $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowedExts = ['mp4', 'webm', 'ogg', 'mov', 'avi'];
        
        if (in_array($fileExt, $allowedExts)) {
            $fileName = time() . '_' . basename($file['name']);
            $uploadDir = '../assets/uploads/videos/';
            
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            $targetPath = $uploadDir . $fileName;
            if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                $filePath = $fileName;
            }
        } else {
            $message = '<div class="error-msg">Invalid video format. Allowed: MP4, WebM, OGG, MOV, AVI</div>';
        }
    } else {
        $message = '<div class="error-msg">Please select a video file</div>';
    }
    
    if ($filePath) {
        $stmt = $pdo->prepare("INSERT INTO videos (title, description, file_name, file_path, class_id, subject_id, teacher_id, uploaded_by, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'approved')");
        $stmt->execute([$title, $description, $fileName, $filePath, $class_id, $subject_id, $teacher_id, $_SESSION['admin_id']]);
        $message = '<div class="success-msg">Video uploaded successfully!</div>';
    }
}

if (isset($_GET['delete'])) {
    $id = sanitize($_GET['delete']);
    $stmt = $pdo->prepare("SELECT * FROM videos WHERE id = ?");
    $stmt->execute([$id]);
    $video = $stmt->fetch();
    
    if ($video && $video['file_path']) {
        $filePath = '../assets/uploads/videos/' . $video['file_path'];
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }
    
    $stmt = $pdo->prepare("DELETE FROM videos WHERE id = ?");
    $stmt->execute([$id]);
    $message = '<div class="success-msg">Video deleted successfully!</div>';
}

$videos = $pdo->query("SELECT v.*, c.class_name, c.section, s.subject_name, t.name as teacher_name 
                      FROM videos v 
                      LEFT JOIN classes c ON v.class_id = c.id 
                      LEFT JOIN subjects s ON v.subject_id = s.id
                      LEFT JOIN teachers t ON v.teacher_id = t.id
                      ORDER BY v.id DESC")->fetchAll();

$classes = $pdo->query("SELECT * FROM classes ORDER BY class_name, section")->fetchAll();
$subjects = $pdo->query("SELECT * FROM subjects ORDER BY subject_name")->fetchAll();
$teachers = $pdo->query("SELECT * FROM teachers WHERE status = 'active' ORDER BY name")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Video Uploads - School Management System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        .video-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 20px;
        }
        .video-card {
            background: var(--white);
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 20px var(--shadow);
            transition: transform 0.3s;
        }
        .video-card:hover {
            transform: translateY(-5px);
        }
        .video-thumbnail {
            position: relative;
            height: 180px;
            background: #000;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .video-thumbnail video {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .video-thumbnail .play-icon {
            position: absolute;
            font-size: 50px;
            color: white;
            opacity: 0.8;
        }
        .video-info {
            padding: 20px;
        }
        .video-info h3 {
            font-size: 16px;
            margin-bottom: 5px;
            color: var(--dark);
        }
        .video-info .meta {
            font-size: 12px;
            color: var(--gray);
            margin-bottom: 10px;
        }
        .video-info p {
            font-size: 13px;
            color: var(--gray);
            margin-bottom: 15px;
        }
        .video-actions {
            display: flex;
            gap: 10px;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }
        .video-actions a {
            flex: 1;
            padding: 8px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 12px;
            text-align: center;
            transition: all 0.3s;
        }
        .video-actions .play {
            background: var(--primary);
            color: var(--white);
        }
        .video-actions .delete {
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
            <li><a href="attendance.php"><i class="fas fa-calendar-check"></i> Attendance</a></li>
            <li><a href="daily_attendance.php"><i class="fas fa-clipboard-list"></i> Daily Register</a></li>
            <li><a href="timetable.php"><i class="fas fa-clock"></i> Timetable</a></li>
            <li><a href="results.php"><i class="fas fa-chart-line"></i> Results</a></li>
            <li><a href="reportcards.php"><i class="fas fa-file-alt"></i> Report Cards</a></li>
            <li><a href="calendar.php"><i class="fas fa-calendar-alt"></i> Calendar</a></li>
            <li><a href="announcements.php"><i class="fas fa-bullhorn"></i> Announcements</a></li>
            <li><a href="feedback.php"><i class="fas fa-comment-dots"></i> Feedback</a></li>
            <li><a href="video_conference.php"><i class="fas fa-video"></i> Video Conference</a></li>
            <li><a href="fees.php"><i class="fas fa-money-bill"></i> School Fees</a></li>
            <li><a href="chatbot.php"><i class="fas fa-robot"></i> AI Chatbot</a></li>
            <li><a href="chatbot_qa.php"><i class="fas fa-database"></i> Chatbot Q&A</a></li>
            <li><a href="lessons.php"><i class="fas fa-book-open"></i> Lessons</a></li>
            <li><a href="video_uploads.php" class="active"><i class="fas fa-video"></i> Video Uploads</a></li>
            <li><a href="lesson_plans.php"><i class="fas fa-clipboard-list"></i> Lesson Plans</a></li>
            <li><a href="test_books.php"><i class="fas fa-file-alt"></i> Test Books</a></li>
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
            <h1><i class="fas fa-video"></i> Video Uploads</h1>
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
                    <h2>Upload New Video</h2>
                </div>
                <form method="POST" enctype="multipart/form-data" style="padding: 20px;">
                    <div class="upload-zone">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <p>Drag & drop video here or click to browse</p>
                        <input type="file" name="video_file" accept="video/mp4,video/webm,video/ogg,video/quicktime" style="display: none;" id="videoInput" onchange="updateFileName(this)">
                        <button type="button" class="btn btn-primary" onclick="document.getElementById('videoInput').click()">
                            <i class="fas fa-folder-open"></i> Browse Videos
                        </button>
                        <p id="fileName" style="margin-top: 10px; font-size: 12px;"></p>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div class="form-group">
                            <label>Video Title</label>
                            <input type="text" name="title" required placeholder="e.g. Introduction to Algebra">
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
                            <label>Teacher (Optional)</label>
                            <select name="teacher_id">
                                <option value="">Select Teacher</option>
                                <?php foreach ($teachers as $t): ?>
                                <option value="<?php echo $t['id']; ?>"><?php echo $t['name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Description (Optional)</label>
                        <input type="text" name="description" placeholder="Brief description of the video">
                    </div>
                    <button type="submit" name="upload_video" class="btn btn-success" style="width: 100%;">
                        <i class="fas fa-upload"></i> Upload Video
                    </button>
                </form>
            </div>
            
            <h2 style="margin: 30px 0 20px;">All Videos</h2>
            
            <?php if (empty($videos)): ?>
            <div class="table-container" style="text-align: center; padding: 40px;">
                <i class="fas fa-video" style="font-size: 50px; color: #ddd; margin-bottom: 15px;"></i>
                <p style="color: var(--gray);">No videos uploaded yet. Upload your first video above.</p>
            </div>
            <?php else: ?>
            <div class="video-grid">
                <?php foreach ($videos as $video): ?>
                <div class="video-card">
                    <div class="video-thumbnail">
                        <video preload="metadata">
                            <source src="../assets/uploads/videos/<?php echo $video['file_path']; ?>" type="video/mp4">
                            Your browser does not support the video tag.
                        </video>
                    </div>
                    <div class="video-info">
                        <h3><?php echo $video['title']; ?></h3>
                        <div class="meta">
                            <?php echo $video['class_name'] . ' - ' . $video['section']; ?> | 
                            <?php echo $video['subject_name']; ?>
                        </div>
                        <p><?php echo $video['description'] ?: 'No description provided.'; ?></p>
                        <div class="video-actions">
                            <a href="#" class="play" onclick="playVideo('<?php echo $video['file_path']; ?>', '<?php echo addslashes($video['title']); ?>')">
                                <i class="fas fa-play"></i> Play
                            </a>
                            <a href="../assets/uploads/videos/<?php echo $video['file_path']; ?>" class="play" download>
                                <i class="fas fa-download"></i>
                            </a>
                            <a href="?delete=<?php echo $video['id']; ?>" class="delete delete-btn">
                                <i class="fas fa-trash"></i>
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </main>
    
    <div id="videoModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.9); z-index: 9999; align-items: center; justify-content: center;">
        <div style="position: relative; width: 80%; max-width: 900px;">
            <span onclick="closeVideo()" style="position: absolute; top: -40px; right: 0; color: white; font-size: 30px; cursor: pointer;">&times;</span>
            <h3 id="videoTitle" style="color: white; margin-bottom: 15px; text-align: center;"></h3>
            <video id="videoPlayer" controls style="width: 100%; border-radius: 10px;">
                <source src="" type="video/mp4">
            </video>
        </div>
    </div>
    
    <script src="../assets/js/script.js"></script>
    <script>
        function updateFileName(input) {
            document.getElementById('fileName').textContent = input.files[0] ? input.files[0].name : '';
        }
        
        function playVideo(filename, title) {
            document.getElementById('videoTitle').textContent = title;
            document.getElementById('videoPlayer').src = '../assets/uploads/videos/' + filename;
            document.getElementById('videoModal').style.display = 'flex';
            document.getElementById('videoPlayer').play();
            return false;
        }
        
        function closeVideo() {
            document.getElementById('videoModal').style.display = 'none';
            document.getElementById('videoPlayer').pause();
        }
    </script>
</body>
</html>
