<?php
require_once '../config/database.php';

if (!isset($_SESSION['student_id'])) {
    redirect('../login.php');
}

$studentId = $_SESSION['student_id'];
$classId = $_SESSION['student_class_id'];

$settings = [];
$stmt = $pdo->query("SELECT * FROM school_settings");
while ($row = $stmt->fetch()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
$schoolName = $settings['school_name'] ?? 'School Management System';
$schoolLogo = $settings['school_logo'] ?? '';

$videos = $pdo->prepare("SELECT v.*, c.class_name, c.section, s.subject_name, t.name as teacher_name 
                         FROM videos v 
                         LEFT JOIN classes c ON v.class_id = c.id 
                         LEFT JOIN subjects s ON v.subject_id = s.id
                         LEFT JOIN teachers t ON v.teacher_id = t.id
                         WHERE v.status = 'approved' AND (v.class_id = ? OR v.class_id IS NULL)
                         ORDER BY v.id DESC");
$videos->execute([$classId]);
$videos = $videos->fetchAll();

if (isset($_GET['watch'])) {
    $videoId = sanitize($_GET['watch']);
    $stmt = $pdo->prepare("UPDATE videos SET views = views + 1 WHERE id = ?");
    $stmt->execute([$videoId]);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Video Uploads - Student Portal</title>
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
            background: rgba(0,0,0,0.3);
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
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
        .video-actions .play:hover {
            background: var(--primary-dark);
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
            <i class="fas fa-user-graduate"></i> Student Portal
        </div>
        <ul class="sidebar-menu">
            <li><a href="index.php"><i class="fas fa-home"></i> Dashboard</a></li>
            <li><a href="video_conference.php"><i class="fas fa-video"></i> Video Conference</a></li>
            <li><a href="announcements.php"><i class="fas fa-bullhorn"></i> Announcements</a></li>
            <li><a href="results.php"><i class="fas fa-chart-line"></i> My Results</a></li>
            <li><a href="reportcard.php"><i class="fas fa-file-alt"></i> Report Card</a></li>
            <li><a href="lessons.php"><i class="fas fa-book-open"></i> Lessons</a></li>
            <li><a href="video_lessons.php"><i class="fas fa-video"></i> Video Lessons</a></li>
            <li><a href="video_uploads.php" class="active"><i class="fas fa-upload"></i> Video Uploads</a></li>
            <li><a href="feedback.php"><i class="fas fa-comment-dots"></i> Feedback</a></li>
            <li><a href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
            <li><a href="../logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </nav>
    
    <main class="main-content">
        <div class="header">
            <h1><i class="fas fa-upload"></i> Video Uploads</h1>
            <div class="header-right">
                <div class="theme-switcher">
                    <button class="theme-btn" data-theme="blue" title="Blue"></button>
                    <button class="theme-btn" data-theme="green" title="Green"></button>
                    <button class="theme-btn" data-theme="purple" title="Purple"></button>
                    <button class="theme-btn" data-theme="red" title="Red"></button>
                    <button class="theme-btn" data-theme="orange" title="Orange"></button>
                    <button class="theme-btn" data-theme="dark" title="Dark"></button>
                    <button class="theme-btn" data-theme="ocean" title="Ocean"></button>
                    <button class="theme-btn" data-theme="sunset" title="Sunset"></button>
                </div>
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
            <h2 style="margin-bottom: 20px;">Teacher Video Uploads</h2>
            
            <?php if (empty($videos)): ?>
            <div class="table-container" style="text-align: center; padding: 40px;">
                <i class="fas fa-video" style="font-size: 50px; color: #ddd; margin-bottom: 15px;"></i>
                <p style="color: var(--gray);">No videos uploaded yet for your class.</p>
            </div>
            <?php else: ?>
            <div class="video-grid">
                <?php foreach ($videos as $video): ?>
                <div class="video-card">
                    <div class="video-thumbnail">
                        <video preload="metadata" poster="../assets/images/video-placeholder.png">
                            <source src="../assets/uploads/videos/<?php echo $video['file_path']; ?>" type="video/mp4">
                            Your browser does not support the video tag.
                        </video>
                        <div class="play-icon"><i class="fas fa-play"></i></div>
                    </div>
                    <div class="video-info">
                        <h3><?php echo htmlspecialchars($video['title']); ?></h3>
                        <div class="meta">
                            <i class="fas fa-user"></i> <?php echo htmlspecialchars($video['teacher_name'] ?? 'Teacher'); ?> |
                            <i class="fas fa-eye"></i> <?php echo $video['views']; ?> views
                        </div>
                        <div class="meta">
                            <?php echo ($video['class_name'] ? $video['class_name'] . ' - ' . $video['section'] : 'All Classes'); ?> | 
                            <?php echo htmlspecialchars($video['subject_name'] ?? 'General'); ?>
                        </div>
                        <p><?php echo htmlspecialchars($video['description'] ?: 'No description provided.'); ?></p>
                        <div class="video-actions">
                            <a href="#" class="play" onclick="playVideo('<?php echo $video['file_path']; ?>', '<?php echo addslashes(htmlspecialchars($video['title'])); ?>')">
                                <i class="fas fa-play"></i> Watch Video
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
        <div style="position: relative; width: 90%; max-width: 1000px;">
            <span onclick="closeVideo()" style="position: absolute; top: -40px; right: 0; color: white; font-size: 30px; cursor: pointer;">&times;</span>
            <h3 id="videoTitle" style="color: white; margin-bottom: 15px; text-align: center;"></h3>
            <video id="videoPlayer" controls style="width: 100%; border-radius: 10px;">
                <source src="" type="video/mp4">
            </video>
        </div>
    </div>
    
    <script src="../assets/js/script.js"></script>
    <script>
        function playVideo(filename, title) {
            document.getElementById('videoTitle').textContent = title;
            document.getElementById('videoPlayer').src = '../assets/uploads/videos/' + filename;
            document.getElementById('videoModal').style.display = 'flex';
            document.getElementById('videoPlayer').play();
            
            fetch('video_uploads.php?watch=1', {
                method: 'POST',
                body: 'file=' + filename
            });
            
            return false;
        }
        
        function closeVideo() {
            document.getElementById('videoModal').style.display = 'none';
            document.getElementById('videoPlayer').pause();
        }
        
        document.getElementById('videoModal').addEventListener('click', function(e) {
            if (e.target === this) closeVideo();
        });
    </script>
</body>
</html>
