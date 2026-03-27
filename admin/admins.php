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
$error = '';

if (isset($_POST['add_admin'])) {
    $username = sanitize(trim($_POST['username']));
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $email = sanitize(trim($_POST['email']));
    $role = sanitize($_POST['role']);
    
    if (empty($username) || empty($password) || empty($email)) {
        $error = 'All fields are required';
    } elseif (strlen($username) < 3) {
        $error = 'Username must be at least 3 characters';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format';
    } else {
        $checkStmt = $pdo->prepare("SELECT id FROM admins WHERE username = ? OR email = ?");
        $checkStmt->execute([$username, $email]);
        if ($checkStmt->fetch()) {
            $error = 'Username or email already exists';
        } else {
            $hashedPassword = md5($password);
            $stmt = $pdo->prepare("INSERT INTO admins (username, password, email, role) VALUES (?, ?, ?, ?)");
            $stmt->execute([$username, $hashedPassword, $email, $role]);
            $newId = $pdo->lastInsertId();
            logActivity($pdo, 'create', "Added new admin: $username ($role)", $_SESSION['admin_username'] ?? 'admin', 'admin', $newId);
            $message = '<div class="success-msg">Admin ' . htmlspecialchars($username) . ' added successfully!</div>';
        }
    }
}

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    if ($id == $_SESSION['admin_id']) {
        $error = 'You cannot delete your own account';
    } else {
        $stmt = $pdo->prepare("SELECT username, role FROM admins WHERE id = ?");
        $stmt->execute([$id]);
        $admin = $stmt->fetch();
        if ($admin) {
            if ($admin['role'] === 'superadmin') {
                $error = 'Cannot delete a Super Admin account. Contact your system administrator.';
            } else {
                $stmt = $pdo->prepare("DELETE FROM admins WHERE id = ?");
                $stmt->execute([$id]);
                logActivity($pdo, 'delete', "Deleted admin: " . $admin['username'] . " (ID: $id)", $_SESSION['admin_username'] ?? 'admin', 'admin', $id);
                $message = '<div class="success-msg">Admin deleted successfully!</div>';
            }
        }
    }
}

if (isset($_POST['reset_admin_password'])) {
    $id = (int)$_POST['id'];
    $newPassword = $_POST['new_password'];
    
    $stmt = $pdo->prepare("SELECT role FROM admins WHERE id = ?");
    $stmt->execute([$id]);
    $targetAdmin = $stmt->fetch();
    
    if ($targetAdmin && $targetAdmin['role'] === 'superadmin') {
        $error = 'Cannot reset password for Super Admin account.';
    } elseif (strlen($newPassword) < 6) {
        $error = 'Password must be at least 6 characters';
    } else {
        $hashedPassword = md5($newPassword);
        $stmt = $pdo->prepare("UPDATE admins SET password = ? WHERE id = ?");
        $stmt->execute([$hashedPassword, $id]);
        logActivity($pdo, 'update', "Reset password for admin ID: $id", $_SESSION['admin_username'] ?? 'admin', 'admin', $id);
        $message = '<div class="success-msg">Password reset successfully!</div>';
    }
}

$admins = $pdo->query("SELECT * FROM admins ORDER BY created_at DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Admins - School Management System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        .admin-cards { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; margin-top: 20px; }
        .admin-card { background: white; border-radius: 15px; padding: 20px; box-shadow: 0 5px 20px rgba(0,0,0,0.1); }
        .admin-card .header { display: flex; align-items: center; gap: 15px; margin-bottom: 15px; }
        .admin-card .avatar { width: 50px; height: 50px; border-radius: 50%; background: linear-gradient(135deg, #4a90e2, #357abd); display: flex; align-items: center; justify-content: center; color: white; font-size: 20px; font-weight: bold; }
        .admin-card .info h3 { margin: 0; font-size: 16px; color: #2c3e50; }
        .admin-card .info p { margin: 5px 0 0; font-size: 12px; color: #888; }
        .admin-card .badge { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 600; }
        .admin-card .badge.admin { background: #e8f4fd; color: #4a90e2; }
        .admin-card .badge.superadmin { background: #f3e5f5; color: #9b59b6; }
        .admin-card .actions { display: flex; gap: 10px; margin-top: 15px; }
        .admin-card .actions a, .admin-card .actions button { flex: 1; padding: 8px; text-align: center; border-radius: 8px; font-size: 12px; text-decoration: none; }
        .form-modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center; }
        .form-modal.active { display: flex; }
        .form-modal .content { background: white; padding: 30px; border-radius: 15px; max-width: 400px; width: 90%; }
    </style>
</head>
<body>
    <button class="mobile-menu-toggle">
        <i class="fas fa-bars"></i>
    </button>
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
            <li><a href="admins.php" class="active"><i class="fas fa-user-shield"></i> Manage Admins</a></li>
            <li><a href="validate.php"><i class="fas fa-clipboard-check"></i> Validate System</a></li>
            <li><a href="user_access.php"><i class="fas fa-key"></i> User Access</a></li>
            <li><a href="reset_password.php"><i class="fas fa-key"></i> Reset Password</a></li>
            <li><a href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
            <li><a href="../logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </nav>
    
    <main class="main-content">
        <div class="header">
            <h1><i class="fas fa-user-shield"></i> Manage Admins</h1>
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
            <?php if ($error): ?>
            <div class="error-msg" style="background: #fee; color: #c00; padding: 12px; border-radius: 10px; margin-bottom: 20px;">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
            <?php endif; ?>
            
            <?php echo $message; ?>
            
            <div style="margin-bottom: 20px;">
                <button class="btn btn-primary" onclick="document.getElementById('addModal').classList.add('active')">
                    <i class="fas fa-plus"></i> Add New Admin
                </button>
            </div>
            
            <div class="admin-cards">
                <?php foreach ($admins as $admin): ?>
                <div class="admin-card">
                    <div class="header">
                        <div class="avatar">
                            <?php echo strtoupper(substr($admin['username'], 0, 1)); ?>
                        </div>
                        <div class="info">
                            <h3><?php echo htmlspecialchars($admin['username']); ?></h3>
                            <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($admin['email']); ?></p>
                            <p><i class="fas fa-clock"></i> <?php echo date('d M Y', strtotime($admin['created_at'])); ?></p>
                        </div>
                    </div>
                    <span class="badge <?php echo $admin['role']; ?>"><?php echo ucfirst($admin['role']); ?></span>
                    <div class="actions">
                        <?php if ($admin['id'] != $_SESSION['admin_id']): ?>
                        <?php if ($admin['role'] !== 'superadmin'): ?>
                        <button class="btn btn-warning" onclick="openResetModal(<?php echo $admin['id']; ?>, '<?php echo htmlspecialchars($admin['username']); ?>')">
                            <i class="fas fa-key"></i> Reset
                        </button>
                        <a href="?delete=<?php echo $admin['id']; ?>" class="btn btn-danger delete-btn" onclick="return confirm('Delete this admin?')">
                            <i class="fas fa-trash"></i> Delete
                        </a>
                        <?php else: ?>
                        <span style="color: #9b59b6; font-size: 11px; display: flex; align-items: center; gap: 5px;">
                            <i class="fas fa-shield-alt"></i> Protected
                        </span>
                        <?php endif; ?>
                        <?php else: ?>
                        <span style="color: #888; font-size: 12px;"><i class="fas fa-info-circle"></i> Current User</span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </main>
    
    <div id="addModal" class="form-modal">
        <div class="content">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2 style="margin: 0;"><i class="fas fa-user-plus"></i> Add New Admin</h2>
                <button onclick="document.getElementById('addModal').classList.remove('active')" style="background: none; border: none; font-size: 24px; cursor: pointer;">&times;</button>
            </div>
            <form method="POST">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" required placeholder="Enter username" minlength="3">
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" required placeholder="Enter email">
                </div>
                <div class="form-group">
                    <label>Role</label>
                    <select name="role" required>
                        <option value="admin">Admin</option>
                        <option value="superadmin">Super Admin</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" required placeholder="Enter password" minlength="6">
                </div>
                <div class="form-group">
                    <label>Confirm Password</label>
                    <input type="password" name="confirm_password" required placeholder="Confirm password" minlength="6">
                </div>
                <button type="submit" name="add_admin" class="btn btn-primary" style="width: 100%;">
                    <i class="fas fa-save"></i> Add Admin
                </button>
            </form>
        </div>
    </div>
    
    <div id="resetModal" class="form-modal">
        <div class="content">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2 style="margin: 0;"><i class="fas fa-key"></i> Reset Password</h2>
                <button onclick="document.getElementById('resetModal').classList.remove('active')" style="background: none; border: none; font-size: 24px; cursor: pointer;">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="id" id="resetAdminId">
                <p style="margin-bottom: 15px;">Reset password for: <strong id="resetAdminName"></strong></p>
                <div class="form-group">
                    <label>New Password</label>
                    <input type="password" name="new_password" required placeholder="Enter new password" minlength="6">
                </div>
                <button type="submit" name="reset_admin_password" class="btn btn-warning" style="width: 100%;">
                    <i class="fas fa-save"></i> Reset Password
                </button>
            </form>
        </div>
    </div>
    
    <script src="../assets/js/script.js"></script>
    <script>
        function openResetModal(id, username) {
            document.getElementById('resetAdminId').value = id;
            document.getElementById('resetAdminName').textContent = username;
            document.getElementById('resetModal').classList.add('active');
        }
    </script>
</body>
</html>
