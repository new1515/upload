<?php
require_once '../config/database.php';

if (!isset($_SESSION['student_id'])) {
    redirect('../login.php');
}

$studentId = $_SESSION['student_id'];
$studentName = $_SESSION['student_name'];

$settings = [];
$stmt = $pdo->query("SELECT * FROM school_settings");
while ($row = $stmt->fetch()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
$schoolName = $settings['school_name'] ?? 'School Management System';
$schoolLogo = $settings['school_logo'] ?? '';

$student = null;
if ($studentId) {
    $stmt = $pdo->prepare("SELECT s.*, c.class_name, c.section, c.level FROM students s LEFT JOIN classes c ON s.class_id = c.id WHERE s.id = ?");
    $stmt->execute([$studentId]);
    $student = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - Student Portal</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        .portal-header { background: linear-gradient(135deg, #e74c3c, #c0392b); color: white; padding: 25px 30px; border-radius: 15px; margin-bottom: 25px; }
        .profile-card { background: white; border-radius: 15px; padding: 30px; box-shadow: 0 3px 15px rgba(0,0,0,0.08); max-width: 600px; margin: 0 auto; }
        .profile-avatar { width: 100px; height: 100px; border-radius: 50%; background: linear-gradient(135deg, #e74c3c, #c0392b); display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; font-size: 40px; color: white; }
        .profile-info { text-align: center; }
        .profile-info h3 { margin: 0 0 5px 0; color: #2c3e50; }
        .profile-info p { margin: 0; color: #888; }
        .profile-details { margin-top: 25px; }
        .profile-row { display: flex; padding: 15px 0; border-bottom: 1px solid #eee; }
        .profile-row i { width: 30px; color: #e74c3c; margin-top: 3px; }
        .profile-row .label { width: 120px; color: #888; font-size: 13px; }
        .profile-row .value { flex: 1; font-weight: 600; color: #2c3e50; }
    </style>
</head>
<body>
    <button class="mobile-menu-toggle">
        <i class="fas fa-bars"></i>
    </button>
    <div class="sidebar-overlay"></div>
    <nav class="sidebar">
        <div class="sidebar-brand"><i class="fas fa-user-graduate"></i> Student Portal</div>
        <ul class="sidebar-menu">
            <li><a href="index.php"><i class="fas fa-home"></i> Dashboard</a></li>
            <li><a href="announcements.php"><i class="fas fa-bullhorn"></i> Announcements</a></li>
            <li><a href="results.php"><i class="fas fa-chart-line"></i> My Results</a></li>
            <li><a href="reportcard.php"><i class="fas fa-file-alt"></i> Report Card</a></li>
            <li><a href="lessons.php"><i class="fas fa-book-open"></i> Lessons</a></li>
            <li><a href="profile.php" class="active"><i class="fas fa-user"></i> Profile</a></li>
            <li><a href="../logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </nav>
    
    <main class="main-content">
        <div class="header">
            <h1><i class="fas fa-user"></i> My Profile</h1>
            <div class="header-right">
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
            <div class="profile-card">
                <div class="profile-avatar">
                    <i class="fas fa-user-graduate"></i>
                </div>
                <div class="profile-info">
                    <h3><?php echo htmlspecialchars($studentName); ?></h3>
                    <p>Student</p>
                </div>
                
                <div class="profile-details">
                    <div class="profile-row">
                        <i class="fas fa-user"></i>
                        <span class="label">Name</span>
                        <span class="value"><?php echo htmlspecialchars($studentName); ?></span>
                    </div>
                    <div class="profile-row">
                        <i class="fas fa-id-badge"></i>
                        <span class="label">Student ID</span>
                        <span class="value"><?php echo $studentId; ?></span>
                    </div>
                    <?php if ($student): ?>
                    <div class="profile-row">
                        <i class="fas fa-school"></i>
                        <span class="label">Class</span>
                        <span class="value"><?php echo htmlspecialchars($student['class_name'] . ' - Section ' . $student['section']); ?></span>
                    </div>
                    <div class="profile-row">
                        <i class="fas fa-venus-mars"></i>
                        <span class="label">Gender</span>
                        <span class="value"><?php echo htmlspecialchars($student['gender']); ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
    <script src="../assets/js/script.js"></script>
</body>
</html>
