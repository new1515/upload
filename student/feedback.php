<?php
require_once '../config/database.php';

if (!isset($_SESSION['student_id'])) {
    redirect('../login.php');
}

$studentId = $_SESSION['student_id'];
$studentName = $_SESSION['student_name'];
$studentEmail = $_SESSION['student_email'] ?? '';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = sanitize($_POST['name'] ?? $studentName);
    $email = sanitize($_POST['email'] ?? $studentEmail);
    $role = 'student';
    $subject = sanitize($_POST['subject'] ?? '');
    $message_text = sanitize($_POST['message'] ?? '');
    
    if (empty($subject) || empty($message_text)) {
        $error = 'Please fill in all required fields';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO feedback (name, email, role, subject, message, status) VALUES (?, ?, ?, ?, ?, 'pending')");
            $stmt->execute([$name, $email, $role, $subject, $message_text]);
            $message = 'Thank you! Your feedback has been submitted successfully. We will get back to you soon.';
            $subject = ''; $message_text = '';
        } catch (Exception $e) {
            $error = 'Failed to submit feedback. Please try again.';
        }
    }
}

$settings = [];
$stmt = $pdo->query("SELECT * FROM school_settings");
while ($row = $stmt->fetch()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
$schoolName = $settings['school_name'] ?? 'School Management System';
$schoolLogo = $settings['school_logo'] ?? '';
$dashboardTheme = $settings['dashboard_theme'] ?? 'default';
$dashboardBackground = $settings['dashboard_background'] ?? '';

$themeColors = [
    'default' => '#4a90e2',
    'ocean' => '#2196F3',
    'sunset' => '#ff7043',
    'forest' => '#4caf50',
    'royal' => '#9c27b0',
    'dark' => '#2c3e50'
];
$themeColor = $themeColors[$dashboardTheme] ?? $themeColors['default'];

$bgStyle = '';
if (!empty($dashboardBackground) && file_exists('../assets/images/backgrounds/' . $dashboardBackground)) {
    $bgStyle = 'background: linear-gradient(rgba(255,255,255,0.9), rgba(255,255,255,0.9)), url(\'../assets/images/backgrounds/' . htmlspecialchars($dashboardBackground) . '\'); background-size: cover; background-position: center;';
}

$myFeedback = $pdo->prepare("SELECT * FROM feedback WHERE email = ? OR name = ? ORDER BY created_at DESC");
$myFeedback->execute([$studentEmail, $studentName]);
$myFeedback = $myFeedback->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback - <?php echo htmlspecialchars($schoolName); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        :root {
            --primary-color: <?php echo $themeColor; ?>;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
            min-height: 100vh;
        }
        .main-content {
            margin-left: 260px;
            padding: 20px;
            <?php echo $bgStyle; ?>
        }
        .feedback-container {
            max-width: 700px;
            margin: 0 auto;
        }
        .feedback-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .page-header {
            margin-bottom: 25px;
        }
        .page-header h1 {
            color: #2c3e50;
            font-size: 28px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .page-header h1 i {
            color: var(--primary-color);
        }
        .success-msg {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .error-msg {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 8px;
        }
        .form-group label .required {
            color: #e74c3c;
        }
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 14px;
            transition: all 0.3s;
            font-family: inherit;
        }
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(74, 144, 226, 0.1);
        }
        .form-group textarea {
            min-height: 150px;
            resize: vertical;
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        .btn-submit {
            padding: 14px 30px;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.3s;
        }
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(74, 144, 226, 0.4);
        }
        @media (max-width: 768px) {
            .main-content { margin-left: 0; }
            .form-row { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <button class="mobile-menu-toggle">
        <i class="fas fa-bars"></i>
    </button>
    <div class="sidebar-overlay"></div>
    <nav class="sidebar">
        <div class="sidebar-brand">
            <i class="fas fa-user-graduate"></i> Student Portal
        </div>
        <ul class="sidebar-menu">
            <li><a href="index.php"><i class="fas fa-home"></i> Dashboard</a></li>
            <li><a href="video_conference.php"><i class="fas fa-video"></i> Video Conference</a></li>
            <li><a href="announcements.php"><i class="fas fa-bullhorn"></i> Announcements</a></li>
            <li><a href="results.php"><i class="fas fa-chart-line"></i> My Results</a></li>
            <li><a href="reportcard.php"><i class="fas fa-file-alt"></i> Report Card</a></li>
            <li><a href="lessons.php"><i class="fas fa-book-open"></i> Lessons</a></li>
            <li><a href="feedback.php" class="active"><i class="fas fa-comment-dots"></i> Feedback</a></li>
            <li><a href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
            <li><a href="change_password.php"><i class="fas fa-key"></i> Change Password</a></li>
            <li><a href="../logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </nav>
    
    <main class="main-content" <?php echo $bgStyle ? 'style="' . $bgStyle . '"' : ''; ?>>
        <div class="header">
            <h1><i class="fas fa-comment-dots"></i> Feedback</h1>
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
        
        <div class="feedback-container">
            <div class="page-header">
                <h1><i class="fas fa-comment-dots"></i> Feedback & Suggestions</h1>
                <p style="color: #666; margin-top: 8px;">Share your questions, suggestions, or concerns with us.</p>
            </div>
            
            <?php if ($message): ?>
                <div class="success-msg">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="error-msg">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <div class="feedback-card">
                <form method="POST">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Your Name <span class="required">*</span></label>
                            <input type="text" name="name" value="<?php echo htmlspecialchars($studentName); ?>" readonly>
                        </div>
                        <div class="form-group">
                            <label>Email Address</label>
                            <input type="hidden" name="email" value="<?php echo htmlspecialchars($studentEmail ?? ''); ?>">
                            <input type="text" value="<?php echo htmlspecialchars($studentEmail ?? 'Not provided'); ?>" disabled style="background: #f5f5f5; color: #666;">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>I am a <span class="required">*</span></label>
                            <select name="role" disabled>
                                <option value="student" selected>Student</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Category <span class="required">*</span></label>
                            <select name="subject" required>
                                <option value="">Select a category</option>
                                <option value="Question" <?php echo ($subject ?? '') == 'Question' ? 'selected' : ''; ?>>Question</option>
                                <option value="Suggestion" <?php echo ($subject ?? '') == 'Suggestion' ? 'selected' : ''; ?>>Suggestion</option>
                                <option value="Complaint" <?php echo ($subject ?? '') == 'Complaint' ? 'selected' : ''; ?>>Complaint</option>
                                <option value="Compliment" <?php echo ($subject ?? '') == 'Compliment' ? 'selected' : ''; ?>>Compliment</option>
                                <option value="Technical Issue" <?php echo ($subject ?? '') == 'Technical Issue' ? 'selected' : ''; ?>>Technical Issue</option>
                                <option value="Academic Inquiry" <?php echo ($subject ?? '') == 'Academic Inquiry' ? 'selected' : ''; ?>>Academic Inquiry</option>
                                <option value="Other" <?php echo ($subject ?? '') == 'Other' ? 'selected' : ''; ?>>Other</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Your Message <span class="required">*</span></label>
                        <textarea name="message" placeholder="Please describe your question, suggestion, or concern in detail..." required><?php echo htmlspecialchars($message_text ?? ''); ?></textarea>
                    </div>
                    
                    <button type="submit" class="btn-submit">
                        <i class="fas fa-paper-plane"></i> Submit Feedback
                    </button>
                </form>
            </div>
            
            <?php if (!empty($myFeedback)): ?>
            <div class="my-feedback-section" style="margin-top: 40px;">
                <h2 style="color: #2c3e50; margin-bottom: 20px;"><i class="fas fa-history"></i> My Feedback History</h2>
                <?php foreach ($myFeedback as $fb): ?>
                    <div class="feedback-item" style="background: white; border-radius: 15px; padding: 20px; margin-bottom: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 10px;">
                            <div>
                                <strong><?php echo htmlspecialchars($fb['subject']); ?></strong>
                                <span class="badge" style="margin-left: 10px; padding: 4px 10px; border-radius: 10px; font-size: 11px; background: <?php echo $fb['status'] == 'pending' ? '#e74c3c' : ($fb['status'] == 'replied' ? '#f39c12' : '#27ae60'); ?>; color: white;"><?php echo ucfirst($fb['status']); ?></span>
                            </div>
                            <small style="color: #999;"><?php echo date('M d, Y', strtotime($fb['created_at'])); ?></small>
                        </div>
                        <p style="color: #666; margin-bottom: 10px;"><?php echo nl2br(htmlspecialchars($fb['message'])); ?></p>
                        <?php if (!empty($fb['reply'])): ?>
                            <div style="background: #e8f5e9; padding: 15px; border-radius: 10px; border-left: 4px solid #27ae60; margin-top: 10px;">
                                <strong><i class="fas fa-reply"></i> Reply from School:</strong>
                                <p style="margin-top: 8px; color: #2c3e50;"><?php echo nl2br(htmlspecialchars($fb['reply'])); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            
        </div>
    </main>
</body>
</html>
