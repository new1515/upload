<?php
require_once '../config/database.php';

if (!isLoggedIn()) {
    redirect('../login.php');
}

$settings = [];
$stmt = $pdo->query("SELECT * FROM school_settings");
while ($row = $stmt->fetch()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

$schoolName = $settings['school_name'] ?? 'Ghana Basic School';
$schoolPhone = $settings['school_phone'] ?? '';
$schoolEmail = $settings['school_email'] ?? '';
$schoolAddress = $settings['school_address'] ?? '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['send_message'])) {
    $message = sanitize($_POST['message']);
    $sessionId = session_id();
    
    $response = generateBotResponse($message, $schoolName, $schoolPhone, $schoolEmail, $schoolAddress, $pdo);
    
    $stmt = $pdo->prepare("INSERT INTO chatbot_messages (session_id, user_message, bot_response) VALUES (?, ?, ?)");
    $stmt->execute([$sessionId, $message, $response]);
    
    header("Location: chatbot.php");
    exit();
}

function replaceVariables($text, $schoolName, $schoolPhone, $schoolEmail, $schoolAddress) {
    $text = str_replace('[SCHOOL_NAME]', $schoolName, $text);
    $text = str_replace('[SCHOOL_PHONE]', $schoolPhone, $text);
    $text = str_replace('[SCHOOL_EMAIL]', $schoolEmail, $text);
    $text = str_replace('[SCHOOL_ADDRESS]', $schoolAddress, $text);
    return $text;
}

function generateBotResponse($message, $schoolName, $schoolPhone, $schoolEmail, $schoolAddress, $pdo) {
    global $settings;
    $message = strtolower(trim($message));
    
    $qaList = $pdo->query("SELECT * FROM chatbot_qa WHERE is_active = 1 ORDER BY id DESC")->fetchAll();
    
    $matchedAnswers = [];
    foreach ($qaList as $qa) {
        $keywords = array_map('trim', explode(',', strtolower($qa['keywords'])));
        foreach ($keywords as $keyword) {
            if (!empty($keyword) && strpos($message, $keyword) !== false) {
                $matchedAnswers[] = $qa['answer'];
                break;
            }
        }
    }
    
    if (!empty($matchedAnswers)) {
        $answer = $matchedAnswers[array_rand($matchedAnswers)];
        return replaceVariables($answer, $schoolName, $schoolPhone, $schoolEmail, $schoolAddress);
    }
    
    $defaultResponses = [
        "I'm not sure I understand. Could you rephrase your question?",
        "I don't have information about that yet. Try asking about admissions, fees, school hours, or subjects!",
        "That's an interesting question! Please contact the school administration for detailed information.",
        "I'd be happy to help with more specific questions about the school. What would you like to know?",
        "For detailed information, please contact the school office or check our resources."
    ];
    
    return $defaultResponses[array_rand($defaultResponses)];
}

$quickActions = $pdo->query("SELECT * FROM chatbot_qa WHERE is_active = 1 ORDER BY RAND() LIMIT 6")->fetchAll();

$messages = $pdo->query("SELECT * FROM chatbot_messages ORDER BY created_at DESC LIMIT 50")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Chatbot - School Management System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        .chatbot-container {
            display: grid;
            grid-template-columns: 1fr 350px;
            gap: 30px;
            height: calc(100vh - 200px);
        }
        .chat-area {
            background: var(--white);
            border-radius: 15px;
            box-shadow: 0 5px 20px var(--shadow);
            display: flex;
            flex-direction: column;
        }
        .chat-header {
            padding: 20px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: var(--white);
            border-radius: 15px 15px 0 0;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .chat-header img {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: var(--white);
        }
        .chat-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: var(--white);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary);
            font-size: 24px;
        }
        .chat-header h3 {
            font-size: 18px;
        }
        .chat-header p {
            font-size: 12px;
            opacity: 0.9;
        }
        .chat-messages {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
            background: #f8f9fa;
        }
        .message {
            margin-bottom: 15px;
            animation: fadeIn 0.3s ease;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .message.bot {
            display: flex;
            gap: 10px;
        }
        .message.user {
            justify-content: flex-end;
        }
        .message-avatar {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            background: var(--primary);
            color: var(--white);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .message-content {
            max-width: 70%;
            padding: 12px 16px;
            border-radius: 15px;
            font-size: 14px;
            line-height: 1.5;
        }
        .message.bot .message-content {
            background: var(--white);
            border-bottom-left-radius: 5px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .message.user .message-content {
            background: var(--primary);
            color: var(--white);
            border-bottom-right-radius: 5px;
        }
        .chat-input {
            padding: 20px;
            border-top: 1px solid #eee;
            display: flex;
            gap: 10px;
        }
        .chat-input input {
            flex: 1;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 25px;
            font-size: 14px;
        }
        .chat-input input:focus {
            outline: none;
            border-color: var(--primary);
        }
        .chat-input button {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            border: none;
            background: var(--primary);
            color: var(--white);
            cursor: pointer;
            transition: all 0.3s;
        }
        .chat-input button:hover {
            background: var(--primary-dark);
            transform: scale(1.05);
        }
        .quick-actions {
            background: var(--white);
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 5px 20px var(--shadow);
        }
        .quick-actions h3 {
            margin-bottom: 15px;
            color: var(--dark);
        }
        .quick-btn {
            display: block;
            width: 100%;
            padding: 12px 15px;
            margin-bottom: 10px;
            background: var(--light);
            border: none;
            border-radius: 10px;
            text-align: left;
            cursor: pointer;
            transition: all 0.3s;
            color: var(--dark);
        }
        .quick-btn:hover {
            background: var(--primary);
            color: var(--white);
        }
        .quick-btn i {
            margin-right: 10px;
            color: var(--primary);
        }
        .quick-btn:hover i {
            color: var(--white);
        }
        .chat-history {
            margin-top: 20px;
        }
        .chat-history h4 {
            font-size: 14px;
            color: var(--gray);
            margin-bottom: 10px;
        }
        .history-item {
            padding: 10px;
            background: var(--light);
            border-radius: 8px;
            margin-bottom: 8px;
            font-size: 13px;
            color: var(--dark);
        }
        @media (max-width: 992px) {
            .chatbot-container {
                grid-template-columns: 1fr;
            }
            .quick-actions {
                display: none;
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
            <li><a href="results.php"><i class="fas fa-chart-line"></i> Results</a></li>
            <li><a href="reportcards.php"><i class="fas fa-file-alt"></i> Report Cards</a></li>
            <li><a href="calendar.php"><i class="fas fa-calendar-alt"></i> Calendar</a></li>
            <li><a href="announcements.php"><i class="fas fa-bullhorn"></i> Announcements</a></li>
            <li><a href="fees.php"><i class="fas fa-money-bill"></i> School Fees</a></li>
            <li><a href="chatbot.php" class="active"><i class="fas fa-robot"></i> AI Chatbot</a></li>
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
            <h1><i class="fas fa-robot"></i> AI Chatbot</h1>
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
        
        <div class="content">
            <div class="chatbot-container">
                <div class="chat-area">
                    <div class="chat-header">
                        <div class="chat-avatar">
                            <i class="fas fa-robot"></i>
                        </div>
                        <div>
                            <h3><?php echo $schoolName; ?> Assistant</h3>
                            <p>Online - Ready to help</p>
                        </div>
                    </div>
                    <div class="chat-messages" id="chatMessages">
                        <div class="message bot">
                            <div class="message-avatar">
                                <i class="fas fa-robot"></i>
                            </div>
                            <div class="message-content">
                                Hello! I'm the <?php echo $schoolName; ?> AI assistant. I can help you with information about:
                                <br><br>
                                • Admissions<br>
                                • School fees<br>
                                • Contact details<br>
                                • School hours<br>
                                • Subjects<br>
                                • Results<br>
                                • And more!<br>
                                <br>
                                What would you like to know?<br>
                                <small style="opacity: 0.7;">Ask me anything or click a quick action below!</small>
                            </div>
                        </div>
                        <?php foreach (array_reverse($messages) as $msg): ?>
                        <div class="message user">
                            <div class="message-content"><?php echo htmlspecialchars($msg['user_message']); ?></div>
                        </div>
                        <div class="message bot">
                            <div class="message-avatar">
                                <i class="fas fa-robot"></i>
                            </div>
                            <div class="message-content"><?php echo nl2br(htmlspecialchars($msg['bot_response'])); ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <form method="POST" class="chat-input">
                        <input type="text" name="message" placeholder="Type your message here..." required autocomplete="off">
                        <button type="submit" name="send_message">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </form>
                </div>
                
                <div class="quick-actions">
                    <h3><i class="fas fa-bolt"></i> Quick Actions</h3>
                    <?php if (empty($quickActions)): ?>
                        <p style="color: var(--gray); font-size: 13px;">No Q&A configured yet. <a href="chatbot_qa.php">Add some Q&A</a></p>
                    <?php else: ?>
                        <?php foreach ($quickActions as $qa): ?>
                            <?php 
                            $firstKeyword = explode(',', $qa['keywords'])[0];
                            $firstKeyword = trim(ucfirst($firstKeyword));
                            ?>
                            <form method="POST" style="display: contents;">
                                <input type="hidden" name="message" value="<?php echo htmlspecialchars($firstKeyword); ?>">
                                <button type="submit" class="quick-btn"><i class="fas fa-question-circle"></i> <?php echo htmlspecialchars($firstKeyword); ?></button>
                            </form>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    
                    <div class="chat-history">
                        <h4><i class="fas fa-history"></i> Recent Conversations</h4>
                        <?php foreach (array_slice(array_reverse($messages), 0, 5) as $msg): ?>
                        <div class="history-item">
                            <strong>You:</strong> <?php echo substr($msg['user_message'], 0, 30); ?>...
                        </div>
                        <?php endforeach; ?>
                        <?php if (empty($messages)): ?>
                        <p style="color: var(--gray); font-size: 13px;">No conversations yet</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    <script>
        document.querySelector('.chat-messages').scrollTop = document.querySelector('.chat-messages').scrollHeight;
    </script>
    <script src="../assets/js/script.js"></script>
</body>
</html>
