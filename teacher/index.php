<?php
require_once '../config/database.php';

if (!isset($_SESSION['teacher_id'])) {
    redirect('../login.php');
}

$teacherId = $_SESSION['teacher_id'];
$teacherName = $_SESSION['teacher_name'];

$settings = [];
$stmt = $pdo->query("SELECT * FROM school_settings");
while ($row = $stmt->fetch()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
$schoolName = $settings['school_name'] ?? 'School Management System';
$schoolLogo = $settings['school_logo'] ?? '';
$dashboardTheme = $settings['dashboard_theme'] ?? 'default';
$dashboardBackground = $settings['dashboard_background'] ?? '';

$themeColors = [
    'default' => '#4a90e2',
    'ocean' => '#2196F3',
    'sunset' => '#ff7043',
    'forest' => '#4caf50',
    'royal' => '#9c27b0',
    'dark' => '#2c3e50'
];
$themeColor = $themeColors[$dashboardTheme] ?? $themeColors['default'];

$bgStyle = '';
if (!empty($dashboardBackground) && file_exists('../assets/images/backgrounds/' . $dashboardBackground)) {
    $bgStyle = 'background: linear-gradient(rgba(255,255,255,0.9), rgba(255,255,255,0.9)), url(\'../assets/images/backgrounds/' . htmlspecialchars($dashboardBackground) . '\'); background-size: cover; background-position: center;';
}

$teacherSubjects = $pdo->prepare("SELECT sub.* FROM subjects sub 
    INNER JOIN teacher_subjects ts ON sub.id = ts.subject_id 
    WHERE ts.teacher_id = ?");
$teacherSubjects->execute([$teacherId]);
$teacherSubjects = $teacherSubjects->fetchAll();

$myClasses = $pdo->query("SELECT c.* FROM classes c 
    INNER JOIN teacher_classes tc ON c.id = tc.class_id 
    WHERE tc.teacher_id = $teacherId ORDER BY c.level, c.class_name, c.section")->fetchAll();

$announcements = $pdo->query("SELECT * FROM announcements WHERE target_audience IN ('teachers', 'all') AND status = 'active' ORDER BY created_at DESC LIMIT 5")->fetchAll();

$totalStudents = $pdo->query("SELECT COUNT(DISTINCT s.id) FROM students s 
    INNER JOIN teacher_classes tc ON s.class_id = tc.class_id 
    WHERE tc.teacher_id = $teacherId")->fetchColumn();

$totalClasses = count($myClasses);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Portal - <?php echo htmlspecialchars($schoolName); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="../assets/css/header-animations.css">
    <style>
        .portal-header {
            background: linear-gradient(135deg, #27ae60, #2ecc71);
            color: white;
            padding: 25px 30px;
            border-radius: 15px;
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .portal-header h1 { margin: 0; font-size: 24px; }
        .portal-header .welcome { font-size: 14px; opacity: 0.9; }
        .portal-header a { background: rgba(255,255,255,0.2); color: white; padding: 8px 15px; border-radius: 20px; text-decoration: none; font-size: 13px; }
        .portal-header a:hover { background: rgba(255,255,255,0.3); }
        
        .teacher-card {
            background: linear-gradient(135deg, #27ae60, #2ecc71);
            color: white;
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 20px;
        }
        .teacher-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: rgba(255,255,255,0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 36px;
        }
        .teacher-card h3 { margin: 0 0 5px 0; font-size: 22px; }
        .teacher-card p { margin: 0; opacity: 0.9; font-size: 14px; }
        
        .stat-cards {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 25px;
        }
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 3px 15px rgba(0,0,0,0.08);
        }
        .stat-card i {
            font-size: 30px;
            color: #27ae60;
            margin-bottom: 10px;
        }
        .stat-card h3 { font-size: 28px; margin: 0; color: #2c3e50; }
        .stat-card p { margin: 5px 0 0 0; color: #888; font-size: 13px; }
        
        .announcement-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 15px;
            border-left: 4px solid #27ae60;
            box-shadow: 0 3px 15px rgba(0,0,0,0.08);
        }
        .announcement-card h4 { margin: 0 0 10px 0; color: #2c3e50; font-size: 16px; }
        .announcement-card h4 i { color: #27ae60; margin-right: 8px; }
        .announcement-card p { margin: 0 0 10px 0; color: #666; font-size: 14px; line-height: 1.6; }
        .announcement-meta { font-size: 12px; color: #999; }
        
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }
        .quick-action {
            background: white;
            padding: 20px;
            border-radius: 15px;
            text-align: center;
            text-decoration: none;
            color: #2c3e50;
            box-shadow: 0 3px 15px rgba(0,0,0,0.08);
            transition: all 0.3s;
        }
        .quick-action:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        .quick-action i {
            font-size: 32px;
            color: #27ae60;
            margin-bottom: 10px;
        }
        .quick-action h4 { margin: 0; font-size: 14px; }
        
        @media (max-width: 768px) {
            .stat-cards { grid-template-columns: repeat(2, 1fr); }
            .quick-actions { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body style="--primary: <?php echo htmlspecialchars($themeColor); ?>">
    <button class="mobile-menu-toggle">
        <i class="fas fa-bars"></i>
    </button>
    <div class="sidebar-overlay"></div>
    <nav class="sidebar">
        <div class="sidebar-brand">
            <i class="fas fa-chalkboard-teacher"></i> Teacher Portal
        </div>
        <ul class="sidebar-menu">
            <li><a href="index.php" class="active"><i class="fas fa-home"></i> Dashboard</a></li>
            <li><a href="video_conference.php"><i class="fas fa-video"></i> Video Conference</a></li>
            <li><a href="announcements.php"><i class="fas fa-bullhorn"></i> Announcements</a></li>
            <li><a href="my_classes.php"><i class="fas fa-school"></i> My Classes</a></li>
            <li><a href="enter_scores.php"><i class="fas fa-edit"></i> Enter Scores</a></li>
            <li><a href="attendance.php"><i class="fas fa-calendar-check"></i> Attendance</a></li>
            <li><a href="results.php"><i class="fas fa-chart-line"></i> View Results</a></li>
            <li><a href="lessons.php"><i class="fas fa-book-open"></i> Lessons</a></li>
            <li><a href="video_uploads.php"><i class="fas fa-video"></i> Video Uploads</a></li>
            <li><a href="lesson_plans.php"><i class="fas fa-calendar-alt"></i> Lesson Plans</a></li>
            <li><a href="test_books.php"><i class="fas fa-file-alt"></i> Test Books</a></li>
            <li><a href="feedback.php"><i class="fas fa-comment-dots"></i> Feedback</a></li>
            <li><a href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
            <li><a href="change_password.php"><i class="fas fa-key"></i> Change Password</a></li>
            <li><a href="../logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </nav>
    
    <main class="main-content" <?php echo $bgStyle ? 'style="' . $bgStyle . '"' : ''; ?>>
        <div class="header">
            <h1><i class="fas fa-chalkboard-teacher"></i> Teacher Dashboard</h1>
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
                <h1><?php echo htmlspecialchars($schoolName); ?></h1>
                <p><?php echo htmlspecialchars($settings['school_tagline'] ?? ''); ?></p>
            </div>
        </div>
        
        <div class="content">
            <div class="portal-header">
                <div class="school-logo">
                    <?php if ($schoolLogo && file_exists('../assets/images/' . $schoolLogo)): ?>
                        <img src="../assets/images/<?php echo $schoolLogo; ?>" alt="School Logo">
                    <?php else: ?>
                        <i class="fas fa-graduation-cap"></i>
                    <?php endif; ?>
                </div>
                <div class="school-info">
                    <h1><?php echo htmlspecialchars($schoolName); ?></h1>
                    <p><?php echo $settings['school_tagline'] ?? ''; ?></p>
                </div>
            </div>
            
            <div class="teacher-card">
                <div class="teacher-avatar">
                    <i class="fas fa-chalkboard-teacher"></i>
                </div>
                <div>
                    <h3><?php echo htmlspecialchars($teacherName); ?></h3>
                    <p>Subject Teacher</p>
                    <div style="margin-top: 10px;">
                        <span style="background: rgba(255,255,255,0.2); padding: 5px 12px; border-radius: 15px; font-size: 12px; margin-right: 10px;">
                            <i class="fas fa-book"></i> <?php echo count($teacherSubjects); ?> Subjects
                        </span>
                        <span style="background: rgba(255,255,255,0.2); padding: 5px 12px; border-radius: 15px; font-size: 12px;">
                            <i class="fas fa-users"></i> <?php echo $totalStudents; ?> Students
                        </span>
                    </div>
                </div>
            </div>
            
            <div class="stat-cards">
                <div class="stat-card">
                    <i class="fas fa-book"></i>
                    <h3><?php echo count($teacherSubjects); ?></h3>
                    <p>My Subjects</p>
                </div>
                <div class="stat-card">
                    <i class="fas fa-school"></i>
                    <h3><?php echo $totalClasses; ?></h3>
                    <p>My Classes</p>
                </div>
                <div class="stat-card">
                    <i class="fas fa-user-graduate"></i>
                    <h3><?php echo $totalStudents; ?></h3>
                    <p>Total Students</p>
                </div>
                <div class="stat-card">
                    <i class="fas fa-tasks"></i>
                    <h3><?php echo $pdo->query("SELECT COUNT(*) FROM student_assessments sa INNER JOIN teacher_subjects ts ON sa.subject_id = ts.subject_id WHERE ts.teacher_id = $teacherId")->fetchColumn(); ?></h3>
                    <p>Records Entry</p>
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 25px;">
                <div>
                    <h3 style="margin-bottom: 15px; color: #2c3e50;"><i class="fas fa-bullhorn" style="color: #27ae60;"></i> Latest Announcements</h3>
                    
                    <?php if (empty($announcements)): ?>
                    <div class="announcement-card">
                        <p style="text-align: center; color: #999;">No announcements at this time.</p>
                    </div>
                    <?php else: ?>
                        <?php foreach ($announcements as $ann): ?>
                        <div class="announcement-card">
                            <h4><i class="fas fa-bullhorn"></i> <?php echo htmlspecialchars($ann['title']); ?></h4>
                            <p><?php echo nl2br(htmlspecialchars($ann['content'])); ?></p>
                            <div class="announcement-meta">
                                <i class="fas fa-clock"></i> <?php echo date('M d, Y', strtotime($ann['created_at'])); ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <div>
                    <h3 style="margin-bottom: 15px; color: #2c3e50;"><i class="fas fa-bolt" style="color: #f39c12;"></i> Quick Actions</h3>
                    
                    <div class="quick-actions">
                        <a href="enter_scores.php" class="quick-action">
                            <i class="fas fa-edit"></i>
                            <h4>Enter Scores</h4>
                        </a>
                        <a href="my_classes.php" class="quick-action">
                            <i class="fas fa-school"></i>
                            <h4>My Classes</h4>
                        </a>
                        <a href="lessons.php" class="quick-action">
                            <i class="fas fa-upload"></i>
                            <h4>Upload Lesson</h4>
                        </a>
                        <a href="results.php" class="quick-action">
                            <i class="fas fa-chart-bar"></i>
                            <h4>View Results</h4>
                        </a>
                    </div>
                    
                    <h3 style="margin: 25px 0 15px 0; color: #2c3e50;"><i class="fas fa-book" style="color: #9b59b6;"></i> My Subjects</h3>
                    <div style="background: white; padding: 15px; border-radius: 10px; box-shadow: 0 3px 15px rgba(0,0,0,0.08);">
                        <?php foreach ($teacherSubjects as $sub): ?>
                        <div style="padding: 10px 0; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center;">
                            <span><i class="fas fa-book" style="color: #9b59b6; margin-right: 8px;"></i> <?php echo htmlspecialchars($sub['subject_name']); ?></span>
                            <span class="badge" style="background: #f0f0f0; color: #666; font-size: 11px; padding: 3px 10px; border-radius: 10px;"><?php echo ucfirst($sub['category']); ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="site-footer">
            <p>Made with <i class="fas fa-heart" style="color: #e74c3c;"></i> Designed by <strong>Sir Abraham Ashong Tetteh</strong></p>
            <p>Contact: 0594646631 | 0209484452</p>
        </div>
    </main>
    
    <style>
        .site-footer {
            background: linear-gradient(135deg, #2c3e50, #34495e);
            color: white;
            text-align: center;
            padding: 20px;
            margin-top: 30px;
        }
        .site-footer p {
            margin: 5px 0;
            font-size: 14px;
        }
        .site-footer i {
            margin: 0 3px;
        }
    </style>
    
    <script src="../assets/js/script.js"></script>
</body>
</html>
