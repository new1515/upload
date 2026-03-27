<?php
require_once '../config/database.php';

if (!isset($_SESSION['teacher_id'])) {
    redirect('../login.php');
}

$teacherId = $_SESSION['teacher_id'];

$settings = [];
$stmt = $pdo->query("SELECT * FROM school_settings");
while ($row = $stmt->fetch()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
$schoolName = $settings['school_name'] ?? 'School Management System';
$schoolLogo = $settings['school_logo'] ?? '';

$announcements = $pdo->query("SELECT * FROM announcements WHERE target_audience IN ('teachers', 'all') AND status = 'active' ORDER BY created_at DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Announcements - Teacher Portal</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        .portal-header { background: linear-gradient(135deg, #27ae60, #2ecc71); color: white; padding: 25px 30px; border-radius: 15px; margin-bottom: 25px; }
        .announcement-card { background: white; border-radius: 15px; padding: 20px; margin-bottom: 15px; border-left: 4px solid #27ae60; box-shadow: 0 3px 15px rgba(0,0,0,0.08); }
        .announcement-card h4 { margin: 0 0 10px 0; color: #2c3e50; }
        .announcement-card h4 i { color: #27ae60; margin-right: 8px; }
        .announcement-card p { margin: 0 0 10px 0; color: #666; line-height: 1.6; }
        .announcement-meta { font-size: 12px; color: #999; }
        .badge { padding: 3px 10px; border-radius: 15px; font-size: 11px; color: white; margin-left: 10px; }
        .badge-high { background: #e74c3c; }
    </style>
</head>
<body>
    <button class="mobile-menu-toggle">
        <i class="fas fa-bars"></i>
    </button>
    <div class="sidebar-overlay"></div>
    <nav class="sidebar">
        <div class="sidebar-brand"><i class="fas fa-chalkboard-teacher"></i> Teacher Portal</div>
        <ul class="sidebar-menu">
            <li><a href="index.php"><i class="fas fa-home"></i> Dashboard</a></li>
            <li><a href="announcements.php" class="active"><i class="fas fa-bullhorn"></i> Announcements</a></li>
            <li><a href="my_classes.php"><i class="fas fa-school"></i> My Classes</a></li>
            <li><a href="enter_scores.php"><i class="fas fa-edit"></i> Enter Scores</a></li>
            <li><a href="results.php"><i class="fas fa-chart-line"></i> View Results</a></li>
            <li><a href="lessons.php"><i class="fas fa-book-open"></i> Lessons</a></li>
            <li><a href="lesson_plans.php"><i class="fas fa-calendar-alt"></i> Lesson Plans</a></li>
            <li><a href="test_books.php"><i class="fas fa-file-alt"></i> Test Books</a></li>
            <li><a href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
            <li><a href="../logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </nav>
    
    <main class="main-content">
        <div class="header">
            <h1><i class="fas fa-bullhorn"></i> Announcements</h1>
            <div class="header-right">
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
            <div class="portal-header" style="display: block; text-align: center;">
                <h2 style="margin: 0;"><i class="fas fa-bullhorn"></i> School Announcements for Teachers</h2>
                <p style="margin: 5px 0 0 0; opacity: 0.9;">Stay updated with school news and events</p>
            </div>
            
            <?php if (empty($announcements)): ?>
            <div class="announcement-card" style="text-align: center;">
                <p style="color: #999;">No announcements at this time.</p>
            </div>
            <?php else: ?>
                <?php foreach ($announcements as $ann): ?>
                <div class="announcement-card">
                    <h4>
                        <i class="fas fa-bullhorn"></i> 
                        <?php echo htmlspecialchars($ann['title']); ?>
                        <?php if ($ann['priority'] == 'high'): ?>
                        <span class="badge badge-high"><i class="fas fa-exclamation-circle"></i> Important</span>
                        <?php endif; ?>
                    </h4>
                    <p><?php echo nl2br(htmlspecialchars($ann['content'])); ?></p>
                    <div class="announcement-meta">
                        <span><i class="fas fa-clock"></i> <?php echo date('M d, Y g:i A', strtotime($ann['created_at'])); ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>
    <script src="../assets/js/script.js"></script>
</body>
</html>
