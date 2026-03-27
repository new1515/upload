<?php
require_once '../config/database.php';

if (!isset($_SESSION['teacher_id'])) {
    redirect('../login.php');
}

$teacherId = $_SESSION['teacher_id'];
$teacherName = $_SESSION['teacher_name'];

$settings = [];
$stmt = $pdo->query("SELECT * FROM school_settings");
while ($row = $stmt->fetch()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
$schoolName = $settings['school_name'] ?? 'School Management System';

$message = '';
$error = '';

$selectedTerm = isset($_GET['term']) ? sanitize($_GET['term']) : 'Term 1';
$selectedClass = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;
$terms = ['Term 1', 'Term 2', 'Term 3'];

if (isset($_POST['record_payment'])) {
    $studentId = (int)$_POST['student_id'];
    $categoryId = (int)$_POST['category_id'];
    $amount = floatval($_POST['amount']);
    $method = sanitize($_POST['payment_method']);
    $paymentDate = sanitize($_POST['payment_date']);
    $term = sanitize($_POST['term']);
    
    $stmt = $pdo->prepare("SELECT * FROM student_fees WHERE student_id = ? AND category_id = ? AND term = ?");
    $stmt->execute([$studentId, $categoryId, $term]);
    $fee = $stmt->fetch();
    
    if ($fee) {
        $newPaid = $fee['amount_paid'] + $amount;
        $newBalance = max(0, $fee['amount'] - $newPaid);
        $newStatus = $newBalance == 0 ? 'paid' : ($newPaid > 0 ? 'partial' : 'unpaid');
        
        $pdo->prepare("UPDATE student_fees SET amount_paid = ?, balance = ?, payment_status = ? WHERE id = ?")
            ->execute([$newPaid, $newBalance, $newStatus, $fee['id']]);
        
        $pdo->prepare("INSERT INTO fee_payments (student_id, category_id, amount, payment_method, payment_date, recorded_by) VALUES (?, ?, ?, ?, ?, ?)")
            ->execute([$studentId, $categoryId, $amount, $method, $paymentDate, $teacherName]);
        
        logActivity($pdo, 'create', "Recorded fee payment: GH¢$amount for student ID $studentId", $teacherName, 'fee_payment', $pdo->lastInsertId());
        
        $message = '<div class="success-msg"><i class="fas fa-check-circle"></i> Payment of GH¢' . number_format($amount, 2) . ' recorded successfully!</div>';
    } else {
        $error = '<div class="error-msg"><i class="fas fa-exclamation-circle"></i> Fee record not found.</div>';
    }
}

if (isset($_POST['refund_payment'])) {
    $paymentId = (int)$_POST['payment_id'];
    $stmt = $pdo->prepare("SELECT * FROM fee_payments WHERE id = ?");
    $stmt->execute([$paymentId]);
    $payment = $stmt->fetch();
    
    if ($payment) {
        $stmt = $pdo->prepare("SELECT * FROM student_fees WHERE student_id = ? AND category_id = ? AND term = ?");
        $stmt->execute([$payment['student_id'], $payment['category_id'], $payment['term']]);
        $fee = $stmt->fetch();
        
        if ($fee) {
            $newPaid = max(0, $fee['amount_paid'] - $payment['amount']);
            $newBalance = max(0, $fee['amount'] - $newPaid);
            $newStatus = $newBalance == 0 ? 'paid' : ($newPaid > 0 ? 'partial' : 'unpaid');
            
            $pdo->prepare("UPDATE student_fees SET amount_paid = ?, balance = ?, payment_status = ? WHERE id = ?")
                ->execute([$newPaid, $newBalance, $newStatus, $fee['id']]);
            
            $pdo->prepare("DELETE FROM fee_payments WHERE id = ?")->execute([$paymentId]);
            logActivity($pdo, 'delete', "Refunded fee payment ID: $paymentId", $teacherName, 'fee_refund', $paymentId);
            
            $message = '<div class="success-msg"><i class="fas fa-check-circle"></i> Payment refunded successfully!</div>';
        }
    }
}

$myClasses = $pdo->query("SELECT c.* FROM classes c 
    INNER JOIN teacher_classes tc ON c.id = tc.class_id 
    WHERE tc.teacher_id = $teacherId ORDER BY c.level, c.class_name, c.section")->fetchAll();

$classFeesSummary = [];
if (!empty($myClasses)) {
    foreach ($myClasses as $cls) {
        $stmt = $pdo->prepare("SELECT 
            SUM(sf.amount) as total_amount,
            SUM(sf.balance) as total_balance,
            COUNT(DISTINCT s.id) as student_count
            FROM student_fees sf
            JOIN students s ON sf.student_id = s.id
            WHERE s.class_id = ? AND sf.term = ?");
        $stmt->execute([$cls['id'], $selectedTerm]);
        $summary = $stmt->fetch();
        
        $classFeesSummary[$cls['id']] = [
            'total' => $summary['total_amount'] ?? 0,
            'balance' => $summary['total_balance'] ?? 0,
            'student_count' => $summary['student_count'] ?? 0
        ];
    }
}

$studentFees = [];
$selectedStudentId = isset($_GET['student_id']) ? (int)$_GET['student_id'] : 0;

if ($selectedClass > 0) {
    $stmt = $pdo->prepare("SELECT 
        s.id as student_id, s.name as student_name, s.student_id as student_number,
        c.class_name, c.section
        FROM students s
        JOIN classes c ON s.class_id = c.id
        WHERE s.class_id = ? AND s.status = 'active'
        ORDER BY s.name");
    $stmt->execute([$selectedClass]);
    $studentsInClass = $stmt->fetchAll();
    
    if ($selectedStudentId > 0) {
        $stmt = $pdo->prepare("SELECT sf.*, fc.name as category_name, fc.is_mandatory 
            FROM student_fees sf 
            JOIN fee_categories fc ON sf.category_id = fc.id 
            WHERE sf.student_id = ? AND sf.term = ?
            ORDER BY fc.is_mandatory DESC, fc.name");
        $stmt->execute([$selectedStudentId, $selectedTerm]);
        $studentFees = $stmt->fetchAll();
        
        $stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
        $stmt->execute([$selectedStudentId]);
        $selectedStudent = $stmt->fetch();
        
        $stmt = $pdo->prepare("SELECT fp.*, fc.name as category_name 
            FROM fee_payments fp 
            JOIN fee_categories fc ON fp.category_id = fc.id
            WHERE fp.student_id = ? ORDER BY fp.payment_date DESC LIMIT 20");
        $stmt->execute([$selectedStudentId]);
        $paymentHistory = $stmt->fetchAll();
    }
}

$totalAmount = 0;
$totalPaid = 0;
$totalBalance = 0;
foreach ($studentFees as $fee) {
    $totalAmount += $fee['amount'];
    $totalPaid += $fee['amount_paid'];
    $totalBalance += $fee['balance'];
}

$feeCategories = $pdo->query("SELECT * FROM fee_categories ORDER BY is_mandatory DESC, name")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>School Fees - Teacher Portal</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        .fee-header {
            background: linear-gradient(135deg, #27ae60, #2ecc71);
            color: white;
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 25px;
        }
        .fee-header h2 { margin: 0 0 5px; }
        .fee-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        .summary-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 3px 15px rgba(0,0,0,0.08);
        }
        .summary-card h3 { font-size: 28px; margin: 0; color: #2c3e50; }
        .summary-card p { margin: 5px 0 0; color: #888; font-size: 13px; }
        .summary-card.total { border-top: 4px solid #3498db; }
        .summary-card.paid { border-top: 4px solid #27ae60; }
        .summary-card.balance { border-top: 4px solid #e74c3c; }
        .class-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 3px 15px rgba(0,0,0,0.08);
            display: flex;
            justify-content: space-between;
            align-items: center;
            text-decoration: none;
            color: inherit;
            transition: all 0.3s;
        }
        .class-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.12);
        }
        .class-card.selected { border: 2px solid #27ae60; }
        .class-info h4 { margin: 0; color: #2c3e50; }
        .class-info p { margin: 5px 0 0; font-size: 13px; color: #888; }
        .class-amount { text-align: right; }
        .class-amount .total { font-size: 18px; font-weight: bold; color: #2c3e50; }
        .class-amount .balance { font-size: 14px; color: #e74c3c; }
        .student-table { width: 100%; border-collapse: collapse; background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 3px 15px rgba(0,0,0,0.08); }
        .student-table th { background: #27ae60; color: white; padding: 12px 15px; text-align: left; }
        .student-table td { padding: 12px 15px; border-bottom: 1px solid #eee; }
        .student-table tr:last-child td { border-bottom: none; }
        .student-table tr:hover { background: #f8f9fa; }
        .student-table tr.clickable { cursor: pointer; }
        .student-table tr.clickable:hover { background: #e8f5e9; }
        .status-badge { padding: 4px 12px; border-radius: 15px; font-size: 11px; font-weight: 600; display: inline-block; }
        .status-paid { background: #d4edda; color: #155724; }
        .status-partial { background: #fff3cd; color: #856404; }
        .status-unpaid { background: #f8d7da; color: #721c24; }
        .term-selector, .class-selector { background: white; padding: 15px; border-radius: 10px; margin-bottom: 20px; display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
        .term-selector label, .class-selector label { font-weight: 600; }
        .term-selector select, .class-selector select { padding: 8px 15px; border-radius: 8px; border: 1px solid #ddd; }
        .back-link { display: inline-block; margin-bottom: 15px; color: #27ae60; text-decoration: none; }
        .back-link:hover { text-decoration: underline; }
        .payment-modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center; }
        .payment-modal.active { display: flex; }
        .payment-form { background: white; padding: 30px; border-radius: 15px; width: 90%; max-width: 500px; }
        .payment-form h3 { margin: 0 0 20px; color: #27ae60; }
        .payment-form .form-group { margin-bottom: 15px; }
        .payment-form label { display: block; margin-bottom: 5px; font-weight: 600; }
        .payment-form input, .payment-form select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 8px; }
        .payment-form .btn-row { display: flex; gap: 10px; margin-top: 20px; }
        .history-table { width: 100%; border-collapse: collapse; background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 3px 15px rgba(0,0,0,0.08); margin-top: 20px; }
        .history-table th { background: #95a5a6; color: white; padding: 10px 12px; text-align: left; font-size: 13px; }
        .history-table td { padding: 10px 12px; border-bottom: 1px solid #eee; font-size: 13px; }
        .btn-sm { padding: 5px 10px; font-size: 12px; border-radius: 5px; border: none; cursor: pointer; }
        .btn-sm.btn-danger { background: #e74c3c; color: white; }
        .btn-sm.btn-danger:hover { background: #c0392b; }
        .fee-card { background: white; border-radius: 10px; padding: 15px; margin-bottom: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); display: flex; justify-content: space-between; align-items: center; }
        .fee-card.paid { border-left: 4px solid #27ae60; }
        .fee-card.unpaid { border-left: 4px solid #e74c3c; }
        .fee-card.partial { border-left: 4px solid #f39c12; }
        .fee-card .info h4 { margin: 0; font-size: 14px; }
        .fee-card .info p { margin: 3px 0 0; font-size: 12px; color: #888; }
        .fee-card .amount { text-align: right; }
        .fee-card .amount .total { font-weight: bold; }
        .fee-card .amount .paid { color: #27ae60; font-size: 12px; }
        .fee-card .amount .balance { color: #e74c3c; font-size: 12px; }
        .record-payment-btn { background: #27ae60; color: white; padding: 8px 15px; border-radius: 8px; border: none; cursor: pointer; font-size: 13px; }
        .record-payment-btn:hover { background: #219a52; }
    </style>
</head>
<body>
    <button class="mobile-menu-toggle"><i class="fas fa-bars"></i></button>
    <div class="sidebar-overlay"></div>
    <nav class="sidebar">
        <div class="sidebar-brand"><i class="fas fa-chalkboard-teacher"></i> Teacher Portal</div>
        <ul class="sidebar-menu">
            <li><a href="index.php"><i class="fas fa-home"></i> Dashboard</a></li>
            <li><a href="announcements.php"><i class="fas fa-bullhorn"></i> Announcements</a></li>
            <li><a href="my_classes.php"><i class="fas fa-school"></i> My Classes</a></li>
            <li><a href="enter_scores.php"><i class="fas fa-edit"></i> Enter Scores</a></li>
            <li><a href="attendance.php"><i class="fas fa-calendar-check"></i> Attendance</a></li>
            <li><a href="results.php"><i class="fas fa-chart-line"></i> View Results</a></li>
            <li><a href="fees.php" class="active"><i class="fas fa-money-bill"></i> School Fees</a></li>
            <li><a href="lessons.php"><i class="fas fa-book-open"></i> Lessons</a></li>
            <li><a href="lesson_plans.php"><i class="fas fa-calendar-alt"></i> Lesson Plans</a></li>
            <li><a href="test_books.php"><i class="fas fa-file-alt"></i> Test Books</a></li>
            <li><a href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
            <li><a href="change_password.php"><i class="fas fa-key"></i> Change Password</a></li>
            <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </nav>
    
    <main class="main-content">
        <div class="header">
            <h1><i class="fas fa-money-bill"></i> School Fees Management</h1>
        </div>
        
        <div class="content">
            <?php echo $message; ?>
            <?php echo $error; ?>
            
            <div class="fee-header">
                <h2><i class="fas fa-coins"></i> <?php echo htmlspecialchars($schoolName); ?></h2>
                <p>Class Fee Overview - <?php echo htmlspecialchars($teacherName); ?></p>
            </div>
            
            <div class="term-selector">
                <label><i class="fas fa-calendar"></i> Select Term:</label>
                <select onchange="window.location.href='?term='+this.value+'<?php echo $selectedClass ? '&class_id='.$selectedClass : ''; ?>'">
                    <?php foreach ($terms as $t): ?>
                    <option value="<?php echo $t; ?>" <?php echo $selectedTerm == $t ? 'selected' : ''; ?>><?php echo $t; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <?php if ($selectedClass > 0 && $selectedStudentId > 0): ?>
            <a href="fees.php?term=<?php echo $selectedTerm; ?>&class_id=<?php echo $selectedClass; ?>" class="back-link">
                <i class="fas fa-arrow-left"></i> Back to Students
            </a>
            
            <div class="fee-summary">
                <div class="summary-card total">
                    <h3>GH¢ <?php echo number_format($totalAmount, 2); ?></h3>
                    <p>Total Fees</p>
                </div>
                <div class="summary-card paid">
                    <h3>GH¢ <?php echo number_format($totalPaid, 2); ?></h3>
                    <p>Amount Paid</p>
                </div>
                <div class="summary-card balance">
                    <h3>GH¢ <?php echo number_format($totalBalance, 2); ?></h3>
                    <p>Outstanding</p>
                </div>
            </div>
            
            <div style="background: white; padding: 20px; border-radius: 10px; margin-bottom: 20px; box-shadow: 0 3px 15px rgba(0,0,0,0.08);">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h3 style="margin: 0;"><?php echo htmlspecialchars($selectedStudent['name'] ?? ''); ?></h3>
                        <p style="margin: 5px 0 0; color: #888; font-size: 13px;">ID: <?php echo htmlspecialchars($selectedStudent['student_id'] ?? ''); ?></p>
                    </div>
                    <?php if ($totalBalance > 0): ?>
                    <button class="record-payment-btn" onclick="openPaymentModal()">
                        <i class="fas fa-plus"></i> Record Payment
                    </button>
                    <?php endif; ?>
                </div>
            </div>
            
            <h3 style="margin: 20px 0 15px;"><i class="fas fa-list"></i> Fee Details</h3>
            
            <?php if (empty($studentFees)): ?>
            <div style="background: white; padding: 40px; border-radius: 10px; text-align: center; color: #888;">
                <i class="fas fa-receipt" style="font-size: 50px; margin-bottom: 15px;"></i>
                <p>No fee records found for this student.</p>
            </div>
            <?php else: foreach ($studentFees as $fee): ?>
            <div class="fee-card <?php echo $fee['payment_status']; ?>">
                <div class="info">
                    <h4><?php echo htmlspecialchars($fee['category_name']); ?></h4>
                    <p>
                        <?php echo $fee['is_mandatory'] ? '<i class="fas fa-exclamation-circle" style="color:#e74c3c;"></i> Mandatory' : '<i class="fas fa-info-circle" style="color:#27ae60;"></i> Optional'; ?>
                        | <span class="status-badge status-<?php echo $fee['payment_status']; ?>"><?php echo ucfirst($fee['payment_status']); ?></span>
                    </p>
                </div>
                <div class="amount">
                    <div class="total">GH¢ <?php echo number_format($fee['amount'], 2); ?></div>
                    <?php if ($fee['amount_paid'] > 0): ?>
                    <div class="paid">Paid: GH¢ <?php echo number_format($fee['amount_paid'], 2); ?></div>
                    <?php endif; ?>
                    <?php if ($fee['balance'] > 0): ?>
                    <div class="balance">Due: GH¢ <?php echo number_format($fee['balance'], 2); ?></div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; endif; ?>
            
            <?php if (!empty($paymentHistory)): ?>
            <h3 style="margin: 30px 0 15px;"><i class="fas fa-history"></i> Payment History</h3>
            <table class="history-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Category</th>
                        <th>Amount</th>
                        <th>Method</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($paymentHistory as $pay): ?>
                    <tr>
                        <td><?php echo date('M d, Y', strtotime($pay['payment_date'])); ?></td>
                        <td><?php echo htmlspecialchars($pay['category_name']); ?></td>
                        <td>GH¢ <?php echo number_format($pay['amount'], 2); ?></td>
                        <td><?php echo htmlspecialchars(ucfirst($pay['payment_method'] ?? 'Cash')); ?></td>
                        <td>
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Refund this payment?');">
                                <input type="hidden" name="payment_id" value="<?php echo $pay['id']; ?>">
                                <button type="submit" name="refund_payment" class="btn-sm btn-danger">
                                    <i class="fas fa-undo"></i> Refund
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
            
            <?php elseif ($selectedClass > 0): ?>
            <a href="fees.php?term=<?php echo $selectedTerm; ?>" class="back-link">
                <i class="fas fa-arrow-left"></i> Back to All Classes
            </a>
            
            <h3 style="margin: 20px 0 15px;"><i class="fas fa-users"></i> Students in <?php 
                foreach ($myClasses as $cls) {
                    if ($cls['id'] == $selectedClass) {
                        echo htmlspecialchars($cls['class_name'] . ' ' . $cls['section']);
                        break;
                    }
                }
            ?></h3>
            
            <?php if (empty($studentsInClass)): ?>
            <div style="background: white; padding: 40px; border-radius: 10px; text-align: center; color: #888;">
                <i class="fas fa-users" style="font-size: 50px; margin-bottom: 15px;"></i>
                <p>No students found in this class.</p>
            </div>
            <?php else: ?>
            <table class="student-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Student Name</th>
                        <th>Student ID</th>
                        <th>Total (GH¢)</th>
                        <th>Paid (GH¢)</th>
                        <th>Balance (GH¢)</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $count = 1;
                    foreach ($studentsInClass as $student): 
                        $stmt = $pdo->prepare("SELECT SUM(amount) as total, SUM(amount_paid) as paid, SUM(balance) as balance FROM student_fees WHERE student_id = ? AND term = ?");
                        $stmt->execute([$student['student_id'], $selectedTerm]);
                        $feeSummary = $stmt->fetch();
                        
                        $total = $feeSummary['total'] ?? 0;
                        $paid = $feeSummary['paid'] ?? 0;
                        $balance = $feeSummary['balance'] ?? 0;
                        $status = $total == 0 ? 'none' : ($balance == 0 ? 'paid' : ($paid > 0 ? 'partial' : 'unpaid'));
                    ?>
                    <tr>
                        <td><?php echo $count++; ?></td>
                        <td><?php echo htmlspecialchars($student['student_name']); ?></td>
                        <td><?php echo htmlspecialchars($student['student_number']); ?></td>
                        <td><?php echo number_format($total, 2); ?></td>
                        <td><?php echo number_format($paid, 2); ?></td>
                        <td><?php echo number_format($balance, 2); ?></td>
                        <td>
                            <?php if ($status != 'none'): ?>
                            <span class="status-badge status-<?php echo $status; ?>"><?php echo ucfirst($status); ?></span>
                            <?php else: ?>
                            <span style="color: #888;">No fees</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="fees.php?term=<?php echo $selectedTerm; ?>&class_id=<?php echo $selectedClass; ?>&student_id=<?php echo $student['student_id']; ?>" class="btn btn-sm btn-primary">
                                <i class="fas fa-eye"></i> View
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
            
            <?php else: ?>
            
            <h3 style="margin: 20px 0 15px;"><i class="fas fa-school"></i> My Classes - Fee Overview</h3>
            
            <?php if (empty($myClasses)): ?>
            <div style="background: white; padding: 40px; border-radius: 10px; text-align: center; color: #888;">
                <i class="fas fa-school" style="font-size: 50px; margin-bottom: 15px;"></i>
                <p>No classes assigned to you yet.</p>
            </div>
            <?php else: ?>
            <?php foreach ($myClasses as $cls): 
                $summary = $classFeesSummary[$cls['id']] ?? ['total' => 0, 'balance' => 0, 'student_count' => 0];
            ?>
            <a href="fees.php?term=<?php echo $selectedTerm; ?>&class_id=<?php echo $cls['id']; ?>" class="class-card">
                <div class="class-info">
                    <h4><i class="fas fa-school" style="color: #27ae60; margin-right: 10px;"></i><?php echo htmlspecialchars($cls['class_name'] . ' ' . $cls['section']); ?></h4>
                    <p><i class="fas fa-users"></i> <?php echo $summary['student_count']; ?> Students with fees | Level: <?php echo ucfirst($cls['level']); ?></p>
                </div>
                <div class="class-amount">
                    <div class="total">GH¢ <?php echo number_format($summary['total'], 2); ?></div>
                    <?php if ($summary['balance'] > 0): ?>
                    <div class="balance">Outstanding: GH¢ <?php echo number_format($summary['balance'], 2); ?></div>
                    <?php else: ?>
                    <div style="color: #27ae60; font-size: 12px;"><i class="fas fa-check-circle"></i> All Paid</div>
                    <?php endif; ?>
                </div>
            </a>
            <?php endforeach; ?>
            <?php endif; ?>
            
            <?php endif; ?>
        </div>
        
        <div class="payment-modal" id="paymentModal">
            <div class="payment-form">
                <h3><i class="fas fa-money-bill"></i> Record Payment</h3>
                <?php if ($selectedStudentId > 0 && !empty($studentFees)): ?>
                <form method="POST">
                    <input type="hidden" name="student_id" value="<?php echo $selectedStudentId; ?>">
                    <input type="hidden" name="term" value="<?php echo $selectedTerm; ?>">
                    
                    <div class="form-group">
                        <label>Fee Category</label>
                        <select name="category_id" required>
                            <?php foreach ($studentFees as $fee): ?>
                            <?php if ($fee['balance'] > 0): ?>
                            <option value="<?php echo $fee['category_id']; ?>">
                                <?php echo htmlspecialchars($fee['category_name']); ?> (Balance: GH¢<?php echo number_format($fee['balance'], 2); ?>)
                            </option>
                            <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Amount (GH¢)</label>
                        <input type="number" name="amount" step="0.01" min="0.01" max="<?php echo $totalBalance; ?>" required placeholder="Enter amount">
                    </div>
                    
                    <div class="form-group">
                        <label>Payment Method</label>
                        <select name="payment_method" required>
                            <option value="cash">Cash</option>
                            <option value="mobile_money">Mobile Money</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="cheque">Cheque</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Payment Date</label>
                        <input type="date" name="payment_date" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    
                    <div class="btn-row">
                        <button type="submit" name="record_payment" class="btn btn-primary" style="flex: 1;">
                            <i class="fas fa-save"></i> Record Payment
                        </button>
                        <button type="button" onclick="closePaymentModal()" class="btn" style="flex: 1; background: #95a5a6; color: white;">
                            Cancel
                        </button>
                    </div>
                </form>
                <?php else: ?>
                <p style="color: #888; text-align: center;">No outstanding fees for this student.</p>
                <button type="button" onclick="closePaymentModal()" class="btn" style="width: 100%; background: #95a5a6; color: white;">
                    Close
                </button>
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
    
    <script>
        function openPaymentModal() {
            document.getElementById('paymentModal').classList.add('active');
        }
        function closePaymentModal() {
            document.getElementById('paymentModal').classList.remove('active');
        }
        window.onclick = function(event) {
            if (event.target == document.getElementById('paymentModal')) {
                closePaymentModal();
            }
        }
    </script>
    <script src="../assets/js/script.js"></script>
</body>
</html>
