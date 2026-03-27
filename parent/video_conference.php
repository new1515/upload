<?php
require_once '../config/database.php';

if (!isset($_SESSION['parent_id'])) {
    redirect('../login.php');
}

$settings = [];
$stmt = $pdo->query("SELECT * FROM school_settings");
while ($row = $stmt->fetch()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
$schoolName = $settings['school_name'] ?? 'School Management System';
$schoolLogo = $settings['school_logo'] ?? '';

$error = '';
$roomName = '';
$userName = $_SESSION['parent_name'] ?? 'Parent';
$userRole = 'parent';
$isAdmin = false;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['join_room'])) {
        $roomName = sanitize($_POST['join_room']);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Video Conference - <?php echo htmlspecialchars($schoolName); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        :root {
            --primary: #fbbc05;
            --primary-dark: #f9a825;
            --success: #1ab26b;
            --danger: #ea4335;
            --warning: #fbbc05;
            --bg-dark: #1a1a2e;
            --bg-darker: #16213e;
            --bg-card: #2d2d44;
            --text-light: #ffffff;
            --text-muted: #9ca3af;
        }
        
        html, body { height: 100%; overflow: hidden; }
        
        body {
            font-family: 'Google Sans', 'Segoe UI', Roboto, Arial, sans-serif;
            background: linear-gradient(135deg, var(--bg-dark) 0%, var(--bg-darker) 100%);
            color: var(--text-light);
        }
        
        .meet-app { display: flex; flex-direction: column; height: 100vh; height: 100dvh; }
        
        .meet-header {
            display: flex; align-items: center; justify-content: space-between;
            padding: 12px 20px; background: rgba(0,0,0,0.3);
            backdrop-filter: blur(10px); z-index: 100;
        }
        
        .meet-logo { display: flex; align-items: center; gap: 12px; }
        
        .meet-logo-icon {
            width: 40px; height: 40px; border-radius: 50%;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            display: flex; align-items: center; justify-content: center;
        }
        
        .meet-logo-icon i { font-size: 20px; color: #333; }
        .meet-title { font-size: 16px; font-weight: 500; }
        
        .meet-timer {
            display: flex; align-items: center; gap: 8px;
            padding: 6px 12px; background: rgba(255,255,255,0.1);
            border-radius: 20px; font-size: 13px;
        }
        
        .meet-timer .dot {
            width: 8px; height: 8px; background: var(--danger);
            border-radius: 50%; animation: blink 1s infinite;
        }
        
        @keyframes blink { 0%, 100% { opacity: 1; } 50% { opacity: 0.3; } }
        
        .meet-main {
            flex: 1;
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .video-container-wrapper {
            width: 100%;
            height: 100%;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .video-scroll-container {
            width: 100%;
            height: 100%;
            overflow: hidden;
            position: relative;
        }
        
        .video-scroll-btn {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            width: 48px;
            height: 48px;
            border-radius: 50%;
            border: none;
            background: rgba(0,0,0,0.6);
            color: white;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            z-index: 60;
            opacity: 0;
            transition: opacity 0.3s, transform 0.3s;
        }
        
        .video-scroll-btn:hover {
            background: rgba(0,0,0,0.8);
            transform: translateY(-50%) scale(1.1);
        }
        
        .video-scroll-btn.left {
            left: 16px;
        }
        
        .video-scroll-btn.right {
            right: 16px;
        }
        
        .video-scroll-btn.show {
            opacity: 1;
        }
        
        .video-grid {
            display: grid;
            gap: 8px;
            padding: 16px;
            width: 100%;
            height: 100%;
            align-content: center;
            justify-items: center;
        }
        
        .video-grid {
            display: grid; gap: 8px; padding: 16px;
            width: 100%; height: 100%; align-content: center; justify-items: center;
        }
        
        .video-grid.count-1 { grid-template-columns: 1fr; max-width: 640px; justify-self: center; }
        .video-grid.count-2, .video-grid.count-3, .video-grid.count-4 { grid-template-columns: repeat(2, 1fr); max-width: 900px; margin: 0 auto; }
        .video-grid.count-5, .video-grid.count-6 { grid-template-columns: repeat(3, 1fr); max-width: 1000px; margin: 0 auto; }
        
        .video-tile {
            position: relative; background: var(--bg-card);
            border-radius: 16px; overflow: hidden;
            aspect-ratio: 16/9; width: 100%; max-width: 320px; min-width: 180px;
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .video-tile:hover { transform: scale(1.02); box-shadow: 0 8px 30px rgba(0,0,0,0.4); }
        .video-tile.speaking { box-shadow: 0 0 0 3px var(--success), 0 8px 30px rgba(26, 178, 107, 0.3); }
        
        .video-tile video { width: 100%; height: 100%; object-fit: cover; border-radius: 16px; }
        
        .video-tile .avatar {
            width: 60px; height: 60px; border-radius: 50%;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            display: flex; align-items: center; justify-content: center;
            font-size: 24px; color: #333;
        }
        
        .video-tile .name-tag {
            position: absolute; bottom: 12px; left: 12px;
            display: flex; align-items: center; gap: 8px;
            padding: 6px 12px; background: rgba(0,0,0,0.7);
            border-radius: 8px; font-size: 13px;
        }
        
        .video-tile .name-tag .badge {
            padding: 2px 8px; border-radius: 4px; font-size: 10px; font-weight: 600;
        }
        
        .badge-host { background: var(--danger); }
        .badge-you { background: var(--primary); color: #333; }
        .badge-dummy { background: var(--warning); color: #000; }
        
        .video-tile .mic-indicator {
            position: absolute; top: 12px; right: 12px;
            width: 32px; height: 32px; border-radius: 50%;
            background: rgba(0,0,0,0.6);
            display: flex; align-items: center; justify-content: center;
        }
        
        .video-tile .mic-indicator.muted { background: var(--danger); }
        
        .video-tile .hand-indicator {
            position: absolute; top: 12px; left: 12px;
            font-size: 24px; animation: wave 0.5s infinite alternate;
        }
        
        @keyframes wave { from { transform: rotate(-10deg); } to { transform: rotate(10deg); } }
        
        .meet-controls {
            display: flex; align-items: center; justify-content: center;
            gap: 12px; padding: 20px;
            background: rgba(0,0,0,0.5); backdrop-filter: blur(10px);
        }
        
        .control-btn {
            width: 56px; height: 56px; border-radius: 50%;
            border: none; cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            font-size: 20px; transition: all 0.2s; position: relative;
        }
        
        .control-btn:hover { transform: scale(1.1); }
        .control-btn:active { transform: scale(0.95); }
        
        .control-btn.mic { background: rgba(255,255,255,0.9); color: #333; }
        .control-btn.mic.muted { background: var(--danger); color: white; }
        
        .control-btn.camera { background: rgba(255,255,255,0.9); color: #333; }
        .control-btn.camera.off { background: var(--danger); color: white; }
        
        .control-btn.hand { background: rgba(255,255,255,0.9); color: #333; }
        .control-btn.hand.active { background: var(--warning); color: #333; }
        
        .control-btn.end-call { width: 72px; background: var(--danger); color: white; }
        
        .control-btn.chat, .control-btn.people, .control-btn.reactions {
            background: rgba(255,255,255,0.9); color: #333;
        }
        
        .control-btn .tooltip {
            position: absolute; bottom: 100%; left: 50%; transform: translateX(-50%);
            padding: 6px 12px; background: rgba(0,0,0,0.8);
            border-radius: 6px; font-size: 12px; white-space: nowrap;
            opacity: 0; visibility: hidden; transition: all 0.2s; margin-bottom: 8px;
        }
        
        .control-btn:hover .tooltip { opacity: 1; visibility: visible; }
        
        .control-btn .badge-count {
            position: absolute; top: -4px; right: -4px;
            min-width: 18px; height: 18px; background: var(--danger);
            color: white; font-size: 11px; border-radius: 9px;
            display: flex; align-items: center; justify-content: center; padding: 0 4px;
        }
        
        .side-panel {
            position: fixed; top: 0; right: 0;
            width: 360px; max-width: 100%; height: 100%;
            background: rgba(30, 30, 46, 0.98); backdrop-filter: blur(20px);
            transform: translateX(100%); transition: transform 0.3s ease;
            z-index: 200; display: flex; flex-direction: column;
        }
        
        .side-panel.open { transform: translateX(0); }
        
        .side-panel-header {
            display: flex; align-items: center; justify-content: space-between;
            padding: 16px 20px; border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .side-panel-header h3 { font-size: 16px; font-weight: 500; }
        
        .side-panel-close {
            width: 36px; height: 36px; border-radius: 50%; border: none;
            background: rgba(255,255,255,0.1); color: white; cursor: pointer;
            display: flex; align-items: center; justify-content: center;
        }
        
        .side-panel-content { flex: 1; overflow-y: auto; padding: 16px; }
        
        .participant-item {
            display: flex; align-items: center; gap: 12px; padding: 12px;
            border-radius: 12px; margin-bottom: 8px; background: rgba(255,255,255,0.05);
        }
        
        .participant-item .avatar-sm {
            width: 40px; height: 40px; border-radius: 50%;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            display: flex; align-items: center; justify-content: center;
            font-size: 14px; color: #333;
        }
        
        .participant-item .info { flex: 1; }
        .participant-item .name { font-size: 14px; font-weight: 500; }
        .participant-item .role { font-size: 12px; color: var(--text-muted); }
        .participant-item .icons { display: flex; gap: 8px; }
        .participant-item .icons i { font-size: 14px; color: var(--text-muted); }
        
        .chat-message {
            padding: 12px; border-radius: 12px; margin-bottom: 8px;
            background: rgba(255,255,255,0.05);
        }
        
        .chat-message.own { background: rgba(251, 188, 5, 0.2); }
        .chat-message.system {
            background: rgba(243, 156, 18, 0.1); font-style: italic;
            font-size: 12px; text-align: center;
        }
        
        .chat-message .sender { font-size: 12px; font-weight: 600; margin-bottom: 4px; }
        .chat-message .text { font-size: 14px; line-height: 1.4; }
        .chat-message .time { font-size: 10px; color: var(--text-muted); margin-top: 4px; }
        
        .chat-input-area {
            padding: 16px; border-top: 1px solid rgba(255,255,255,0.1);
            display: flex; gap: 8px;
        }
        
        .chat-input-area input {
            flex: 1; padding: 12px 16px; border: none; border-radius: 24px;
            background: rgba(255,255,255,0.1); color: white; font-size: 14px;
        }
        
        .chat-input-area input::placeholder { color: var(--text-muted); }
        
        .chat-input-area button {
            width: 44px; height: 44px; border-radius: 50%; border: none;
            background: var(--primary); color: #333; cursor: pointer;
            display: flex; align-items: center; justify-content: center;
        }
        
        .reactions-bar {
            position: absolute; bottom: 90px; left: 50%; transform: translateX(-50%);
            display: flex; gap: 8px; padding: 8px 16px;
            background: rgba(0,0,0,0.7); border-radius: 30px;
            opacity: 0; visibility: hidden; transition: all 0.3s;
        }
        
        .reactions-bar.show { opacity: 1; visibility: visible; }
        
        .reactions-bar button {
            width: 40px; height: 40px; border-radius: 50%; border: none;
            background: transparent; font-size: 24px; cursor: pointer;
            transition: transform 0.2s;
        }
        
        .reactions-bar button:hover { transform: scale(1.3); }
        
        .floating-reaction {
            position: absolute; font-size: 48px;
            animation: floatUp 2s ease-out forwards;
            pointer-events: none; z-index: 100;
        }
        
        @keyframes floatUp {
            0% { opacity: 1; transform: translateY(0) scale(1); }
            100% { opacity: 0; transform: translateY(-150px) scale(1.5); }
        }
        
        .waiting-screen {
            display: flex; flex-direction: column; align-items: center;
            justify-content: center; height: 100%; text-align: center; padding: 20px;
        }
        
        .waiting-screen .room-icon {
            width: 120px; height: 120px; border-radius: 50%;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            display: flex; align-items: center; justify-content: center;
            margin-bottom: 24px; animation: pulse 2s infinite;
        }
        
        @keyframes pulse { 0%, 100% { transform: scale(1); } 50% { transform: scale(1.05); } }
        .waiting-screen .room-icon i { font-size: 48px; color: #333; }
        .waiting-screen h2 { font-size: 24px; margin-bottom: 8px; }
        .waiting-screen p { color: var(--text-muted); margin-bottom: 24px; }
        
        .room-code-display {
            display: flex; flex-direction: column; align-items: center;
            gap: 12px; padding: 20px;
            background: rgba(255,255,255,0.1); border-radius: 16px; margin-bottom: 24px;
        }
        
        .room-code-display .code {
            font-size: 36px; font-weight: 600;
            letter-spacing: 4px; color: var(--primary);
        }
        
        .room-code-display input {
            padding: 12px 24px; border: none; border-radius: 24px;
            background: rgba(255,255,255,0.1); color: white;
            font-size: 18px; text-align: center; width: 250px;
        }
        
        .room-code-display input::placeholder { color: var(--text-muted); }
        
        .join-btn {
            padding: 14px 32px; border: none; border-radius: 24px;
            background: var(--primary); color: #333; font-size: 16px; font-weight: 500;
            cursor: pointer; transition: all 0.2s;
        }
        
        .join-btn:hover { transform: scale(1.05); }
        
        .floating-back {
            position: absolute; top: 12px; left: 12px;
            padding: 10px 16px; border-radius: 24px; border: none;
            background: rgba(0,0,0,0.5); color: white; cursor: pointer;
            font-size: 13px; display: flex; align-items: center; gap: 8px; z-index: 50;
        }
        
        .overlay {
            position: fixed; top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.5); z-index: 150;
            opacity: 0; visibility: hidden; transition: all 0.3s;
        }
        
        .overlay.show { opacity: 1; visibility: visible; }
        
        @media (max-width: 768px) {
            .meet-header { padding: 8px 12px; }
            .meet-title { font-size: 14px; }
            .video-grid { padding: 8px; gap: 6px; }
            .video-grid.count-2, .video-grid.count-3, .video-grid.count-4,
            .video-grid.count-5, .video-grid.count-6 { grid-template-columns: repeat(2, 1fr); }
            .video-tile { border-radius: 12px; max-width: 100%; min-width: 140px; aspect-ratio: 4/3; }
            .video-tile .avatar { width: 50px; height: 50px; font-size: 20px; }
            .video-tile .name-tag { font-size: 11px; padding: 4px 8px; }
            .meet-controls { gap: 8px; padding: 12px; }
            .control-btn { width: 48px; height: 48px; font-size: 18px; }
            .control-btn.end-call { width: 64px; }
            .control-btn .tooltip { display: none; }
            .side-panel { width: 100%; }
            .waiting-screen .room-icon { width: 80px; height: 80px; }
            .waiting-screen .room-icon i { font-size: 32px; }
            .waiting-screen h2 { font-size: 20px; }
            .room-code-display .code { font-size: 28px; }
        }
        
        @media (max-width: 480px) {
            .video-grid.count-3, .video-grid.count-4,
            .video-grid.count-5, .video-grid.count-6 { grid-template-columns: repeat(2, 1fr); }
            
            .video-scroll-btn {
                width: 40px;
                height: 40px;
                font-size: 16px;
            }
            
            .video-scroll-btn.left {
                left: 8px;
            }
            
            .video-scroll-btn.right {
                right: 8px;
            }
        }
    </style>
</head>
<body>
    <div class="meet-app">
        <header class="meet-header">
            <div class="meet-logo">
                <div class="meet-logo-icon"><i class="fas fa-video"></i></div>
                <span class="meet-title"><?php echo htmlspecialchars($schoolName); ?></span>
            </div>
            <div class="meet-timer" id="meetingTimer" style="display: none;">
                <div class="dot"></div>
                <span id="timerDisplay">00:00</span>
            </div>
        </header>
        
        <main class="meet-main">
            <div class="video-container-wrapper" id="videoContainerWrapper">
                <div class="video-scroll-container" id="videoScrollContainer">
                    <div class="video-grid" id="videoGrid">
                        <div class="waiting-screen" id="waitingScreen">
                            <div class="room-icon"><i class="fas fa-user-friends"></i></div>
                            <h2>Join Meeting</h2>
                            <p>Enter the code from your school</p>
                            
                            <?php if ($roomName): ?>
                            <div class="room-code-display">
                                <span class="code"><?php echo strtoupper($roomName); ?></span>
                                <button class="join-btn" onclick="startConference()">
                                    <i class="fas fa-video"></i> Join Now
                                </button>
                            </div>
                            <?php else: ?>
                            <div class="room-code-display">
                                <input type="text" id="roomCodeInput" placeholder="Enter meeting code">
                                <button class="join-btn" onclick="joinRoom()">
                                    <i class="fas fa-sign-in-alt"></i> Join
                                </button>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <button class="video-scroll-btn left" id="scrollLeft" onclick="scrollVideoGrid('left')">
                    <i class="fas fa-chevron-left"></i>
                </button>
                <button class="video-scroll-btn right" id="scrollRight" onclick="scrollVideoGrid('right')">
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>
            
            <button class="floating-back" onclick="goBack()">
                <i class="fas fa-arrow-left"></i> Leave
            </button>
        </main>
        
        <footer class="meet-controls" id="meetControls" style="display: none;">
            <button class="control-btn mic" id="micBtn" onclick="toggleMic()">
                <i class="fas fa-microphone"></i>
                <span class="tooltip">Mute</span>
            </button>
            
            <button class="control-btn camera" id="cameraBtn" onclick="toggleCamera()">
                <i class="fas fa-video"></i>
                <span class="tooltip">Turn off camera</span>
            </button>
            
            <button class="control-btn hand" id="handBtn" onclick="toggleHand()">
                <i class="fas fa-hand-paper"></i>
                <span class="tooltip">Raise hand</span>
            </button>
            
            <button class="control-btn end-call" onclick="endCall()">
                <i class="fas fa-phone-slash"></i>
                <span class="tooltip">Leave</span>
            </button>
            
            <button class="control-btn chat" onclick="toggleChat()">
                <i class="fas fa-comment-dots"></i>
                <span class="tooltip">Chat</span>
                <span class="badge-count" id="chatBadge" style="display: none;">0</span>
            </button>
            
            <button class="control-btn people" onclick="togglePeople()">
                <i class="fas fa-users"></i>
                <span class="tooltip">People</span>
            </button>
            
            <button class="control-btn reactions" onclick="toggleReactions()">
                <i class="fas fa-smile"></i>
                <span class="tooltip">Reactions</span>
            </button>
        </footer>
        
        <div class="reactions-bar" id="reactionsBar">
            <button onclick="sendReaction('👍')">👍</button>
            <button onclick="sendReaction('👏')">👏</button>
            <button onclick="sendReaction('😂')">😂</button>
            <button onclick="sendReaction('❤️')">❤️</button>
            <button onclick="sendReaction('😮')">😮</button>
            <button onclick="sendReaction('🎉')">🎉</button>
            <button onclick="sendReaction('🙋')">🙋</button>
            <button onclick="sendReaction('🙏')">🙏</button>
        </div>
    </div>
    
    <div class="overlay" id="overlay" onclick="closeAllPanels()"></div>
    
    <div class="side-panel" id="chatPanel">
        <div class="side-panel-header">
            <h3>Chat</h3>
            <button class="side-panel-close" onclick="toggleChat()"><i class="fas fa-times"></i></button>
        </div>
        <div class="side-panel-content" id="chatMessages">
            <div class="chat-message system"><div class="text">Messages can be seen by everyone</div></div>
        </div>
        <div class="chat-input-area">
            <input type="text" id="chatInput" placeholder="Send a message...">
            <button onclick="sendChatMessage()"><i class="fas fa-paper-plane"></i></button>
        </div>
    </div>
    
    <div class="side-panel" id="peoplePanel">
        <div class="side-panel-header">
            <h3>People (<span id="peopleCount">0</span>)</h3>
            <button class="side-panel-close" onclick="togglePeople()"><i class="fas fa-times"></i></button>
        </div>
        <div class="side-panel-content" id="peopleList"></div>
    </div>
    
    <script>
        let localStream = null;
        let isMuted = false, isCameraOff = false, isHandRaised = false;
        let isInCall = false;
        let currentRoom = '<?php echo htmlspecialchars($roomName); ?>';
        let currentUserName = '<?php echo htmlspecialchars($userName); ?>';
        let currentUserRole = '<?php echo htmlspecialchars($userRole); ?>';
        
        let participants = [], chatMessages = [];
        let lastChatUpdate = 0, chatPollInterval = null;
        let unreadCount = 0, meetingStartTime = null, timerInterval = null;
        
        const roleColors = {'admin':'#ea4335','teacher':'#667eea','student':'#1ab26b','parent':'#fbbc05'};
        const roleIcons = {'admin':'crown','teacher':'chalkboard-teacher','student':'user-graduate','parent':'user-friends'};
        
        function startConference() {
            isInCall = true;
            meetingStartTime = Date.now();
            
            document.getElementById('waitingScreen').style.display = 'none';
            document.getElementById('meetControls').style.display = 'flex';
            document.getElementById('meetingTimer').style.display = 'flex';
            
            navigator.mediaDevices.getUserMedia({ video: true, audio: true })
                .then(stream => { localStream = stream; renderVideoGrid(); })
                .catch(err => { console.warn('Media error:', err.message); renderVideoGrid(); });
            
            simulateParticipants();
            startTimer();
            startLiveChat();
            notifyJoin();
            chatPollInterval = setInterval(pollChat, 2000);
        }
        
        function joinRoom() {
            const code = document.getElementById('roomCodeInput').value.trim().toUpperCase();
            if (code) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = '<input name="join_room" value="'+code+'">';
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        <?php if ($roomName): ?>
        window.addEventListener('load', function() { startConference(); });
        <?php endif; ?>
        
        function simulateParticipants() {
            participants = [];
            const names = ['Teacher', 'Teacher', 'Student', 'Student', 'Parent'];
            const roles = ['teacher', 'teacher', 'student', 'student', 'parent'];
            const count = Math.floor(Math.random() * 5) + 2;
            for (let i = 0; i < count; i++) {
                const idx = Math.floor(Math.random() * names.length);
                participants.push({
                    id: i + 1, name: names[idx] + ' ' + (i + 1), role: roles[idx],
                    isMuted: Math.random() > 0.6, isHost: false, isDummy: true, hasRaisedHand: false
                });
            }
        }
        
        function renderVideoGrid() {
            const grid = document.getElementById('videoGrid');
            const all = [
                { id: 0, name: 'You', role: currentUserRole, isMuted: isMuted, isHost: false, isDummy: false, isYou: true, stream: localStream },
                ...participants
            ];
            
            grid.className = `video-grid count-${all.length}`;
            
            let html = '';
            all.forEach(p => {
                const initials = p.name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
                const roleColor = roleColors[p.role] || '#667eea';
                
                html += `
                    <div class="video-tile ${p.isMuted ? '' : 'speaking'}" id="tile-${p.id}">
                        ${p.stream && !isCameraOff && p.isYou ? 
                            `<video id="video-${p.id}" autoplay playsinline muted></video>` :
                            `<div class="avatar" style="background: linear-gradient(135deg, ${roleColor}, ${roleColor}88); color: ${roleColor === '#fbbc05' ? '#333' : 'white'};">${initials}</div>`
                        }
                        <div class="name-tag">
                            <i class="fas fa-user"></i> ${escapeHtml(p.name)}
                            ${p.isYou ? '<span class="badge badge-you">YOU</span>' : ''}
                            ${p.isDummy && !p.isYou ? '<span class="badge badge-dummy">DEMO</span>' : ''}
                        </div>
                        <div class="mic-indicator ${p.isMuted ? 'muted' : ''}">
                            <i class="fas fa-microphone${p.isMuted ? '-slash' : ''}"></i>
                        </div>
                        ${p.hasRaisedHand ? '<div class="hand-indicator">🙋</div>' : ''}
                    </div>
                `;
            });
            
            grid.innerHTML = html;
            
            if (localStream && !isCameraOff) {
                const video = document.getElementById('video-0');
                if (video) video.srcObject = localStream;
            }
            
            updatePeopleList();
            updateScrollButtons();
        }
        
        function updateScrollButtons() {
            const scrollContainer = document.getElementById('videoScrollContainer');
            const scrollLeft = document.getElementById('scrollLeft');
            const scrollRight = document.getElementById('scrollRight');
            
            if (!scrollContainer || !scrollLeft || !scrollRight) return;
            
            const hasOverflow = scrollContainer.scrollWidth > scrollContainer.clientWidth;
            
            if (hasOverflow) {
                scrollLeft.classList.add('show');
                scrollRight.classList.add('show');
            } else {
                scrollLeft.classList.remove('show');
                scrollRight.classList.remove('show');
            }
            
            scrollLeft.style.opacity = scrollContainer.scrollLeft > 10 ? '1' : '0.3';
            scrollRight.style.opacity = (scrollContainer.scrollWidth - scrollContainer.scrollLeft - scrollContainer.clientWidth) > 10 ? '1' : '0.3';
        }
        
        function scrollVideoGrid(direction) {
            const scrollContainer = document.getElementById('videoScrollContainer');
            if (!scrollContainer) return;
            
            const scrollAmount = 300;
            if (direction === 'left') {
                scrollContainer.scrollBy({ left: -scrollAmount, behavior: 'smooth' });
            } else {
                scrollContainer.scrollBy({ left: scrollAmount, behavior: 'smooth' });
            }
            
            setTimeout(updateScrollButtons, 300);
        }
        
        function updatePeopleList() {
            const list = document.getElementById('peopleList');
            const all = [{ name: currentUserName, role: currentUserRole, isYou: true }, ...participants];
            document.getElementById('peopleCount').textContent = all.length;
            
            list.innerHTML = all.map(p => {
                const roleColor = roleColors[p.role] || '#667eea';
                const icon = roleIcons[p.role] || 'user';
                return `
                    <div class="participant-item">
                        <div class="avatar-sm" style="background: linear-gradient(135deg, ${roleColor}, ${roleColor}88); color: ${roleColor === '#fbbc05' ? '#333' : 'white'};">
                            <i class="fas fa-${icon}"></i>
                        </div>
                        <div class="info">
                            <div class="name">${escapeHtml(p.name)} ${p.isYou ? '(You)' : ''}</div>
                            <div class="role">${ucfirst(p.role)}</div>
                        </div>
                        <div class="icons">
                            ${p.hasRaisedHand ? '<i class="fas fa-hand-paper" style="color: #fbbc05;"></i>' : ''}
                        </div>
                    </div>
                `;
            }).join('');
        }
        
        function toggleMic() {
            isMuted = !isMuted;
            if (localStream) localStream.getAudioTracks().forEach(t => t.enabled = !isMuted);
            const btn = document.getElementById('micBtn');
            btn.classList.toggle('muted', isMuted);
            btn.querySelector('i').className = `fas fa-microphone${isMuted ? '-slash' : ''}`;
            btn.querySelector('.tooltip').textContent = isMuted ? 'Unmute' : 'Mute';
            renderVideoGrid();
        }
        
        function toggleCamera() {
            isCameraOff = !isCameraOff;
            if (localStream) localStream.getVideoTracks().forEach(t => t.enabled = !isCameraOff);
            const btn = document.getElementById('cameraBtn');
            btn.classList.toggle('off', isCameraOff);
            btn.querySelector('i').className = `fas fa-video${isCameraOff ? '-slash' : ''}`;
            btn.querySelector('.tooltip').textContent = isCameraOff ? 'Turn on camera' : 'Turn off camera';
            renderVideoGrid();
        }
        
        function toggleHand() {
            isHandRaised = !isHandRaised;
            document.getElementById('handBtn').classList.toggle('active', isHandRaised);
            if (isHandRaised) showFloatingReaction('🙋', currentUserName);
            renderVideoGrid();
        }
        
        function toggleReactions() { document.getElementById('reactionsBar').classList.toggle('show'); }
        
        function sendReaction(emoji) {
            showFloatingReaction(emoji, currentUserName);
            document.getElementById('reactionsBar').classList.remove('show');
            fetch(`../admin/chat_api.php?action=reaction&emoji=${encodeURIComponent(emoji)}&sender=${encodeURIComponent(currentUserName)}`).catch(() => {});
        }
        
        function showFloatingReaction(emoji, from) {
            const main = document.querySelector('.meet-main');
            const r = document.createElement('div');
            r.className = 'floating-reaction';
            r.textContent = emoji;
            r.style.left = Math.random() * 60 + 20 + '%';
            r.style.bottom = '30%';
            main.appendChild(r);
            setTimeout(() => r.remove(), 2000);
        }
        
        function toggleChat() {
            document.getElementById('chatPanel').classList.toggle('open');
            document.getElementById('overlay').classList.toggle('show', document.getElementById('chatPanel').classList.contains('open'));
            if (document.getElementById('chatPanel').classList.contains('open')) {
                unreadCount = 0;
                document.getElementById('chatBadge').style.display = 'none';
                document.getElementById('peoplePanel').classList.remove('open');
            }
        }
        
        function togglePeople() {
            document.getElementById('peoplePanel').classList.toggle('open');
            document.getElementById('overlay').classList.toggle('show', document.getElementById('peoplePanel').classList.contains('open'));
            if (document.getElementById('peoplePanel').classList.contains('open')) {
                document.getElementById('chatPanel').classList.remove('open');
            }
        }
        
        function closeAllPanels() {
            document.getElementById('chatPanel').classList.remove('open');
            document.getElementById('peoplePanel').classList.remove('open');
            document.getElementById('overlay').classList.remove('show');
        }
        
        function sendChatMessage() {
            const input = document.getElementById('chatInput');
            const text = input.value.trim();
            if (text) {
                fetch(`../admin/chat_api.php?action=send&sender=${encodeURIComponent(currentUserName)}&message=${encodeURIComponent(text)}&role=${encodeURIComponent(currentUserRole)}`)
                    .then(r => r.json()).then(d => { if (d.success) input.value = ''; }).catch(() => {});
            }
        }
        
        function endCall() { if (confirm('Leave the meeting?')) leaveMeeting(); }
        
        function goBack() { isInCall ? endCall() : window.location.href = 'index.php'; }
        
        function leaveMeeting() {
            if (localStream) localStream.getTracks().forEach(t => t.stop());
            if (chatPollInterval) clearInterval(chatPollInterval);
            if (timerInterval) clearInterval(timerInterval);
            notifyLeave();
            window.location.href = 'index.php';
        }
        
        function startTimer() {
            timerInterval = setInterval(() => {
                const e = Math.floor((Date.now() - meetingStartTime) / 1000);
                document.getElementById('timerDisplay').textContent = `${Math.floor(e/60).toString().padStart(2,'0')}:${(e%60).toString().padStart(2,'0')}`;
            }, 1000);
        }
        
        function startLiveChat() { lastChatUpdate = Math.floor(Date.now() / 1000) - 10; pollChat(); }
        
        function pollChat() {
            fetch(`../admin/chat_api.php?action=get&since=${lastChatUpdate}`)
                .then(r => r.json())
                .then(data => {
                    if (data.messages) data.messages.forEach(msg => {
                        if (!chatMessages.find(m => m.id === msg.id)) {
                            chatMessages.push({ id: msg.id, sender: msg.sender, text: msg.text, time: new Date(msg.timestamp * 1000).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }), role: msg.role, isSystem: msg.isSystem });
                            if (msg.sender !== currentUserName && !document.getElementById('chatPanel').classList.contains('open')) {
                                unreadCount++;
                                const b = document.getElementById('chatBadge');
                                b.textContent = unreadCount > 99 ? '99+' : unreadCount;
                                b.style.display = 'flex';
                            }
                        }
                    });
                    renderChat();
                    if (data.reactions) data.reactions.forEach(rxn => showFloatingReaction(rxn.emoji, rxn.sender));
                    if (data.last_update > lastChatUpdate) lastChatUpdate = data.last_update;
                }).catch(() => {});
        }
        
        function renderChat() {
            const c = document.getElementById('chatMessages');
            c.innerHTML = chatMessages.map(msg => {
                const isOwn = msg.sender === currentUserName;
                const roleColor = roleColors[msg.role] || '#667eea';
                if (msg.isSystem) return `<div class="chat-message system"><div class="text">${escapeHtml(msg.text)}</div></div>`;
                return `<div class="chat-message ${isOwn ? 'own' : ''}"><div class="sender" style="color: ${roleColor};">${escapeHtml(msg.sender)}</div><div class="text">${escapeHtml(msg.text)}</div><div class="time">${msg.time}</div></div>`;
            }).join('');
            c.scrollTop = c.scrollHeight;
        }
        
        function notifyJoin() { fetch(`../admin/chat_api.php?action=join&sender=${encodeURIComponent(currentUserName)}&role=${encodeURIComponent(currentUserRole)}`).catch(() => {}); }
        function notifyLeave() { fetch(`../admin/chat_api.php?action=leave&sender=${encodeURIComponent(currentUserName)}&role=${encodeURIComponent(currentUserRole)}`).catch(() => {}); }
        function ucfirst(s) { return s.charAt(0).toUpperCase() + s.slice(1); }
        function escapeHtml(t) { const d = document.createElement('div'); d.textContent = t; return d.innerHTML; }
        
        document.getElementById('chatInput')?.addEventListener('keypress', e => { if (e.key === 'Enter') sendChatMessage(); });
        window.addEventListener('beforeunload', () => { if (isInCall) notifyLeave(); });
    </script>
</body>
</html>
