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

$filter = isset($_GET['filter']) ? sanitize($_GET['filter']) : 'all';
$whereClause = '';
if ($filter !== 'all') {
    $whereClause = "WHERE action_type = '$filter'";
}

$history = $pdo->query("SELECT * FROM activity_history $whereClause ORDER BY created_at DESC LIMIT 100")->fetchAll();

$actionIcons = [
    'login' => 'fa-sign-in-alt',
    'logout' => 'fa-sign-out-alt',
    'create' => 'fa-plus-circle',
    'update' => 'fa-edit',
    'delete' => 'fa-trash'
];

$actionColors = [
    'login' => '#27ae60',
    'logout' => '#e74c3c',
    'create' => '#4a90e2',
    'update' => '#f39c12',
    'delete' => '#e74c3c'
];

$typeColors = [
    'auth' => '#9b59b6',
    'student' => '#4a90e2',
    'teacher' => '#27ae60',
    'subject' => '#f39c12',
    'class' => '#e67e22',
    'result' => '#1abc9c',
    'calendar' => '#e74c3c'
];

if (isset($_GET['clear']) && $_GET['clear'] == 'all') {
    $pdo->query("TRUNCATE TABLE activity_history");
    header("Location: history.php");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity History - School Management System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        .history-filters {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .filter-btn {
            padding: 8px 20px;
            border: 2px solid #e0e0e0;
            border-radius: 25px;
            background: #fff;
            color: #666;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.3s;
        }
        .filter-btn:hover, .filter-btn.active {
            border-color: #4a90e2;
            background: #4a90e2;
            color: #fff;
        }
        .timeline {
            position: relative;
            padding-left: 40px;
        }
        .timeline::before {
            content: '';
            position: absolute;
            left: 15px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #e0e0e0;
        }
        .timeline-item {
            position: relative;
            padding: 15px 20px;
            margin-bottom: 15px;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .timeline-item::before {
            content: '';
            position: absolute;
            left: -33px;
            top: 20px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #4a90e2;
            border: 3px solid #fff;
            box-shadow: 0 0 0 2px #4a90e2;
        }
        .timeline-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }
        .timeline-action {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .timeline-action i {
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            color: #fff;
        }
        .timeline-action span {
            font-weight: 600;
            font-size: 14px;
        }
        .timeline-time {
            font-size: 12px;
            color: #999;
        }
        .timeline-desc {
            color: #666;
            font-size: 14px;
            margin-bottom: 8px;
        }
        .timeline-meta {
            display: flex;
            gap: 15px;
            font-size: 12px;
        }
        .timeline-meta span {
            padding: 3px 10px;
            border-radius: 15px;
            background: #f0f4f8;
        }
        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }
        .stat-box {
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .stat-box h4 {
            font-size: 28px;
            margin-bottom: 5px;
        }
        .stat-box p {
            color: #666;
            font-size: 13px;
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
            <li><a href="lessons.php"><i class="fas fa-book-open"></i> Lessons</a></li>
            <li><a href="history.php" class="active"><i class="fas fa-history"></i> History</a></li>
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
            <h1><i class="fas fa-history"></i> Activity History</h1>
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
            <?php
            $totalActions = $pdo->query("SELECT COUNT(*) FROM activity_history")->fetchColumn();
            $loginCount = $pdo->query("SELECT COUNT(*) FROM activity_history WHERE action_type = 'login'")->fetchColumn();
            $createCount = $pdo->query("SELECT COUNT(*) FROM activity_history WHERE action_type = 'create'")->fetchColumn();
            $updateCount = $pdo->query("SELECT COUNT(*) FROM activity_history WHERE action_type = 'update'")->fetchColumn();
            ?>
            
            <div class="stats-row">
                <div class="stat-box">
                    <h4><?php echo $totalActions; ?></h4>
                    <p>Total Activities</p>
                </div>
                <div class="stat-box">
                    <h4><?php echo $loginCount; ?></h4>
                    <p>Logins</p>
                </div>
                <div class="stat-box">
                    <h4><?php echo $createCount; ?></h4>
                    <p>Creations</p>
                </div>
                <div class="stat-box">
                    <h4><?php echo $updateCount; ?></h4>
                    <p>Updates</p>
                </div>
            </div>
            
            <div class="table-container">
                <div class="table-header">
                    <h2>Recent Activities</h2>
                    <a href="?clear=all" class="btn btn-danger" onclick="return confirm('Clear all history?');">
                        <i class="fas fa-trash"></i> Clear All
                    </a>
                </div>
                
                <div class="history-filters">
                    <a href="history.php" class="filter-btn <?php echo $filter == 'all' ? 'active' : ''; ?>">All</a>
                    <a href="?filter=login" class="filter-btn <?php echo $filter == 'login' ? 'active' : ''; ?>">Logins</a>
                    <a href="?filter=create" class="filter-btn <?php echo $filter == 'create' ? 'active' : ''; ?>">Created</a>
                    <a href="?filter=update" class="filter-btn <?php echo $filter == 'update' ? 'active' : ''; ?>">Updated</a>
                    <a href="?filter=delete" class="filter-btn <?php echo $filter == 'delete' ? 'active' : ''; ?>">Deleted</a>
                </div>
                
                <?php if (empty($history)): ?>
                <div style="text-align: center; padding: 40px; color: #999;">
                    <i class="fas fa-history" style="font-size: 50px; margin-bottom: 15px;"></i>
                    <p>No activity history found.</p>
                </div>
                <?php else: ?>
                <div class="timeline">
                    <?php foreach ($history as $item): ?>
                    <div class="timeline-item">
                        <div class="timeline-header">
                            <div class="timeline-action">
                                <i style="background: <?php echo $actionColors[$item['action_type']] ?? '#95a5a6'; ?>;" class="fas <?php echo $actionIcons[$item['action_type']] ?? 'fa-circle'; ?>"></i>
                                <span style="text-transform: capitalize;"><?php echo $item['action_type']; ?></span>
                            </div>
                            <span class="timeline-time">
                                <i class="fas fa-clock"></i> <?php echo date('M d, Y h:i A', strtotime($item['created_at'])); ?>
                            </span>
                        </div>
                        <p class="timeline-desc"><?php echo $item['description']; ?></p>
                        <div class="timeline-meta">
                            <span><i class="fas fa-user"></i> <?php echo $item['username']; ?></span>
                            <?php if ($item['record_type']): ?>
                            <span style="background: <?php echo $typeColors[$item['record_type']] ?? '#95a5a6'; ?>; color: white;">
                                <i class="fas fa-tag"></i> <?php echo ucfirst($item['record_type']); ?>
                            </span>
                            <?php endif; ?>
                            <?php if ($item['ip_address']): ?>
                            <span><i class="fas fa-laptop"></i> <?php echo $item['ip_address']; ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
    
    <script src="../assets/js/script.js"></script>
</body>
</html>
