<?php
require_once '../config/database.php';

if (!isset($_SESSION['parent_id'])) {
    redirect('../login.php');
}

$parentId = $_SESSION['parent_id'];
$parentName = $_SESSION['parent_name'];
$studentId = $_SESSION['parent_student_id'];

$settings = [];
$stmt = $pdo->query("SELECT * FROM school_settings");
while ($row = $stmt->fetch()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
$schoolName = $settings['school_name'] ?? 'School Management System';

$selectedTerm = isset($_GET['term']) ? sanitize($_GET['term']) : 'Term 1';
$terms = ['Term 1', 'Term 2', 'Term 3'];

$studentFees = [];
if ($studentId) {
    $stmt = $pdo->prepare("SELECT sf.*, fc.name as category_name, fc.is_mandatory 
                          FROM student_fees sf 
                          JOIN fee_categories fc ON sf.category_id = fc.id 
                          WHERE sf.student_id = ? AND sf.term = ?
                          ORDER BY fc.is_mandatory DESC, fc.name");
    $stmt->execute([$studentId, $selectedTerm]);
    $studentFees = $stmt->fetchAll();
}

$totalAmount = 0;
$totalPaid = 0;
$totalBalance = 0;
foreach ($studentFees as $fee) {
    $totalAmount += $fee['amount'];
    $totalPaid += $fee['amount_paid'];
    $totalBalance += $fee['balance'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>School Fees - Parent Portal</title>
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
        .fee-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 3px 15px rgba(0,0,0,0.08);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .fee-card.paid { border-left: 4px solid #27ae60; }
        .fee-card.unpaid { border-left: 4px solid #e74c3c; }
        .fee-card.partial { border-left: 4px solid #f39c12; }
        .fee-info h4 { margin: 0; color: #2c3e50; }
        .fee-info p { margin: 5px 0 0; font-size: 13px; color: #888; }
        .fee-amount { text-align: right; }
        .fee-amount .amount { font-size: 20px; font-weight: bold; color: #2c3e50; }
        .fee-amount .paid { font-size: 12px; color: #27ae60; }
        .fee-amount .balance { font-size: 14px; color: #e74c3c; }
        .status-badge { padding: 4px 12px; border-radius: 15px; font-size: 11px; font-weight: 600; display: inline-block; margin-top: 5px; }
        .status-paid { background: #d4edda; color: #155724; }
        .status-unpaid { background: #f8d7da; color: #721c24; }
        .status-partial { background: #fff3cd; color: #856404; }
        .term-selector { background: white; padding: 15px; border-radius: 10px; margin-bottom: 20px; display: flex; gap: 10px; align-items: center; }
        .term-selector label { font-weight: 600; }
        .term-selector select { padding: 8px 15px; border-radius: 8px; border: 1px solid #ddd; }
        .payment-notice {
            background: #fff3cd;
            border: 1px solid #ffc107;
            color: #856404;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .payment-notice h4 { margin: 0 0 5px; }
        .payment-notice p { margin: 0; font-size: 13px; }
    </style>
</head>
<body>
    <button class="mobile-menu-toggle"><i class="fas fa-bars"></i></button>
    <div class="sidebar-overlay"></div>
    <nav class="sidebar">
        <div class="sidebar-brand"><i class="fas fa-user-tie"></i> Parent Portal</div>
        <ul class="sidebar-menu">
            <li><a href="index.php"><i class="fas fa-home"></i> Dashboard</a></li>
            <li><a href="announcements.php"><i class="fas fa-bullhorn"></i> Announcements</a></li>
            <li><a href="results.php"><i class="fas fa-chart-line"></i> Results</a></li>
            <li><a href="reportcard.php"><i class="fas fa-file-alt"></i> Report Card</a></li>
            <li><a href="fees.php" class="active"><i class="fas fa-money-bill"></i> School Fees</a></li>
            <li><a href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
            <li><a href="change_password.php"><i class="fas fa-key"></i> Change Password</a></li>
            <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </nav>
    
    <main class="main-content">
        <div class="header">
            <h1><i class="fas fa-money-bill"></i> School Fees</h1>
        </div>
        
        <div class="content">
            <div class="fee-header">
                <h2><i class="fas fa-coins"></i> <?php echo $schoolName; ?></h2>
                <p>Fee Payment Portal</p>
            </div>
            
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
                    <p>Balance Outstanding</p>
                </div>
            </div>
            
            <?php if ($totalBalance > 0): ?>
            <div class="payment-notice">
                <h4><i class="fas fa-exclamation-triangle"></i> Payment Required</h4>
                <p>Please contact the school administration for fee payment arrangements or visit the school to settle outstanding fees.</p>
            </div>
            <?php else: ?>
            <div style="background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 10px; margin-bottom: 20px;">
                <h4 style="margin: 0 0 5px;"><i class="fas fa-check-circle"></i> All Fees Paid!</h4>
                <p style="margin: 0; font-size: 13px;">Thank you for your payment. Your child's school fees are fully paid for this term.</p>
            </div>
            <?php endif; ?>
            
            <div class="term-selector">
                <label><i class="fas fa-calendar"></i> Select Term:</label>
                <select onchange="window.location.href='?term='+this.value">
                    <?php foreach ($terms as $t): ?>
                    <option value="<?php echo $t; ?>" <?php echo $selectedTerm == $t ? 'selected' : ''; ?>><?php echo $t; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <h3 style="margin: 20px 0 15px;"><i class="fas fa-list"></i> Fee Breakdown - <?php echo $selectedTerm; ?></h3>
            
            <?php if (empty($studentFees)): ?>
            <div style="background: white; padding: 40px; border-radius: 10px; text-align: center; color: #888;">
                <i class="fas fa-receipt" style="font-size: 50px; margin-bottom: 15px;"></i>
                <p>No fee records found for this term.</p>
            </div>
            <?php else: foreach ($studentFees as $fee): ?>
            <div class="fee-card <?php echo $fee['payment_status']; ?>">
                <div class="fee-info">
                    <h4><?php echo htmlspecialchars($fee['category_name']); ?></h4>
                    <p>
                        <?php echo $fee['is_mandatory'] ? '<i class="fas fa-exclamation-circle" style="color:#e74c3c;"></i> Mandatory' : '<i class="fas fa-info-circle" style="color:#27ae60;"></i> Optional'; ?>
                        | Term: <?php echo $fee['term']; ?>
                    </p>
                    <span class="status-badge status-<?php echo $fee['payment_status']; ?>">
                        <?php echo ucfirst($fee['payment_status']); ?>
                    </span>
                </div>
                <div class="fee-amount">
                    <div class="amount">GH¢ <?php echo number_format($fee['amount'], 2); ?></div>
                    <?php if ($fee['amount_paid'] > 0): ?>
                    <div class="paid">Paid: GH¢ <?php echo number_format($fee['amount_paid'], 2); ?></div>
                    <?php endif; ?>
                    <?php if ($fee['balance'] > 0): ?>
                    <div class="balance">Balance: GH¢ <?php echo number_format($fee['balance'], 2); ?></div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; endif; ?>
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
</body>
</html>
