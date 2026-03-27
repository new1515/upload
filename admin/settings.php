<?php
require_once '../config/database.php';

if (!isLoggedIn()) {
    redirect('../login.php');
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['save_settings'])) {
        foreach ($_POST as $key => $value) {
            if ($key !== 'save_settings') {
                $stmt = $pdo->prepare("INSERT INTO school_settings (setting_key, setting_value) VALUES (?, ?) 
                    ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
                $stmt->execute([$key, $value]);
            }
        }
        $message = '<div class="success-msg">Settings saved successfully!</div>';
    }
    
    if (isset($_POST['upload_logo']) && isset($_FILES['logo'])) {
        $file = $_FILES['logo'];
        if ($file['error'] === UPLOAD_ERR_OK) {
            $uploadDir = '../assets/images/';
            $fileName = 'logo.' . pathinfo($file['name'], PATHINFO_EXTENSION);
            $targetPath = $uploadDir . $fileName;
            
            if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                $stmt = $pdo->prepare("INSERT INTO school_settings (setting_key, setting_value) VALUES ('school_logo', ?) 
                    ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
                $stmt->execute([$fileName]);
                $message = '<div class="success-msg">Logo uploaded successfully!</div>';
            }
        }
    }
    
    if (isset($_FILES['login_background']) && $_FILES['login_background']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['login_background'];
        $uploadDir = '../assets/images/backgrounds/';
        if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);
        $fileName = 'login_bg.' . pathinfo($file['name'], PATHINFO_EXTENSION);
        $targetPath = $uploadDir . $fileName;
        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            $stmt = $pdo->prepare("INSERT INTO school_settings (setting_key, setting_value) VALUES ('login_background', ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
            $stmt->execute([$fileName]);
        }
    }
    
    if (isset($_FILES['dashboard_background']) && $_FILES['dashboard_background']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['dashboard_background'];
        $uploadDir = '../assets/images/backgrounds/';
        if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);
        $fileName = 'dashboard_bg.' . pathinfo($file['name'], PATHINFO_EXTENSION);
        $targetPath = $uploadDir . $fileName;
        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            $stmt = $pdo->prepare("INSERT INTO school_settings (setting_key, setting_value) VALUES ('dashboard_background', ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
            $stmt->execute([$fileName]);
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - School Management System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        .settings-container {
            display: grid;
            grid-template-columns: 250px 1fr;
            gap: 30px;
        }
        .settings-nav {
            background: var(--white);
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 5px 20px var(--shadow);
            height: fit-content;
        }
        .settings-nav a {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 15px;
            color: var(--gray);
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s;
            margin-bottom: 5px;
        }
        .settings-nav a:hover, .settings-nav a.active {
            background: var(--primary);
            color: var(--white);
        }
        .settings-content {
            background: var(--white);
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 20px var(--shadow);
        }
        .settings-section {
            margin-bottom: 30px;
        }
        .settings-section h3 {
            color: var(--dark);
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--primary);
        }
        .logo-preview {
            display: flex;
            align-items: center;
            gap: 20px;
            margin: 20px 0;
            padding: 20px;
            background: var(--light);
            border-radius: 10px;
        }
        .logo-preview img {
            max-width: 150px;
            max-height: 150px;
            border-radius: 10px;
        }
        .logo-placeholder {
            width: 150px;
            height: 150px;
            background: var(--primary);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--white);
            font-size: 50px;
        }
        @media (max-width: 768px) {
            .settings-container {
                grid-template-columns: 1fr;
            }
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
            <i class="fas fa-graduation-cap"></i>
            School Admin
        </div>
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
            <li><a href="settings.php" class="active"><i class="fas fa-cog"></i> Settings</a></li>
            <li><a href="../logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </nav>
    
    <main class="main-content">
        <div class="school-header">
            <div class="school-logo">
                <?php if ($schoolLogo && file_exists('../assets/images/' . $schoolLogo)): ?>
                    <img src="../assets/images/<?php echo $schoolLogo; ?>" alt="School Logo">
                <?php else: ?>
                    <i class="fas fa-graduation-cap"></i>
                <?php endif; ?>
            </div>
            <div class="school-info">
                <h1><?php echo $schoolName; ?></h1>
                <p><?php echo $settings['school_tagline'] ?? ''; ?></p>
            </div>
        </div>
        
        <div class="header">
            <h1><i class="fas fa-cog"></i> Settings</h1>
            <div class="header-right">
                <div class="theme-switcher">
                    <button class="theme-btn active" data-theme="blue" title="Blue"></button>
                    <button class="theme-btn" data-theme="green" title="Green"></button>
                    <button class="theme-btn" data-theme="purple" title="Purple"></button>
                    <button class="theme-btn" data-theme="red" title="Red"></button>
                    <button class="theme-btn" data-theme="orange" title="Orange"></button>
                    <button class="theme-btn" data-theme="teal" title="Teal"></button>
                    <button class="theme-btn" data-theme="pink" title="Pink"></button>
                    <button class="theme-btn" data-theme="gold" title="Gold"></button>
                    <button class="theme-btn" data-theme="dark" title="Dark"></button>
                    <button class="theme-btn" data-theme="ocean" title="Ocean"></button>
                    <button class="theme-btn" data-theme="sky" title="Sky"></button>
                    <button class="theme-btn" data-theme="emerald" title="Emerald"></button>
                    <button class="theme-btn" data-theme="violet" title="Violet"></button>
                </div>
                <a href="settings.php" title="Settings"><i class="fas fa-cog"></i></a>
                <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
        
        <div class="content">
            <?php echo $message; ?>
            
            <div class="settings-container">
                <div class="settings-nav">
                    <a href="#school-info" class="active"><i class="fas fa-school"></i> School Info</a>
                    <a href="#logo"><i class="fas fa-image"></i> Logo</a>
                    <a href="#contact"><i class="fas fa-address-book"></i> Contact</a>
                    <a href="#school-hours"><i class="fas fa-clock"></i> School Hours</a>
                    <a href="#background-theme"><i class="fas fa-palette"></i> Background & Theme</a>
                </div>
                
                <div class="settings-content">
                    <form method="POST" enctype="multipart/form-data">
                        <div class="settings-section" id="school-info">
                            <h3><i class="fas fa-school"></i> School Information</h3>
                            <div class="form-group">
                                <label>School Name</label>
                                <input type="text" name="school_name" value="<?php echo $settings['school_name'] ?? 'Ghana Basic School'; ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Tagline</label>
                                <input type="text" name="school_tagline" value="<?php echo $settings['school_tagline'] ?? ''; ?>">
                            </div>
                            <div class="form-group">
                                <label>Address</label>
                                <input type="text" name="school_address" value="<?php echo $settings['school_address'] ?? ''; ?>">
                            </div>
                            <div class="form-group">
                                <label>Headmaster/Headmistress Name</label>
                                <input type="text" name="headmaster_name" value="<?php echo $settings['headmaster_name'] ?? ''; ?>" placeholder="e.g., Mr. Emmanuel Kofi Asante">
                                <small style="color: #888;">This name will appear on report cards.</small>
                            </div>
                            <div class="form-group">
                                <label>Headmaster Title</label>
                                <input type="text" name="headmaster_title" value="<?php echo $settings['headmaster_title'] ?? 'Headmaster'; ?>" placeholder="e.g., Headmaster">
                                <small style="color: #888;">Title displayed before name on report cards.</small>
                            </div>
                            <div class="form-group">
                                <label>Class Teacher Label</label>
                                <input type="text" name="class_teacher_title" value="<?php echo $settings['class_teacher_title'] ?? 'Class Teacher'; ?>" placeholder="e.g., Class Teacher">
                                <small style="color: #888;">Label for class teacher signature.</small>
                            </div>
                        </div>
                        
                        <div class="settings-section" id="logo">
                            <h3><i class="fas fa-image"></i> School Logo</h3>
                            <div class="logo-preview">
                                <?php if (!empty($settings['school_logo']) && file_exists('../assets/images/' . $settings['school_logo'])): ?>
                                <img src="../assets/images/<?php echo $settings['school_logo']; ?>" alt="School Logo">
                                <?php else: ?>
                                <div class="logo-placeholder">
                                    <i class="fas fa-graduation-cap"></i>
                                </div>
                                <?php endif; ?>
                                <div>
                                    <p style="margin-bottom: 15px;">Upload a logo for your school.</p>
                                    <div style="background: #f8f9fa; padding: 10px; border-radius: 8px; margin-bottom: 15px; font-size: 13px;">
                                        <strong>Recommended Sizes:</strong>
                                        <ul style="margin: 5px 0 0 20px; padding: 0;">
                                            <li>Report Cards: 150x150px</li>
                                            <li>Header: 200x200px</li>
                                            <li>Login Page: 250x250px</li>
                                        </ul>
                                    </div>
                                    <input type="file" name="logo" accept="image/*">
                                    <button type="submit" name="upload_logo" class="btn btn-primary" style="margin-top: 10px;">
                                        <i class="fas fa-upload"></i> Upload Logo
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="settings-section" id="contact">
                            <h3><i class="fas fa-address-book"></i> Contact Information</h3>
                            <div class="form-group">
                                <label>Phone Number</label>
                                <input type="text" name="school_phone" value="<?php echo $settings['school_phone'] ?? ''; ?>">
                            </div>
                            <div class="form-group">
                                <label>Email</label>
                                <input type="email" name="school_email" value="<?php echo $settings['school_email'] ?? ''; ?>">
                            </div>
                        </div>
                        
                        <div class="settings-section" id="school-hours">
                            <h3><i class="fas fa-clock"></i> School Hours & Periods</h3>
                            <p style="color: #666; margin-bottom: 20px;">Configure your school's daily schedule. These times will be used for generating the timetable.</p>
                            
                            <div class="form-group">
                                <label>School Start Time</label>
                                <input type="time" name="school_start_time" value="<?php echo $settings['school_start_time'] ?? '07:30'; ?>">
                                <small style="color: #888;">When the first period begins each day</small>
                            </div>
                            
                            <div class="form-group">
                                <label>Period Duration (minutes)</label>
                                <select name="period_duration">
                                    <option value="30" <?php echo ($settings['period_duration'] ?? '') == '30' ? 'selected' : ''; ?>>30 minutes</option>
                                    <option value="35" <?php echo ($settings['period_duration'] ?? '') == '35' ? 'selected' : ''; ?>>35 minutes</option>
                                    <option value="40" <?php echo ($settings['period_duration'] ?? '40') == '40' ? 'selected' : ''; ?>>40 minutes</option>
                                    <option value="45" <?php echo ($settings['period_duration'] ?? '') == '45' ? 'selected' : ''; ?>>45 minutes</option>
                                    <option value="50" <?php echo ($settings['period_duration'] ?? '') == '50' ? 'selected' : ''; ?>>50 minutes</option>
                                    <option value="60" <?php echo ($settings['period_duration'] ?? '') == '60' ? 'selected' : ''; ?>>60 minutes</option>
                                </select>
                                <small style="color: #888;">Length of each teaching period</small>
                            </div>
                            
                            <div style="background: #f8f9fa; padding: 20px; border-radius: 10px; margin: 20px 0;">
                                <h4 style="margin-bottom: 15px;"><i class="fas fa-coffee"></i> Break Times</h4>
                                
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                                    <div class="form-group">
                                        <label>Morning Break Start</label>
                                        <input type="time" name="break1_start" value="<?php echo $settings['break1_start'] ?? '09:30'; ?>">
                                    </div>
                                    <div class="form-group">
                                        <label>Morning Break End</label>
                                        <input type="time" name="break1_end" value="<?php echo $settings['break1_end'] ?? '10:00'; ?>">
                                    </div>
                                </div>
                                
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                                    <div class="form-group">
                                        <label>Lunch Break Start</label>
                                        <input type="time" name="break2_start" value="<?php echo $settings['break2_start'] ?? '12:00'; ?>">
                                    </div>
                                    <div class="form-group">
                                        <label>Lunch Break End</label>
                                        <input type="time" name="break2_end" value="<?php echo $settings['break2_end'] ?? '12:40'; ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label>School End Time</label>
                                <input type="time" name="school_end_time" value="<?php echo $settings['school_end_time'] ?? '14:30'; ?>">
                                <small style="color: #888;">When the last period ends each day</small>
                            </div>
                            
                            <div style="background: #e8f4fd; padding: 15px; border-radius: 10px; margin-top: 15px;">
                                <h4 style="margin-bottom: 10px;"><i class="fas fa-info-circle"></i> Quick Presets</h4>
                                <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                                    <button type="button" class="btn btn-sm" onclick="applyPreset('early')" style="background: #4a90e2; color: white; padding: 8px 15px; border: none; border-radius: 5px; cursor: pointer; font-size: 12px;">7:30 Start</button>
                                    <button type="button" class="btn btn-sm" onclick="applyPreset('standard')" style="background: #27ae60; color: white; padding: 8px 15px; border: none; border-radius: 5px; cursor: pointer; font-size: 12px;">8:00 Start</button>
                                    <button type="button" class="btn btn-sm" onclick="applyPreset('late')" style="background: #9b59b6; color: white; padding: 8px 15px; border: none; border-radius: 5px; cursor: pointer; font-size: 12px;">8:30 Start</button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="settings-section" id="background-theme">
                            <h3><i class="fas fa-palette"></i> Background & Theme Settings</h3>
                            
                            <div style="background: #f8f9fa; padding: 20px; border-radius: 10px; margin-bottom: 20px;">
                                <h4 style="margin-bottom: 15px;"><i class="fas fa-image"></i> Upload Background Image</h4>
                                <div class="form-group">
                                    <label>Login Page Background</label>
                                    <input type="file" name="login_background" accept="image/*">
                                    <small style="color: #888;">Recommended size: 1920x1080px (JPG, PNG, WebP)</small>
                                    <?php if (!empty($settings['login_background'])): ?>
                                        <div style="margin-top: 10px;">
                                            <img src="../assets/images/backgrounds/<?php echo htmlspecialchars($settings['login_background']); ?>" style="max-width: 200px; border-radius: 10px;">
                                            <p style="margin-top: 5px; color: green;">Current: <?php echo htmlspecialchars($settings['login_background']); ?></p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="form-group">
                                    <label>Dashboard Background</label>
                                    <input type="file" name="dashboard_background" accept="image/*">
                                    <small style="color: #888;">Background image for all dashboards</small>
                                    <?php if (!empty($settings['dashboard_background'])): ?>
                                        <div style="margin-top: 10px;">
                                            <img src="../assets/images/backgrounds/<?php echo htmlspecialchars($settings['dashboard_background']); ?>" style="max-width: 200px; border-radius: 10px;">
                                            <p style="margin-top: 5px; color: green;">Current: <?php echo htmlspecialchars($settings['dashboard_background']); ?></p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div style="background: #f8f9fa; padding: 20px; border-radius: 10px; margin-bottom: 20px;">
                                <h4 style="margin-bottom: 15px;"><i class="fas fa-paint-brush"></i> Predesigned Themes</h4>
                                
                                <div class="form-group">
                                    <label>Login Page Theme</label>
                                    <select name="login_theme">
                                        <option value="default" <?php echo ($settings['login_theme'] ?? 'default') == 'default' ? 'selected' : ''; ?>>Default (Purple Gradient)</option>
                                        <option value="ocean" <?php echo ($settings['login_theme'] ?? '') == 'ocean' ? 'selected' : ''; ?>>Ocean Blue</option>
                                        <option value="sunset" <?php echo ($settings['login_theme'] ?? '') == 'sunset' ? 'selected' : ''; ?>>Sunset Orange</option>
                                        <option value="forest" <?php echo ($settings['login_theme'] ?? '') == 'forest' ? 'selected' : ''; ?>>Forest Green</option>
                                        <option value="royal" <?php echo ($settings['login_theme'] ?? '') == 'royal' ? 'selected' : ''; ?>>Royal Purple</option>
                                        <option value="midnight" <?php echo ($settings['login_theme'] ?? '') == 'midnight' ? 'selected' : ''; ?>>Midnight Dark</option>
                                        <option value="Cherry Blossom" <?php echo ($settings['login_theme'] ?? '') == 'Cherry Blossom' ? 'selected' : ''; ?>>Cherry Blossom Pink</option>
                                        <option value="Snow" <?php echo ($settings['login_theme'] ?? '') == 'Snow' ? 'selected' : ''; ?>>Snow White</option>
                                        <option value="Space" <?php echo ($settings['login_theme'] ?? '') == 'Space' ? 'selected' : ''; ?>>Space Galaxy</option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label>Dashboard Theme</label>
                                    <select name="dashboard_theme">
                                        <option value="default" <?php echo ($settings['dashboard_theme'] ?? 'default') == 'default' ? 'selected' : ''; ?>>Default (Blue)</option>
                                        <option value="ocean" <?php echo ($settings['dashboard_theme'] ?? '') == 'ocean' ? 'selected' : ''; ?>>Ocean Blue</option>
                                        <option value="sunset" <?php echo ($settings['dashboard_theme'] ?? '') == 'sunset' ? 'selected' : ''; ?>>Sunset Orange</option>
                                        <option value="forest" <?php echo ($settings['dashboard_theme'] ?? '') == 'forest' ? 'selected' : ''; ?>>Forest Green</option>
                                        <option value="royal" <?php echo ($settings['dashboard_theme'] ?? '') == 'royal' ? 'selected' : ''; ?>>Royal Purple</option>
                                        <option value="dark" <?php echo ($settings['dashboard_theme'] ?? '') == 'dark' ? 'selected' : ''; ?>>Dark Mode</option>
                                    </select>
                                </div>
                                
                                <div style="display: flex; gap: 10px; flex-wrap: wrap; margin-top: 15px;">
                                    <?php
                                    $themes = [
                                        'default' => '#667eea',
                                        'ocean' => '#2196F3',
                                        'sunset' => '#ff7043',
                                        'forest' => '#4caf50',
                                        'royal' => '#9c27b0',
                                        'midnight' => '#1a1a2e',
                                        'Cherry Blossom' => '#f8bbd0',
                                        'Snow' => '#eceff1',
                                        'Space' => '#0d0d1a',
                                        'dark' => '#2c3e50'
                                    ];
                                    foreach ($themes as $theme => $color) {
                                        $active = ($settings['login_theme'] ?? 'default') == $theme ? 'border: 3px solid #000;' : '';
                                        echo '<div style="width: 50px; height: 50px; background: ' . $color . '; border-radius: 10px; cursor: pointer; ' . $active . '" title="' . $theme . '"></div>';
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                        
                        <button type="submit" name="save_settings" class="btn btn-success" style="width: 100%;">
                            <i class="fas fa-save"></i> Save All Settings
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </main>
    
    <script src="../assets/js/script.js"></script>
    <script>
        function applyPreset(preset) {
            const presets = {
                early: {
                    school_start_time: '07:30',
                    period_duration: '40',
                    break1_start: '09:30',
                    break1_end: '10:00',
                    break2_start: '12:00',
                    break2_end: '12:40',
                    school_end_time: '14:30'
                },
                standard: {
                    school_start_time: '08:00',
                    period_duration: '40',
                    break1_start: '10:00',
                    break1_end: '10:30',
                    break2_start: '12:30',
                    break2_end: '13:00',
                    school_end_time: '15:00'
                },
                late: {
                    school_start_time: '08:30',
                    period_duration: '40',
                    break1_start: '10:15',
                    break1_end: '10:45',
                    break2_start: '12:45',
                    break2_end: '13:15',
                    school_end_time: '15:30'
                }
            };
            
            const times = presets[preset];
            document.querySelector('input[name="school_start_time"]').value = times.school_start_time;
            document.querySelector('select[name="period_duration"]').value = times.period_duration;
            document.querySelector('input[name="break1_start"]').value = times.break1_start;
            document.querySelector('input[name="break1_end"]').value = times.break1_end;
            document.querySelector('input[name="break2_start"]').value = times.break2_start;
            document.querySelector('input[name="break2_end"]').value = times.break2_end;
            document.querySelector('input[name="school_end_time"]').value = times.school_end_time;
        }
        
        document.querySelectorAll('.settings-nav a').forEach(link => {
            link.addEventListener('click', function(e) {
                document.querySelectorAll('.settings-nav a').forEach(l => l.classList.remove('active'));
                this.classList.add('active');
            });
        });
    </script>
</body>
</html>
