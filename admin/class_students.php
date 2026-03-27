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

$classId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$class = $pdo->prepare("SELECT * FROM classes WHERE id = ?");
$class->execute([$classId]);
$class = $class->fetch();

if (!$class) {
    header("Location: classes.php");
    exit();
}

$message = '';

if (isset($_POST['add_student'])) {
    $name = sanitize($_POST['name']);
    $gender = sanitize($_POST['gender']);
    $email = sanitize($_POST['email']);
    $phone = sanitize($_POST['phone']);
    
    $stmt = $pdo->prepare("INSERT INTO students (name, class_id, gender, email, phone) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$name, $classId, $gender, $email, $phone]);
    $message = '<div class="success-msg">Student added successfully!</div>';
}

if (isset($_POST['update_student'])) {
    $id = sanitize($_POST['id']);
    $name = sanitize($_POST['name']);
    $gender = sanitize($_POST['gender']);
    $email = sanitize($_POST['email']);
    $phone = sanitize($_POST['phone']);
    
    $stmt = $pdo->prepare("UPDATE students SET name = ?, gender = ?, email = ?, phone = ? WHERE id = ? AND class_id = ?");
    $stmt->execute([$name, $gender, $email, $phone, $id, $classId]);
    $message = '<div class="success-msg">Student updated successfully!</div>';
}

if (isset($_GET['delete'])) {
    $id = sanitize($_GET['delete']);
    $stmt = $pdo->prepare("DELETE FROM students WHERE id = ? AND class_id = ?");
    $stmt->execute([$id, $classId]);
    $message = '<div class="success-msg">Student deleted successfully!</div>';
}

if (isset($_GET['transfer'])) {
    $studentId = sanitize($_GET['transfer']);
    $newClassId = sanitize($_GET['new_class']);
    
    $stmt = $pdo->prepare("UPDATE students SET class_id = ? WHERE id = ?");
    $stmt->execute([$newClassId, $studentId]);
    $message = '<div class="success-msg">Student transferred successfully!</div>';
}

$students = $pdo->prepare("SELECT * FROM students WHERE class_id = ? ORDER BY name");
$students->execute([$classId]);
$students = $students->fetchAll();

$allClasses = $pdo->query("SELECT * FROM classes ORDER BY class_name, section")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $class['class_name']; ?> - Students - School Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        .class-banner {
            background: linear-gradient(135deg, <?php 
                if ($class['level'] === 'nursery') echo '#e74c3c, #c0392b';
                elseif ($class['level'] === 'kg') echo '#f39c12, #d35400';
                elseif ($class['level'] === 'primary') echo '#4a90e2, #357abd';
                else echo '#27ae60, #2ecc71';
            ?>);
            color: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .class-banner h2 {
            font-size: 28px;
            margin-bottom: 5px;
        }
        .class-banner p {
            opacity: 0.9;
        }
        .class-banner a {
            background: rgba(255,255,255,0.2);
            color: white;
            padding: 10px 20px;
            border-radius: 25px;
            text-decoration: none;
            transition: background 0.3s;
        }
        .class-banner a:hover {
            background: rgba(255,255,255,0.3);
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
            <h1><i class="fas fa-users"></i> Students in <?php echo $class['class_name']; ?></h1>
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
                <a href="classes.php"><i class="fas fa-arrow-left"></i> Back to Classes</a>
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
            <div class="class-banner">
                <div>
                    <h2><?php echo $class['class_name']; ?> - Section <?php echo $class['section']; ?></h2>
                    <p><i class="fas fa-user-graduate"></i> <?php echo count($students); ?> Students | <?php 
                        if ($class['level'] === 'nursery') echo 'Nursery';
                        elseif ($class['level'] === 'kg') echo 'Kindergarten';
                        elseif ($class['level'] === 'primary') echo 'Primary School';
                        else echo 'Junior High School';
                    ?></p>
                </div>
                <a href="class_scores.php?id=<?php echo $classId; ?>">
                    <i class="fas fa-edit"></i> Enter Scores
                </a>
            </div>
            
            <?php echo $message; ?>
            
            <div class="table-container">
                <div class="table-header">
                    <h2>All Students</h2>
                    <button class="btn btn-primary modal-trigger" data-modal="addModal">
                        <i class="fas fa-plus"></i> Add Student
                    </button>
                </div>
                
                <?php if (empty($students)): ?>
                <div style="text-align: center; padding: 40px; color: #999;">
                    <i class="fas fa-users" style="font-size: 50px; margin-bottom: 15px;"></i>
                    <p>No students in this class yet.</p>
                </div>
                <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Gender</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $student): ?>
                        <tr>
                            <td><?php echo $student['id']; ?></td>
                            <td><?php echo $student['name']; ?></td>
                            <td><span class="badge <?php echo strtolower($student['gender']); ?>"><?php echo $student['gender']; ?></span></td>
                            <td><?php echo $student['email'] ?: '-'; ?></td>
                            <td><?php echo $student['phone'] ?: '-'; ?></td>
                            <td class="action-btns">
                                <button class="edit modal-trigger" data-modal="editModal<?php echo $student['id']; ?>">Edit</button>
                                <button class="view modal-trigger" data-modal="transferModal<?php echo $student['id']; ?>">Transfer</button>
                                <a href="?id=<?php echo $classId; ?>&delete=<?php echo $student['id']; ?>" class="delete delete-btn">Delete</a>
                            </td>
                        </tr>
                        
                        <div id="editModal<?php echo $student['id']; ?>" class="modal">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h3>Edit Student</h3>
                                    <button class="modal-close">&times;</button>
                                </div>
                                <form method="POST">
                                    <input type="hidden" name="id" value="<?php echo $student['id']; ?>">
                                    <div class="form-group">
                                        <label>Name</label>
                                        <input type="text" name="name" value="<?php echo $student['name']; ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Gender</label>
                                        <select name="gender" required>
                                            <option value="Male" <?php echo $student['gender'] == 'Male' ? 'selected' : ''; ?>>Male</option>
                                            <option value="Female" <?php echo $student['gender'] == 'Female' ? 'selected' : ''; ?>>Female</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Email</label>
                                        <input type="email" name="email" value="<?php echo $student['email']; ?>">
                                    </div>
                                    <div class="form-group">
                                        <label>Phone</label>
                                        <input type="text" name="phone" value="<?php echo $student['phone']; ?>">
                                    </div>
                                    <button type="submit" name="update_student" class="btn btn-primary" style="width: 100%;">Update Student</button>
                                </form>
                            </div>
                        </div>
                        
                        <div id="transferModal<?php echo $student['id']; ?>" class="modal">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h3>Transfer Student</h3>
                                    <button class="modal-close">&times;</button>
                                </div>
                                <p style="margin-bottom: 15px;">Transfer <strong><?php echo $student['name']; ?></strong> to another class:</p>
                                <form>
                                    <div class="form-group">
                                        <label>Select New Class</label>
                                        <select onchange="window.location.href='?id=<?php echo $classId; ?>&transfer=<?php echo $student['id']; ?>&new_class=' + this.value">
                                            <option value="">Select Class</option>
                                            <?php foreach ($allClasses as $c): ?>
                                            <option value="<?php echo $c['id']; ?>"><?php echo $c['class_name']; ?> - Section <?php echo $c['section']; ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
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
                <h3>Add Student to <?php echo $class['class_name']; ?></h3>
                <button class="modal-close">&times;</button>
            </div>
            <form method="POST">
                <div class="form-group">
                    <label>Name</label>
                    <input type="text" name="name" required>
                </div>
                <div class="form-group">
                    <label>Gender</label>
                    <select name="gender" required>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email">
                </div>
                <div class="form-group">
                    <label>Phone</label>
                    <input type="text" name="phone">
                </div>
                <button type="submit" name="add_student" class="btn btn-primary" style="width: 100%;">Add Student</button>
            </form>
        </div>
    </div>
    
    <script src="../assets/js/script.js"></script>
</body>
</html>
