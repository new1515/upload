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

if (isset($_POST['add_teacher'])) {
    $name = sanitize($_POST['name']);
    $username = sanitize($_POST['username']);
    $password = !empty($_POST['password']) ? md5($_POST['password']) : md5('12345678');
    $email = sanitize($_POST['email']);
    $phone = sanitize($_POST['phone']);
    $subjects = isset($_POST['subjects']) ? $_POST['subjects'] : [];
    $classes = isset($_POST['classes']) ? $_POST['classes'] : [];
    
    $check = $pdo->prepare("SELECT id FROM teachers WHERE username = ?");
    $check->execute([$username]);
    if ($check->fetch()) {
        $message = '<div class="error-msg">Username already exists!</div>';
    } else {
        $stmt = $pdo->prepare("INSERT INTO teachers (name, username, password, email, phone) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$name, $username, $password, $email, $phone]);
        $teacherId = $pdo->lastInsertId();
        
        foreach ($subjects as $subjectId) {
            $stmt = $pdo->prepare("INSERT INTO teacher_subjects (teacher_id, subject_id) VALUES (?, ?)");
            $stmt->execute([$teacherId, $subjectId]);
        }
        
        foreach ($classes as $classId) {
            $stmt = $pdo->prepare("INSERT INTO teacher_classes (teacher_id, class_id) VALUES (?, ?)");
            $stmt->execute([$teacherId, $classId]);
        }
        
        logActivity($pdo, 'create', "Added new teacher: $name (Username: $username)", $_SESSION['admin_username'] ?? 'admin', 'teacher', $teacherId);
        $message = '<div class="success-msg">Teacher added successfully! Default password: 12345678</div>';
    }
}

if (isset($_POST['update_teacher'])) {
    $id = sanitize($_POST['id']);
    $name = sanitize($_POST['name']);
    $username = sanitize($_POST['username']);
    $email = sanitize($_POST['email']);
    $phone = sanitize($_POST['phone']);
    $subjects = isset($_POST['subjects']) ? $_POST['subjects'] : [];
    $classes = isset($_POST['classes']) ? $_POST['classes'] : [];
    
    if (!empty($_POST['password'])) {
        $password = md5($_POST['password']);
        $stmt = $pdo->prepare("UPDATE teachers SET name = ?, username = ?, password = ?, email = ?, phone = ? WHERE id = ?");
        $stmt->execute([$name, $username, $password, $email, $phone, $id]);
    } else {
        $stmt = $pdo->prepare("UPDATE teachers SET name = ?, username = ?, email = ?, phone = ? WHERE id = ?");
        $stmt->execute([$name, $username, $email, $phone, $id]);
    }
    
    $pdo->prepare("DELETE FROM teacher_subjects WHERE teacher_id = ?")->execute([$id]);
    foreach ($subjects as $subjectId) {
        $stmt = $pdo->prepare("INSERT INTO teacher_subjects (teacher_id, subject_id) VALUES (?, ?)");
        $stmt->execute([$id, $subjectId]);
    }
    
    $pdo->prepare("DELETE FROM teacher_classes WHERE teacher_id = ?")->execute([$id]);
    foreach ($classes as $classId) {
        $stmt = $pdo->prepare("INSERT INTO teacher_classes (teacher_id, class_id) VALUES (?, ?)");
        $stmt->execute([$id, $classId]);
    }
    
    logActivity($pdo, 'update', "Updated teacher: $name (ID: $id)", $_SESSION['admin_username'] ?? 'admin', 'teacher', $id);
    $message = '<div class="success-msg">Teacher updated successfully!</div>';
}

if (isset($_GET['reset_password'])) {
    $id = sanitize($_GET['reset_password']);
    $stmt = $pdo->prepare("SELECT name FROM teachers WHERE id = ?");
    $stmt->execute([$id]);
    $teacher = $stmt->fetch();
    $teacherName = $teacher['name'] ?? 'Unknown';
    $pdo->prepare("UPDATE teachers SET password = ? WHERE id = ?")->execute([md5('12345678'), $id]);
    logActivity($pdo, 'update', "Reset password for teacher: $teacherName (ID: $id)", $_SESSION['admin_username'] ?? 'admin', 'teacher', $id);
    $message = '<div class="success-msg">Password reset to: 12345678</div>';
}

if (isset($_POST['add_subject_to_teacher'])) {
    $teacherId = sanitize($_POST['teacher_id']);
    $subjectId = sanitize($_POST['subject_id']);
    
    try {
        $stmt = $pdo->prepare("INSERT INTO teacher_subjects (teacher_id, subject_id) VALUES (?, ?)");
        $stmt->execute([$teacherId, $subjectId]);
        $message = '<div class="success-msg">Subject added to teacher!</div>';
    } catch (Exception $e) {
        $message = '<div class="error-msg">Subject already assigned!</div>';
    }
}

if (isset($_POST['add_class_to_teacher'])) {
    $teacherId = sanitize($_POST['teacher_id']);
    $classId = sanitize($_POST['class_id']);
    
    try {
        $stmt = $pdo->prepare("INSERT INTO teacher_classes (teacher_id, class_id) VALUES (?, ?)");
        $stmt->execute([$teacherId, $classId]);
        $message = '<div class="success-msg">Class added to teacher!</div>';
    } catch (Exception $e) {
        $message = '<div class="error-msg">Class already assigned!</div>';
    }
}

if (isset($_GET['remove_subject'])) {
    $id = sanitize($_GET['remove_subject']);
    $pdo->prepare("DELETE FROM teacher_subjects WHERE id = ?")->execute([$id]);
    $message = '<div class="success-msg">Subject removed from teacher!</div>';
}

if (isset($_GET['remove_class'])) {
    $id = sanitize($_GET['remove_class']);
    $pdo->prepare("DELETE FROM teacher_classes WHERE id = ?")->execute([$id]);
    $message = '<div class="success-msg">Class removed from teacher!</div>';
}

if (isset($_GET['delete'])) {
    $id = sanitize($_GET['delete']);
    $stmt = $pdo->prepare("SELECT name FROM teachers WHERE id = ?");
    $stmt->execute([$id]);
    $teacher = $stmt->fetch();
    $teacherName = $teacher['name'] ?? 'Unknown';
    $pdo->prepare("DELETE FROM teacher_subjects WHERE teacher_id = ?")->execute([$id]);
    $pdo->prepare("DELETE FROM teacher_classes WHERE teacher_id = ?")->execute([$id]);
    $pdo->prepare("DELETE FROM teachers WHERE id = ?")->execute([$id]);
    logActivity($pdo, 'delete', "Deleted teacher: $teacherName (ID: $id)", $_SESSION['admin_username'] ?? 'admin', 'teacher', $id);
    $message = '<div class="success-msg">Teacher deleted successfully!</div>';
}

$teachers = $pdo->query("SELECT * FROM teachers ORDER BY name")->fetchAll();

$teacherSubjects = [];
foreach ($teachers as $t) {
    $stmt = $pdo->prepare("SELECT ts.id as ts_id, s.* FROM teacher_subjects ts 
                            JOIN subjects s ON ts.subject_id = s.id 
                            WHERE ts.teacher_id = ?");
    $stmt->execute([$t['id']]);
    $teacherSubjects[$t['id']] = $stmt->fetchAll();
}

$teacherClasses = [];
foreach ($teachers as $t) {
    $stmt = $pdo->prepare("SELECT tc.id as tc_id, c.* FROM teacher_classes tc 
                            JOIN classes c ON tc.class_id = c.id 
                            WHERE tc.teacher_id = ?");
    $stmt->execute([$t['id']]);
    $teacherClasses[$t['id']] = $stmt->fetchAll();
}

$subjects = $pdo->query("SELECT * FROM subjects ORDER BY subject_name")->fetchAll();
$classes = $pdo->query("SELECT * FROM classes ORDER BY level, class_name, section")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Teachers - School Management System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        .teacher-card {
            background: var(--white);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 5px 20px var(--shadow);
        }
        .teacher-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        .teacher-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .teacher-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: var(--primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }
        .teacher-details h3 {
            margin-bottom: 3px;
            color: var(--dark);
        }
        .teacher-details p {
            color: var(--gray);
            font-size: 13px;
        }
        .teacher-actions {
            display: flex;
            gap: 10px;
        }
        .subjects-list {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 15px;
        }
        .subject-tag {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 15px;
            background: var(--light);
            border-radius: 20px;
            font-size: 13px;
        }
        .subject-tag .remove {
            color: var(--danger);
            cursor: pointer;
            font-size: 16px;
        }
        .subject-tag .remove:hover {
            color: #c0392b;
        }
        .add-subject-form {
            display: flex;
            gap: 10px;
            align-items: flex-end;
        }
        .add-subject-form select {
            flex: 1;
            padding: 10px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
        }
        .no-subjects {
            color: var(--gray);
            font-style: italic;
        }
        .btn-warning {
            background: var(--warning);
            color: var(--white);
        }
        .btn-warning:hover {
            background: #d68910;
            color: var(--white);
        }
        .teacher-card .teacher-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        .teacher-card .teacher-actions .btn {
            padding: 8px 12px;
            font-size: 13px;
        }
        .teacher-card {
            padding: 25px;
        }
        .add-subject-form {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        .add-subject-form select {
            flex: 1;
            padding: 10px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
        }
        .subject-tag .remove {
            color: var(--danger);
            cursor: pointer;
            font-size: 16px;
            margin-left: 5px;
        }
        .subject-tag .remove:hover {
            color: #c0392b;
        }
        @media (max-width: 768px) {
            .teacher-card > div[style*="grid"] {
                grid-template-columns: 1fr !important;
            }
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
            <li><a href="teachers.php" class="active"><i class="fas fa-chalkboard-teacher"></i> Teachers</a></li>
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
            <h1><i class="fas fa-chalkboard-teacher"></i> Manage Teachers</h1>
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
            <?php echo $message; ?>
            
            <div class="table-container" style="margin-bottom: 30px;">
                <div class="table-header">
                    <h2>All Teachers (<?php echo count($teachers); ?>)</h2>
                    <button class="btn btn-primary modal-trigger" data-modal="addModal">
                        <i class="fas fa-plus"></i> Add Teacher
                    </button>
                </div>
            </div>
            
            <?php if (empty($teachers)): ?>
            <div class="table-container" style="text-align: center; padding: 40px;">
                <i class="fas fa-chalkboard-teacher" style="font-size: 50px; color: #ddd;"></i>
                <p style="color: #666; margin-top: 15px;">No teachers added yet.</p>
            </div>
            <?php else: ?>
            
            <?php foreach ($teachers as $teacher): ?>
            <div class="teacher-card">
                <div class="teacher-header">
                    <div class="teacher-info">
                        <div class="teacher-avatar">
                            <i class="fas fa-user"></i>
                        </div>
                        <div class="teacher-details">
                            <h3><?php echo $teacher['name']; ?></h3>
                            <p>
                                <i class="fas fa-user-circle"></i> <strong>Username:</strong> <?php echo $teacher['username'] ?: '<em style="color:#e74c3c;">Not set</em>'; ?> |
                                <i class="fas fa-envelope"></i> <?php echo $teacher['email'] ?: 'No email'; ?> |
                                <i class="fas fa-phone"></i> <?php echo $teacher['phone'] ?: 'No phone'; ?>
                            </p>
                        </div>
                    </div>
                    <div class="teacher-actions">
                        <button class="btn btn-primary modal-trigger" data-modal="editModal<?php echo $teacher['id']; ?>">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                        <a href="?reset_password=<?php echo $teacher['id']; ?>" class="btn btn-warning" onclick="return confirm('Reset password to 12345678?');" title="Reset Password">
                            <i class="fas fa-key"></i>
                        </a>
                        <a href="?delete=<?php echo $teacher['id']; ?>" class="btn btn-danger delete-btn">
                            <i class="fas fa-trash"></i>
                        </a>
                    </div>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div>
                        <h4 style="margin-bottom: 10px; color: var(--dark);"><i class="fas fa-school"></i> Classes Assigned:</h4>
                        
                        <div class="subjects-list">
                            <?php 
                            $teacherCls = $teacherClasses[$teacher['id']] ?? [];
                            if (empty($teacherCls)): ?>
                                <span class="no-subjects">No classes assigned yet.</span>
                            <?php else: ?>
                                <?php foreach ($teacherCls as $tc): 
                                    $levelLabel = ['nursery' => 'Nursery', 'kg' => 'KG', 'primary' => 'Primary', 'jhs' => 'JHS'][$tc['level']] ?? $tc['level'];
                                ?>
                                <span class="subject-tag" style="background: rgba(74, 144, 226, 0.15);">
                                    <i class="fas fa-school"></i> <?php echo $levelLabel . ' ' . $tc['class_name'] . ' - ' . $tc['section']; ?>
                                    <a href="?remove_class=<?php echo $tc['tc_id']; ?>" class="remove" title="Remove class" style="color: #e74c3c;">
                                        <i class="fas fa-times-circle"></i>
                                    </a>
                                </span>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        
                        <form method="POST" class="add-subject-form">
                            <input type="hidden" name="teacher_id" value="<?php echo $teacher['id']; ?>">
                            <select name="class_id" required style="flex: 1; padding: 8px; border: 1px solid #ddd; border-radius: 5px;">
                                <option value="">Add class...</option>
                                <?php 
                                $assignedClassIds = array_column($teacherCls, 'id');
                                foreach ($classes as $c): 
                                    if (!in_array($c['id'], $assignedClassIds)):
                                        $levelLabel = ['nursery' => 'Nursery', 'kg' => 'KG', 'primary' => 'Primary', 'jhs' => 'JHS'][$c['level']] ?? $c['level'];
                                ?>
                                    <option value="<?php echo $c['id']; ?>"><?php echo $levelLabel . ' ' . $c['class_name'] . ' - Section ' . $c['section']; ?></option>
                                <?php endif; endforeach; ?>
                            </select>
                            <button type="submit" name="add_class_to_teacher" class="btn btn-primary" style="padding: 8px 15px;">
                                <i class="fas fa-plus"></i>
                            </button>
                        </form>
                    </div>
                    
                    <div>
                        <h4 style="margin-bottom: 10px; color: var(--dark);"><i class="fas fa-book"></i> Subjects Taught:</h4>
                        
                        <div class="subjects-list">
                            <?php 
                            $teacherSubs = $teacherSubjects[$teacher['id']] ?? [];
                            if (empty($teacherSubs)): ?>
                                <span class="no-subjects">No subjects assigned yet.</span>
                            <?php else: ?>
                                <?php foreach ($teacherSubs as $ts): ?>
                                <span class="subject-tag">
                                    <i class="fas fa-book"></i> <?php echo $ts['subject_name']; ?>
                                    <a href="?remove_subject=<?php echo $ts['ts_id']; ?>" class="remove" title="Remove subject">
                                        <i class="fas fa-times-circle"></i>
                                    </a>
                                </span>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        
                        <form method="POST" class="add-subject-form">
                            <input type="hidden" name="teacher_id" value="<?php echo $teacher['id']; ?>">
                            <select name="subject_id" required style="flex: 1; padding: 8px; border: 1px solid #ddd; border-radius: 5px;">
                                <option value="">Add subject...</option>
                                <?php 
                                $assignedSubIds = array_column($teacherSubs, 'id');
                                foreach ($subjects as $s): 
                                    if (!in_array($s['id'], $assignedSubIds)):
                                ?>
                                    <option value="<?php echo $s['id']; ?>"><?php echo $s['subject_name']; ?></option>
                                <?php endif; endforeach; ?>
                            </select>
                            <button type="submit" name="add_subject_to_teacher" class="btn btn-success" style="padding: 8px 15px;">
                                <i class="fas fa-plus"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div id="editModal<?php echo $teacher['id']; ?>" class="modal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3>Edit Teacher</h3>
                        <button class="modal-close">&times;</button>
                    </div>
                    <form method="POST">
                        <input type="hidden" name="id" value="<?php echo $teacher['id']; ?>">
                        <div class="form-group">
                            <label>Name *</label>
                            <input type="text" name="name" value="<?php echo $teacher['name']; ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Username *</label>
                            <input type="text" name="username" value="<?php echo $teacher['username'] ?? ''; ?>" required placeholder="Unique username">
                        </div>
                        <div class="form-group">
                            <label>New Password</label>
                            <input type="password" name="password" placeholder="Leave empty to keep current">
                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="email" value="<?php echo $teacher['email']; ?>">
                        </div>
                        <div class="form-group">
                            <label>Phone</label>
                            <input type="text" name="phone" value="<?php echo $teacher['phone']; ?>">
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-school"></i> Classes (Hold Ctrl/Cmd to select multiple) *</label>
                            <select name="classes[]" multiple required style="height: 100px;">
                                <?php 
                                $assignedClassIds = array_column($teacherCls, 'id');
                                foreach ($classes as $c): 
                                    $levelLabel = ['nursery' => 'Nursery', 'kg' => 'KG', 'primary' => 'Primary', 'jhs' => 'JHS'][$c['level']] ?? $c['level'];
                                ?>
                                    <option value="<?php echo $c['id']; ?>" <?php echo in_array($c['id'], $assignedClassIds) ? 'selected' : ''; ?>>
                                        <?php echo $levelLabel . ' ' . $c['class_name'] . ' - Section ' . $c['section']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-book"></i> Subjects (Hold Ctrl/Cmd to select multiple)</label>
                            <select name="subjects[]" multiple style="height: 100px;">
                                <?php 
                                $assignedSubIds = array_column($teacherSubs, 'id');
                                foreach ($subjects as $s): ?>
                                    <option value="<?php echo $s['id']; ?>" <?php echo in_array($s['id'], $assignedSubIds) ? 'selected' : ''; ?>>
                                        <?php echo $s['subject_name']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" name="update_teacher" class="btn btn-primary" style="width: 100%;">Update Teacher</button>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
            
            <?php endif; ?>
        </div>
    </main>
    
    <div id="addModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-chalkboard-teacher"></i> Add New Teacher</h3>
                <button class="modal-close">&times;</button>
            </div>
            <form method="POST">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label>Full Name *</label>
                        <input type="text" name="name" required placeholder="Enter teacher name">
                    </div>
                    <div class="form-group">
                        <label>Username *</label>
                        <input type="text" name="username" required placeholder="Unique username for login">
                    </div>
                    <div class="form-group">
                        <label>Password (leave blank for default: 12345678)</label>
                        <input type="password" name="password" placeholder="Enter password">
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" placeholder="teacher@school.edu">
                    </div>
                    <div class="form-group">
                        <label>Phone</label>
                        <input type="text" name="phone" placeholder="055-XXX-XXXX">
                    </div>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-school"></i> Classes Assigned (Hold Ctrl/Cmd to select multiple) *</label>
                    <select name="classes[]" multiple required style="height: 100px;">
                        <?php foreach ($classes as $c): 
                            $levelLabel = ['nursery' => 'Nursery', 'kg' => 'KG', 'primary' => 'Primary', 'jhs' => 'JHS'][$c['level']] ?? $c['level'];
                        ?>
                            <option value="<?php echo $c['id']; ?>"><?php echo $levelLabel . ' ' . $c['class_name'] . ' - Section ' . $c['section']; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <small style="color: #888;">Select the classes this teacher will teach</small>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-book"></i> Subjects Taught (Hold Ctrl/Cmd to select multiple)</label>
                    <select name="subjects[]" multiple style="height: 80px;">
                        <?php foreach ($subjects as $s): ?>
                            <option value="<?php echo $s['id']; ?>"><?php echo $s['subject_name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" name="add_teacher" class="btn btn-primary" style="width: 100%; margin-top: 10px;">
                    <i class="fas fa-plus"></i> Add Teacher
                </button>
            </form>
        </div>
    </div>
    
    <script src="../assets/js/script.js"></script>
</body>
</html>
