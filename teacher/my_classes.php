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
$schoolLogo = $settings['school_logo'] ?? '';

$teacherSubjects = $pdo->prepare("SELECT sub.* FROM subjects sub 
    INNER JOIN teacher_subjects ts ON sub.id = ts.subject_id 
    WHERE ts.teacher_id = ?");
$teacherSubjects->execute([$teacherId]);
$teacherSubjects = $teacherSubjects->fetchAll();

$myClasses = $pdo->query("SELECT c.* FROM classes c 
    INNER JOIN teacher_classes tc ON c.id = tc.class_id 
    WHERE tc.teacher_id = $teacherId ORDER BY c.level, c.class_name, c.section")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Classes - Teacher Portal</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        .portal-header { background: linear-gradient(135deg, #27ae60, #2ecc71); color: white; padding: 25px 30px; border-radius: 15px; margin-bottom: 25px; }
        .class-card { background: white; border-radius: 15px; padding: 20px; margin-bottom: 15px; border-left: 5px solid #27ae60; box-shadow: 0 3px 15px rgba(0,0,0,0.08); display: flex; justify-content: space-between; align-items: center; }
        .class-info h4 { margin: 0 0 5px 0; color: #2c3e50; font-size: 18px; }
        .class-info p { margin: 0; color: #888; font-size: 14px; }
        .class-stats { display: flex; gap: 20px; }
        .class-stat { text-align: center; }
        .class-stat h3 { margin: 0; color: #27ae60; font-size: 24px; }
        .class-stat p { margin: 5px 0 0 0; font-size: 12px; color: #888; }
        .subject-badge { background: #27ae60; color: white; padding: 5px 12px; border-radius: 15px; font-size: 12px; margin: 5px 5px 5px 0; display: inline-block; }
    </style>
</head>
<body>
    <button class="mobile-menu-toggle">
        <i class="fas fa-bars"></i>
    </button>
    <div class="sidebar-overlay"></div>
    <nav class="sidebar">
        <div class="sidebar-brand"><i class="fas fa-chalkboard-teacher"></i> Teacher Portal</div>
        <ul class="sidebar-menu">
            <li><a href="index.php"><i class="fas fa-home"></i> Dashboard</a></li>
            <li><a href="announcements.php"><i class="fas fa-bullhorn"></i> Announcements</a></li>
            <li><a href="my_classes.php" class="active"><i class="fas fa-school"></i> My Classes</a></li>
            <li><a href="enter_scores.php"><i class="fas fa-edit"></i> Enter Scores</a></li>
            <li><a href="results.php"><i class="fas fa-chart-line"></i> View Results</a></li>
            <li><a href="lessons.php"><i class="fas fa-book-open"></i> Lessons</a></li>
            <li><a href="lesson_plans.php"><i class="fas fa-calendar-alt"></i> Lesson Plans</a></li>
            <li><a href="test_books.php"><i class="fas fa-file-alt"></i> Test Books</a></li>
            <li><a href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
            <li><a href="../logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </nav>
    
    <main class="main-content">
        <div class="header">
            <h1><i class="fas fa-school"></i> My Classes</h1>
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
            <div class="portal-header" style="display: block; text-align: center;">
                <h2 style="margin: 0;"><i class="fas fa-chalkboard-teacher"></i> Welcome, <?php echo htmlspecialchars($teacherName); ?></h2>
                <p style="margin: 5px 0 0 0; opacity: 0.9;">Your assigned classes and subjects</p>
            </div>
            
            <h3 style="margin-bottom: 15px; color: #2c3e50;"><i class="fas fa-book" style="color: #27ae60;"></i> My Subjects</h3>
            <div style="margin-bottom: 25px;">
                <?php foreach ($teacherSubjects as $sub): ?>
                <span class="subject-badge"><?php echo htmlspecialchars($sub['subject_name']); ?></span>
                <?php endforeach; ?>
            </div>
            
            <h3 style="margin-bottom: 15px; color: #2c3e50;"><i class="fas fa-school" style="color: #27ae60;"></i> Assigned Classes</h3>
            
            <?php if (empty($myClasses)): ?>
            <div class="class-card" style="text-align: center; border-left-color: #ccc;">
                <p style="color: #888;">No classes assigned yet. Contact administration.</p>
            </div>
            <?php else: ?>
                <?php foreach ($myClasses as $class): 
                    $studentCount = $pdo->prepare("SELECT COUNT(*) FROM students WHERE class_id = ?");
                    $studentCount->execute([$class['id']]);
                    $studentCount = $studentCount->fetchColumn();
                ?>
                <div class="class-card">
                    <div class="class-info">
                        <h4><i class="fas fa-school" style="color: #27ae60; margin-right: 10px;"></i> 
                            <?php echo htmlspecialchars($class['class_name'] . ' - Section ' . $class['section']); ?>
                        </h4>
                        <p><?php echo ucfirst($class['level']); ?> Level</p>
                    </div>
                    <div class="class-stats">
                        <div class="class-stat">
                            <h3><?php echo $studentCount; ?></h3>
                            <p>Students</p>
                        </div>
                        <a href="enter_scores.php?class=<?php echo $class['id']; ?>" class="btn btn-primary" style="padding: 10px 20px;">
                            <i class="fas fa-edit"></i> Enter Scores
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>
    <script src="../assets/js/script.js"></script>
</body>
</html>
