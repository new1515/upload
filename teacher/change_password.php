<?php
require_once '../config/database.php';

if (!isset($_SESSION['teacher_id'])) {
    redirect('../login.php');
}

$teacherId = $_SESSION['teacher_id'];
$teacherName = $_SESSION['teacher_name'];
$message = '';

$settings = [];
$stmt = $pdo->query("SELECT * FROM school_settings");
while ($row = $stmt->fetch()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
$schoolName = $settings['school_name'] ?? 'School Management System';
$schoolLogo = $settings['school_logo'] ?? '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $current = md5($_POST['current_password']);
    $new = $_POST['new_password'];
    $confirm = $_POST['confirm_password'];
    
    $stmt = $pdo->prepare("SELECT * FROM teachers WHERE id = ?");
    $stmt->execute([$teacherId]);
    $teacher = $stmt->fetch();
    
    if ($teacher['password'] !== $current) {
        $message = '<div class="error-msg"><i class="fas fa-exclamation-circle"></i> Current password is incorrect!</div>';
    } elseif (strlen($new) < 8) {
        $message = '<div class="error-msg"><i class="fas fa-exclamation-circle"></i> New password must be at least 8 characters!</div>';
    } elseif ($new !== $confirm) {
        $message = '<div class="error-msg"><i class="fas fa-exclamation-circle"></i> New passwords do not match!</div>';
    } else {
        $stmt = $pdo->prepare("UPDATE teachers SET password = ? WHERE id = ?");
        $stmt->execute([md5($new), $teacherId]);
        $message = '<div class="success-msg"><i class="fas fa-check-circle"></i> Password changed successfully!</div>';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password - Teacher Portal</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        .portal-header { background: linear-gradient(135deg, #27ae60, #2ecc71); color: white; padding: 25px 30px; border-radius: 15px; margin-bottom: 25px; }
        .password-card { background: white; border-radius: 15px; padding: 30px; box-shadow: 0 3px 15px rgba(0,0,0,0.08); max-width: 500px; margin: 0 auto; }
        .password-card h3 { text-align: center; margin-bottom: 25px; color: #2c3e50; }
        .password-card h3 i { color: #27ae60; margin-right: 10px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-weight: 600; margin-bottom: 8px; color: #2c3e50; }
        .form-group input { width: 100%; padding: 12px 15px; border: 2px solid #e0e0e0; border-radius: 10px; font-size: 14px; transition: all 0.3s; }
        .form-group input:focus { border-color: #27ae60; outline: none; box-shadow: 0 0 0 3px rgba(39, 174, 96, 0.1); }
        .btn-change { width: 100%; padding: 14px; background: linear-gradient(135deg, #27ae60, #2ecc71); color: white; border: none; border-radius: 10px; font-size: 16px; font-weight: 600; cursor: pointer; transition: all 0.3s; }
        .btn-change:hover { transform: translateY(-2px); box-shadow: 0 5px 20px rgba(39, 174, 96, 0.4); }
        .password-toggle { position: relative; }
        .toggle-btn { position: absolute; right: 15px; top: 42px; cursor: pointer; color: #888; }
        .toggle-btn:hover { color: #27ae60; }
        .strength-bar { height: 5px; background: #eee; border-radius: 3px; margin-top: 8px; overflow: hidden; }
        .strength-bar div { height: 100%; width: 0; transition: all 0.3s; }
        .strength-bar .weak { width: 33%; background: #e74c3c; }
        .strength-bar .medium { width: 66%; background: #f39c12; }
        .strength-bar .strong { width: 100%; background: #27ae60; }
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
            <li><a href="my_classes.php"><i class="fas fa-school"></i> My Classes</a></li>
            <li><a href="enter_scores.php"><i class="fas fa-edit"></i> Enter Scores</a></li>
            <li><a href="results.php"><i class="fas fa-chart-line"></i> View Results</a></li>
            <li><a href="lessons.php"><i class="fas fa-book-open"></i> Lessons</a></li>
            <li><a href="lesson_plans.php"><i class="fas fa-calendar-alt"></i> Lesson Plans</a></li>
            <li><a href="test_books.php"><i class="fas fa-file-alt"></i> Test Books</a></li>
            <li><a href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
            <li><a href="change_password.php" class="active"><i class="fas fa-key"></i> Change Password</a></li>
            <li><a href="../logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </nav>
    
    <main class="main-content">
        <div class="header">
            <h1><i class="fas fa-key"></i> Change Password</h1>
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
            <?php echo $message; ?>
            
            <div class="password-card">
                <h3><i class="fas fa-lock"></i> Change Your Password</h3>
                
                <form method="POST" onsubmit="return validatePassword()">
                    <div class="form-group">
                        <label><i class="fas fa-lock"></i> Current Password</label>
                        <div class="password-toggle">
                            <input type="password" name="current_password" id="current_password" placeholder="Enter current password" required>
                            <span class="toggle-btn" onclick="togglePassword('current_password')">
                                <i class="fas fa-eye"></i>
                            </span>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-key"></i> New Password</label>
                        <div class="password-toggle">
                            <input type="password" name="new_password" id="new_password" placeholder="Enter new password (min 8 characters)" required oninput="checkStrength()">
                            <span class="toggle-btn" onclick="togglePassword('new_password')">
                                <i class="fas fa-eye"></i>
                            </span>
                        </div>
                        <div class="strength-bar">
                            <div id="strengthIndicator"></div>
                        </div>
                        <small style="color: #888; margin-top: 5px; display: block;">Password strength indicator</small>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-key"></i> Confirm New Password</label>
                        <div class="password-toggle">
                            <input type="password" name="confirm_password" id="confirm_password" placeholder="Confirm new password" required>
                            <span class="toggle-btn" onclick="togglePassword('confirm_password')">
                                <i class="fas fa-eye"></i>
                            </span>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn-change">
                        <i class="fas fa-save"></i> Change Password
                    </button>
                </form>
            </div>
        </div>
    </main>
    
    <script src="../assets/js/script.js"></script>
    <script>
        function togglePassword(id) {
            const input = document.getElementById(id);
            const icon = input.nextElementSibling.querySelector('i');
            if (input.type === 'password') {
                input.type = 'text';
                icon.className = 'fas fa-eye-slash';
            } else {
                input.type = 'password';
                icon.className = 'fas fa-eye';
            }
        }
        
        function checkStrength() {
            const password = document.getElementById('new_password').value;
            const indicator = document.getElementById('strengthIndicator');
            
            if (password.length === 0) {
                indicator.className = '';
                return;
            }
            
            let strength = 0;
            if (password.length >= 8) strength++;
            if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
            if (/\d/.test(password)) strength++;
            if (/[!@#$%^&*(),.?":{}|<>]/.test(password)) strength++;
            
            indicator.className = '';
            if (strength <= 1) {
                indicator.classList.add('weak');
            } else if (strength <= 2) {
                indicator.classList.add('medium');
            } else {
                indicator.classList.add('strong');
            }
        }
        
        function validatePassword() {
            const newPass = document.getElementById('new_password').value;
            const confirmPass = document.getElementById('confirm_password').value;
            
            if (newPass.length < 8) {
                alert('Password must be at least 8 characters!');
                return false;
            }
            
            if (newPass !== confirmPass) {
                alert('Passwords do not match!');
                return false;
            }
            
            return true;
        }
    </script>
</body>
</html>
