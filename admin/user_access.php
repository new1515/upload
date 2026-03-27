<?php
require_once '../config/database.php';

if (!isLoggedIn()) {
    redirect('../login.php');
}

$settings = [];
$stmt = $pdo->query("SELECT * FROM school_settings");
while ($row = $stmt->fetch()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
$schoolName = $settings['school_name'] ?? 'School Management System';
$schoolLogo = $settings['school_logo'] ?? '';

$message = '';

if (isset($_POST['reset_password'])) {
    $type = sanitize($_POST['type']);
    $id = (int)$_POST['id'];
    $newPassword = sanitize($_POST['new_password']);
    $hashedPassword = md5($newPassword);
    
    $tables = [
        'student' => 'students',
        'parent' => 'parents',
        'teacher' => 'teachers',
        'admin' => 'admins'
    ];
    
    if (isset($tables[$type])) {
        $table = $tables[$type];
        $stmt = $pdo->prepare("UPDATE $table SET password = ? WHERE id = ?");
        $stmt->execute([$hashedPassword, $id]);
        $message = '<div class="success-msg"><i class="fas fa-check-circle"></i> Password reset successfully! New password: ' . htmlspecialchars($newPassword) . '</div>';
        logActivity($pdo, 'update', "Reset password for $type ID: $id", $_SESSION['admin_username'] ?? 'admin', 'user_access', $id);
    }
}

if (isset($_POST['change_username'])) {
    $type = sanitize($_POST['type']);
    $id = (int)$_POST['id'];
    $newUsername = sanitize($_POST['new_username']);
    
    $checkStmt = $pdo->prepare("SELECT id FROM {$type}s WHERE username = ? AND id != ?");
    $tableName = $type . 's';
    if ($type === 'admin') $tableName = 'admins';
    if ($type === 'parent') $tableName = 'parents';
    if ($type === 'student') $tableName = 'students';
    if ($type === 'teacher') $tableName = 'teachers';
    
    $checkStmt = $pdo->prepare("SELECT id FROM $tableName WHERE username = ? AND id != ?");
    $checkStmt->execute([$newUsername, $id]);
    
    if ($checkStmt->fetch()) {
        $message = '<div class="error-msg"><i class="fas fa-exclamation-circle"></i> Username already exists! Please choose a different one.</div>';
    } else {
        $stmt = $pdo->prepare("UPDATE $tableName SET username = ? WHERE id = ?");
        $stmt->execute([$newUsername, $id]);
        $message = '<div class="success-msg"><i class="fas fa-check-circle"></i> Username changed successfully! New username: ' . htmlspecialchars($newUsername) . '</div>';
        logActivity($pdo, 'update', "Changed username for $type ID: $id to: $newUsername", $_SESSION['admin_username'] ?? 'admin', 'user_access', $id);
    }
}

$userType = isset($_GET['type']) ? sanitize($_GET['type']) : 'all';

$students = $pdo->query("
    SELECT s.id, s.name, s.username, s.email, s.class_id, c.class_name, c.section, 'student' as user_type
    FROM students s
    LEFT JOIN classes c ON s.class_id = c.id
    ORDER BY s.name
")->fetchAll();

$parents = $pdo->query("
    SELECT p.id, p.name, p.username, p.email, p.phone, p.student_id, s.name as student_name, 'parent' as user_type
    FROM parents p
    LEFT JOIN students s ON p.student_id = s.id
    ORDER BY p.name
")->fetchAll();

$teachers = $pdo->query("
    SELECT t.id, t.name, t.username, t.email, t.phone, 'teacher' as user_type
    FROM teachers t
    ORDER BY t.name
")->fetchAll();

$admins = $pdo->query("
    SELECT a.id, a.username, a.email, a.role, 'admin' as user_type
    FROM admins a
    ORDER BY a.username
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Access - School Management System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        .access-container {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .access-tabs {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .access-tab {
            padding: 12px 20px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .access-tab:hover {
            transform: translateY(-2px);
        }
        .access-tab.all { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
        .access-tab.students { background: linear-gradient(135deg, #4a90e2 0%, #357abd 100%); color: white; }
        .access-tab.parents { background: linear-gradient(135deg, #27ae60 0%, #1e8449 100%); color: white; }
        .access-tab.teachers { background: linear-gradient(135deg, #f39c12 0%, #d35400 100%); color: white; }
        .access-tab.admins { background: linear-gradient(135deg, #9b59b6 0%, #7d3c98 100%); color: white; }
        .access-tab.active { box-shadow: 0 4px 15px rgba(0,0,0,0.3); }
        
        .user-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            display: grid;
            grid-template-columns: auto 1fr auto;
            gap: 20px;
            align-items: center;
            transition: all 0.3s;
        }
        .user-card:hover {
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }
        .user-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            color: white;
        }
        .user-icon.student { background: linear-gradient(135deg, #4a90e2, #357abd); }
        .user-icon.parent { background: linear-gradient(135deg, #27ae60, #1e8449); }
        .user-icon.teacher { background: linear-gradient(135deg, #f39c12, #d35400); }
        .user-icon.admin { background: linear-gradient(135deg, #9b59b6, #7d3c98); }
        
        .user-info h3 {
            margin: 0 0 5px 0;
            color: var(--dark);
        }
        .user-info p {
            margin: 0;
            color: var(--gray);
            font-size: 13px;
        }
        .user-info .credentials {
            margin-top: 8px;
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }
        .credential-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 5px 12px;
            background: var(--light);
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .credential-badge i {
            color: var(--primary);
        }
        
        .user-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        
        .action-form {
            display: none;
            background: var(--light);
            padding: 15px;
            border-radius: 10px;
            margin-top: 15px;
            border: 2px solid var(--primary);
        }
        .action-form.active {
            display: block;
        }
        .action-form input {
            padding: 10px;
            border: 2px solid #ddd;
            border-radius: 8px;
            margin-right: 10px;
            width: 200px;
        }
        .action-form button {
            padding: 10px 15px;
        }
        
        .username-display {
            display: flex;
            align-items: center;
            gap: 10px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 10px 20px;
            border-radius: 25px;
            font-weight: 600;
            font-size: 16px;
        }
        .username-display i {
            font-size: 18px;
        }
        
        .password-display {
            background: #27ae60;
            color: white;
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 13px;
        }
        
        .credentials-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-top: 15px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 10px;
        }
        .credential-item {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .credential-item label {
            font-size: 12px;
            color: var(--gray);
            min-width: 70px;
        }
        
        .search-box {
            margin-bottom: 20px;
        }
        .search-box input {
            width: 100%;
            max-width: 400px;
            padding: 12px 15px 12px 45px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 14px;
        }
        .search-wrapper {
            position: relative;
        }
        .search-wrapper i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray);
        }
        
        .print-btn {
            padding: 12px 25px;
            background: linear-gradient(135deg, #2c3e50, #34495e);
            color: white;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .print-btn:hover {
            background: linear-gradient(135deg, #34495e, #2c3e50);
        }
        
        .stats-bar {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            gap: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .stat-card i {
            font-size: 30px;
            width: 60px;
            height: 60px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }
        .stat-card .stat-info h4 {
            margin: 0;
            font-size: 24px;
            color: var(--dark);
        }
        .stat-card .stat-info p {
            margin: 0;
            color: var(--gray);
            font-size: 13px;
        }
        
        .count-badge {
            background: rgba(255,255,255,0.3);
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 12px;
        }
        
        @media print {
            .sidebar, .header, .access-tabs, .user-actions, .search-box, .print-btn, .reset-form { display: none !important; }
            .user-card { box-shadow: none; border: 1px solid #ddd; page-break-inside: avoid; }
            .main-content { margin: 0 !important; padding: 20px !important; }
            .school-header { display: block !important; text-align: center; margin-bottom: 20px; }
        }
        
        @media (max-width: 768px) {
            .user-card { grid-template-columns: 1fr; text-align: center; }
            .user-actions { justify-content: center; flex-wrap: wrap; }
        }
    </style>
</head>
<body>
    <button class="mobile-menu-toggle"><i class="fas fa-bars"></i></button>
    <div class="sidebar-overlay"></div>
    <nav class="sidebar">
        <div class="sidebar-brand"><i class="fas fa-graduation-cap"></i> School Admin</div>
        <ul class="sidebar-menu">
            <li><a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
            <li><a href="students.php"><i class="fas fa-user-graduate"></i> Students</a></li>
            <li><a href="parents.php"><i class="fas fa-users"></i> Parents</a></li>
            <li><a href="teachers.php"><i class="fas fa-chalkboard-teacher"></i> Teachers</a></li>
            <li><a href="subjects.php"><i class="fas fa-book"></i> Subjects</a></li>
            <li><a href="classes.php"><i class="fas fa-school"></i> Classes</a></li>
            <li><a href="attendance.php"><i class="fas fa-calendar-check"></i> Attendance</a></li>
            <li><a href="timetable.php"><i class="fas fa-clock"></i> Timetable</a></li>
            <li><a href="results.php"><i class="fas fa-chart-line"></i> Results</a></li>
            <li><a href="reportcards.php"><i class="fas fa-file-alt"></i> Report Cards</a></li>
            <li><a href="calendar.php"><i class="fas fa-calendar-alt"></i> Calendar</a></li>
            <li><a href="announcements.php"><i class="fas fa-bullhorn"></i> Announcements</a></li>
            <li><a href="fees.php"><i class="fas fa-money-bill"></i> School Fees</a></li>
            <li><a href="chatbot.php"><i class="fas fa-robot"></i> AI Chatbot</a></li>
            <li><a href="chatbot_qa.php"><i class="fas fa-database"></i> Chatbot Q&A</a></li>
            <li><a href="lessons.php"><i class="fas fa-book-open"></i> Lessons</a></li>
            <li><a href="history.php"><i class="fas fa-history"></i> History</a></li>
            <li><a href="admins.php"><i class="fas fa-user-shield"></i> Manage Admins</a></li>
            <li><a href="validate.php"><i class="fas fa-clipboard-check"></i> Validate System</a></li>
            <li><a href="user_access.php" class="active"><i class="fas fa-key"></i> User Access</a></li>
            <li><a href="reset_password.php"><i class="fas fa-key"></i> Reset Password</a></li>
            <li><a href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
            <li><a href="../logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </nav>
    
    <main class="main-content">
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
            <h1><i class="fas fa-key"></i> User Access & Credentials</h1>
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
                <a href="settings.php"><i class="fas fa-cog"></i></a>
                <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
        
        <div class="content">
            <?php echo $message; ?>
            
            <div class="stats-bar">
                <div class="stat-card">
                    <i class="fas fa-user-graduate" style="background: linear-gradient(135deg, #4a90e2, #357abd);"></i>
                    <div class="stat-info">
                        <h4><?php echo count($students); ?></h4>
                        <p>Students</p>
                    </div>
                </div>
                <div class="stat-card">
                    <i class="fas fa-users" style="background: linear-gradient(135deg, #27ae60, #1e8449);"></i>
                    <div class="stat-info">
                        <h4><?php echo count($parents); ?></h4>
                        <p>Parents</p>
                    </div>
                </div>
                <div class="stat-card">
                    <i class="fas fa-chalkboard-teacher" style="background: linear-gradient(135deg, #f39c12, #d35400);"></i>
                    <div class="stat-info">
                        <h4><?php echo count($teachers); ?></h4>
                        <p>Teachers</p>
                    </div>
                </div>
                <div class="stat-card">
                    <i class="fas fa-user-shield" style="background: linear-gradient(135deg, #9b59b6, #7d3c98);"></i>
                    <div class="stat-info">
                        <h4><?php echo count($admins); ?></h4>
                        <p>Admins</p>
                    </div>
                </div>
            </div>
            
            <div class="access-container">
                <div class="access-tabs">
                    <a href="?type=all" class="access-tab all <?php echo $userType == 'all' ? 'active' : ''; ?>">
                        <i class="fas fa-users"></i> All Users <span class="count-badge"><?php echo count($students) + count($parents) + count($teachers) + count($admins); ?></span>
                    </a>
                    <a href="?type=student" class="access-tab students <?php echo $userType == 'student' ? 'active' : ''; ?>">
                        <i class="fas fa-user-graduate"></i> Students
                    </a>
                    <a href="?type=parent" class="access-tab parents <?php echo $userType == 'parent' ? 'active' : ''; ?>">
                        <i class="fas fa-users"></i> Parents
                    </a>
                    <a href="?type=teacher" class="access-tab teachers <?php echo $userType == 'teacher' ? 'active' : ''; ?>">
                        <i class="fas fa-chalkboard-teacher"></i> Teachers
                    </a>
                    <a href="?type=admin" class="access-tab admins <?php echo $userType == 'admin' ? 'active' : ''; ?>">
                        <i class="fas fa-user-shield"></i> Admins
                    </a>
                </div>
                <button onclick="window.print()" class="print-btn">
                    <i class="fas fa-print"></i> Print List
                </button>
            </div>
            
            <div class="search-box">
                <div class="search-wrapper">
                    <i class="fas fa-search"></i>
                    <input type="text" id="searchInput" placeholder="Search by name or username..." onkeyup="filterUsers()">
                </div>
            </div>
            
            <?php if ($userType == 'all' || $userType == 'student'): ?>
            <?php if ($userType == 'all'): ?>
            <h2 style="margin: 25px 0 15px; display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-user-graduate" style="color: #4a90e2;"></i> Students
            </h2>
            <?php endif; ?>
            <?php foreach ($students as $student): ?>
            <div class="user-card" data-name="<?php echo strtolower($student['name']); ?>" data-username="<?php echo strtolower($student['username']); ?>">
                <div class="user-icon student">
                    <i class="fas fa-user-graduate"></i>
                </div>
                <div class="user-info">
                    <h3><?php echo htmlspecialchars($student['name']); ?></h3>
                    <p>
                        <?php if ($student['class_name']): ?>
                        <i class="fas fa-school"></i> <?php echo $student['class_name']; ?> - Section <?php echo $student['section']; ?>
                        <?php else: ?>
                        <em style="color: #e74c3c;">No class assigned</em>
                        <?php endif; ?>
                    </p>
                    <div class="credentials-grid">
                        <div class="credential-item">
                            <label><i class="fas fa-user" style="color: #667eea;"></i> Username:</label>
                            <span class="username-display"><?php echo htmlspecialchars($student['username'] ?: 'Not set'); ?></span>
                        </div>
                        <div class="credential-item">
                            <label><i class="fas fa-lock" style="color: #27ae60;"></i> Password:</label>
                            <span class="password-display">12345678</span>
                        </div>
                    </div>
                </div>
                <div class="user-actions">
                    <button class="btn btn-sm btn-primary" onclick="toggleForm('username', 'student', <?php echo $student['id']; ?>)">
                        <i class="fas fa-edit"></i> Username
                    </button>
                    <button class="btn btn-sm btn-success" onclick="toggleForm('password', 'student', <?php echo $student['id']; ?>)">
                        <i class="fas fa-key"></i> Password
                    </button>
                    
                    <div id="username-student-<?php echo $student['id']; ?>" class="action-form">
                        <form method="POST" style="display: flex; align-items: center; flex-wrap: wrap; gap: 10px;">
                            <input type="hidden" name="type" value="student">
                            <input type="hidden" name="id" value="<?php echo $student['id']; ?>">
                            <input type="text" name="new_username" value="<?php echo htmlspecialchars($student['username']); ?>" required placeholder="New username">
                            <button type="submit" name="change_username" class="btn btn-success btn-sm">
                                <i class="fas fa-check"></i> Save
                            </button>
                            <button type="button" class="btn btn-secondary btn-sm" onclick="toggleForm('username', 'student', <?php echo $student['id']; ?>)">
                                <i class="fas fa-times"></i>
                            </button>
                        </form>
                    </div>
                    
                    <div id="password-student-<?php echo $student['id']; ?>" class="action-form">
                        <form method="POST" style="display: flex; align-items: center; flex-wrap: wrap; gap: 10px;">
                            <input type="hidden" name="type" value="student">
                            <input type="hidden" name="id" value="<?php echo $student['id']; ?>">
                            <input type="text" name="new_password" placeholder="New password" required>
                            <button type="submit" name="reset_password" class="btn btn-success btn-sm">
                                <i class="fas fa-check"></i> Save
                            </button>
                            <button type="button" class="btn btn-secondary btn-sm" onclick="toggleForm('password', 'student', <?php echo $student['id']; ?>)">
                                <i class="fas fa-times"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
            
            <?php if ($userType == 'all' || $userType == 'parent'): ?>
            <?php if ($userType == 'all'): ?>
            <h2 style="margin: 25px 0 15px; display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-users" style="color: #27ae60;"></i> Parents
            </h2>
            <?php endif; ?>
            <?php foreach ($parents as $parent): ?>
            <div class="user-card" data-name="<?php echo strtolower($parent['name']); ?>" data-username="<?php echo strtolower($parent['username']); ?>">
                <div class="user-icon parent">
                    <i class="fas fa-users"></i>
                </div>
                <div class="user-info">
                    <h3><?php echo htmlspecialchars($parent['name']); ?></h3>
                    <p>
                        <i class="fas fa-child"></i> Child: <?php echo htmlspecialchars($parent['student_name'] ?: 'Not linked'); ?>
                        <?php if ($parent['phone']): ?>
                        | <i class="fas fa-phone"></i> <?php echo $parent['phone']; ?>
                        <?php endif; ?>
                    </p>
                    <div class="credentials-grid">
                        <div class="credential-item">
                            <label><i class="fas fa-user" style="color: #667eea;"></i> Username:</label>
                            <span class="username-display"><?php echo htmlspecialchars($parent['username']); ?></span>
                        </div>
                        <div class="credential-item">
                            <label><i class="fas fa-lock" style="color: #27ae60;"></i> Password:</label>
                            <span class="password-display">12345678</span>
                        </div>
                    </div>
                </div>
                <div class="user-actions">
                    <button class="btn btn-sm btn-primary" onclick="toggleForm('username', 'parent', <?php echo $parent['id']; ?>)">
                        <i class="fas fa-edit"></i> Username
                    </button>
                    <button class="btn btn-sm btn-success" onclick="toggleForm('password', 'parent', <?php echo $parent['id']; ?>)">
                        <i class="fas fa-key"></i> Password
                    </button>
                    
                    <div id="username-parent-<?php echo $parent['id']; ?>" class="action-form">
                        <form method="POST" style="display: flex; align-items: center; flex-wrap: wrap; gap: 10px;">
                            <input type="hidden" name="type" value="parent">
                            <input type="hidden" name="id" value="<?php echo $parent['id']; ?>">
                            <input type="text" name="new_username" value="<?php echo htmlspecialchars($parent['username']); ?>" required placeholder="New username">
                            <button type="submit" name="change_username" class="btn btn-success btn-sm">
                                <i class="fas fa-check"></i> Save
                            </button>
                            <button type="button" class="btn btn-secondary btn-sm" onclick="toggleForm('username', 'parent', <?php echo $parent['id']; ?>)">
                                <i class="fas fa-times"></i>
                            </button>
                        </form>
                    </div>
                    
                    <div id="password-parent-<?php echo $parent['id']; ?>" class="action-form">
                        <form method="POST" style="display: flex; align-items: center; flex-wrap: wrap; gap: 10px;">
                            <input type="hidden" name="type" value="parent">
                            <input type="hidden" name="id" value="<?php echo $parent['id']; ?>">
                            <input type="text" name="new_password" placeholder="New password" required>
                            <button type="submit" name="reset_password" class="btn btn-success btn-sm">
                                <i class="fas fa-check"></i> Save
                            </button>
                            <button type="button" class="btn btn-secondary btn-sm" onclick="toggleForm('password', 'parent', <?php echo $parent['id']; ?>)">
                                <i class="fas fa-times"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
            
            <?php if ($userType == 'all' || $userType == 'teacher'): ?>
            <?php if ($userType == 'all'): ?>
            <h2 style="margin: 25px 0 15px; display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-chalkboard-teacher" style="color: #f39c12;"></i> Teachers
            </h2>
            <?php endif; ?>
            <?php foreach ($teachers as $teacher): ?>
            <div class="user-card" data-name="<?php echo strtolower($teacher['name']); ?>" data-username="<?php echo strtolower($teacher['username']); ?>">
                <div class="user-icon teacher">
                    <i class="fas fa-chalkboard-teacher"></i>
                </div>
                <div class="user-info">
                    <h3><?php echo htmlspecialchars($teacher['name']); ?></h3>
                    <p>
                        <?php if ($teacher['email']): ?>
                        <i class="fas fa-envelope"></i> <?php echo $teacher['email']; ?>
                        <?php endif; ?>
                        <?php if ($teacher['phone']): ?>
                        | <i class="fas fa-phone"></i> <?php echo $teacher['phone']; ?>
                        <?php endif; ?>
                    </p>
                    <div class="credentials-grid">
                        <div class="credential-item">
                            <label><i class="fas fa-user" style="color: #667eea;"></i> Username:</label>
                            <span class="username-display"><?php echo htmlspecialchars($teacher['username']); ?></span>
                        </div>
                        <div class="credential-item">
                            <label><i class="fas fa-lock" style="color: #27ae60;"></i> Password:</label>
                            <span class="password-display">12345678</span>
                        </div>
                    </div>
                </div>
                <div class="user-actions">
                    <button class="btn btn-sm btn-primary" onclick="toggleForm('username', 'teacher', <?php echo $teacher['id']; ?>)">
                        <i class="fas fa-edit"></i> Username
                    </button>
                    <button class="btn btn-sm btn-success" onclick="toggleForm('password', 'teacher', <?php echo $teacher['id']; ?>)">
                        <i class="fas fa-key"></i> Password
                    </button>
                    
                    <div id="username-teacher-<?php echo $teacher['id']; ?>" class="action-form">
                        <form method="POST" style="display: flex; align-items: center; flex-wrap: wrap; gap: 10px;">
                            <input type="hidden" name="type" value="teacher">
                            <input type="hidden" name="id" value="<?php echo $teacher['id']; ?>">
                            <input type="text" name="new_username" value="<?php echo htmlspecialchars($teacher['username']); ?>" required placeholder="New username">
                            <button type="submit" name="change_username" class="btn btn-success btn-sm">
                                <i class="fas fa-check"></i> Save
                            </button>
                            <button type="button" class="btn btn-secondary btn-sm" onclick="toggleForm('username', 'teacher', <?php echo $teacher['id']; ?>)">
                                <i class="fas fa-times"></i>
                            </button>
                        </form>
                    </div>
                    
                    <div id="password-teacher-<?php echo $teacher['id']; ?>" class="action-form">
                        <form method="POST" style="display: flex; align-items: center; flex-wrap: wrap; gap: 10px;">
                            <input type="hidden" name="type" value="teacher">
                            <input type="hidden" name="id" value="<?php echo $teacher['id']; ?>">
                            <input type="text" name="new_password" placeholder="New password" required>
                            <button type="submit" name="reset_password" class="btn btn-success btn-sm">
                                <i class="fas fa-check"></i> Save
                            </button>
                            <button type="button" class="btn btn-secondary btn-sm" onclick="toggleForm('password', 'teacher', <?php echo $teacher['id']; ?>)">
                                <i class="fas fa-times"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
            
            <?php if ($userType == 'all' || $userType == 'admin'): ?>
            <?php if ($userType == 'all'): ?>
            <h2 style="margin: 25px 0 15px; display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-user-shield" style="color: #9b59b6;"></i> Admins
            </h2>
            <?php endif; ?>
            <?php foreach ($admins as $admin): ?>
            <div class="user-card" data-name="<?php echo strtolower($admin['username']); ?>" data-username="<?php echo strtolower($admin['username']); ?>">
                <div class="user-icon admin">
                    <i class="fas fa-user-shield"></i>
                </div>
                <div class="user-info">
                    <h3><?php echo htmlspecialchars($admin['username']); ?></h3>
                    <p>
                        <i class="fas fa-shield-alt"></i> Role: <?php echo ucfirst($admin['role']); ?>
                        <?php if ($admin['email']): ?>
                        | <i class="fas fa-envelope"></i> <?php echo $admin['email']; ?>
                        <?php endif; ?>
                    </p>
                    <div class="credentials-grid">
                        <div class="credential-item">
                            <label><i class="fas fa-user" style="color: #667eea;"></i> Username:</label>
                            <span class="username-display"><?php echo htmlspecialchars($admin['username']); ?></span>
                        </div>
                        <div class="credential-item">
                            <label><i class="fas fa-lock" style="color: #27ae60;"></i> Password:</label>
                            <span class="password-display">********</span>
                        </div>
                    </div>
                </div>
                <div class="user-actions">
                    <?php if ($admin['id'] != $_SESSION['admin_id']): ?>
                    <button class="btn btn-sm btn-primary" onclick="toggleForm('username', 'admin', <?php echo $admin['id']; ?>)">
                        <i class="fas fa-edit"></i> Username
                    </button>
                    <button class="btn btn-sm btn-success" onclick="toggleForm('password', 'admin', <?php echo $admin['id']; ?>)">
                        <i class="fas fa-key"></i> Password
                    </button>
                    
                    <div id="username-admin-<?php echo $admin['id']; ?>" class="action-form">
                        <form method="POST" style="display: flex; align-items: center; flex-wrap: wrap; gap: 10px;">
                            <input type="hidden" name="type" value="admin">
                            <input type="hidden" name="id" value="<?php echo $admin['id']; ?>">
                            <input type="text" name="new_username" value="<?php echo htmlspecialchars($admin['username']); ?>" required placeholder="New username">
                            <button type="submit" name="change_username" class="btn btn-success btn-sm">
                                <i class="fas fa-check"></i> Save
                            </button>
                            <button type="button" class="btn btn-secondary btn-sm" onclick="toggleForm('username', 'admin', <?php echo $admin['id']; ?>)">
                                <i class="fas fa-times"></i>
                            </button>
                        </form>
                    </div>
                    
                    <div id="password-admin-<?php echo $admin['id']; ?>" class="action-form">
                        <form method="POST" style="display: flex; align-items: center; flex-wrap: wrap; gap: 10px;">
                            <input type="hidden" name="type" value="admin">
                            <input type="hidden" name="id" value="<?php echo $admin['id']; ?>">
                            <input type="text" name="new_password" placeholder="New password" required>
                            <button type="submit" name="reset_password" class="btn btn-success btn-sm">
                                <i class="fas fa-check"></i> Save
                            </button>
                            <button type="button" class="btn btn-secondary btn-sm" onclick="toggleForm('password', 'admin', <?php echo $admin['id']; ?>)">
                                <i class="fas fa-times"></i>
                            </button>
                        </form>
                    </div>
                    <?php else: ?>
                    <span style="color: #888; font-size: 12px; padding: 8px 15px; background: #f0f0f0; border-radius: 8px;">
                        <i class="fas fa-info-circle"></i> Current User
                    </span>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
            
            <div style="background: #f8f9fa; padding: 20px; border-radius: 10px; margin-top: 30px; border-left: 4px solid #4a90e2;">
                <h4 style="margin: 0 0 10px;"><i class="fas fa-info-circle"></i> Default Password Information</h4>
                <p style="margin: 0; color: #666; font-size: 14px;">
                    <strong>Students:</strong> Default password is <code>12345678</code><br>
                    <strong>Parents:</strong> Default password is <code>12345678</code><br>
                    <strong>Teachers:</strong> Default password is <code>12345678</code><br>
                    <strong>Admin:</strong> Default password is <code>admin</code><br>
                    <strong>Superadmin:</strong> Default password is <code>123456</code>
                </p>
                <p style="margin: 15px 0 0; color: #666; font-size: 13px;">
                    <i class="fas fa-lightbulb" style="color: #f39c12;"></i> 
                    Use the buttons to change username or reset password. New values will be shown after saving.
                </p>
            </div>
        </div>
    </main>
    
    <script src="../assets/js/script.js"></script>
    <script>
        function toggleForm(action, type, id) {
            document.querySelectorAll('.action-form').forEach(f => f.classList.remove('active'));
            const form = document.getElementById(action + '-' + type + '-' + id);
            if (form) {
                form.classList.toggle('active');
            }
        }
        
        function filterUsers() {
            const input = document.getElementById('searchInput');
            const filter = input.value.toLowerCase();
            const cards = document.querySelectorAll('.user-card');
            
            cards.forEach(card => {
                const name = card.getAttribute('data-name');
                const username = card.getAttribute('data-username');
                if (name.includes(filter) || username.includes(filter)) {
                    card.style.display = 'grid';
                } else {
                    card.style.display = 'none';
                }
            });
        }
    </script>
</body>
</html>
