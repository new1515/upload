<?php
require_once 'config/database.php';

if (!isset($_SESSION['teacher_id'])) {
    redirect('login.php');
}

$planId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$stmt = $pdo->prepare("
    SELECT lp.*, c.class_name, c.section, s.subject_name, t.name as teacher_name 
    FROM lesson_plans lp 
    LEFT JOIN classes c ON lp.class_id = c.id 
    LEFT JOIN subjects s ON lp.subject_id = s.id 
    LEFT JOIN teachers t ON lp.teacher_id = t.id 
    WHERE lp.id = ?
");
$stmt->execute([$planId]);
$plan = $stmt->fetch();

if (!$plan) {
    die('Lesson plan not found');
}

$schoolSettings = [];
$settingsStmt = $pdo->query("SELECT * FROM school_settings");
while ($row = $settingsStmt->fetch()) {
    $schoolSettings[$row['setting_key']] = $row['setting_value'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Lesson Plan - <?php echo htmlspecialchars($plan['title']); ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            font-size: 12px;
        }
        .header {
            text-align: center;
            border-bottom: 3px solid #1e3c72;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .header h1 {
            margin: 0;
            color: #1e3c72;
            font-size: 20px;
        }
        .header p {
            margin: 5px 0 0;
            color: #666;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-bottom: 20px;
        }
        .info-item {
            background: #f0f7ff;
            padding: 10px;
            border-radius: 5px;
        }
        .info-item label {
            font-weight: bold;
            color: #1e3c72;
            display: block;
            font-size: 10px;
        }
        .section {
            margin-bottom: 15px;
        }
        .section h3 {
            background: #1e3c72;
            color: white;
            margin: 0;
            padding: 8px 15px;
            font-size: 12px;
        }
        .section-content {
            border: 1px solid #ddd;
            padding: 15px;
            min-height: 60px;
            white-space: pre-wrap;
        }
        .footer {
            margin-top: 30px;
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 20px;
        }
        .signature-box {
            text-align: center;
            border-top: 1px solid #333;
            padding-top: 5px;
        }
        .signature-box small {
            font-size: 10px;
            color: #666;
        }
        @media print {
            body { padding: 0; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <button onclick="window.print()" class="no-print" style="padding: 10px 20px; margin-bottom: 20px; cursor: pointer;">
        <i class="fas fa-print"></i> Print Lesson Plan
    </button>
    
    <div class="header">
        <h1>GES LESSON PLAN</h1>
        <p><?php echo htmlspecialchars($schoolSettings['school_name'] ?? 'School Name'); ?></p>
        <p style="font-size: 10px; color: #999;">Academic Year: <?php echo $plan['academic_year'] ?? '2025-2026'; ?></p>
    </div>
    
    <div class="info-grid">
        <div class="info-item">
            <label>SUBJECT</label>
            <?php echo htmlspecialchars($plan['subject_name'] ?? 'N/A'); ?>
        </div>
        <div class="info-item">
            <label>CLASS</label>
            <?php echo htmlspecialchars(($plan['class_name'] ?? 'N/A') . ' - Section ' . ($plan['section'] ?? '')); ?>
        </div>
        <div class="info-item">
            <label>WEEK</label>
            <?php echo htmlspecialchars($plan['week'] ?? 'N/A'); ?>
        </div>
        <div class="info-item">
            <label>DURATION</label>
            <?php echo htmlspecialchars($plan['duration'] ?? '40 minutes'); ?>
        </div>
        <div class="info-item">
            <label>TEACHER</label>
            <?php echo htmlspecialchars($plan['teacher_name'] ?? 'N/A'); ?>
        </div>
        <div class="info-item">
            <label>DATE</label>
            <?php echo date('d M Y', strtotime($plan['created_at'])); ?>
        </div>
    </div>
    
    <div class="section">
        <h3>TOPIC / SUB-TOPIC</h3>
        <div class="section-content">
            <strong>Main Topic:</strong> <?php echo htmlspecialchars($plan['topic'] ?? 'N/A'); ?>
            <?php if ($plan['sub_topic']): ?>
            
            <strong>Sub-Topic:</strong> <?php echo htmlspecialchars($plan['sub_topic']); ?>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="section">
        <h3>BEHAVIORAL OBJECTIVES</h3>
        <div class="section-content"><?php echo htmlspecialchars($plan['objectives'] ?? 'N/A'); ?></div>
    </div>
    
    <div class="section">
        <h3>TEACHING & LEARNING ACTIVITIES</h3>
        <div class="section-content"><?php echo htmlspecialchars($plan['activities'] ?? 'N/A'); ?></div>
    </div>
    
    <div class="info-grid">
        <div class="section" style="grid-column: span 2;">
            <h3 style="margin: 0;">TEACHING AIDS / MATERIALS</h3>
            <div class="section-content" style="min-height: 40px;"><?php echo htmlspecialchars($plan['materials'] ?? 'N/A'); ?></div>
        </div>
    </div>
    
    <div class="section">
        <h3>EVALUATION / ASSESSMENT</h3>
        <div class="section-content"><?php echo htmlspecialchars($plan['evaluation'] ?? 'N/A'); ?></div>
    </div>
    
    <div class="footer">
        <div class="signature-box">
            <strong>Teacher</strong><br>
            <small>Signature & Date</small>
        </div>
        <div class="signature-box">
            <strong>Head Teacher</strong><br>
            <small>Signature & Date</small>
        </div>
        <div class="signature-box">
            <strong>Inspector</strong><br>
            <small>Signature & Date</small>
        </div>
    </div>
</body>
</html>
