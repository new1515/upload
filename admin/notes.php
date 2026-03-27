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

$message = '';

if (isset($_POST['add_note'])) {
    $title = sanitize($_POST['title']);
    $content = sanitize($_POST['content']);
    $category = sanitize($_POST['category']);
    
    $stmt = $pdo->prepare("INSERT INTO notes (title, content, category) VALUES (?, ?, ?)");
    $stmt->execute([$title, $content, $category]);
    $message = '<div class="success-msg"><i class="fas fa-check-circle"></i> Note added successfully!</div>';
}

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM notes WHERE id = ?");
    $stmt->execute([$id]);
    $message = '<div class="success-msg"><i class="fas fa-check-circle"></i> Note deleted successfully!</div>';
}

$notes = $pdo->query("SELECT * FROM notes ORDER BY id DESC")->fetchAll();
$categories = $pdo->query("SELECT DISTINCT category FROM notes WHERE category IS NOT NULL AND category != ''")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lesson Notes - School Management System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        .note-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border-left: 4px solid var(--primary);
        }
        .note-card:hover {
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        .note-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 10px;
        }
        .note-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--dark);
            margin: 0;
        }
        .note-category {
            background: var(--primary);
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
        }
        .note-content {
            color: var(--gray);
            font-size: 14px;
            line-height: 1.6;
            white-space: pre-wrap;
        }
        .note-footer {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .note-date {
            color: #999;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <button class="mobile-menu-toggle"><i class="fas fa-bars"></i></button>
    <div class="sidebar-overlay"></div>
    <nav class="sidebar">
        <div class="sidebar-brand"><i class="fas fa-graduation-cap"></i> School Admin</div>
        <ul class="sidebar-menu">
            <li><a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
            <li><a href="students.php"><i class="fas fa-user-graduate"></i> Students</a></li>
            <li><a href="parents.php"><i class="fas fa-users"></i> Parents</a></li>
            <li><a href="teachers.php"><i class="fas fa-chalkboard-teacher"></i> Teachers</a></li>
            <li><a href="subjects.php"><i class="fas fa-book"></i> Subjects</a></li>
            <li><a href="classes.php"><i class="fas fa-school"></i> Classes</a></li>
            <li><a href="attendance.php"><i class="fas fa-calendar-check"></i> Attendance</a></li>
            <li><a href="timetable.php"><i class="fas fa-clock"></i> Timetable</a></li>
            <li><a href="results.php"><i class="fas fa-chart-line"></i> Results</a></li>
            <li><a href="reportcards.php"><i class="fas fa-file-alt"></i> Report Cards</a></li>
            <li><a href="lessons.php"><i class="fas fa-book-open"></i> Lessons</a></li>
            <li><a href="notes.php" class="active"><i class="fas fa-sticky-note"></i> Notes</a></li>
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
            <h1><i class="fas fa-sticky-note"></i> Lesson Notes</h1>
            <div class="header-right">
                <a href="settings.php"><i class="fas fa-cog"></i></a>
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
            <?php echo $message; ?>
            
            <div class="table-container" style="margin-bottom: 25px;">
                <div class="table-header">
                    <h2>Add New Note</h2>
                </div>
                <form method="POST" style="padding: 20px;">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div class="form-group">
                            <label>Title</label>
                            <input type="text" name="title" required placeholder="e.g., English Grammar Rules">
                        </div>
                        <div class="form-group">
                            <label>Category</label>
                            <input type="text" name="category" placeholder="e.g., English, Mathematics">
                        </div>
                        <div class="form-group" style="grid-column: span 2;">
                            <label>Content</label>
                            <textarea name="content" rows="5" placeholder="Enter the note content..."></textarea>
                        </div>
                    </div>
                    <button type="submit" name="add_note" class="btn btn-primary" style="margin-top: 15px;">
                        <i class="fas fa-plus"></i> Add Note
                    </button>
                </form>
            </div>
            
            <div class="table-container">
                <div class="table-header">
                    <h2>All Notes (<?php echo count($notes); ?>)</h2>
                </div>
                
                <?php if (empty($notes)): ?>
                <div style="text-align: center; padding: 40px; color: #999;">
                    <i class="fas fa-sticky-note" style="font-size: 50px; margin-bottom: 15px;"></i>
                    <p>No notes found. Use the form above to add notes.</p>
                </div>
                <?php else: ?>
                <?php foreach ($notes as $note): ?>
                <div class="note-card">
                    <div class="note-header">
                        <h3 class="note-title"><?php echo htmlspecialchars($note['title']); ?></h3>
                        <?php if ($note['category']): ?>
                        <span class="note-category"><?php echo htmlspecialchars($note['category']); ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="note-content"><?php echo nl2br(htmlspecialchars($note['content'] ?? '')); ?></div>
                    <div class="note-footer">
                        <span class="note-date">
                            <i class="fas fa-calendar"></i> <?php echo date('d M Y', strtotime($note['created_at'])); ?>
                        </span>
                        <a href="?delete=<?php echo $note['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this note?')">
                            <i class="fas fa-trash"></i> Delete
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>
    
    <script src="../assets/js/script.js"></script>
</body>
</html>
