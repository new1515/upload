<?php
require_once '../config/database.php';

if (!isLoggedIn()) {
    redirect('../login.php');
}

$message = '';
$error = '';

if (isset($_POST['add_student'])) {
    $name = sanitize($_POST['name']);
    $username = sanitize($_POST['username']);
    $class_id = sanitize($_POST['class_id']);
    $gender = sanitize($_POST['gender']);
    $email = sanitize($_POST['email']);
    $phone = sanitize($_POST['phone']);
    $password = !empty($_POST['password']) ? md5($_POST['password']) : md5('12345678');
    $fee_category_id = !empty($_POST['fee_category_id']) ? (int)$_POST['fee_category_id'] : 0;
    $initial_payment = !empty($_POST['initial_payment']) ? (float)$_POST['initial_payment'] : 0;
    
    $photo = '';
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['photo'];
        $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
        $fileType = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (in_array($fileType, $allowedTypes) && $file['size'] <= 5 * 1024 * 1024) {
            $photo = time() . '_' . basename($file['name']);
            $uploadDir = '../assets/images/students/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
            move_uploaded_file($file['tmp_name'], $uploadDir . $photo);
        }
    }
    
    $check = $pdo->prepare("SELECT id FROM students WHERE username = ?");
    $check->execute([$username]);
    if ($check->fetch()) {
        $error = '<div class="error-msg">Username already exists!</div>';
    } else {
        $stmt = $pdo->prepare("INSERT INTO students (name, username, password, class_id, gender, email, phone, photo) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name, $username, $password, $class_id, $gender, $email, $phone, $photo]);
        $newId = $pdo->lastInsertId();
        
        // Create fee records for the student
        $stmt = $pdo->prepare("SELECT c.level FROM classes c WHERE c.id = ?");
        $stmt->execute([$class_id]);
        $classLevel = $stmt->fetchColumn() ?: 'primary';
        
        $feeCategories = $pdo->query("SELECT * FROM fee_categories")->fetchAll();
        foreach ($feeCategories as $fc) {
            $stmt = $pdo->prepare("SELECT amount FROM class_fees WHERE category_id = ? AND class_level = ?");
            $stmt->execute([$fc['id'], $classLevel]);
            $feeAmount = $stmt->fetchColumn() ?: 0;
            
            if ($feeAmount > 0) {
                $stmt = $pdo->prepare("INSERT INTO student_fees (student_id, category_id, amount, term) VALUES (?, ?, ?, ?)");
                $stmt->execute([$newId, $fc['id'], $feeAmount, 'Term 1']);
                $studentFeeId = $pdo->lastInsertId();
                
                // Record initial payment if provided
                if ($fee_category_id == $fc['id'] && $initial_payment > 0) {
                    $newBalance = $feeAmount - $initial_payment;
                    $status = $newBalance <= 0 ? 'paid' : 'partial';
                    
                    $stmt = $pdo->prepare("UPDATE student_fees SET amount_paid = ?, balance = ?, payment_status = ?, paid_date = CURDATE() WHERE id = ?");
                    $stmt->execute([$initial_payment, $newBalance, $status, $studentFeeId]);
                    
                    $stmt = $pdo->prepare("INSERT INTO fee_payments (student_fee_id, amount, payment_date, payment_method, received_by) VALUES (?, ?, CURDATE(), 'Cash', ?)");
                    $stmt->execute([$studentFeeId, $initial_payment, $_SESSION['admin_id']]);
                }
            }
        }
        
        logActivity($pdo, 'create', "Added new student: $name", $_SESSION['admin_username'] ?? 'admin', 'student', $newId);
        
        if ($initial_payment > 0) {
            $message = '<div class="success-msg">Student added successfully! Initial payment of GH¢' . number_format($initial_payment, 2) . ' recorded. Default password: 12345678</div>';
        } else {
            $message = '<div class="success-msg">Student added successfully! Default password: 12345678</div>';
        }
    }
}

if (isset($_POST['update_student'])) {
    $id = sanitize($_POST['id']);
    $name = sanitize($_POST['name']);
    $class_id = sanitize($_POST['class_id']);
    $gender = sanitize($_POST['gender']);
    $email = sanitize($_POST['email']);
    $phone = sanitize($_POST['phone']);
    
    $photoSql = '';
    $photoParams = [];
    
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['photo'];
        $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
        $fileType = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (in_array($fileType, $allowedTypes) && $file['size'] <= 5 * 1024 * 1024) {
            $photo = time() . '_' . basename($file['name']);
            $uploadDir = '../assets/images/students/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
            move_uploaded_file($file['tmp_name'], $uploadDir . $photo);
            $photoSql = ', photo = ?';
            $photoParams = [$photo];
        }
    }
    
    if (!empty($_POST['password'])) {
        $password = md5($_POST['password']);
        $stmt = $pdo->prepare("UPDATE students SET name = ?, class_id = ?, gender = ?, email = ?, phone = ?, password = ? $photoSql WHERE id = ?");
        $params = array_merge([$name, $class_id, $gender, $email, $phone, $password], $photoParams, [$id]);
        $stmt->execute($params);
    } else {
        $stmt = $pdo->prepare("UPDATE students SET name = ?, class_id = ?, gender = ?, email = ?, phone = ? $photoSql WHERE id = ?");
        $params = array_merge([$name, $class_id, $gender, $email, $phone], $photoParams, [$id]);
        $stmt->execute($params);
    }
    logActivity($pdo, 'update', "Updated student: $name (ID: $id)", $_SESSION['admin_username'] ?? 'admin', 'student', $id);
    $message = '<div class="success-msg">Student updated successfully!</div>';
}

if (isset($_GET['delete'])) {
    $id = sanitize($_GET['delete']);
    $stmt = $pdo->prepare("SELECT name FROM students WHERE id = ?");
    $stmt->execute([$id]);
    $student = $stmt->fetch();
    $studentName = $student['name'] ?? 'Unknown';
    $pdo->prepare("DELETE FROM student_assessments WHERE student_id = ?")->execute([$id]);
    $pdo->prepare("DELETE FROM students WHERE id = ?")->execute([$id]);
    logActivity($pdo, 'delete', "Deleted student: $studentName (ID: $id)", $_SESSION['admin_username'] ?? 'admin', 'student', $id);
    $message = '<div class="success-msg">Student deleted successfully!</div>';
}

if (isset($_GET['reset_password'])) {
    $id = sanitize($_GET['reset_password']);
    $stmt = $pdo->prepare("SELECT name FROM students WHERE id = ?");
    $stmt->execute([$id]);
    $student = $stmt->fetch();
    $studentName = $student['name'] ?? 'Unknown';
    $pdo->prepare("UPDATE students SET password = ? WHERE id = ?")->execute([md5('12345678'), $id]);
    logActivity($pdo, 'update', "Reset password for student: $studentName (ID: $id)", $_SESSION['admin_username'] ?? 'admin', 'student', $id);
    $message = '<div class="success-msg">Password reset to: 12345678</div>';
}

$students = $pdo->query("SELECT s.*, c.class_name, c.section 
    FROM students s 
    LEFT JOIN classes c ON s.class_id = c.id 
    ORDER BY c.class_name, s.name")->fetchAll();

$classes = $pdo->query("SELECT * FROM classes ORDER BY class_name, section")->fetchAll();

$settings = [];
$stmt = $pdo->query("SELECT * FROM school_settings");
while ($row = $stmt->fetch()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
$schoolName = $settings['school_name'] ?? 'School Management System';
$schoolLogo = $settings['school_logo'] ?? '';

$levelLabels = [
    'nursery' => 'Nursery',
    'kg' => 'KG',
    'primary' => 'Primary',
    'jhs' => 'JHS'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Students - <?php echo htmlspecialchars($schoolName); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
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
            <li><a href="students.php" class="active"><i class="fas fa-user-graduate"></i> Students</a></li>
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
            <li><a href="user_access.php"><i class="fas fa-key"></i> User Access</a></li>
            <li><a href="reset_password.php"><i class="fas fa-key"></i> Reset Password</a></li>
            <li><a href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
            <li><a href="../logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </nav>
    
    <main class="main-content">
        <div class="school-header">
            <div class="school-logo">
                <?php if ($schoolLogo && file_exists('../assets/images/' . $schoolLogo)): ?>
                    <img src="../assets/images/<?php echo $schoolLogo; ?>" alt="Logo">
                <?php else: ?>
                    <i class="fas fa-graduation-cap"></i>
                <?php endif; ?>
            </div>
            <div class="school-info">
                <h1><?php echo htmlspecialchars($schoolName); ?></h1>
                <p><?php echo $settings['school_tagline'] ?? ''; ?></p>
            </div>
        </div>
        
        <div class="header">
            <h1><i class="fas fa-user-graduate"></i> Manage Students</h1>
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
                <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
        
        <div class="content">
            <?php echo $error; ?>
            <?php echo $message; ?>
            
            <div class="table-container">
                <div class="table-header">
                    <h2>All Students (<?php echo count($students); ?>)</h2>
                    <button class="btn btn-primary modal-trigger" data-modal="addModal">
                        <i class="fas fa-plus"></i> Add Student
                    </button>
                </div>
                
                <table>
                    <thead>
                        <tr>
                            <th>Photo</th>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Username</th>
                            <th>Class</th>
                            <th>Gender</th>
                            <th>Contact</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $student): ?>
                        <tr>
                            <td style="text-align: center;">
                                <?php if (!empty($student['photo']) && file_exists('../assets/images/students/' . $student['photo'])): ?>
                                    <img src="../assets/images/students/<?php echo $student['photo']; ?>" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
                                <?php else: ?>
                                    <i class="fas fa-user" style="font-size: 20px; color: #ccc;"></i>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $student['id']; ?></td>
                            <td><?php echo htmlspecialchars($student['name']); ?></td>
                            <td><code><?php echo htmlspecialchars($student['username'] ?? '-'); ?></code></td>
                            <td><?php echo htmlspecialchars(($student['class_name'] ?? '-') . ' ' . ($student['section'] ?? '')); ?></td>
                            <td><span class="badge"><?php echo $student['gender']; ?></span></td>
                            <td><small><?php echo $student['email'] ?? '-'; ?><br><?php echo $student['phone'] ?? '-'; ?></small></td>
                            <td class="action-btns">
                                <button class="edit modal-trigger" data-modal="editModal<?php echo $student['id']; ?>">Edit</button>
                                <a href="?reset_password=<?php echo $student['id']; ?>" class="reset-password-btn" onclick="return confirm('Reset password to 12345678?')" title="Reset Password">
                                    <i class="fas fa-key"></i>
                                </a>
                                <a href="?delete=<?php echo $student['id']; ?>" class="delete delete-btn" onclick="return confirm('Delete this student?')">Delete</a>
                            </td>
                        </tr>
                        
                        <div id="editModal<?php echo $student['id']; ?>" class="modal">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h3>Edit Student</h3>
                                    <button class="modal-close">&times;</button>
                                </div>
                                <form method="POST" enctype="multipart/form-data">
                                    <input type="hidden" name="id" value="<?php echo $student['id']; ?>">
                                    <div style="text-align: center; margin-bottom: 15px;">
                                        <?php if (!empty($student['photo']) && file_exists('../assets/images/students/' . $student['photo'])): ?>
                                            <img src="../assets/images/students/<?php echo $student['photo']; ?>" style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover; border: 2px solid #4a90e2;">
                                        <?php else: ?>
                                            <i class="fas fa-user" style="font-size: 50px; color: #ccc;"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div class="form-group">
                                        <label>Full Name</label>
                                        <input type="text" name="name" value="<?php echo htmlspecialchars($student['name']); ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Class</label>
                                        <select name="class_id" required>
                                            <?php foreach ($classes as $c): ?>
                                            <option value="<?php echo $c['id']; ?>" <?php echo $student['class_id'] == $c['id'] ? 'selected' : ''; ?>>
                                                <?php echo $c['class_name'] . ' - Section ' . $c['section']; ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
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
                                        <input type="email" name="email" value="<?php echo htmlspecialchars($student['email'] ?? ''); ?>">
                                    </div>
                                    <div class="form-group">
                                        <label>Phone</label>
                                        <input type="text" name="phone" value="<?php echo htmlspecialchars($student['phone'] ?? ''); ?>">
                                    </div>
                                    <div class="form-group">
                                        <label>Change Photo</label>
                                        <input type="file" name="photo" accept="image/*">
                                        <small style="color: #888;">JPG, PNG, GIF (Max 5MB)</small>
                                    </div>
                                    <div class="form-group">
                                        <label>New Password (leave blank)</label>
                                        <input type="password" name="password" placeholder="Enter new password">
                                    </div>
                                    <button type="submit" name="update_student" class="btn btn-primary" style="width:100%">Update Student</button>
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
                <h3><i class="fas fa-user-plus"></i> Add New Student</h3>
                <button class="modal-close">&times;</button>
            </div>
            <form method="POST" enctype="multipart/form-data" id="addStudentForm">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="name" placeholder="Enter student name" required>
                </div>
                <div class="form-group">
                    <label>Username (for login)</label>
                    <input type="text" name="username" placeholder="e.g. john.smith" required>
                </div>
                <div class="form-group">
                    <label>Class</label>
                    <select name="class_id" id="feeClassSelect" required onchange="loadFeeAmount()">
                        <option value="">Select Class</option>
                        <?php foreach ($classes as $c): ?>
                        <option value="<?php echo $c['id']; ?>" data-level="<?php echo $c['level']; ?>">
                            <?php echo $c['class_name'] . ' - Section ' . $c['section'] . ' (' . ($levelLabels[$c['level']] ?? $c['level']) . ')'; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Gender</label>
                    <select name="gender" required>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Email (optional)</label>
                    <input type="email" name="email" placeholder="student@email.com">
                </div>
                <div class="form-group">
                    <label>Phone (optional)</label>
                    <input type="text" name="phone" placeholder="055-XXX-XXXX">
                </div>
                <div class="form-group">
                    <label>Student Photo (optional)</label>
                    <input type="file" name="photo" accept="image/*">
                    <small style="color: #888;">JPG, PNG, GIF (Max 5MB)</small>
                </div>
                <div class="form-group">
                    <label>Password (leave blank for default: 12345678)</label>
                    <input type="password" name="password" placeholder="Enter password">
                </div>
                
                <div style="background: #f0f7ff; padding: 15px; border-radius: 10px; margin: 15px 0;">
                    <h4 style="margin: 0 0 10px; color: #27ae60; display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-money-bill"></i> Initial Fee Payment (Optional)
                    </h4>
                    <div class="form-group">
                        <label>Select Fee to Pay</label>
                        <select name="fee_category_id" id="feeCategorySelect" onchange="updateTotalFee()">
                            <option value="">-- No initial payment --</option>
                            <?php 
                            $feeCategories = $pdo->query("SELECT * FROM fee_categories ORDER BY is_mandatory DESC, name")->fetchAll();
                            foreach ($feeCategories as $fc): ?>
                            <option value="<?php echo $fc['id']; ?>" data-amount="0">
                                <?php echo htmlspecialchars($fc['name']); ?> <?php echo $fc['is_mandatory'] ? '(Mandatory)' : '(Optional)'; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group" style="margin-bottom: 0;">
                        <label>Amount to Pay (GH¢)</label>
                        <input type="number" name="initial_payment" id="initialPayment" step="0.01" min="0" placeholder="0.00">
                        <small style="color: #888;">Leave as 0 if no payment now</small>
                    </div>
                </div>
                
                <button type="submit" name="add_student" class="btn btn-primary" style="width:100%">
                    <i class="fas fa-save"></i> Add Student
                </button>
            </form>
        </div>
    </div>
    
    <script>
    function loadFeeAmount() {
        var classSelect = document.getElementById('feeClassSelect');
        var feeSelect = document.getElementById('feeCategorySelect');
        var selectedOption = classSelect.options[classSelect.selectedIndex];
        var level = selectedOption.getAttribute('data-level');
        
        // Update fee options with amounts
        <?php
        $feeAmounts = [];
        $stmt = $pdo->query("SELECT cf.*, fc.name as category_name FROM class_fees cf JOIN fee_categories fc ON cf.category_id = fc.id");
        while ($row = $stmt->fetch()) {
            $feeAmounts[] = [
                'category_id' => $row['category_id'],
                'level' => $row['class_level'],
                'amount' => $row['amount'],
                'name' => $row['category_name']
            ];
        }
        ?>
        var feeData = <?php echo json_encode($feeAmounts); ?>;
        
        for (var i = 0; i < feeSelect.options.length; i++) {
            var opt = feeSelect.options[i];
            if (opt.value) {
                var catId = parseInt(opt.value);
                var matchingFee = feeData.find(function(f) { return f.category_id === catId && f.level === level; });
                if (matchingFee) {
                    opt.setAttribute('data-amount', matchingFee.amount);
                    opt.text = opt.text.replace(/\(\s*GH¢[\d.,]+\s*\)/, '') + ' (GH¢' + parseFloat(matchingFee.amount).toFixed(2) + ')';
                }
            }
        }
    }
    
    function updateTotalFee() {
        var feeSelect = document.getElementById('feeCategorySelect');
        var selectedOption = feeSelect.options[feeSelect.selectedIndex];
        var amount = selectedOption.getAttribute('data-amount') || 0;
        document.getElementById('initialPayment').value = parseFloat(amount).toFixed(2);
    }
    </script>
    
    <script src="../assets/js/script.js"></script>
</body>
</html>
