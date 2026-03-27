<?php
require_once 'config/database.php';

if (isset($_SESSION['admin_id'])) {
    redirect('admin/dashboard.php');
}

$step = isset($_GET['step']) ? sanitize($_GET['step']) : 'request';
$token = isset($_GET['token']) ? sanitize($_GET['token']) : '';
$error = '';
$success = '';

$settings = [];
$stmt = $pdo->query("SELECT * FROM school_settings");
while ($row = $stmt->fetch()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
$schoolName = $settings['school_name'] ?? 'School Management System';
$schoolLogo = $settings['school_logo'] ?? '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['request_reset'])) {
    $username = sanitize($_POST['username']);
    $email = sanitize($_POST['email']);
    
    $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ?");
    $stmt->execute([$username]);
    $admin = $stmt->fetch();
    
    if ($admin && $admin['email'] == $email) {
        $token = bin2hex(random_bytes(32));
        $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        $pdo->prepare("UPDATE admins SET reset_token = ?, reset_expiry = ? WHERE id = ?")
            ->execute([hash('sha256', $token), $expiry, $admin['id']]);
        
        $success = 'A password reset link has been generated. Please contact your system administrator for the reset token.';
        
        $resetLink = "forgot_password.php?step=reset&token=" . $token;
        
        $success .= '<br><br><div style="background:#f8f9fa;padding:15px;border-radius:8px;font-family:monospace;font-size:12px;word-break:break-all;">';
        $success .= '<strong>Reset Token:</strong> ' . $token;
        $success .= '<br><br><strong>Reset Link:</strong> ' . $resetLink;
        $success .= '</div>';
        
        $success .= '<br><p style="font-size:12px;color:#888;">In production, this token would be sent to your email address.</p>';
    } else {
        $error = 'No account found with that username and email combination.';
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['reset_password'])) {
    if (empty($token)) {
        $error = 'Invalid or expired reset token.';
    } else {
        $newPassword = $_POST['new_password'];
        $confirmPassword = $_POST['confirm_password'];
        
        if (strlen($newPassword) < 6) {
            $error = 'Password must be at least 6 characters.';
        } elseif ($newPassword !== $confirmPassword) {
            $error = 'Passwords do not match.';
        } else {
            $hashedToken = hash('sha256', $token);
            $stmt = $pdo->prepare("SELECT * FROM admins WHERE reset_token = ? AND reset_expiry > NOW()");
            $stmt->execute([$hashedToken]);
            $admin = $stmt->fetch();
            
            if ($admin) {
                $pdo->prepare("UPDATE admins SET password = ?, reset_token = NULL, reset_expiry = NULL WHERE id = ?")
                    ->execute([md5($newPassword), $admin['id']]);
                
                $success = '<div style="text-align:center;padding:30px;">
                    <i class="fas fa-check-circle" style="font-size:60px;color:#27ae60;margin-bottom:20px;"></i>
                    <h3 style="color:#27ae60;">Password Reset Successful!</h3>
                    <p>Your password has been changed successfully.</p>
                    <a href="login.php" class="btn" style="display:inline-block;margin-top:20px;padding:12px 30px;background:#4a90e2;color:white;text-decoration:none;border-radius:8px;">
                        <i class="fas fa-sign-in-alt"></i> Go to Login
                    </a>
                </div>';
            } else {
                $error = 'Invalid or expired reset token. Please request a new one.';
            }
        }
    }
}

if ($step === 'reset' && !empty($token)) {
    $hashedToken = hash('sha256', $token);
    $stmt = $pdo->prepare("SELECT * FROM admins WHERE reset_token = ? AND reset_expiry > NOW()");
    $stmt->execute([$hashedToken]);
    $validAdmin = $stmt->fetch();
    
    if (!$validAdmin) {
        $error = 'Invalid or expired reset token. Please request a new one.';
        $step = 'request';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - <?php echo htmlspecialchars($schoolName); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/styles.css">
    <style>
        body.forgot-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            margin: 0;
        }
        
        .forgot-container {
            max-width: 450px;
            width: 100%;
        }
        
        .forgot-box {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 25px 50px rgba(0,0,0,0.2);
            position: relative;
        }
        
        .forgot-box::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, #4a90e2, #f39c12, #e74c3c, #27ae60, #9b59b6);
        }
        
        .forgot-header {
            text-align: center;
            margin-bottom: 30px;
            padding-top: 10px;
        }
        
        .forgot-logo {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            margin: 0 auto 15px;
            overflow: hidden;
            background: linear-gradient(135deg, #4a90e2, #357abd);
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 8px 25px rgba(74, 144, 226, 0.3);
        }
        
        .forgot-logo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .forgot-logo i {
            font-size: 36px;
            color: white;
        }
        
        .forgot-header h2 {
            font-size: 22px;
            color: #2c3e50;
            margin: 0 0 8px 0;
        }
        
        .forgot-header p {
            font-size: 14px;
            color: #888;
            margin: 0;
            line-height: 1.5;
        }
        
        .forgot-steps {
            display: flex;
            justify-content: center;
            margin-bottom: 30px;
            gap: 20px;
        }
        
        .forgot-step {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #ccc;
            font-size: 13px;
        }
        
        .forgot-step.active {
            color: #4a90e2;
        }
        
        .forgot-step.completed {
            color: #27ae60;
        }
        
        .forgot-step .step-num {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            border: 2px solid currentColor;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 12px;
        }
        
        .forgot-step.completed .step-num {
            background: #27ae60;
            color: white;
            border-color: #27ae60;
        }
        
        .forgot-step.active .step-num {
            background: #4a90e2;
            color: white;
            border-color: #4a90e2;
        }
        
        .step-line {
            width: 40px;
            height: 2px;
            background: #e0e0e0;
            margin-top: 14px;
        }
        
        .step-line.completed {
            background: #27ae60;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #2c3e50;
            font-size: 14px;
        }
        
        .form-group input {
            width: 100%;
            padding: 14px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 15px;
            transition: all 0.3s;
            box-sizing: border-box;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #4a90e2;
            box-shadow: 0 0 0 3px rgba(74, 144, 226, 0.1);
        }
        
        .password-group {
            position: relative;
        }
        
        .password-toggle {
            position: absolute;
            right: 15px;
            top: 42px;
            cursor: pointer;
            color: #888;
            transition: color 0.3s;
        }
        
        .password-toggle:hover {
            color: #4a90e2;
        }
        
        .btn {
            width: 100%;
            padding: 15px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 15px;
            cursor: pointer;
            transition: all 0.3s;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-top: 10px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #4a90e2, #357abd);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(74, 144, 226, 0.4);
        }
        
        .btn-success {
            background: linear-gradient(135deg, #27ae60, #2ecc71);
            color: white;
        }
        
        .btn-back {
            background: #f8f9fa;
            color: #666;
            margin-top: 15px;
        }
        
        .btn-back:hover {
            background: #eee;
        }
        
        .back-link {
            text-align: center;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        
        .back-link a {
            color: #4a90e2;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        
        .back-link a:hover {
            text-decoration: underline;
        }
        
        .strength-bar {
            height: 5px;
            background: #eee;
            border-radius: 3px;
            margin-top: 8px;
            overflow: hidden;
        }
        
        .strength-bar div {
            height: 100%;
            width: 0;
            transition: all 0.3s;
            border-radius: 3px;
        }
        
        .strength-bar .weak { width: 33%; background: #e74c3c; }
        .strength-bar .medium { width: 66%; background: #f39c12; }
        .strength-bar .strong { width: 100%; background: #27ae60; }
        
        .strength-text {
            font-size: 12px;
            margin-top: 5px;
            color: #888;
        }
    </style>
</head>
<body class="forgot-page">
    <div class="forgot-container">
        <div class="forgot-box">
            <div class="forgot-header">
                <div class="forgot-logo">
                    <?php if ($schoolLogo && file_exists('assets/images/' . $schoolLogo)): ?>
                        <img src="assets/images/<?php echo htmlspecialchars($schoolLogo); ?>" alt="School Logo">
                    <?php else: ?>
                        <i class="fas fa-key"></i>
                    <?php endif; ?>
                </div>
                <h2><?php echo htmlspecialchars($schoolName); ?></h2>
                <p>Reset your password to regain access</p>
            </div>
            
            <div class="forgot-steps">
                <div class="forgot-step <?php echo $step == 'request' ? 'active' : 'completed'; ?>">
                    <div class="step-num"><?php echo $step == 'reset' ? '<i class="fas fa-check"></i>' : '1'; ?></div>
                    <span>Request</span>
                </div>
                <div class="step-line <?php echo $step == 'reset' ? 'completed' : ''; ?>"></div>
                <div class="forgot-step <?php echo $step == 'reset' ? 'active' : ''; ?>">
                    <div class="step-num">2</div>
                    <span>Reset</span>
                </div>
            </div>
            
            <?php if ($error): ?>
                <div class="error-msg" style="margin-bottom: 20px; padding: 12px; border-radius: 8px; background: #fee;">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="success-msg" style="padding: 20px; border-radius: 8px; background: #efe;">
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($step == 'request' && !$success): ?>
            <form method="POST" action="">
                <div class="form-group">
                    <label><i class="fas fa-user"></i> Username</label>
                    <input type="text" name="username" placeholder="Enter your username" required>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-envelope"></i> Email Address</label>
                    <input type="email" name="email" placeholder="Enter your registered email" required>
                </div>
                <button type="submit" name="request_reset" class="btn btn-primary">
                    <i class="fas fa-paper-plane"></i> Request Password Reset
                </button>
            </form>
            <?php endif; ?>
            
            <?php if ($step == 'reset' && empty($success)): ?>
            <form method="POST" action="?step=reset&token=<?php echo htmlspecialchars($token); ?>">
                <div class="form-group password-group">
                    <label><i class="fas fa-lock"></i> New Password</label>
                    <input type="password" name="new_password" id="new_password" placeholder="Enter new password" required oninput="checkStrength(this.value)">
                    <i class="fas fa-eye password-toggle" onclick="togglePassword('new_password')"></i>
                    <div class="strength-bar"><div id="strength"></div></div>
                    <div class="strength-text" id="strength_text"></div>
                </div>
                <div class="form-group password-group">
                    <label><i class="fas fa-lock"></i> Confirm Password</label>
                    <input type="password" name="confirm_password" id="confirm_password" placeholder="Confirm new password" required>
                    <i class="fas fa-eye password-toggle" onclick="togglePassword('confirm_password')"></i>
                </div>
                <button type="submit" name="reset_password" class="btn btn-success">
                    <i class="fas fa-check"></i> Reset Password
                </button>
            </form>
            <?php endif; ?>
            
            <div class="back-link">
                <a href="login.php">
                    <i class="fas fa-arrow-left"></i> Back to Login
                </a>
            </div>
        </div>
    </div>
    
    <script>
        function togglePassword(id) {
            var input = document.getElementById(id);
            var icon = input.nextElementSibling;
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
        
        function checkStrength(password) {
            var strength = document.getElementById('strength');
            var strengthText = document.getElementById('strength_text');
            strength.className = '';
            
            if (password.length === 0) {
                strength.style.width = '0';
                strengthText.textContent = '';
            } else if (password.length < 6) {
                strength.classList.add('weak');
                strengthText.textContent = 'Weak - Use at least 6 characters';
                strengthText.style.color = '#e74c3c';
            } else if (password.length < 10) {
                strength.classList.add('medium');
                strengthText.textContent = 'Medium - Add special characters for stronger';
                strengthText.style.color = '#f39c12';
            } else {
                strength.classList.add('strong');
                strengthText.textContent = 'Strong password!';
                strengthText.style.color = '#27ae60';
            }
        }
    </script>
</body>
</html>
