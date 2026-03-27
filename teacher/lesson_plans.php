<?php
require_once '../config/database.php';

if (!isset($_SESSION['teacher_id'])) {
    redirect('../login.php');
}

$teacherId = $_SESSION['teacher_id'];
$message = '';

if (isset($_POST['save_plan'])) {
    $title = sanitize($_POST['title']);
    $class_id = (int)$_POST['class_id'];
    $subject_id = (int)$_POST['subject_id'];
    $week = sanitize($_POST['week']);
    $topic = sanitize($_POST['topic']);
    $objectives = sanitize($_POST['objectives']);
    $activities = sanitize($_POST['activities']);
    $materials = sanitize($_POST['materials']);
    $duration = sanitize($_POST['duration']);
    $evaluation = sanitize($_POST['evaluation']);
    
    $pdo->prepare("INSERT INTO lesson_plans (teacher_id, class_id, subject_id, title, week, topic, objectives, activities, materials, duration, evaluation) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)")
        ->execute([$teacherId, $class_id, $subject_id, $title, $week, $topic, $objectives, $activities, $materials, $duration, $evaluation]);
    
    $message = '<div class="success-msg"><i class="fas fa-check-circle"></i> Lesson plan saved!</div>';
    try {
        logActivity($pdo, 'create', 'Created lesson plan: ' . $title, $_SESSION['teacher_username'] ?? 'teacher', 'lesson_plan', $pdo->lastInsertId());
    } catch(Exception $e) {}
}

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $pdo->prepare("DELETE FROM lesson_plans WHERE id = ? AND teacher_id = ?")->execute([$id, $teacherId]);
    $message = '<div class="success-msg"><i class="fas fa-check-circle"></i> Deleted!</div>';
    try {
        logActivity($pdo, 'delete', 'Deleted lesson plan', $_SESSION['teacher_username'] ?? 'teacher', 'lesson_plan', $id);
    } catch(Exception $e) {}
}

$classes = $pdo->query("SELECT c.* FROM teacher_classes tc JOIN classes c ON tc.class_id = c.id WHERE tc.teacher_id = $teacherId ORDER BY c.class_name")->fetchAll();
$subjects = $pdo->query("SELECT s.* FROM teacher_subjects ts JOIN subjects s ON ts.subject_id = s.id WHERE ts.teacher_id = $teacherId ORDER BY s.subject_name")->fetchAll();
$myPlans = $pdo->query("SELECT lp.*, c.class_name, s.subject_name FROM lesson_plans lp LEFT JOIN classes c ON lp.class_id = c.id LEFT JOIN subjects s ON lp.subject_id = s.id WHERE lp.teacher_id = $teacherId ORDER BY lp.id DESC")->fetchAll();

$weeks = ['Week 1', 'Week 2', 'Week 3', 'Week 4', 'Week 5', 'Week 6', 'Week 7', 'Week 8', 'Week 9', 'Week 10', 'Week 11', 'Week 12', 'Week 13'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lesson Plans - Teacher Portal</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
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
            <li><a href="lessons.php"><i class="fas fa-book-open"></i> Lessons</a></li>
            <li><a href="lesson_plans.php" class="active"><i class="fas fa-calendar-alt"></i> Lesson Plans</a></li>
            <li><a href="test_books.php"><i class="fas fa-file-alt"></i> Test Books</a></li>
            <li><a href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
            <li><a href="change_password.php"><i class="fas fa-key"></i> Change Password</a></li>
            <li><a href="../logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </nav>
    
    <main class="main-content">
        <div class="header"><h1><i class="fas fa-calendar-alt"></i> GES Lesson Plans</h1></div>
        
        <div class="content">
            <?php echo $message; ?>
            
            <div class="table-container" style="margin-bottom: 25px;">
                <div class="table-header"><h3>Create New Lesson Plan</h3></div>
                <form method="POST" style="padding: 20px;">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div class="form-group">
                            <label>Title</label>
                            <input type="text" name="title" required placeholder="e.g., Introduction to Fractions">
                        </div>
                        <div class="form-group">
                            <label>Week</label>
                            <select name="week" required>
                                <option value="">Select Week</option>
                                <?php foreach ($weeks as $w): ?>
                                <option value="<?php echo $w; ?>"><?php echo $w; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Subject</label>
                            <select name="subject_id" required>
                                <option value="">Select Subject</option>
                                <?php foreach ($subjects as $s): ?>
                                <option value="<?php echo $s['id']; ?>"><?php echo $s['subject_name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Class</label>
                            <select name="class_id" required>
                                <option value="">Select Class</option>
                                <?php foreach ($classes as $c): ?>
                                <option value="<?php echo $c['id']; ?>"><?php echo $c['class_name'] . ' - Section ' . $c['section']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Duration (min)</label>
                            <input type="text" name="duration" value="40" placeholder="e.g., 40">
                        </div>
                        <div class="form-group" style="grid-column: span 2;">
                            <label>Topic</label>
                            <input type="text" name="topic" required placeholder="Main topic for this lesson">
                        </div>
                        <div class="form-group" style="grid-column: span 2;">
                            <label>Objectives</label>
                            <textarea name="objectives" rows="2" placeholder="What students will learn..."></textarea>
                        </div>
                        <div class="form-group" style="grid-column: span 2;">
                            <label>Activities</label>
                            <textarea name="activities" rows="2" placeholder="Teaching activities..."></textarea>
                        </div>
                        <div class="form-group">
                            <label>Materials</label>
                            <input type="text" name="materials" placeholder="e.g., Charts, Books">
                        </div>
                        <div class="form-group">
                            <label>Evaluation</label>
                            <input type="text" name="evaluation" placeholder="How to assess...">
                        </div>
                    </div>
                    <button type="submit" name="save_plan" class="btn btn-primary" style="margin-top: 15px;">
                        <i class="fas fa-save"></i> Save Lesson Plan
                    </button>
                </form>
            </div>
            
            <div class="table-container">
                <div class="table-header"><h3>My Lesson Plans (<?php echo count($myPlans); ?>)</h3></div>
                
                <?php if (empty($myPlans)): ?>
                <div style="text-align: center; padding: 40px; color: #999;">
                    <i class="fas fa-calendar-alt" style="font-size: 50px;"></i>
                    <p>No lesson plans yet.</p>
                </div>
                <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Subject</th>
                            <th>Class</th>
                            <th>Week</th>
                            <th>Topic</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($myPlans as $p): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($p['title']); ?></td>
                            <td><?php echo htmlspecialchars($p['subject_name'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars(($p['class_name'] ?? '-') . ' ' . ($p['section'] ?? '')); ?></td>
                            <td><?php echo $p['week']; ?></td>
                            <td><?php echo htmlspecialchars($p['topic']); ?></td>
                            <td>
                                <a href="?delete=<?php echo $p['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete?')">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>
    </main>
    <script src="../assets/js/script.js"></script>
</body>
</html>
