<?php
require_once '../config/database.php';

if (!isset($_SESSION['parent_id'])) {
    redirect('../login.php');
}

$parentId = $_SESSION['parent_id'];
$studentId = $_SESSION['parent_student_id'];

$settings = [];
$stmt = $pdo->query("SELECT * FROM school_settings");
while ($row = $stmt->fetch()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
$schoolName = $settings['school_name'] ?? 'School Management System';
$schoolLogo = $settings['school_logo'] ?? '';

$results = [];
if ($studentId) {
    $stmt = $pdo->prepare("SELECT a.*, sub.subject_name FROM student_assessments a 
        LEFT JOIN subjects sub ON a.subject_id = sub.id 
        WHERE a.student_id = ? ORDER BY a.term, sub.subject_name");
    $stmt->execute([$studentId]);
    $results = $stmt->fetchAll();
}

$terms = array_unique(array_column($results, 'term'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Results - Parent Portal</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        .portal-header { background: linear-gradient(135deg, #f39c12, #d35400); color: white; padding: 25px 30px; border-radius: 15px; margin-bottom: 25px; }
        .result-card { background: white; border-radius: 15px; padding: 20px; margin-bottom: 20px; box-shadow: 0 3px 15px rgba(0,0,0,0.08); }
        .result-card h3 { margin: 0 0 15px 0; color: #2c3e50; }
        .result-card h3 i { color: #f39c12; margin-right: 8px; }
        .result-table { width: 100%; border-collapse: collapse; }
        .result-table th { background: #f39c12; color: white; padding: 12px; text-align: left; }
        .result-table td { padding: 12px; border-bottom: 1px solid #eee; }
        .result-table tr:hover { background: #f8f9fa; }
        .grade-badge { padding: 4px 12px; border-radius: 15px; color: white; font-weight: 600; }
        .grade-a { background: #27ae60; }
        .grade-b { background: #2ecc71; }
        .grade-c { background: #f39c12; }
        .grade-d { background: #e67e22; }
        .grade-e { background: #e74c3c; }
        .grade-f { background: #c0392b; }
        .term-summary { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin-top: 15px; }
        .summary-item { text-align: center; padding: 15px; background: #f8f9fa; border-radius: 10px; }
        .summary-item h4 { margin: 0; color: #f39c12; font-size: 24px; }
        .summary-item p { margin: 5px 0 0 0; color: #888; font-size: 12px; }
    </style>
</head>
<body>
    <button class="mobile-menu-toggle">
        <i class="fas fa-bars"></i>
    </button>
    <div class="sidebar-overlay"></div>
    <nav class="sidebar">
        <div class="sidebar-brand"><i class="fas fa-users"></i> Parent Portal</div>
        <ul class="sidebar-menu">
            <li><a href="index.php"><i class="fas fa-home"></i> Dashboard</a></li>
            <li><a href="announcements.php"><i class="fas fa-bullhorn"></i> Announcements</a></li>
            <li><a href="results.php" class="active"><i class="fas fa-chart-line"></i> Results</a></li>
            <li><a href="reportcard.php"><i class="fas fa-file-alt"></i> Report Card</a></li>
            <li><a href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
            <li><a href="../logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </nav>
    
    <main class="main-content">
        <div class="header">
            <h1><i class="fas fa-chart-line"></i> Student Results</h1>
            <div class="header-right">
                <div class="theme-switcher">
                    <button class="theme-btn" data-theme="blue" title="Blue"></button>
                    <button class="theme-btn" data-theme="green" title="Green"></button>
                    <button class="theme-btn" data-theme="purple" title="Purple"></button>
                    <button class="theme-btn" data-theme="red" title="Red"></button>
                    <button class="theme-btn" data-theme="orange" title="Orange"></button>
                    <button class="theme-btn" data-theme="dark" title="Dark"></button>
                    <button class="theme-btn" data-theme="ocean" title="Ocean"></button>
                    <button class="theme-btn" data-theme="sunset" title="Sunset"></button>
                </div>
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
            <div class="portal-header" style="display: block; text-align: center;">
                <h2 style="margin: 0;"><i class="fas fa-chart-line"></i> Your Child's Academic Results</h2>
                <p style="margin: 5px 0 0 0; opacity: 0.9;">Track your child's academic performance</p>
            </div>
            
            <?php if (empty($results)): ?>
            <div class="result-card" style="text-align: center;">
                <p style="color: #999;">No results available yet. Scores will appear here once entered by teachers.</p>
            </div>
            <?php else: ?>
                <?php foreach ($terms as $term): 
                    $termResults = array_filter($results, fn($r) => $r['term'] == $term);
                    $totalMarks = 0;
                    $count = count($termResults);
                    foreach ($termResults as $r) {
                        $totalMarks += ($r['test1'] ?? 0) + ($r['test2'] ?? 0) + ($r['test3'] ?? 0) + ($r['project'] ?? 0) + ($r['class_assessment'] ?? 0) + ($r['exam'] ?? 0);
                    }
                    $average = $count > 0 ? $totalMarks / $count : 0;
                ?>
                <div class="result-card">
                    <h3><i class="fas fa-calendar"></i> <?php echo htmlspecialchars($term); ?> - Academic Year <?php echo htmlspecialchars($termResults[0]['academic_year'] ?? '2025-2026'); ?></h3>
                    
                    <div class="term-summary">
                        <div class="summary-item">
                            <h4><?php echo $count; ?></h4>
                            <p>Subjects</p>
                        </div>
                        <div class="summary-item">
                            <h4><?php echo number_format($totalMarks, 0); ?></h4>
                            <p>Total Marks</p>
                        </div>
                        <div class="summary-item">
                            <h4><?php echo number_format($average, 1); ?>%</h4>
                            <p>Average</p>
                        </div>
                        <div class="summary-item">
                            <h4 style="color: <?php echo $average >= 60 ? '#27ae60' : '#e74c3c'; ?>">
                                <?php echo $average >= 80 ? 'A' : ($average >= 70 ? 'B' : ($average >= 60 ? 'C' : ($average >= 50 ? 'D' : 'F'))); ?>
                            </h4>
                            <p>Overall Grade</p>
                        </div>
                    </div>
                    
                    <table class="result-table" style="margin-top: 15px;">
                        <thead>
                            <tr>
                                <th>Subject</th>
                                <th>Test 1</th>
                                <th>Test 2</th>
                                <th>Test 3</th>
                                <th>Project</th>
                                <th>CA</th>
                                <th>Exam</th>
                                <th>Total</th>
                                <th>Grade</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($termResults as $r): 
                                $total = ($r['test1'] ?? 0) + ($r['test2'] ?? 0) + ($r['test3'] ?? 0) + ($r['project'] ?? 0) + ($r['class_assessment'] ?? 0) + ($r['exam'] ?? 0);
                                $grade = $r['grade'] ?? '';
                            ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($r['subject_name']); ?></strong></td>
                                <td><?php echo $r['test1'] !== null ? number_format($r['test1'], 0) : '-'; ?></td>
                                <td><?php echo $r['test2'] !== null ? number_format($r['test2'], 0) : '-'; ?></td>
                                <td><?php echo $r['test3'] !== null ? number_format($r['test3'], 0) : '-'; ?></td>
                                <td><?php echo $r['project'] !== null ? number_format($r['project'], 0) : '-'; ?></td>
                                <td><?php echo $r['class_assessment'] !== null ? number_format($r['class_assessment'], 0) : '-'; ?></td>
                                <td><?php echo $r['exam'] !== null ? number_format($r['exam'], 0) : '-'; ?></td>
                                <td><strong><?php echo number_format($total, 0); ?></strong></td>
                                <td><?php if ($grade): ?><span class="grade-badge grade-<?php echo strtolower($grade[0]); ?>"><?php echo $grade; ?></span><?php endif; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>
    <script src="../assets/js/script.js"></script>
</body>
</html>
