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
$error = '';

if (isset($_POST['add_announcement'])) {
    $title = sanitize($_POST['title']);
    $content = sanitize($_POST['content']);
    $target = sanitize($_POST['target_audience']);
    $priority = isset($_POST['priority']) ? 'high' : 'normal';
    $status = sanitize($_POST['status']);
    
    $stmt = $pdo->prepare("INSERT INTO announcements (title, content, target_audience, priority, status, create_by) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$title, $content, $target, $priority, $status, $_SESSION['admin_id']]);
    $newId = $pdo->lastInsertId();
    logActivity($pdo, 'create', "Created announcement: $title", $_SESSION['admin_username'] ?? 'admin', 'announcement', $newId);
    
    $message = '<div class="success-msg">Announcement published successfully!</div>';
}

if (isset($_GET['delete'])) {
    $id = sanitize($_GET['delete']);
    $stmt = $pdo->prepare("SELECT title FROM announcements WHERE id = ?");
    $stmt->execute([$id]);
    $announcement = $stmt->fetch();
    $title = $announcement['title'] ?? 'Unknown';
    $stmt = $pdo->prepare("DELETE FROM announcements WHERE id = ?");
    $stmt->execute([$id]);
    logActivity($pdo, 'delete', "Deleted announcement: $title (ID: $id)", $_SESSION['admin_username'] ?? 'admin', 'announcement', $id);
    $message = '<div class="success-msg">Announcement deleted successfully!</div>';
}

if (isset($_POST['update_announcement'])) {
    $id = sanitize($_POST['id']);
    $title = sanitize($_POST['title']);
    $content = sanitize($_POST['content']);
    $target = sanitize($_POST['target_audience']);
    $priority = isset($_POST['priority']) ? 'high' : 'normal';
    $status = sanitize($_POST['status']);
    
    $stmt = $pdo->prepare("UPDATE announcements SET title = ?, content = ?, target_audience = ?, priority = ?, status = ? WHERE id = ?");
    $stmt->execute([$title, $content, $target, $priority, $status, $id]);
    logActivity($pdo, 'update', "Updated announcement: $title (ID: $id)", $_SESSION['admin_username'] ?? 'admin', 'announcement', $id);
    $message = '<div class="success-msg">Announcement updated successfully!</div>';
}

$announcements = $pdo->query("SELECT a.*, admin.username as author_name FROM announcements a LEFT JOIN admins admin ON a.created_by = admin.id ORDER BY a.created_at DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Announcements - School Management System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        .announcement-form {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        .announcement-form h3 {
            margin: 0 0 20px 0;
            color: #2c3e50;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .announcement-form h3 i { color: #e74c3c; }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 15px;
        }
        .form-row.full { grid-template-columns: 1fr; }
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 15px 0;
        }
        .checkbox-group input[type="checkbox"] {
            width: 20px;
            height: 20px;
            accent-color: #28a745;
        }

        .announcement-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 15px;
            border-left: 5px solid #4a90e2;
            box-shadow: 0 3px 15px rgba(0,0,0,0.08);
            position: relative;
        }
        .announcement-card.important { border-left-color: #e74c3c; }
        .announcement-card.inactive { border-left-color: #95a5a6; opacity: 0.7; }
        .announcement-card h4 {
            margin: 0 0 10px 0;
            color: #2c3e50;
            font-size: 18px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .announcement-card h4 i { color: #4a90e2; }
        .announcement-card.important h4 i { color: #e74c3c; }
        .announcement-card .badge {
            font-size: 11px;
            padding: 3px 10px;
            border-radius: 15px;
            font-weight: normal;
        }
        .badge-all { background: #4a90e2; color: white; }
        .badge-parents { background: #f39c12; color: white; }
        .badge-teachers { background: #27ae60; color: white; }
        .badge-students { background: #e74c3c; color: white; }
        .badge-high { background: #c0392b; color: white; }
        .badge-active { background: #27ae60; color: white; }
        .badge-inactive { background: #95a5a6; color: white; }
        .announcement-card p {
            margin: 0 0 15px 0;
            color: #666;
            line-height: 1.6;
            font-size: 14px;
        }
        .announcement-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 15px;
            border-top: 1px solid #eee;
            font-size: 12px;
            color: #888;
        }
        .announcement-meta .author { color: #4a90e2; }
        .announcement-actions {
            display: flex;
            gap: 10px;
        }
        .announcement-actions a, .announcement-actions button {
            padding: 5px 12px;
            border-radius: 5px;
            font-size: 12px;
            text-decoration: none;
            cursor: pointer;
            border: none;
        }
        .announcement-actions .edit { background: #4a90e2; color: white; }
        .announcement-actions .delete { background: #e74c3c; color: white; }
        
        @media (max-width: 768px) {
            .form-row { grid-template-columns: 1fr; }
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
            <li><a href="announcements.php" class="active"><i class="fas fa-bullhorn"></i> Announcements</a></li>
            <li><a href="results.php"><i class="fas fa-chart-line"></i> Results</a></li>
            <li><a href="reportcards.php"><i class="fas fa-file-alt"></i> Report Cards</a></li>
            <li><a href="calendar.php"><i class="fas fa-calendar-alt"></i> Calendar</a></li>
            <li><a href="announcements.php"><i class="fas fa-bullhorn"></i> Announcements</a></li>
            <li><a href="fees.php"><i class="fas fa-money-bill"></i> School Fees</a></li>
            <li><a href="chatbot.php"><i class="fas fa-robot"></i> AI Chatbot</a></li>
            <li><a href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
            <li><a href="../logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </nav>
    
    <main class="main-content">
        <div class="header">
            <h1><i class="fas fa-bullhorn"></i> Announcements</h1>
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
                <h1><?php echo htmlspecialchars($schoolName); ?></h1>
                <p><?php echo htmlspecialchars($settings['school_tagline'] ?? ''); ?></p>
            </div>
        </div>
        
        <div class="content">
            <?php echo $message . $error; ?>
            
            <div class="announcement-form">
                <h3><i class="fas fa-plus-circle"></i> Publish New Announcement</h3>
                <form method="POST">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Title</label>
                            <input type="text" name="title" placeholder="Announcement title" required>
                        </div>
                        <div class="form-group">
                            <label>Target Audience</label>
                            <select name="target_audience" required>
                                <option value="all">All (Parents, Teachers, Students)</option>
                                <option value="parents">Parents Only</option>
                                <option value="teachers">Teachers Only</option>
                                <option value="students">Students Only</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Status</label>
                            <select name="status" required>
                                <option value="active">Active (Published)</option>
                                <option value="inactive">Inactive (Draft)</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-row full">
                        <div class="form-group">
                            <label>Content</label>
                            <textarea name="content" rows="4" placeholder="Write your announcement message here..." required></textarea>
                        </div>
                    </div>
                    <div class="checkbox-group">
                        <input type="checkbox" name="priority" id="priority">
                        <label for="priority"><strong>Mark as Important/High Priority</strong></label>
                    </div>
                    <button type="submit" name="add_announcement" class="btn btn-primary">
                        <i class="fas fa-paper-plane"></i> Publish Announcement
                    </button>
                </form>
            </div>
            
            <h3 style="margin: 25px 0 15px 0; color: #2c3e50;">
                <i class="fas fa-list" style="color: #4a90e2;"></i> All Announcements (<?php echo count($announcements); ?>)
            </h3>
            
            <?php if (empty($announcements)): ?>
            <div class="announcement-card">
                <p style="text-align: center; color: #999;">No announcements published yet.</p>
            </div>
            <?php else: ?>
                <?php foreach ($announcements as $ann): ?>
                <div class="announcement-card <?php echo $ann['priority'] == 'high' ? 'important' : ''; ?> <?php echo $ann['status'] == 'inactive' ? 'inactive' : ''; ?>">
                    <h4>
                        <i class="fas fa-bullhorn"></i>
                        <?php echo htmlspecialchars($ann['title']); ?>
                        <?php if ($ann['priority'] == 'high'): ?>
                        <span class="badge badge-high"><i class="fas fa-exclamation-circle"></i> Important</span>
                        <?php endif; ?>
                    </h4>
                    <span class="badge badge-<?php echo $ann['target_audience']; ?>">
                        <?php 
                        $targetLabels = ['all' => 'All', 'parents' => 'Parents', 'teachers' => 'Teachers', 'students' => 'Students'];
                        echo $targetLabels[$ann['target_audience']] ?? $ann['target_audience'];
                        ?>
                    </span>
                    <span class="badge badge-<?php echo $ann['status']; ?>">
                        <?php echo ucfirst($ann['status']); ?>
                    </span>
                    <p><?php echo nl2br(htmlspecialchars($ann['content'])); ?></p>
                    <div class="announcement-meta">
                        <div>
                            <i class="fas fa-user"></i> By <span class="author"><?php echo htmlspecialchars($ann['author_name'] ?? 'Admin'); ?></span>
                            | <i class="fas fa-clock"></i> <?php echo date('M d, Y g:i A', strtotime($ann['created_at'])); ?>
                        </div>
                        <div class="announcement-actions">
                            <button class="edit modal-trigger" data-modal="editModal<?php echo $ann['id']; ?>">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <a href="?delete=<?php echo $ann['id']; ?>" class="delete delete-btn" onclick="return confirm('Delete this announcement?')">
                                <i class="fas fa-trash"></i> Delete
                            </a>
                        </div>
                    </div>
                </div>
                
                <div id="editModal<?php echo $ann['id']; ?>" class="modal">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h3><i class="fas fa-edit"></i> Edit Announcement</h3>
                            <button class="modal-close">&times;</button>
                        </div>
                        <form method="POST">
                            <input type="hidden" name="id" value="<?php echo $ann['id']; ?>">
                            <div class="form-group">
                                <label>Title</label>
                                <input type="text" name="title" value="<?php echo htmlspecialchars($ann['title']); ?>" required>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Target Audience</label>
                                    <select name="target_audience" required>
                                        <option value="all" <?php echo $ann['target_audience'] == 'all' ? 'selected' : ''; ?>>All</option>
                                        <option value="parents" <?php echo $ann['target_audience'] == 'parents' ? 'selected' : ''; ?>>Parents Only</option>
                                        <option value="teachers" <?php echo $ann['target_audience'] == 'teachers' ? 'selected' : ''; ?>>Teachers Only</option>
                                        <option value="students" <?php echo $ann['target_audience'] == 'students' ? 'selected' : ''; ?>>Students Only</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Status</label>
                                    <select name="status" required>
                                        <option value="active" <?php echo $ann['status'] == 'active' ? 'selected' : ''; ?>>Active</option>
                                        <option value="inactive" <?php echo $ann['status'] == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Content</label>
                                <textarea name="content" rows="4" required><?php echo htmlspecialchars($ann['content']); ?></textarea>
                            </div>
                            <div class="checkbox-group">
                                <input type="checkbox" name="priority" id="edit_priority<?php echo $ann['id']; ?>" <?php echo $ann['priority'] == 'high' ? 'checked' : ''; ?>>
                                <label for="edit_priority<?php echo $ann['id']; ?>">Mark as Important</label>
                            </div>
                            <button type="submit" name="update_announcement" class="btn btn-primary" style="width: 100%;">
                                <i class="fas fa-save"></i> Update Announcement
                            </button>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>
    
    <script src="../assets/js/script.js"></script>
</body>
</html>

<?php
?>
