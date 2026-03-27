<?php
require_once 'config/database.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = sanitize($_POST['name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $role = sanitize($_POST['role'] ?? 'parent');
    $subject = sanitize($_POST['subject'] ?? '');
    $message_text = sanitize($_POST['message'] ?? '');
    
    if (empty($name) || empty($subject) || empty($message_text)) {
        $error = 'Please fill in all required fields';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO feedback (name, email, role, subject, message, status) VALUES (?, ?, ?, ?, ?, 'pending')");
            $stmt->execute([$name, $email, $role, $subject, $message_text]);
            $message = 'Thank you! Your feedback has been submitted successfully. We will get back to you soon.';
            $name = ''; $email = ''; $subject = ''; $message_text = '';
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
$loginTheme = $settings['login_theme'] ?? 'default';
$loginBackground = $settings['login_background'] ?? '';

$themeGradients = [
    'default' => 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
    'ocean' => 'linear-gradient(135deg, #2196F3 0%, #0d47a1 100%)',
    'sunset' => 'linear-gradient(135deg, #ff7043 0%, #d84315 100%)',
    'forest' => 'linear-gradient(135deg, #4caf50 0%, #1b5e20 100%)',
    'royal' => 'linear-gradient(135deg, #9c27b0 0%, #4a148c 100%)',
    'midnight' => 'linear-gradient(135deg, #1a1a2e 0%, #16213e 100%)',
    'Cherry Blossom' => 'linear-gradient(135deg, #f8bbd0 0%, #f48fb1 100%)',
    'Snow' => 'linear-gradient(135deg, #eceff1 0%, #cfd8dc 100%)',
    'Space' => 'linear-gradient(135deg, #0d0d1a 0%, #1a1a3e 100%)'
];
$backgroundGradient = $themeGradients[$loginTheme] ?? $themeGradients['default'];

$bgImageStyle = '';
if (!empty($loginBackground) && file_exists('assets/images/backgrounds/' . $loginBackground)) {
    $bgImageStyle = 'background: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.6)), url(\'assets/images/backgrounds/' . htmlspecialchars($loginBackground) . '\'); background-size: cover; background-position: center;';
} else {
    $bgImageStyle = 'background: ' . $backgroundGradient . ';';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback & Suggestions - <?php echo htmlspecialchars($schoolName); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/styles.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        
        body {
            min-height: 100vh;
            <?php echo $bgImageStyle; ?>;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            overflow-x: hidden;
        }
        
        .feedback-wrapper {
            width: 100%;
            max-width: 600px;
            position: relative;
            z-index: 10;
        }
        
        .feedback-card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 25px 50px rgba(0,0,0,0.3);
        }
        
        .top-bar {
            height: 5px;
            background: linear-gradient(90deg, #4a90e2, #27ae60, #f39c12, #e74c3c);
            border-radius: 20px 20px 0 0;
            margin: -30px -30px 25px -30px;
        }
        
        .school-header-section {
            text-align: center;
            padding: 10px 0 20px 0;
            margin-bottom: 15px;
        }
        
        .school-logo {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            background: linear-gradient(135deg, #4a90e2, #357abd);
            margin: 0 auto 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            box-shadow: 0 8px 25px rgba(74, 144, 226, 0.4);
        }
        
        .school-logo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .school-logo i {
            font-size: 30px;
            color: white;
        }
        
        .school-header-section h2 {
            font-size: 18px;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        
        .feedback-header {
            text-align: center;
            margin-bottom: 25px;
        }
        
        .feedback-header h1 {
            font-size: 24px;
            color: #2c3e50;
            margin-bottom: 10px;
        }
        
        .feedback-header p {
            color: #666;
            font-size: 14px;
        }
        
        .feedback-header p i {
            color: #4a90e2;
            margin-right: 8px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 6px;
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
            border-color: #4a90e2;
            box-shadow: 0 0 0 3px rgba(74, 144, 226, 0.1);
        }
        
        .form-group textarea {
            min-height: 120px;
            resize: vertical;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        @media (max-width: 500px) {
            .form-row {
                grid-template-columns: 1fr;
            }
        }
        
        .btn-submit {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #4a90e2, #357abd);
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
        
        .success-msg {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .success-msg i {
            font-size: 20px;
        }
        
        .error-msg {
            background: #f8d7da;
            color: #721c24;
            padding: 12px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .error-msg i {
            font-size: 18px;
        }
        
        .feedback-footer {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        
        .feedback-footer a {
            color: #4a90e2;
            text-decoration: none;
            font-weight: 600;
        }
        
        .feedback-footer a:hover {
            text-decoration: underline;
        }
        
        .category-icons {
            display: flex;
            justify-content: center;
            gap: 30px;
            margin-bottom: 25px;
            flex-wrap: wrap;
        }
        
        .category-icon {
            text-align: center;
            padding: 15px 20px;
            border-radius: 10px;
            background: #f8f9fa;
            transition: all 0.3s;
            cursor: pointer;
        }
        
        .category-icon:hover {
            background: #e8f4fd;
            transform: translateY(-3px);
        }
        
        .category-icon i {
            font-size: 28px;
            color: #4a90e2;
            margin-bottom: 8px;
        }
        
        .category-icon span {
            display: block;
            font-size: 12px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="feedback-wrapper">
        <div class="feedback-card">
            <div class="top-bar"></div>
            
            <div class="school-header-section">
                <div class="school-logo">
                    <?php if ($schoolLogo && file_exists('assets/images/' . $schoolLogo)): ?>
                        <img src="assets/images/<?php echo htmlspecialchars($schoolLogo); ?>" alt="Logo">
                    <?php else: ?>
                        <i class="fas fa-graduation-cap"></i>
                    <?php endif; ?>
                </div>
                <h2><?php echo htmlspecialchars($schoolName); ?></h2>
            </div>
            
            <div class="feedback-header">
                <h1><i class="fas fa-comment-dots"></i> Feedback & Suggestions</h1>
                <p>We value your input! Share your questions, suggestions, or concerns with us.</p>
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
            
            <form method="POST">
                <div class="form-row">
                    <div class="form-group">
                        <label>Your Name <span class="required">*</span></label>
                        <input type="text" name="name" value="<?php echo htmlspecialchars($name ?? ''); ?>" placeholder="Enter your full name" required>
                    </div>
                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($email ?? ''); ?>" placeholder="your@email.com (optional)">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>I am a <span class="required">*</span></label>
                        <select name="role" required>
                            <option value="parent" <?php echo ($role ?? '') == 'parent' ? 'selected' : ''; ?>>Parent</option>
                            <option value="student" <?php echo ($role ?? '') == 'student' ? 'selected' : ''; ?>>Student</option>
                            <option value="teacher" <?php echo ($role ?? '') == 'teacher' ? 'selected' : ''; ?>>Teacher</option>
                            <option value="guardian" <?php echo ($role ?? '') == 'guardian' ? 'selected' : ''; ?>>Guardian</option>
                            <option value="other" <?php echo ($role ?? '') == 'other' ? 'selected' : ''; ?>>Other</option>
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
                            <option value="Admission Inquiry" <?php echo ($subject ?? '') == 'Admission Inquiry' ? 'selected' : ''; ?>>Admission Inquiry</option>
                            <option value="Fee Inquiry" <?php echo ($subject ?? '') == 'Fee Inquiry' ? 'selected' : ''; ?>>Fee Inquiry</option>
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
            
            <div class="feedback-footer">
                <a href="login.php"><i class="fas fa-sign-in-alt"></i> Login to Dashboard</a>
            </div>
        </div>
    </div>
</body>
</html>
