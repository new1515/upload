<?php
require_once '../config/database.php';

if (!isset($_SESSION['student_id'])) {
    redirect('../login.php');
}

$studentId = $_SESSION['student_id'];
$studentName = $_SESSION['student_name'];
$classId = $_SESSION['student_class_id'];

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
    $stmt = $pdo->prepare("SELECT s.*, c.class_name, c.section, c.level FROM students s LEFT JOIN classes c ON s.class_id = c.id WHERE s.id = ?");
    $stmt->execute([$studentId]);
    $student = $stmt->fetch();
}

$announcements = $pdo->query("SELECT * FROM announcements WHERE target_audience IN ('students', 'all') AND status = 'active' ORDER BY created_at DESC LIMIT 5")->fetchAll();

$myResults = [];
if ($studentId) {
    $stmt = $pdo->prepare("SELECT a.*, sub.subject_name FROM student_assessments a 
        LEFT JOIN subjects sub ON a.subject_id = sub.id 
        WHERE a.student_id = ? ORDER BY a.term, sub.subject_name");
    $stmt->execute([$studentId]);
    $myResults = $stmt->fetchAll();
}

$termResults = [];
$term1Total = 0;
$term1Count = 0;
$term2Total = 0;
$term2Count = 0;
foreach ($myResults as $r) {
    $total = ($r['test1'] ?? 0) + ($r['test2'] ?? 0) + ($r['test3'] ?? 0) + ($r['project'] ?? 0) + ($r['class_assessment'] ?? 0) + ($r['exam'] ?? 0);
    if ($r['term'] == 'Term 1') {
        $term1Total += $total;
        $term1Count++;
    } elseif ($r['term'] == 'Term 2') {
        $term2Total += $total;
        $term2Count++;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Portal - <?php echo htmlspecialchars($schoolName); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="../assets/css/header-animations.css">
    <style>
        .portal-header {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
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
        
        .student-profile-card {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
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
        .student-profile-card h3 { margin: 0 0 5px 0; font-size: 22px; }
        .student-profile-card p { margin: 0; opacity: 0.9; font-size: 14px; }
        
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
            color: #e74c3c;
            margin-bottom: 10px;
        }
        .stat-card h3 { font-size: 28px; margin: 0; color: #2c3e50; }
        .stat-card p { margin: 5px 0 0 0; color: #888; font-size: 13px; }
        
        .announcement-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 15px;
            border-left: 4px solid #e74c3c;
            box-shadow: 0 3px 15px rgba(0,0,0,0.08);
        }
        .announcement-card h4 { margin: 0 0 10px 0; color: #2c3e50; font-size: 16px; }
        .announcement-card h4 i { color: #e74c3c; margin-right: 8px; }
        .announcement-card p { margin: 0 0 10px 0; color: #666; font-size: 14px; line-height: 1.6; }
        .announcement-meta { font-size: 12px; color: #999; }
        
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
        
        .term-average {
            background: linear-gradient(135deg, #9b59b6, #8e44ad);
            color: white;
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .term-average h4 { margin: 0; font-size: 14px; }
        .term-average span { font-size: 24px; font-weight: bold; }
        
        @media (max-width: 768px) {
            .stat-cards { grid-template-columns: repeat(2, 1fr); }
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
            <i class="fas fa-user-graduate"></i> Student Portal
        </div>
        <ul class="sidebar-menu">
            <li><a href="index.php" class="active"><i class="fas fa-home"></i> Dashboard</a></li>
            <li><a href="video_conference.php"><i class="fas fa-video"></i> Video Conference</a></li>
            <li><a href="announcements.php"><i class="fas fa-bullhorn"></i> Announcements</a></li>
            <li><a href="results.php"><i class="fas fa-chart-line"></i> My Results</a></li>
            <li><a href="reportcard.php"><i class="fas fa-file-alt"></i> Report Card</a></li>
            <li><a href="lessons.php"><i class="fas fa-book-open"></i> Lessons</a></li>
            <li><a href="video_lessons.php"><i class="fas fa-video"></i> Video Lessons</a></li>
            <li><a href="video_uploads.php"><i class="fas fa-upload"></i> Video Uploads</a></li>
            <li><a href="feedback.php"><i class="fas fa-comment-dots"></i> Feedback</a></li>
            <li><a href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
            <li><a href="../logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </nav>
    
    <main class="main-content" <?php echo $bgStyle ? 'style="' . $bgStyle . '"' : ''; ?>>
        <div class="header">
            <h1><i class="fas fa-user-graduate"></i> Student Dashboard</h1>
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
            <div class="student-profile-card">
                <div class="student-avatar">
                    <i class="fas fa-user-graduate"></i>
                </div>
                <div>
                    <h3><?php echo htmlspecialchars($student['name']); ?></h3>
                    <p><?php echo htmlspecialchars($student['class_name'] . ' - Section ' . $student['section']); ?></p>
                    <div style="margin-top: 10px;">
                        <span style="background: rgba(255,255,255,0.2); padding: 5px 12px; border-radius: 15px; font-size: 12px; margin-right: 10px;">
                            <i class="fas fa-venus-mars"></i> <?php echo htmlspecialchars($student['gender']); ?>
                        </span>
                        <span style="background: rgba(255,255,255,0.2); padding: 5px 12px; border-radius: 15px; font-size: 12px;">
                            <i class="fas fa-id-badge"></i> ID: <?php echo $student['id']; ?>
                        </span>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="stat-cards">
                <div class="stat-card">
                    <i class="fas fa-book"></i>
                    <h3><?php echo count(array_unique(array_column($myResults, 'subject_id'))); ?></h3>
                    <p>Subjects Taken</p>
                </div>
                <div class="stat-card">
                    <i class="fas fa-clipboard-list"></i>
                    <h3><?php echo count($myResults); ?></h3>
                    <p>Total Records</p>
                </div>
                <div class="stat-card">
                    <i class="fas fa-star"></i>
                    <h3><?php echo $term1Count > 0 ? number_format($term1Total / $term1Count, 1) : '-'; ?>%</h3>
                    <p>Term 1 Average</p>
                </div>
                <div class="stat-card">
                    <i class="fas fa-trophy"></i>
                    <h3><?php echo $term2Count > 0 ? number_format($term2Total / $term2Count, 1) : '-'; ?>%</h3>
                    <p>Term 2 Average</p>
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 25px;">
                <div>
                    <h3 style="margin-bottom: 15px; color: #2c3e50;"><i class="fas fa-bullhorn" style="color: #e74c3c;"></i> Latest Announcements</h3>
                    
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
                    <h3 style="margin-bottom: 15px; color: #2c3e50;"><i class="fas fa-chart-line" style="color: #27ae60;"></i> Recent Results</h3>
                    
                    <?php 
                    $term1Results = array_filter($myResults, fn($r) => $r['term'] == 'Term 1');
                    if (!empty($term1Results)): 
                    ?>
                    <div class="term-average">
                        <h4><i class="fas fa-calendar"></i> Term 1 Average</h4>
                        <span><?php echo $term1Count > 0 ? number_format($term1Total / $term1Count, 1) : '0'; ?>%</span>
                    </div>
                    
                        <?php foreach (array_slice($term1Results, 0, 5) as $result): 
                            $total = ($result['test1'] ?? 0) + ($result['test2'] ?? 0) + ($result['test3'] ?? 0) + ($result['project'] ?? 0) + ($result['class_assessment'] ?? 0) + ($result['exam'] ?? 0);
                            $grade = $result['grade'] ?? '';
                        ?>
                        <div class="result-item">
                            <div>
                                <span class="subject"><?php echo htmlspecialchars($result['subject_name']); ?></span>
                            </div>
                            <div style="display: flex; align-items: center; gap: 15px;">
                                <span class="score"><?php echo number_format($total, 0); ?>/100</span>
                                <?php if ($grade): ?>
                                <span class="grade-badge grade-<?php echo strtolower($grade[0]); ?>"><?php echo $grade; ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                    <div class="result-item">
                        <p style="color: #999; text-align: center; width: 100%;">No results available yet.</p>
                    </div>
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
