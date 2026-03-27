<?php
require_once '../config/database.php';

if (!isLoggedIn()) {
    redirect('../login.php');
}

$studentCount = $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();
$teacherCount = $pdo->query("SELECT COUNT(*) FROM teachers")->fetchColumn();
$subjectCount = $pdo->query("SELECT COUNT(*) FROM subjects")->fetchColumn();
$classCount = $pdo->query("SELECT COUNT(*) FROM classes")->fetchColumn();

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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - School Management System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="../assets/css/header-animations.css">
</head>
<body style="--primary: <?php echo htmlspecialchars($themeColor); ?>">
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
            <li><a href="dashboard.php" class="active"><i class="fas fa-home"></i> Dashboard</a></li>
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
            <li><a href="video_uploads.php"><i class="fas fa-video"></i> Video Uploads</a></li>
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
    
    <main class="main-content" <?php echo $bgStyle ? 'style="' . $bgStyle . '"' : ''; ?>>
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
        
        <div class="header">
            <h1><i class="fas fa-tachometer-alt"></i> Dashboard</h1>
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
                <span>Welcome, <?php echo $_SESSION['admin_username']; ?></span>
                <a href="settings.php" title="Settings"><i class="fas fa-cog"></i></a>
                <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
        
        <div class="content">
            <div class="cards">
                <div class="card">
                    <div class="card-icon blue">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                    <div class="card-info">
                        <h3><?php echo $studentCount; ?></h3>
                        <p>Total Students</p>
                    </div>
                </div>
                <div class="card">
                    <div class="card-icon green">
                        <i class="fas fa-chalkboard-teacher"></i>
                    </div>
                    <div class="card-info">
                        <h3><?php echo $teacherCount; ?></h3>
                        <p>Total Teachers</p>
                    </div>
                </div>
                <div class="card">
                    <div class="card-icon orange">
                        <i class="fas fa-book"></i>
                    </div>
                    <div class="card-info">
                        <h3><?php echo $subjectCount; ?></h3>
                        <p>Total Subjects</p>
                    </div>
                </div>
                <div class="card">
                    <div class="card-icon red">
                        <i class="fas fa-school"></i>
                    </div>
                    <div class="card-info">
                        <h3><?php echo $classCount; ?></h3>
                        <p>Total Classes</p>
                    </div>
                </div>
            </div>
            
            <div class="table-container">
                <div class="table-header">
                    <h2>Quick Actions</h2>
                </div>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; padding: 20px;">
                    <a href="students.php" class="btn btn-primary" style="text-align: center;">
                        <i class="fas fa-user-plus"></i> Manage Students
                    </a>
                    <a href="teachers.php" class="btn btn-primary" style="text-align: center;">
                        <i class="fas fa-user-plus"></i> Manage Teachers
                    </a>
                    <a href="subjects.php" class="btn btn-primary" style="text-align: center;">
                        <i class="fas fa-plus"></i> Manage Subjects
                    </a>
                    <a href="classes.php" class="btn btn-primary" style="text-align: center;">
                        <i class="fas fa-plus"></i> Manage Classes
                    </a>
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
