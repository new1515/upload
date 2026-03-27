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
$teachers = $pdo->query("SELECT * FROM teachers ORDER BY name")->fetchAll();

if (isset($_POST['add_class'])) {
    $class_name = sanitize($_POST['class_name']);
    $section = sanitize($_POST['section']);
    $level = sanitize($_POST['level']);
    $class_teacher_id = !empty($_POST['class_teacher_id']) ? sanitize($_POST['class_teacher_id']) : NULL;
    
    $stmt = $pdo->prepare("INSERT INTO classes (class_name, section, level, class_teacher_id) VALUES (?, ?, ?, ?)");
    $stmt->execute([$class_name, $section, $level, $class_teacher_id]);
    $newId = $pdo->lastInsertId();
    logActivity($pdo, 'create', "Added new class: $class_name - Section $section", $_SESSION['admin_username'] ?? 'admin', 'class', $newId);
    $message = '<div class="success-msg">Class added successfully!</div>';
}

if (isset($_POST['update_class'])) {
    $id = sanitize($_POST['id']);
    $class_name = sanitize($_POST['class_name']);
    $section = sanitize($_POST['section']);
    $level = sanitize($_POST['level']);
    $class_teacher_id = !empty($_POST['class_teacher_id']) ? sanitize($_POST['class_teacher_id']) : NULL;
    
    $stmt = $pdo->prepare("UPDATE classes SET class_name = ?, section = ?, level = ?, class_teacher_id = ? WHERE id = ?");
    $stmt->execute([$class_name, $section, $level, $class_teacher_id, $id]);
    logActivity($pdo, 'update', "Updated class: $class_name - Section $section (ID: $id)", $_SESSION['admin_username'] ?? 'admin', 'class', $id);
    $message = '<div class="success-msg">Class updated successfully!</div>';
}

if (isset($_GET['delete'])) {
    $id = sanitize($_GET['delete']);
    $stmt = $pdo->prepare("SELECT class_name, section FROM classes WHERE id = ?");
    $stmt->execute([$id]);
    $class = $stmt->fetch();
    $className = ($class['class_name'] ?? 'Unknown') . ' - Section ' . ($class['section'] ?? '?');
    $stmt = $pdo->prepare("DELETE FROM classes WHERE id = ?");
    $stmt->execute([$id]);
    logActivity($pdo, 'delete', "Deleted class: $className (ID: $id)", $_SESSION['admin_username'] ?? 'admin', 'class', $id);
    $message = '<div class="success-msg">Class deleted successfully!</div>';
}

$classes = $pdo->query("SELECT c.*, COUNT(s.id) as student_count, t.name as class_teacher_name 
                        FROM classes c 
                        LEFT JOIN students s ON c.id = s.class_id 
                        LEFT JOIN teachers t ON c.class_teacher_id = t.id 
                        GROUP BY c.id 
                        ORDER BY c.class_name, c.section")->fetchAll();

$levelLabels = [
    'nursery' => 'Nursery',
    'kg' => 'Kindergarten (KG)',
    'primary' => 'Primary School',
    'jhs' => 'Junior High School (JHS)'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Classes - School Management System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        .class-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .class-card {
            background: #fff;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        .class-card:hover {
            transform: translateY(-5px);
        }
        .class-header {
            padding: 20px;
            color: #fff;
            text-align: center;
        }
        .class-header.primary { background: linear-gradient(135deg, #4a90e2, #357abd); }
        .class-header.jhs { background: linear-gradient(135deg, #27ae60, #2ecc71); }
        .class-header.nursery { background: linear-gradient(135deg, #e74c3c, #c0392b); }
        .class-header.kg { background: linear-gradient(135deg, #f39c12, #d35400); }
        .class-header h3 {
            font-size: 24px;
            margin-bottom: 5px;
        }
        .class-header span {
            font-size: 14px;
            opacity: 0.9;
        }
        .class-body {
            padding: 20px;
        }
        .class-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        .class-info div {
            text-align: center;
        }
        .class-info h4 {
            font-size: 22px;
            color: #2c3e50;
        }
        .class-info p {
            font-size: 12px;
            color: #666;
        }
        .class-actions {
            display: flex;
            gap: 10px;
        }
        .class-actions a, .class-actions button {
            flex: 1;
            padding: 10px;
            text-align: center;
            border-radius: 8px;
            text-decoration: none;
            font-size: 13px;
            transition: all 0.3s;
        }
        .class-actions .manage {
            background: #4a90e2;
            color: #fff;
        }
        .class-actions .scores {
            background: #27ae60;
            color: #fff;
        }
        .level-section {
            margin-bottom: 30px;
        }
        .level-section h2 {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
            color: #2c3e50;
        }
        .level-section h2 i {
            color: #4a90e2;
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
            <li><a href="classes.php" class="active"><i class="fas fa-school"></i> Classes</a></li>
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
            <li><a href="user_access.php"><i class="fas fa-key"></i> User Access</a></li>
            <li><a href="reset_password.php"><i class="fas fa-key"></i> Reset Password</a></li>
            <li><a href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
            <li><a href="../logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </nav>
    
    <main class="main-content">
        <div class="header">
            <h1><i class="fas fa-school"></i> Manage Classes</h1>
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
                <button class="btn btn-primary modal-trigger" data-modal="addModal">
                    <i class="fas fa-plus"></i> Add Class
                </button>
            </div>
            
            <?php
            $nurseryClasses = array_filter($classes, fn($c) => $c['level'] === 'nursery');
            $kgClasses = array_filter($classes, fn($c) => $c['level'] === 'kg');
            $primaryClasses = array_filter($classes, fn($c) => $c['level'] === 'primary');
            $jhsClasses = array_filter($classes, fn($c) => $c['level'] === 'jhs');
            ?>
            
            <?php if (!empty($nurseryClasses)): ?>
            <div class="level-section">
                <h2><i class="fas fa-baby"></i> Nursery (Nursery 1 - Nursery 2)</h2>
                <div class="class-grid">
                    <?php foreach ($nurseryClasses as $class): ?>
                    <div class="class-card">
                        <div class="class-header nursery">
                            <h3><?php echo $class['class_name']; ?> - Section <?php echo $class['section']; ?></h3>
                            <span><i class="fas fa-users"></i> <?php echo $class['student_count']; ?> Students</span>
                        </div>
                        <div class="class-body">
                            <div class="class-info">
                                <div><h4><?php echo $class['student_count']; ?></h4><p>Students</p></div>
                                <div><h4>5</h4><p>Subjects</p></div>
                            </div>
                            <div style="font-size: 12px; color: #666; margin-bottom: 10px; text-align: center;">
                                <i class="fas fa-chalkboard-teacher"></i> Class Teacher: <?php echo htmlspecialchars($class['class_teacher_name'] ?? 'Not assigned'); ?>
                            </div>
                            <div class="class-actions">
                                <a href="class_students.php?id=<?php echo $class['id']; ?>" class="manage"><i class="fas fa-users"></i> Students</a>
                                <a href="class_scores.php?id=<?php echo $class['id']; ?>" class="scores"><i class="fas fa-edit"></i> Scores</a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($kgClasses)): ?>
            <div class="level-section">
                <h2><i class="fas fa-child"></i> Kindergarten (KG 1 - KG 2)</h2>
                <div class="class-grid">
                    <?php foreach ($kgClasses as $class): ?>
                    <div class="class-card">
                        <div class="class-header kg">
                            <h3><?php echo $class['class_name']; ?> - Section <?php echo $class['section']; ?></h3>
                            <span><i class="fas fa-users"></i> <?php echo $class['student_count']; ?> Students</span>
                        </div>
                        <div class="class-body">
                            <div class="class-info">
                                <div><h4><?php echo $class['student_count']; ?></h4><p>Students</p></div>
                                <div><h4>5</h4><p>Subjects</p></div>
                            </div>
                            <div style="font-size: 12px; color: #666; margin-bottom: 10px; text-align: center;">
                                <i class="fas fa-chalkboard-teacher"></i> Class Teacher: <?php echo htmlspecialchars($class['class_teacher_name'] ?? 'Not assigned'); ?>
                            </div>
                            <div class="class-actions">
                                <a href="class_students.php?id=<?php echo $class['id']; ?>" class="manage"><i class="fas fa-users"></i> Students</a>
                                <a href="class_scores.php?id=<?php echo $class['id']; ?>" class="scores"><i class="fas fa-edit"></i> Scores</a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="level-section">
                <h2><i class="fas fa-book-reader"></i> Primary School (Basic 1 - Basic 6)</h2>
                <div class="class-grid">
                    <?php foreach ($primaryClasses as $class): ?>
                    <div class="class-card">
                        <div class="class-header primary">
                            <h3><?php echo $class['class_name']; ?> - Section <?php echo $class['section']; ?></h3>
                            <span><i class="fas fa-users"></i> <?php echo $class['student_count']; ?> Students</span>
                        </div>
                        <div class="class-body">
                            <div class="class-info">
                                <div>
                                    <h4><?php echo $class['student_count']; ?></h4>
                                    <p>Students</p>
                                </div>
                                <div>
                                    <h4>6</h4>
                                    <p>Subjects</p>
                                </div>
                            </div>
                            <div style="font-size: 12px; color: #666; margin-bottom: 10px; text-align: center;">
                                <i class="fas fa-chalkboard-teacher"></i> Class Teacher: <?php echo htmlspecialchars($class['class_teacher_name'] ?? 'Not assigned'); ?>
                            </div>
                            <div class="class-actions">
                                <a href="class_students.php?id=<?php echo $class['id']; ?>" class="manage">
                                    <i class="fas fa-users"></i> Students
                                </a>
                                <a href="class_scores.php?id=<?php echo $class['id']; ?>" class="scores">
                                    <i class="fas fa-edit"></i> Scores
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="level-section">
                <h2><i class="fas fa-graduation-cap"></i> Junior High School (JHS 1 - JHS 3)</h2>
                <div class="class-grid">
                    <?php foreach ($jhsClasses as $class): ?>
                    <div class="class-card">
                        <div class="class-header jhs">
                            <h3><?php echo $class['class_name']; ?> - Section <?php echo $class['section']; ?></h3>
                            <span><i class="fas fa-users"></i> <?php echo $class['student_count']; ?> Students</span>
                        </div>
                        <div class="class-body">
                            <div class="class-info">
                                <div>
                                    <h4><?php echo $class['student_count']; ?></h4>
                                    <p>Students</p>
                                </div>
                                <div>
                                    <h4>10</h4>
                                    <p>Subjects</p>
                                </div>
                            </div>
                            <div style="font-size: 12px; color: #666; margin-bottom: 10px; text-align: center;">
                                <i class="fas fa-chalkboard-teacher"></i> Class Teacher: <?php echo htmlspecialchars($class['class_teacher_name'] ?? 'Not assigned'); ?>
                            </div>
                            <div class="class-actions">
                                <a href="class_students.php?id=<?php echo $class['id']; ?>" class="manage">
                                    <i class="fas fa-users"></i> Students
                                </a>
                                <a href="class_scores.php?id=<?php echo $class['id']; ?>" class="scores">
                                    <i class="fas fa-edit"></i> Scores
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="table-container" style="margin-top: 30px;">
                <div class="table-header">
                    <h2>All Classes (Table View)</h2>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Class Name</th>
                            <th>Section</th>
                            <th>Level</th>
                            <th>Students</th>
                            <th>Class Teacher</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($classes as $class): ?>
                        <tr>
                            <td><?php echo $class['id']; ?></td>
                            <td><?php echo $class['class_name']; ?></td>
                            <td><?php echo $class['section']; ?></td>
                            <td><span class="badge <?php echo $class['level']; ?>"><?php echo $levelLabels[$class['level']] ?? $class['level']; ?></span></td>
                            <td><?php echo $class['student_count']; ?></td>
                            <td><?php echo htmlspecialchars($class['class_teacher_name'] ?? '-'); ?></td>
                            <td class="action-btns">
                                <a href="class_students.php?id=<?php echo $class['id']; ?>" class="view">View</a>
                                <button class="edit modal-trigger" data-modal="editModal<?php echo $class['id']; ?>">Edit</button>
                                <a href="?delete=<?php echo $class['id']; ?>" class="delete delete-btn">Delete</a>
                            </td>
                        </tr>
                        
                        <div id="editModal<?php echo $class['id']; ?>" class="modal">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h3>Edit Class</h3>
                                    <button class="modal-close">&times;</button>
                                </div>
                                <form method="POST">
                                    <input type="hidden" name="id" value="<?php echo $class['id']; ?>">
                                    <div class="form-group">
                                        <label>Class Name</label>
                                        <input type="text" name="class_name" value="<?php echo $class['class_name']; ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Section</label>
                                        <input type="text" name="section" value="<?php echo $class['section']; ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Level</label>
                                        <select name="level" required>
                                            <option value="nursery" <?php echo $class['level'] == 'nursery' ? 'selected' : ''; ?>>Nursery</option>
                                            <option value="kg" <?php echo $class['level'] == 'kg' ? 'selected' : ''; ?>>Kindergarten</option>
                                            <option value="primary" <?php echo $class['level'] == 'primary' ? 'selected' : ''; ?>>Primary School</option>
                                            <option value="jhs" <?php echo $class['level'] == 'jhs' ? 'selected' : ''; ?>>Junior High School</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Class Teacher</label>
                                        <select name="class_teacher_id">
                                            <option value="">-- Select Teacher --</option>
                                            <?php foreach ($teachers as $t): ?>
                                            <option value="<?php echo $t['id']; ?>" <?php echo $class['class_teacher_id'] == $t['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($t['name']); ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <button type="submit" name="update_class" class="btn btn-primary" style="width: 100%;">Update Class</button>
                                </form>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
    
    <div id="addModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Add New Class</h3>
                <button class="modal-close">&times;</button>
            </div>
            <form method="POST">
                <div class="form-group">
                    <label>Level</label>
                    <select name="level" id="addLevelSelect" required onchange="updateClassOptions()">
                        <option value="">Select Level</option>
                        <option value="nursery">Nursery</option>
                        <option value="kg">Kindergarten (KG)</option>
                        <option value="primary">Primary School</option>
                        <option value="jhs">Junior High School</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Class Name</label>
                    <select name="class_name" id="addClassName" required>
                        <option value="">Select Level First</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Section</label>
                    <select name="section" required>
                        <option value="A">Section A</option>
                        <option value="B">Section B</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Class Teacher</label>
                    <select name="class_teacher_id">
                        <option value="">-- Select Teacher --</option>
                        <?php foreach ($teachers as $t): ?>
                        <option value="<?php echo $t['id']; ?>"><?php echo htmlspecialchars($t['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" name="add_class" class="btn btn-primary" style="width: 100%;">Add Class</button>
            </form>
        </div>
    </div>
    
    <style>
        .badge.primary { background: rgba(74, 144, 226, 0.2); color: #4a90e2; }
        .badge.jhs { background: rgba(39, 174, 96, 0.2); color: #27ae60; }
        .badge.nursery { background: rgba(231, 76, 60, 0.2); color: #e74c3c; }
        .badge.kg { background: rgba(243, 156, 18, 0.2); color: #f39c12; }
    </style>
    
    <script>
    const classOptions = {
        nursery: ['Nursery 1', 'Nursery 2'],
        kg: ['KG 1', 'KG 2'],
        primary: ['Basic 1', 'Basic 2', 'Basic 3', 'Basic 4', 'Basic 5', 'Basic 6'],
        jhs: ['JHS 1', 'JHS 2', 'JHS 3']
    };
    
    function updateClassOptions() {
        const level = document.getElementById('addLevelSelect').value;
        const classSelect = document.getElementById('addClassName');
        classSelect.innerHTML = '<option value="">Select Class</option>';
        if (level && classOptions[level]) {
            classOptions[level].forEach(function(c) {
                classSelect.innerHTML += '<option value="' + c + '">' + c + '</option>';
            });
        }
    }
    </script>
    <script src="../assets/js/script.js"></script>
</body>
</html>
