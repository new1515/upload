<?php
require_once '../config/database.php';

if (!isset($_SESSION['parent_id'])) {
    redirect('../login.php');
}

$parentId = $_SESSION['parent_id'];
$parentName = $_SESSION['parent_name'];
$studentId = $_SESSION['parent_student_id'];

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

$student = null;
if ($studentId) {
    $stmt = $pdo->prepare("SELECT s.*, c.class_name, c.section FROM students s LEFT JOIN classes c ON s.class_id = c.id WHERE s.id = ?");
    $stmt->execute([$studentId]);
    $student = $stmt->fetch();
}

$announcements = $pdo->query("SELECT * FROM announcements WHERE target_audience IN ('parents', 'all') AND status = 'active' ORDER BY created_at DESC LIMIT 5")->fetchAll();

$recentResults = [];
if ($studentId) {
    $stmt = $pdo->prepare("SELECT a.*, sub.subject_name FROM student_assessments a 
        LEFT JOIN subjects sub ON a.subject_id = sub.id 
        WHERE a.student_id = ? AND a.term = 'Term 1' 
        ORDER BY a.created_at DESC LIMIT 5");
    $stmt->execute([$studentId]);
    $recentResults = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parent Portal - <?php echo htmlspecialchars($schoolName); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="../assets/css/header-animations.css">
    <style>
        .portal-header {
            background: linear-gradient(135deg, #f39c12, #d35400);
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
        
        .student-card {
            background: linear-gradient(135deg, #f39c12, #e67e22);
            color: white;
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 20px;
        }
        .student-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: rgba(255,255,255,0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 36px;
        }
        .student-card h3 { margin: 0 0 5px 0; font-size: 22px; }
        .student-card p { margin: 0; opacity: 0.9; font-size: 14px; }
        .student-info { display: flex; gap: 30px; margin-top: 10px; }
        .student-info span { font-size: 13px; }
        
        .announcement-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 15px;
            border-left: 4px solid #f39c12;
            box-shadow: 0 3px 15px rgba(0,0,0,0.08);
        }
        .announcement-card h4 { margin: 0 0 10px 0; color: #2c3e50; font-size: 16px; }
        .announcement-card h4 i { color: #f39c12; margin-right: 8px; }
        .announcement-card p { margin: 0 0 10px 0; color: #666; font-size: 14px; line-height: 1.6; }
        .announcement-meta { font-size: 12px; color: #999; }
        .announcement-meta i { margin-right: 5px; }
        
        .result-item {
            background: white;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .result-item .subject { font-weight: 600; color: #2c3e50; }
        .result-item .score { font-size: 18px; font-weight: bold; color: #27ae60; }
        .grade-badge { padding: 4px 12px; border-radius: 15px; color: white; font-size: 12px; font-weight: 600; }
        .grade-a { background: #27ae60; }
        .grade-b { background: #2ecc71; }
        .grade-c { background: #f39c12; }
        .grade-d { background: #e67e22; }
        .grade-e { background: #e74c3c; }
        .grade-f { background: #c0392b; }
    </style>
</head>
<body style="--primary: <?php echo htmlspecialchars($themeColor); ?>">
    <button class="mobile-menu-toggle">
        <i class="fas fa-bars"></i>
    </button>
    <div class="sidebar-overlay"></div>
    <nav class="sidebar">
        <div class="sidebar-brand">
            <i class="fas fa-users"></i> Parent Portal
        </div>
        <ul class="sidebar-menu">
            <li><a href="index.php" class="active"><i class="fas fa-home"></i> Dashboard</a></li>
            <li><a href="video_conference.php"><i class="fas fa-video"></i> Video Conference</a></li>
            <li><a href="announcements.php"><i class="fas fa-bullhorn"></i> Announcements</a></li>
            <li><a href="results.php"><i class="fas fa-chart-line"></i> Results</a></li>
            <li><a href="reportcard.php"><i class="fas fa-file-alt"></i> Report Card</a></li>
            <li><a href="fees.php"><i class="fas fa-money-bill"></i> School Fees</a></li>
            <li><a href="feedback.php"><i class="fas fa-comment-dots"></i> Feedback</a></li>
            <li><a href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
            <li><a href="../logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </nav>
    
    <main class="main-content" <?php echo $bgStyle ? 'style="' . $bgStyle . '"' : ''; ?>>
        <div class="header">
            <h1><i class="fas fa-users"></i> Parent Dashboard</h1>
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
            
            <?php if ($student): ?>
            <div class="student-card">
                <div class="student-avatar">
                    <i class="fas fa-user-graduate"></i>
                </div>
                <div>
                    <h3><?php echo htmlspecialchars($student['name']); ?></h3>
                    <p>Your Child</p>
                    <div class="student-info">
                        <span><i class="fas fa-school"></i> <?php echo htmlspecialchars($student['class_name'] . ' - Section ' . $student['section']); ?></span>
                        <span><i class="fas fa-venus-mars"></i> <?php echo htmlspecialchars($student['gender']); ?></span>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <div class="student-card" style="background: linear-gradient(135deg, #95a5a6, #7f8c8d);">
                <div class="student-avatar"><i class="fas fa-exclamation-triangle"></i></div>
                <div>
                    <h3>No Student Linked</h3>
                    <p>Please contact the school administration to link your account to your child.</p>
                </div>
            </div>
            <?php endif; ?>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 25px;">
                <div>
                    <h3 style="margin-bottom: 15px; color: #2c3e50;"><i class="fas fa-bullhorn" style="color: #f39c12;"></i> Latest Announcements</h3>
                    
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
                                <?php if ($ann['priority'] == 'high'): ?>
                                | <span style="color: #e74c3c;"><i class="fas fa-exclamation-circle"></i> Important</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <div>
                    <h3 style="margin-bottom: 15px; color: #2c3e50;"><i class="fas fa-chart-line" style="color: #27ae60;"></i> Recent Results</h3>
                    
                    <?php if (empty($recentResults)): ?>
                    <div class="result-item">
                        <p style="color: #999; text-align: center; width: 100%;">No results available yet.</p>
                    </div>
                    <?php else: ?>
                        <?php foreach ($recentResults as $result): 
                            $total = ($result['test1'] ?? 0) + ($result['test2'] ?? 0) + ($result['test3'] ?? 0) + ($result['project'] ?? 0) + ($result['class_assessment'] ?? 0) + ($result['exam'] ?? 0);
                            $grade = $result['grade'] ?? '';
                        ?>
                        <div class="result-item">
                            <div>
                                <span class="subject"><?php echo htmlspecialchars($result['subject_name']); ?></span>
                                <div style="font-size: 12px; color: #888;"><?php echo $result['term']; ?></div>
                            </div>
                            <div style="display: flex; align-items: center; gap: 15px;">
                                <span class="score"><?php echo number_format($total, 0); ?>/100</span>
                                <?php if ($grade): ?>
                                <span class="grade-badge grade-<?php echo strtolower($grade[0]); ?>"><?php echo $grade; ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    
                    <a href="results.php" class="btn btn-primary" style="margin-top: 15px; display: block; text-align: center;">
                        <i class="fas fa-chart-bar"></i> View All Results
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
