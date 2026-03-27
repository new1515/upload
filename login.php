<?php
require_once 'config/database.php';

if (isset($_SESSION['admin_id'])) {
    redirect('admin/dashboard.php');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = sanitize($_POST['username']);
    $password = md5($_POST['password']);
    
    if (empty($username) || empty($_POST['password'])) {
        $error = 'Please fill in all fields';
    } else {
        // Check Super Admin first
        $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ? AND password = ? AND role = 'superadmin'");
        $stmt->execute([$username, $password]);
        $user = $stmt->fetch();
        if ($user) {
            $_SESSION['admin_id'] = $user['id'];
            $_SESSION['admin_username'] = $user['username'];
            $_SESSION['admin_role'] = $user['role'];
            try { logActivity($pdo, 'login', "Super Admin logged in", $user['username'], 'auth', $user['id']); } catch(Exception $e) {}
            redirect('admin/dashboard.php');
        }
        
        // Check Admin
        $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ? AND password = ? AND role = 'admin'");
        $stmt->execute([$username, $password]);
        $user = $stmt->fetch();
        if ($user) {
            $_SESSION['admin_id'] = $user['id'];
            $_SESSION['admin_username'] = $user['username'];
            $_SESSION['admin_role'] = $user['role'];
            try { logActivity($pdo, 'login', "Admin logged in", $user['username'], 'auth', $user['id']); } catch(Exception $e) {}
            redirect('admin/dashboard.php');
        }
        
        // Check Teacher
        $stmt = $pdo->prepare("SELECT * FROM teachers WHERE username = ? AND password = ?");
        $stmt->execute([$username, $password]);
        $user = $stmt->fetch();
        if ($user) {
            $_SESSION['teacher_id'] = $user['id'];
            $_SESSION['teacher_username'] = $user['username'];
            $_SESSION['teacher_name'] = $user['name'];
            $_SESSION['teacher_role'] = 'teacher';
            try { logActivity($pdo, 'login', "Teacher logged in", $user['username'], 'auth', $user['id']); } catch(Exception $e) {}
            redirect('teacher/index.php');
        }
        
        // Check Parent
        $stmt = $pdo->prepare("SELECT * FROM parents WHERE username = ? AND password = ?");
        $stmt->execute([$username, $password]);
        $user = $stmt->fetch();
        if ($user) {
            $_SESSION['parent_id'] = $user['id'];
            $_SESSION['parent_username'] = $user['username'];
            $_SESSION['parent_name'] = $user['name'];
            $_SESSION['parent_student_id'] = $user['student_id'];
            $_SESSION['parent_role'] = 'parent';
            try { logActivity($pdo, 'login', "Parent logged in", $user['username'], 'auth', $user['id']); } catch(Exception $e) {}
            redirect('parent/index.php');
        }
        
        // Check Student
        $stmt = $pdo->prepare("SELECT * FROM students WHERE username = ? AND password = ?");
        $stmt->execute([$username, $password]);
        $user = $stmt->fetch();
        if ($user) {
            $_SESSION['student_id'] = $user['id'];
            $_SESSION['student_username'] = $user['username'];
            $_SESSION['student_name'] = $user['name'];
            $_SESSION['student_class_id'] = $user['class_id'];
            $_SESSION['student_role'] = 'student';
            try { logActivity($pdo, 'login', "Student logged in", $user['username'], 'auth', $user['id']); } catch(Exception $e) {}
            redirect('student/index.php');
        }
        
        $error = 'Invalid username or password';
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
    $bgImageStyle = 'background: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.5)), url(\'assets/images/backgrounds/' . htmlspecialchars($loginBackground) . '\'); background-size: cover; background-position: center;';
} else {
    $bgImageStyle = 'background: ' . $backgroundGradient . ';';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo htmlspecialchars($schoolName); ?></title>
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
        
        .login-wrapper {
            width: 100%;
            max-width: 400px;
            position: relative;
            z-index: 10;
        }
        
        .login-card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 25px 50px rgba(0,0,0,0.2);
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
            width: 90px;
            height: 90px;
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
            font-size: 40px;
            color: white;
        }
        
        .school-header-section h2 {
            font-size: 20px;
            color: #2c3e50;
            margin-bottom: 5px;
            font-weight: 700;
            line-height: 1.3;
        }
        
        .school-header-section p {
            font-size: 13px;
            color: #888;
            margin-bottom: 0;
        }
        
        .login-divider {
            height: 1px;
            background: linear-gradient(90deg, transparent, #e0e0e0, transparent);
            margin: 15px 0 20px 0;
        }
        
        .login-form {
            display: block;
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
        
        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #4a90e2;
            box-shadow: 0 0 0 3px rgba(74, 144, 226, 0.1);
        }
        
        .btn-login {
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
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(74, 144, 226, 0.4);
        }
        
        .user-types {
            margin-top: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 10px;
        }
        
        .user-types h4 {
            font-size: 12px;
            color: #888;
            margin-bottom: 10px;
            text-align: center;
        }
        
        .user-types ul {
            list-style: none;
            font-size: 12px;
            color: #666;
        }
        
        .user-types li {
            padding: 5px 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .user-types li i {
            width: 20px;
            color: #4a90e2;
        }
        
        .forgot-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px dashed #ddd;
        }
        
        .forgot-link a {
            color: #4a90e2;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 10px 20px;
            background: #f0f4ff;
            border-radius: 20px;
            transition: all 0.3s;
        }
        
        .forgot-link a:hover {
            background: #4a90e2;
            color: white;
        }
        
        .error-msg {
            background: #fee;
            color: #c00;
            padding: 12px;
            border-radius: 10px;
            margin-bottom: 15px;
            font-size: 13px;
            text-align: center;
        }
        
        @media (max-width: 400px) {
            .login-card { padding: 20px; }
            .school-logo { width: 75px; height: 75px; }
            .school-logo i { font-size: 32px; }
            .school-header-section h2 { font-size: 18px; }
        }
    </style>
</head>
<body data-theme="<?php echo htmlspecialchars($loginTheme); ?>">
    <div class="login-wrapper">
        <div class="login-card">
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
                <p>Sign in to continue</p>
            </div>
            
            <div class="login-divider"></div>
            
            <?php if ($error): ?>
                <div class="error-msg">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <form class="login-form" method="POST">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" placeholder="Enter your username" required autofocus>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" placeholder="Enter your password" required>
                </div>
                <button type="submit" class="btn-login">
                    <i class="fas fa-sign-in-alt"></i> Login
                </button>
                
                <div class="user-types">
                    <h4>Login with your credentials:</h4>
                    <ul>
                        <li><i class="fas fa-user-shield"></i> Admin / Super Admin</li>
                        <li><i class="fas fa-chalkboard-teacher"></i> Teacher</li>
                        <li><i class="fas fa-users"></i> Parent</li>
                        <li><i class="fas fa-user-graduate"></i> Student</li>
                    </ul>
                </div>
            </form>
            
            <div class="forgot-link">
                <a href="forgot_password.php">
                    <i class="fas fa-key"></i> Forgot Password? Reset here
                </a>
            </div>
            
            <div class="login-footer">
                <p>Made with <i class="fas fa-heart" style="color: #e74c3c;"></i> Designed by <strong>Sir Abraham Ashong Tetteh</strong></p>
                <p>Contact: 0594646631 | 0209484452</p>
            </div>
        </div>
    </div>
    
    <style>
        .login-footer {
            text-align: center;
            padding: 20px;
            color: #888;
            font-size: 12px;
            margin-top: 20px;
        }
        .login-footer p {
            margin: 3px 0;
        }
        .login-footer i {
            margin: 0 2px;
        }
    </style>
</body>
</html>
