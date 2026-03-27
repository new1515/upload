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

$message = '';
$error = '';

if (isset($_POST['add_category'])) {
    $name = sanitize($_POST['name']);
    $description = sanitize($_POST['description']);
    $is_mandatory = isset($_POST['is_mandatory']) ? 1 : 0;
    
    $stmt = $pdo->prepare("INSERT INTO fee_categories (name, description, is_mandatory) VALUES (?, ?, ?)");
    $stmt->execute([$name, $description, $is_mandatory]);
    logActivity($pdo, 'create', "Added fee category: $name", $_SESSION['admin_username'] ?? 'admin', 'fee_category', $pdo->lastInsertId());
    $message = '<div class="success-msg"><i class="fas fa-check-circle"></i> Fee category added successfully!</div>';
}

if (isset($_POST['update_fee'])) {
    $id = (int)$_POST['id'];
    $amount = (float)$_POST['amount'];
    $term = sanitize($_POST['term']);
    
    $stmt = $pdo->prepare("UPDATE class_fees SET amount = ?, term = ? WHERE id = ?");
    $stmt->execute([$amount, $term, $id]);
    logActivity($pdo, 'update', "Updated class fee ID: $id", $_SESSION['admin_username'] ?? 'admin', 'class_fee', $id);
    $message = '<div class="success-msg"><i class="fas fa-check-circle"></i> Fee updated successfully!</div>';
}

if (isset($_POST['record_payment'])) {
    $student_fee_id = (int)$_POST['student_fee_id'];
    $amount = (float)$_POST['amount'];
    $payment_date = sanitize($_POST['payment_date']);
    $payment_method = sanitize($_POST['payment_method']);
    $reference = sanitize($_POST['reference']);
    
    $stmt = $pdo->prepare("SELECT * FROM student_fees WHERE id = ?");
    $stmt->execute([$student_fee_id]);
    $studentFee = $stmt->fetch();
    
    $newAmountPaid = $studentFee['amount_paid'] + $amount;
    $newBalance = $studentFee['amount'] - $newAmountPaid;
    $status = $newBalance <= 0 ? 'paid' : 'partial';
    
    $pdo->prepare("UPDATE student_fees SET amount_paid = ?, balance = ?, payment_status = ?, paid_date = ? WHERE id = ?")
        ->execute([$newAmountPaid, $newBalance, $status, $payment_date, $student_fee_id]);
    
    $pdo->prepare("INSERT INTO fee_payments (student_fee_id, amount, payment_date, payment_method, reference_number, received_by) VALUES (?, ?, ?, ?, ?, ?)")
        ->execute([$student_fee_id, $amount, $payment_date, $payment_method, $reference, $_SESSION['admin_id']]);
    
    logActivity($pdo, 'payment', "Recorded payment of $amount for student fee ID: $student_fee_id", $_SESSION['admin_username'] ?? 'admin', 'fee_payment', $student_fee_id);
    $message = '<div class="success-msg"><i class="fas fa-check-circle"></i> Payment recorded successfully!</div>';
}

if (isset($_GET['generate']) && isset($_GET['class_id'])) {
    $class_id = (int)$_GET['class_id'];
    $term = sanitize($_GET['term'] ?? 'Term 1');
    
    $stmt = $pdo->prepare("SELECT s.id as student_id, c.level FROM students s JOIN classes c ON s.class_id = c.id WHERE s.class_id = ?");
    $stmt->execute([$class_id]);
    $students = $stmt->fetchAll();
    
    $stmt = $pdo->prepare("SELECT * FROM fee_categories ORDER BY is_mandatory DESC, name");
    $categories = $stmt->fetchAll();
    
    foreach ($students as $student) {
        foreach ($categories as $cat) {
            $stmt = $pdo->prepare("SELECT * FROM class_fees WHERE category_id = ? AND class_level = ?");
            $stmt->execute([$cat['id'], $student['level']]);
            $classFee = $stmt->fetch();
            
            if ($classFee) {
                $stmt = $pdo->prepare("SELECT * FROM student_fees WHERE student_id = ? AND category_id = ? AND term = ?");
                $stmt->execute([$student['student_id'], $cat['id'], $term]);
                $exists = $stmt->fetch();
                
                if (!$exists) {
                    $pdo->prepare("INSERT INTO student_fees (student_id, category_id, amount, term) VALUES (?, ?, ?, ?)")
                        ->execute([$student['student_id'], $cat['id'], $classFee['amount'], $term]);
                }
            }
        }
    }
    
    $message = '<div class="success-msg"><i class="fas fa-check-circle"></i> Fees generated for all students in class!</div>';
}

if (isset($_GET['generate_all'])) {
    $term = sanitize($_GET['term'] ?? 'Term 1');
    
    $stmt = $pdo->query("SELECT s.id as student_id, c.level FROM students s JOIN classes c ON s.class_id = c.id");
    $students = $stmt->fetchAll();
    
    $stmt = $pdo->query("SELECT * FROM fee_categories ORDER BY is_mandatory DESC, name");
    $categories = $stmt->fetchAll();
    
    $count = 0;
    foreach ($students as $student) {
        foreach ($categories as $cat) {
            $stmt = $pdo->prepare("SELECT * FROM class_fees WHERE category_id = ? AND class_level = ?");
            $stmt->execute([$cat['id'], $student['level']]);
            $classFee = $stmt->fetch();
            
            if ($classFee) {
                $stmt = $pdo->prepare("SELECT * FROM student_fees WHERE student_id = ? AND category_id = ? AND term = ?");
                $stmt->execute([$student['student_id'], $cat['id'], $term]);
                $exists = $stmt->fetch();
                
                if (!$exists) {
                    $pdo->prepare("INSERT INTO student_fees (student_id, category_id, amount, term) VALUES (?, ?, ?, ?)")
                        ->execute([$student['student_id'], $cat['id'], $classFee['amount'], $term]);
                    $count++;
                }
            }
        }
    }
    
    $message = '<div class="success-msg"><i class="fas fa-check-circle"></i> Fees generated for ' . $count . ' student-fee records!</div>';
}

$categories = $pdo->query("SELECT * FROM fee_categories ORDER BY is_mandatory DESC, name")->fetchAll();
$classFees = $pdo->query("SELECT cf.*, fc.name as category_name, fc.is_mandatory FROM class_fees cf JOIN fee_categories fc ON cf.category_id = fc.id ORDER BY fc.is_mandatory DESC, fc.name, cf.class_level")->fetchAll();
$classes = $pdo->query("SELECT * FROM classes ORDER BY class_name, section")->fetchAll();
$levels = ['nursery' => 'Nursery', 'kg' => 'KG', 'primary' => 'Primary', 'jhs' => 'JHS'];
$terms = ['Term 1', 'Term 2', 'Term 3'];
$years = ['2025-2026', '2024-2025'];

$selectedClass = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;
$selectedTerm = isset($_GET['term']) ? sanitize($_GET['term']) : 'Term 1';
$selectedYear = isset($_GET['year']) ? sanitize($_GET['year']) : '2025-2026';

$studentFees = [];
if ($selectedClass) {
    $stmt = $pdo->prepare("SELECT sf.*, s.name as student_name, s.username, fc.name as category_name 
                          FROM student_fees sf 
                          JOIN students s ON sf.student_id = s.id 
                          JOIN fee_categories fc ON sf.category_id = fc.id 
                          WHERE s.class_id = ? AND sf.term = ?
                          ORDER BY s.name, fc.name");
    $stmt->execute([$selectedClass, $selectedTerm]);
    $studentFees = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fees Management - <?php echo $schoolName; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        .fee-card { background: white; border-radius: 10px; padding: 20px; margin-bottom: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .fee-card.mandatory { border-left: 4px solid #e74c3c; }
        .fee-card.optional { border-left: 4px solid #27ae60; }
        .amount { font-size: 24px; font-weight: bold; color: #2c3e50; }
        .status-badge { padding: 4px 12px; border-radius: 15px; font-size: 12px; font-weight: 600; }
        .status-paid { background: #d4edda; color: #155724; }
        .status-unpaid { background: #f8d7da; color: #721c24; }
        .status-partial { background: #fff3cd; color: #856404; }
        .fee-table { width: 100%; border-collapse: collapse; }
        .fee-table th { background: var(--primary); color: white; padding: 10px; text-align: left; }
        .fee-table td { padding: 10px; border-bottom: 1px solid #eee; }
        .fee-summary { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin: 20px 0; }
        .summary-box { background: white; padding: 20px; border-radius: 10px; text-align: center; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .summary-box h3 { font-size: 28px; margin-bottom: 5px; }
        .summary-box p { color: #888; margin: 0; }
        .generate-btn { background: linear-gradient(135deg, #27ae60, #2ecc71); color: white; padding: 12px 25px; border: none; border-radius: 8px; cursor: pointer; }
        .generate-btn:hover { transform: translateY(-2px); box-shadow: 0 5px 20px rgba(39, 174, 96, 0.4); }
        @media (max-width: 768px) {
            .fee-table { font-size: 12px; }
            .fee-table th, .fee-table td { padding: 6px; }
        }
    </style>
</head>
<body>
    <button class="mobile-menu-toggle"><i class="fas fa-bars"></i></button>
    <div class="sidebar-overlay"></div>
    <nav class="sidebar">
        <div class="sidebar-brand"><i class="fas fa-school"></i> <?php echo $schoolName; ?></div>
        <ul class="sidebar-menu">
            <li><a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
            <li><a href="students.php"><i class="fas fa-user-graduate"></i> Students</a></li>
            <li><a href="teachers.php"><i class="fas fa-chalkboard-teacher"></i> Teachers</a></li>
            <li><a href="parents.php"><i class="fas fa-users"></i> Parents</a></li>
            <li><a href="subjects.php"><i class="fas fa-book"></i> Subjects</a></li>
            <li><a href="classes.php"><i class="fas fa-school"></i> Classes</a></li>
            <li><a href="attendance.php"><i class="fas fa-calendar-check"></i> Attendance</a></li>
            <li><a href="timetable.php"><i class="fas fa-table"></i> Timetable</a></li>
            <li><a href="results.php"><i class="fas fa-chart-line"></i> Results</a></li>
            <li><a href="reportcards.php"><i class="fas fa-file-alt"></i> Report Cards</a></li>
            <li><a href="announcements.php"><i class="fas fa-bullhorn"></i> Announcements</a></li>
            <li><a href="fees.php" class="active"><i class="fas fa-money-bill"></i> Fees</a></li>
            <li><a href="lessons.php"><i class="fas fa-book-open"></i> Lessons</a></li>
            <li><a href="lesson_plans.php"><i class="fas fa-calendar-alt"></i> Lesson Plans</a></li>
            <li><a href="test_books.php"><i class="fas fa-file-alt"></i> Test Books</a></li>
            <li><a href="notes.php"><i class="fas fa-sticky-note"></i> Notes</a></li>
            <li><a href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
            <li><a href="chatbot.php"><i class="fas fa-robot"></i> AI Chatbot</a></li>
            <li><a href="chatbot_qa.php"><i class="fas fa-database"></i> Chatbot Q&A</a></li>
            <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </nav>
    
    <main class="main-content">
        <div class="header">
            <h1><i class="fas fa-money-bill"></i> Fees Management</h1>
        </div>
        
        <div class="content">
            <?php echo $message; ?>
            
            <div class="fee-summary">
                <div class="summary-box">
                    <h3 style="color: #e74c3c;">
                        <i class="fas fa-clock"></i> GH¢ <?php 
                        $totalUnpaid = 0;
                        foreach ($studentFees as $sf) { if ($sf['payment_status'] != 'paid') $totalUnpaid += $sf['balance']; }
                        echo number_format($totalUnpaid, 2);
                        ?>
                    </h3>
                    <p>Total Outstanding</p>
                </div>
                <div class="summary-box">
                    <h3 style="color: #27ae60;">
                        <i class="fas fa-check-circle"></i> GH¢ <?php 
                        $totalPaid = 0;
                        foreach ($studentFees as $sf) { if ($sf['payment_status'] == 'paid') $totalPaid += $sf['amount_paid']; }
                        echo number_format($totalPaid, 2);
                        ?>
                    </h3>
                    <p>Total Collected</p>
                </div>
                <div class="summary-box">
                    <h3 style="color: #f39c12;">
                        <i class="fas fa-users"></i> <?php echo count($studentFees); ?>
                    </h3>
                    <p>Fee Records</p>
                </div>
                <div class="summary-box">
                    <h3 style="color: #3498db;">
                        <i class="fas fa-check-double"></i> <?php 
                        $paidCount = 0;
                        foreach ($studentFees as $sf) { if ($sf['payment_status'] == 'paid') $paidCount++; }
                        echo $paidCount . ' / ' . count($studentFees);
                        ?>
                    </h3>
                    <p>Fully Paid</p>
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div class="table-container">
                    <div class="table-header">
                        <h3><i class="fas fa-list"></i> Fee Categories</h3>
                        <button class="btn btn-primary modal-trigger" data-modal="addCategoryModal">
                            <i class="fas fa-plus"></i> Add Category
                        </button>
                    </div>
                    
                    <?php foreach ($categories as $cat): ?>
                    <div class="fee-card <?php echo $cat['is_mandatory'] ? 'mandatory' : 'optional'; ?>">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <h4 style="margin: 0;"><?php echo htmlspecialchars($cat['name']); ?></h4>
                                <p style="margin: 5px 0 0; color: #888; font-size: 13px;">
                                    <?php echo $cat['is_mandatory'] ? '<span style="color: #e74c3c;"><i class="fas fa-exclamation-circle"></i> Mandatory</span>' : '<span style="color: #27ae60;"><i class="fas fa-info-circle"></i> Optional</span>'; ?>
                                </p>
                            </div>
                            <?php if ($cat['description']): ?>
                            <p style="color: #666; font-size: 12px; max-width: 200px;"><?php echo htmlspecialchars($cat['description']); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="table-container">
                    <div class="table-header">
                        <h3><i class="fas fa-coins"></i> Fee Structure by Class</h3>
                    </div>
                    
                    <table class="fee-table">
                        <thead>
                            <tr>
                                <th>Category</th>
                                <th>Level</th>
                                <th>Amount</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($classFees as $cf): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($cf['category_name']); ?></td>
                                <td><span class="badge"><?php echo $levels[$cf['class_level']] ?? $cf['class_level']; ?></span></td>
                                <td><strong>GH¢ <?php echo number_format($cf['amount'], 2); ?></strong></td>
                                <td>
                                    <button class="btn btn-sm btn-primary modal-trigger" data-modal="editFee<?php echo $cf['id']; ?>">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                </td>
                            </tr>
                            
                            <div id="editFee<?php echo $cf['id']; ?>" class="modal">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h3>Edit Fee - <?php echo htmlspecialchars($cf['category_name']); ?></h3>
                                        <button class="modal-close">&times;</button>
                                    </div>
                                    <form method="POST">
                                        <input type="hidden" name="id" value="<?php echo $cf['id']; ?>">
                                        <div class="form-group">
                                            <label>Amount (GH¢)</label>
                                            <input type="number" name="amount" value="<?php echo $cf['amount']; ?>" step="0.01" required>
                                        </div>
                                        <div class="form-group">
                                            <label>Term</label>
                                            <select name="term">
                                                <?php foreach ($terms as $t): ?>
                                                <option value="<?php echo $t; ?>" <?php echo ($cf['term'] ?? '') == $t ? 'selected' : ''; ?>><?php echo $t; ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <button type="submit" name="update_fee" class="btn btn-primary" style="width: 100%;">
                                            <i class="fas fa-save"></i> Update Fee
                                        </button>
                                    </form>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="table-container" style="margin-top: 25px;">
                <div class="table-header">
                    <h3><i class="fas fa-money-check"></i> Student Fee Payments</h3>
                    <div style="display: flex; gap: 10px; align-items: center;">
                        <form method="GET" style="display: flex; gap: 10px; align-items: center;">
                            <select name="class_id" onchange="this.form.submit()" required>
                                <option value="">Select Class</option>
                                <?php foreach ($classes as $c): ?>
                                <option value="<?php echo $c['id']; ?>" <?php echo $selectedClass == $c['id'] ? 'selected' : ''; ?>>
                                    <?php echo $c['class_name'] . ' - Section ' . $c['section']; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <select name="term" onchange="this.form.submit()">
                                <?php foreach ($terms as $t): ?>
                                <option value="<?php echo $t; ?>" <?php echo $selectedTerm == $t ? 'selected' : ''; ?>><?php echo $t; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                        <?php if ($selectedClass): ?>
                        <a href="?generate&class_id=<?php echo $selectedClass; ?>&term=<?php echo $selectedTerm; ?>" class="generate-btn" onclick="return confirm('Generate fees for all students in this class?')">
                            <i class="fas fa-magic"></i> Generate Fees
                        </a>
                        <?php else: ?>
                        <a href="?generate_all&term=<?php echo $selectedTerm; ?>" class="generate-btn" onclick="return confirm('Generate fees for ALL students?')">
                            <i class="fas fa-magic"></i> Generate All Fees
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if ($selectedClass): ?>
                <table class="fee-table">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Fee Type</th>
                            <th>Amount</th>
                            <th>Paid</th>
                            <th>Balance</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($studentFees)): ?>
                        <tr><td colspan="7" style="text-align: center; color: #888; padding: 30px;">
                            No fee records found. Click "Generate Fees" to create records for students.
                        </td></tr>
                        <?php else: foreach ($studentFees as $sf): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($sf['student_name']); ?></td>
                            <td><?php echo htmlspecialchars($sf['category_name']); ?></td>
                            <td>GH¢ <?php echo number_format($sf['amount'], 2); ?></td>
                            <td style="color: #27ae60;">GH¢ <?php echo number_format($sf['amount_paid'], 2); ?></td>
                            <td style="color: <?php echo $sf['balance'] > 0 ? '#e74c3c' : '#27ae60'; ?>;">
                                <strong>GH¢ <?php echo number_format($sf['balance'], 2); ?></strong>
                            </td>
                            <td>
                                <span class="status-badge status-<?php echo $sf['payment_status']; ?>">
                                    <?php echo ucfirst($sf['payment_status']); ?>
                                </span>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-success modal-trigger" data-modal="payment<?php echo $sf['id']; ?>">
                                    <i class="fas fa-dollar-sign"></i> Pay
                                </button>
                            </td>
                        </tr>
                        
                        <div id="payment<?php echo $sf['id']; ?>" class="modal">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h3>Record Payment</h3>
                                    <button class="modal-close">&times;</button>
                                </div>
                                <form method="POST">
                                    <input type="hidden" name="student_fee_id" value="<?php echo $sf['id']; ?>">
                                    <div style="background: #f8f9fa; padding: 15px; border-radius: 10px; margin-bottom: 15px;">
                                        <p><strong>Student:</strong> <?php echo htmlspecialchars($sf['student_name']); ?></p>
                                        <p><strong>Fee:</strong> <?php echo htmlspecialchars($sf['category_name']); ?></p>
                                        <p><strong>Total:</strong> GH¢ <?php echo number_format($sf['amount'], 2); ?></p>
                                        <p><strong>Balance:</strong> GH¢ <?php echo number_format($sf['balance'], 2); ?></p>
                                    </div>
                                    <div class="form-group">
                                        <label>Amount to Pay (GH¢)</label>
                                        <input type="number" name="amount" step="0.01" max="<?php echo $sf['balance']; ?>" value="<?php echo $sf['balance']; ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Payment Date</label>
                                        <input type="date" name="payment_date" value="<?php echo date('Y-m-d'); ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Payment Method</label>
                                        <select name="payment_method">
                                            <option value="Cash">Cash</option>
                                            <option value="Mobile Money">Mobile Money</option>
                                            <option value="Bank Transfer">Bank Transfer</option>
                                            <option value="Cheque">Cheque</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Reference Number (optional)</label>
                                        <input type="text" name="reference" placeholder="e.g., MOMO ref number">
                                    </div>
                                    <button type="submit" name="record_payment" class="btn btn-success" style="width: 100%;">
                                        <i class="fas fa-check"></i> Record Payment
                                    </button>
                                </form>
                            </div>
                        </div>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
                <?php endif; ?>
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
        .site-footer p { margin: 5px 0; font-size: 14px; }
    </style>
    
    <script src="../assets/js/script.js"></script>
    
    <div id="addCategoryModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-plus-circle"></i> Add Fee Category</h3>
                <button class="modal-close">&times;</button>
            </div>
            <form method="POST">
                <div class="form-group">
                    <label>Category Name</label>
                    <input type="text" name="name" placeholder="e.g., Tuition Fee" required>
                </div>
                <div class="form-group">
                    <label>Description (optional)</label>
                    <textarea name="description" rows="2" placeholder="Brief description..."></textarea>
                </div>
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="is_mandatory" value="1" checked>
                        Mandatory Fee (all students must pay)
                    </label>
                </div>
                <button type="submit" name="add_category" class="btn btn-primary" style="width: 100%;">
                    <i class="fas fa-save"></i> Add Category
                </button>
            </form>
        </div>
    </div>
</body>
</html>
