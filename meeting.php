<?php
require_once 'config/database.php';

$error = '';
$meeting = null;
$meetingId = isset($_GET['id']) ? sanitize($_GET['id']) : '';

if ($meetingId) {
    $stmt = $pdo->prepare("SELECT * FROM meetings WHERE meeting_id = ?");
    $stmt->execute([$meetingId]);
    $meeting = $stmt->fetch();
    
    if (!$meeting) {
        $error = 'Meeting not found or has expired';
    } elseif ($meeting['status'] === 'ended') {
        $error = 'This meeting has ended';
    } elseif (strtotime($meeting['scheduled_at']) > time() && $meeting['scheduled_at']) {
        $error = 'This meeting has not started yet';
    }
}

$userName = '';
$userRole = 'guest';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $meeting) {
    $userName = sanitize($_POST['name']);
    $userRole = sanitize($_POST['role']);
    
    if (empty($userName)) {
        $error = 'Please enter your name';
    }
}

$isJoined = $meeting && !empty($userName);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $meeting ? 'Join: ' . htmlspecialchars($meeting['title']) : 'Meeting Not Found'; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/styles.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        :root {
            --primary: #667eea;
            --primary-dark: #764ba2;
            --success: #1ab26b;
            --danger: #ea4335;
            --warning: #fbbc05;
            --bg-dark: #1a1a2e;
            --bg-darker: #16213e;
            --text-light: #ffffff;
            --text-muted: #9ca3af;
        }
        
        body {
            font-family: 'Google Sans', 'Segoe UI', Roboto, Arial, sans-serif;
            background: linear-gradient(135deg, var(--bg-dark) 0%, var(--bg-darker) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .join-container {
            width: 100%;
            max-width: 480px;
            background: rgba(30, 30, 46, 0.95);
            border-radius: 24px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.4);
            backdrop-filter: blur(20px);
        }
        
        .meeting-icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
        }
        
        .meeting-icon i {
            font-size: 32px;
            color: white;
        }
        
        .meeting-icon.scheduled {
            background: linear-gradient(135deg, var(--warning) 0%, #f57c00 100%);
        }
        
        .meeting-icon.ended {
            background: linear-gradient(135deg, #666 0%, #444 100%);
        }
        
        h1 {
            text-align: center;
            font-size: 24px;
            margin-bottom: 8px;
        }
        
        .meeting-title {
            text-align: center;
            color: var(--text-muted);
            margin-bottom: 8px;
            font-size: 14px;
        }
        
        .meeting-id-display {
            text-align: center;
            padding: 12px;
            background: rgba(255,255,255,0.05);
            border-radius: 12px;
            margin-bottom: 24px;
        }
        
        .meeting-id-display .label {
            font-size: 12px;
            color: var(--text-muted);
            margin-bottom: 4px;
        }
        
        .meeting-id-display .code {
            font-size: 20px;
            font-weight: 600;
            color: var(--primary);
            letter-spacing: 2px;
        }
        
        .host-info {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px;
            background: rgba(255,255,255,0.05);
            border-radius: 12px;
            margin-bottom: 24px;
        }
        
        .host-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--danger) 0%, #c62828 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 16px;
        }
        
        .host-info .details {
            flex: 1;
        }
        
        .host-info .name {
            font-weight: 500;
            font-size: 14px;
        }
        
        .host-info .role {
            font-size: 12px;
            color: var(--text-muted);
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            color: var(--text-muted);
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 14px 16px;
            border: none;
            border-radius: 12px;
            background: rgba(255,255,255,0.1);
            color: white;
            font-size: 16px;
            transition: all 0.2s;
        }
        
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            background: rgba(255,255,255,0.15);
            box-shadow: 0 0 0 2px var(--primary);
        }
        
        .form-group select option {
            background: var(--bg-dark);
            color: white;
        }
        
        .join-btn {
            width: 100%;
            padding: 16px;
            border: none;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .join-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
        }
        
        .join-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }
        
        .error-message {
            padding: 16px;
            background: rgba(234, 67, 53, 0.1);
            border: 1px solid var(--danger);
            border-radius: 12px;
            color: var(--danger);
            text-align: center;
            margin-bottom: 24px;
        }
        
        .footer-note {
            text-align: center;
            margin-top: 24px;
            font-size: 12px;
            color: var(--text-muted);
        }
        
        .footer-note a {
            color: var(--primary);
            text-decoration: none;
        }
        
        @media (max-width: 480px) {
            .join-container {
                padding: 24px;
                border-radius: 16px;
            }
            
            h1 {
                font-size: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="join-container">
        <?php if ($error): ?>
            <div class="meeting-icon ended">
                <i class="fas fa-video-slash"></i>
            </div>
            <h1>Cannot Join</h1>
            <div class="error-message">
                <i class="fas fa-exclamation-triangle"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
            <a href="login.php" class="join-btn" style="display: block; text-align: center; text-decoration: none;">
                <i class="fas fa-home"></i> Go to School Portal
            </a>
        <?php elseif ($meeting): ?>
            <div class="meeting-icon <?php echo $meeting['status']; ?>">
                <i class="fas fa-video"></i>
            </div>
            
            <h1>Join Meeting</h1>
            <p class="meeting-title"><?php echo htmlspecialchars($meeting['title']); ?></p>
            
            <div class="meeting-id-display">
                <div class="label">Meeting ID</div>
                <div class="code"><?php echo strtoupper($meeting['meeting_id']); ?></div>
            </div>
            
            <div class="host-info">
                <div class="host-avatar">
                    <i class="fas fa-crown"></i>
                </div>
                <div class="details">
                    <div class="name"><?php echo htmlspecialchars($meeting['host_name']); ?></div>
                    <div class="role"><?php echo ucfirst($meeting['host_role']); ?> (Host)</div>
                </div>
            </div>
            
            <form method="POST">
                <div class="form-group">
                    <label>Your Name</label>
                    <input type="text" name="name" placeholder="Enter your name" value="<?php echo htmlspecialchars($userName); ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Your Role</label>
                    <select name="role">
                        <option value="guest" <?php echo $userRole === 'guest' ? 'selected' : ''; ?>>Guest</option>
                        <option value="student" <?php echo $userRole === 'student' ? 'selected' : ''; ?>>Student</option>
                        <option value="parent" <?php echo $userRole === 'parent' ? 'selected' : ''; ?>>Parent</option>
                        <option value="teacher" <?php echo $userRole === 'teacher' ? 'selected' : ''; ?>>Teacher</option>
                    </select>
                </div>
                
                <button type="submit" class="join-btn">
                    <i class="fas fa-video"></i> Join Meeting
                </button>
            </form>
            
            <div class="footer-note">
                <a href="login.php">Login to school portal</a> for full access
            </div>
        <?php else: ?>
            <div class="meeting-icon ended">
                <i class="fas fa-search"></i>
            </div>
            <h1>Join Meeting</h1>
            <form method="GET" style="margin-top: 24px;">
                <div class="form-group">
                    <label>Enter Meeting ID</label>
                    <input type="text" name="id" placeholder="Enter meeting ID" value="<?php echo htmlspecialchars($meetingId); ?>" required style="text-transform: uppercase; text-align: center; letter-spacing: 2px;">
                </div>
                <button type="submit" class="join-btn">
                    <i class="fas fa-arrow-right"></i> Continue
                </button>
            </form>
            <div class="footer-note">
                <a href="login.php">Login to school portal</a> to create a new meeting
            </div>
        <?php endif; ?>
    </div>
    
    <?php if ($isJoined): ?>
    <script>
        const meetingData = {
            meetingId: '<?php echo htmlspecialchars($meeting['meeting_id']); ?>',
            meetingTitle: '<?php echo htmlspecialchars($meeting['title']); ?>',
            userName: '<?php echo htmlspecialchars($userName); ?>',
            userRole: '<?php echo htmlspecialchars($userRole); ?>',
            hostId: <?php echo (int)$meeting['host_id']; ?>,
            isHost: false
        };
        sessionStorage.setItem('meetingData', JSON.stringify(meetingData));
        window.location.href = 'conference.php?id=<?php echo htmlspecialchars($meeting['meeting_id']); ?>&name=<?php echo urlencode($userName); ?>&role=<?php echo urlencode($userRole); ?>';
    </script>
    <?php endif; ?>
</body>
</html>
