<?php
require_once '../config/database.php';

if (!isLoggedIn()) {
    redirect('../login.php');
}

$message = '';
$error = '';

if (isset($_POST['add_parent'])) {
    $name = sanitize($_POST['name']);
    $username = sanitize($_POST['username']);
    $email = sanitize($_POST['email']);
    $phone = sanitize($_POST['phone']);
    $password = !empty($_POST['password']) ? md5($_POST['password']) : md5('12345678');
    $student_id = sanitize($_POST['student_id']);
    $relationship = sanitize($_POST['relationship']);
    
    $check = $pdo->prepare("SELECT id FROM parents WHERE username = ?");
    $check->execute([$username]);
    if ($check->fetch()) {
        $error = '<div class="error-msg">Username already exists!</div>';
    } else {
        $stmt = $pdo->prepare("INSERT INTO parents (name, username, password, email, phone, student_id, relationship) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name, $username, $password, $email, $phone, $student_id, $relationship]);
        $newId = $pdo->lastInsertId();
        logActivity($pdo, 'create', "Added new parent: $name (Username: $username)", $_SESSION['admin_username'] ?? 'admin', 'parent', $newId);
        $message = '<div class="success-msg">Parent added successfully! Default password: 12345678</div>';
    }
}

if (isset($_GET['delete'])) {
    $id = sanitize($_GET['delete']);
    $stmt = $pdo->prepare("SELECT name FROM parents WHERE id = ?");
    $stmt->execute([$id]);
    $parent = $stmt->fetch();
    $parentName = $parent['name'] ?? 'Unknown';
    $stmt = $pdo->prepare("DELETE FROM parents WHERE id = ?");
    $stmt->execute([$id]);
    logActivity($pdo, 'delete', "Deleted parent: $parentName (ID: $id)", $_SESSION['admin_username'] ?? 'admin', 'parent', $id);
    $message = '<div class="success-msg">Parent deleted successfully!</div>';
}

if (isset($_GET['reset_password'])) {
    $id = sanitize($_GET['reset_password']);
    $stmt = $pdo->prepare("SELECT name FROM parents WHERE id = ?");
    $stmt->execute([$id]);
    $parent = $stmt->fetch();
    $parentName = $parent['name'] ?? 'Unknown';
    $pdo->prepare("UPDATE parents SET password = ? WHERE id = ?")->execute([md5('12345678'), $id]);
    logActivity($pdo, 'update', "Reset password for parent: $parentName (ID: $id)", $_SESSION['admin_username'] ?? 'admin', 'parent', $id);
    $message = '<div class="success-msg">Password reset to: 12345678</div>';
}

if (isset($_POST['update_parent'])) {
    $id = sanitize($_POST['id']);
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $phone = sanitize($_POST['phone']);
    $student_id = sanitize($_POST['student_id']);
    $relationship = sanitize($_POST['relationship']);
    
    if (!empty($_POST['password'])) {
        $password = md5($_POST['password']);
        $stmt = $pdo->prepare("UPDATE parents SET name = ?, email = ?, phone = ?, student_id = ?, relationship = ?, password = ? WHERE id = ?");
        $stmt->execute([$name, $email, $phone, $student_id, $relationship, $password, $id]);
    } else {
        $stmt = $pdo->prepare("UPDATE parents SET name = ?, email = ?, phone = ?, student_id = ?, relationship = ? WHERE id = ?");
        $stmt->execute([$name, $email, $phone, $student_id, $relationship, $id]);
    }
    logActivity($pdo, 'update', "Updated parent: $name (ID: $id)", $_SESSION['admin_username'] ?? 'admin', 'parent', $id);
    $message = '<div class="success-msg">Parent updated successfully!</div>';
}

$parents = $pdo->query("SELECT p.*, s.name as student_name, c.class_name FROM parents p LEFT JOIN students s ON p.student_id = s.id LEFT JOIN classes c ON s.class_id = c.id ORDER BY p.name")->fetchAll();
$students = $pdo->query("SELECT s.*, c.class_name FROM students s LEFT JOIN classes c ON s.class_id = c.id ORDER BY c.class_name, s.name")->fetchAll();

$settings = [];
$stmt = $pdo->query("SELECT * FROM school_settings");
while ($row = $stmt->fetch()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
$schoolName = $settings['school_name'] ?? 'School Management System';
$schoolLogo = $settings['school_logo'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Parents - School Management System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
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
            <li><a href="parents.php" class="active"><i class="fas fa-users"></i> Parents</a></li>
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
            <li><a href="history.php"><i class="fas fa-history"></i> History</a></li>
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
            <h1><i class="fas fa-users"></i> Manage Parents</h1>
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
                <h1><?php echo htmlspecialchars($schoolName); ?></h1>
                <p><?php echo htmlspecialchars($settings['school_tagline'] ?? ''); ?></p>
            </div>
        </div>
        
        <div class="content">
            <?php echo $error; ?>
            <?php echo $message; ?>
            
            <div class="table-container">
                <div class="table-header">
                    <h2>All Parents (<?php echo count($parents); ?>)</h2>
                    <button class="btn btn-primary modal-trigger" data-modal="addModal">
                        <i class="fas fa-plus"></i> Add Parent
                    </button>
                </div>
                
                <?php if (empty($parents)): ?>
                <div style="text-align: center; padding: 40px; color: #999;">
                    <i class="fas fa-users" style="font-size: 50px; margin-bottom: 15px;"></i>
                    <p>No parents added yet.</p>
                </div>
                <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Student</th>
                            <th>Class</th>
                            <th>Relationship</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($parents as $parent): ?>
                        <tr>
                            <td><?php echo $parent['id']; ?></td>
                            <td><?php echo htmlspecialchars($parent['name']); ?></td>
                            <td><?php echo htmlspecialchars($parent['username']); ?></td>
                            <td><?php echo htmlspecialchars($parent['email'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($parent['phone'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($parent['student_name'] ?? 'Not linked'); ?></td>
                            <td><?php echo htmlspecialchars($parent['class_name'] ?? '-'); ?></td>
                            <td><span class="badge"><?php echo ucfirst($parent['relationship'] ?? 'guardian'); ?></span></td>
                            <td class="action-btns">
                                <button class="edit modal-trigger" data-modal="editModal<?php echo $parent['id']; ?>">Edit</button>
                                <a href="?reset_password=<?php echo $parent['id']; ?>" class="reset-password-btn" onclick="return confirm('Reset password to 12345678?')" title="Reset Password">
                                    <i class="fas fa-key"></i>
                                </a>
                                <a href="?delete=<?php echo $parent['id']; ?>" class="delete delete-btn" onclick="return confirm('Delete this parent?')">Delete</a>
                            </td>
                        </tr>
                        
                        <div id="editModal<?php echo $parent['id']; ?>" class="modal">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h3>Edit Parent</h3>
                                    <button class="modal-close">&times;</button>
                                </div>
                                <form method="POST">
                                    <input type="hidden" name="id" value="<?php echo $parent['id']; ?>">
                                    <div class="form-group">
                                        <label>Name *</label>
                                        <input type="text" name="name" value="<?php echo htmlspecialchars($parent['name']); ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Username *</label>
                                        <input type="text" name="username" value="<?php echo htmlspecialchars($parent['username'] ?? ''); ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Email</label>
                                        <input type="email" name="email" value="<?php echo htmlspecialchars($parent['email'] ?? ''); ?>">
                                    </div>
                                    <div class="form-group">
                                        <label>Phone</label>
                                        <input type="text" name="phone" value="<?php echo htmlspecialchars($parent['phone'] ?? ''); ?>">
                                    </div>
                                    <div class="form-group">
                                        <label>Link Student</label>
                                        <select name="student_id">
                                            <option value="">-- Select Student --</option>
                                            <?php foreach ($students as $s): ?>
                                            <option value="<?php echo $s['id']; ?>" <?php echo $parent['student_id'] == $s['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($s['name'] . ' (' . ($s['class_name'] ?? 'No class') . ')'); ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Relationship</label>
                                        <select name="relationship">
                                            <option value="father" <?php echo ($parent['relationship'] ?? '') == 'father' ? 'selected' : ''; ?>>Father</option>
                                            <option value="mother" <?php echo ($parent['relationship'] ?? '') == 'mother' ? 'selected' : ''; ?>>Mother</option>
                                            <option value="guardian" <?php echo ($parent['relationship'] ?? '') == 'guardian' ? 'selected' : ''; ?>>Guardian</option>
                                            <option value="other" <?php echo ($parent['relationship'] ?? '') == 'other' ? 'selected' : ''; ?>>Other</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>New Password (leave blank to keep current)</label>
                                        <input type="password" name="password" placeholder="Enter new password">
                                    </div>
                                    <button type="submit" name="update_parent" class="btn btn-primary" style="width: 100%;">Update Parent</button>
                                </form>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>
    </main>
    
    <div id="addModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-user-plus"></i> Add New Parent</h3>
                <button class="modal-close">&times;</button>
            </div>
            <form method="POST">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="name" placeholder="Enter parent name" required>
                </div>
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" placeholder="Enter username for login" required>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" placeholder="Enter email address">
                </div>
                <div class="form-group">
                    <label>Phone</label>
                    <input type="text" name="phone" placeholder="Enter phone number">
                </div>
                <div class="form-group">
                    <label>Password (leave blank for default: 12345678)</label>
                    <input type="password" name="password" placeholder="Enter password" required minlength="6">
                </div>
                <div class="form-group">
                    <label>Link to Student</label>
                    <select name="student_id">
                        <option value="">-- Select Student --</option>
                        <?php foreach ($students as $s): ?>
                        <option value="<?php echo $s['id']; ?>">
                            <?php echo htmlspecialchars($s['name'] . ' (' . ($s['class_name'] ?? 'No class') . ')'); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Relationship</label>
                    <select name="relationship" required>
                        <option value="father">Father</option>
                        <option value="mother">Mother</option>
                        <option value="guardian" selected>Guardian</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <button type="submit" name="add_parent" class="btn btn-primary" style="width: 100%;">
                    <i class="fas fa-save"></i> Add Parent
                </button>
            </form>
        </div>
    </div>
    
    <script src="../assets/js/script.js"></script>
</body>
</html>
