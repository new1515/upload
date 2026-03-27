<?php
require_once 'config/database.php';

$meetingId = isset($_GET['id']) ? sanitize($_GET['id']) : '';
$userName = isset($_GET['name']) ? sanitize($_GET['name']) : 'Guest';
$userRole = isset($_GET['role']) ? sanitize($_GET['role']) : 'guest';

$settings = [];
$stmt = $pdo->query("SELECT * FROM school_settings");
while ($row = $stmt->fetch()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
$schoolName = $settings['school_name'] ?? 'School Management System';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Video Conference - <?php echo htmlspecialchars($schoolName); ?></title>
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
            padding: 12px 20px; background: rgba(0,0,0,0.3); backdrop-filter: blur(10px); z-index: 100;
        }
        
        .meet-logo { display: flex; align-items: center; gap: 12px; }
        
        .meet-logo-icon {
            width: 40px; height: 40px; border-radius: 50%;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            display: flex; align-items: center; justify-content: center;
        }
        
        .meet-logo-icon i { font-size: 20px; color: white; }
        
        .meet-title { font-size: 16px; font-weight: 500; }
        
        .meet-timer {
            display: flex; align-items: center; gap: 8px; padding: 6px 12px;
            background: rgba(255,255,255,0.1); border-radius: 20px; font-size: 13px;
        }
        
        .meet-timer .dot {
            width: 8px; height: 8px; background: var(--danger);
            border-radius: 50%; animation: blink 1s infinite;
        }
        
        .meet-timer.recording .dot { background: var(--danger); animation: blink 0.5s infinite; }
        
        .recording-indicator {
            display: flex; align-items: center; gap: 6px; padding: 4px 12px;
            background: rgba(234,67,53,0.2); border: 1px solid var(--danger);
            border-radius: 20px; font-size: 12px; color: var(--danger);
        }
        
        .recording-indicator .rec-dot {
            width: 8px; height: 8px; background: var(--danger);
            border-radius: 50%; animation: blink 0.5s infinite;
        }
        
        @keyframes blink { 0%, 100% { opacity: 1; } 50% { opacity: 0.3; } }
        
        .meet-main { flex: 1; position: relative; overflow: hidden; display: flex; align-items: center; justify-content: center; }
        
        .video-grid {
            display: grid; gap: 8px; padding: 16px; width: 100%; height: 100%;
            align-content: center; justify-items: center;
        }
        
        .video-grid.count-1 { grid-template-columns: 1fr; max-width: 640px; justify-self: center; }
        .video-grid.count-2 { grid-template-columns: repeat(2, 1fr); max-width: 700px; margin: 0 auto; }
        .video-grid.count-3, .video-grid.count-4 { grid-template-columns: repeat(2, 1fr); max-width: 900px; margin: 0 auto; }
        .video-grid.count-5, .video-grid.count-6 { grid-template-columns: repeat(3, 1fr); max-width: 1000px; margin: 0 auto; }
        
        .video-tile {
            position: relative; background: var(--bg-card); border-radius: 16px;
            overflow: hidden; aspect-ratio: 16/9; width: 100%; max-width: 320px; min-width: 180px;
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
        }
        
        .video-tile.speaking { box-shadow: 0 0 0 3px var(--success), 0 8px 30px rgba(26,178,107,0.3); }
        
.video-tile video { width: 100%; height: 100%; object-fit: cover; border-radius: 16px; }
        
        .video-tile video.local-video { transform: scaleX(-1); }
        
        .video-tile .avatar {
            width: 60px; height: 60px; border-radius: 50%;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            display: flex; align-items: center; justify-content: center; font-size: 24px; color: white;
        }
        
        .video-tile .name-tag {
            position: absolute; bottom: 12px; left: 12px; display: flex; align-items: center;
            gap: 8px; padding: 6px 12px; background: rgba(0,0,0,0.7); border-radius: 8px; font-size: 13px;
        }
        
        .video-tile .name-tag .badge {
            padding: 2px 8px; border-radius: 4px; font-size: 10px; font-weight: 600;
        }
        
        .badge-you { background: var(--primary); }
        .badge-host { background: var(--danger); }
        .badge-rec { background: var(--danger); }
        
        .video-tile .mic-indicator {
            position: absolute; top: 12px; right: 12px; width: 32px; height: 32px;
            border-radius: 50%; background: rgba(0,0,0,0.6); display: flex; align-items: center; justify-content: center;
        }
        
        .video-tile .mic-indicator.muted { background: var(--danger); }
        
        .video-tile .hand-indicator {
            position: absolute; top: 12px; left: 12px; font-size: 24px;
            animation: wave 0.5s infinite alternate;
        }
        
        @keyframes wave { from { transform: rotate(-10deg); } to { transform: rotate(10deg); } }
        
        .meet-controls {
            display: flex; align-items: center; justify-content: center; gap: 12px;
            padding: 20px; background: rgba(0,0,0,0.5); backdrop-filter: blur(10px);
        }
        
        .control-btn {
            width: 56px; height: 56px; border-radius: 50%; border: none; cursor: pointer;
            display: flex; align-items: center; justify-content: center; font-size: 20px;
            transition: all 0.2s; position: relative;
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
        
        .control-btn.chat { background: rgba(255,255,255,0.9); color: #333; }
        
        .control-btn.people { background: rgba(255,255,255,0.9); color: #333; }
        
        .control-btn.whiteboard { background: rgba(255,255,255,0.9); color: #333; }
        .control-btn.whiteboard.active { background: var(--success); color: white; }
        
        .control-btn .tooltip {
            position: absolute; bottom: 100%; left: 50%; transform: translateX(-50%);
            padding: 6px 12px; background: rgba(0,0,0,0.8); border-radius: 6px;
            font-size: 12px; white-space: nowrap; opacity: 0; visibility: hidden;
            transition: all 0.2s; margin-bottom: 8px;
        }
        
        .control-btn:hover .tooltip { opacity: 1; visibility: visible; }
        
        .control-btn .badge-count {
            position: absolute; top: -4px; right: -4px; min-width: 18px; height: 18px;
            background: var(--danger); color: white; font-size: 11px; border-radius: 9px;
            display: flex; align-items: center; justify-content: center; padding: 0 4px;
        }
        
        .side-panel {
            position: fixed; top: 0; right: 0; width: 360px; max-width: 100%; height: 100%;
            background: rgba(30, 30, 46, 0.98); backdrop-filter: blur(20px);
            transform: translateX(100%); transition: transform 0.3s ease; z-index: 200;
            display: flex; flex-direction: column;
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
            display: flex; align-items: center; justify-content: center; font-size: 14px; color: white;
        }
        
        .participant-item .info { flex: 1; }
        .participant-item .name { font-size: 14px; font-weight: 500; }
        .participant-item .role { font-size: 12px; color: var(--text-muted); }
        
        .chat-message {
            padding: 12px; border-radius: 12px; margin-bottom: 8px;
            background: rgba(255,255,255,0.05);
        }
        
        .chat-message.own { background: rgba(102, 126, 234, 0.2); }
        .chat-message.system { background: rgba(243,156,18,0.1); font-style: italic; font-size: 12px; text-align: center; }
        
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
            background: var(--primary); color: white; cursor: pointer;
            display: flex; align-items: center; justify-content: center;
        }
        
        .reactions-bar {
            position: absolute; bottom: 90px; left: 50%; transform: translateX(-50%);
            display: flex; gap: 8px; padding: 8px 16px; background: rgba(0,0,0,0.7);
            border-radius: 30px; opacity: 0; visibility: hidden; transition: all 0.3s;
        }
        
        .reactions-bar.show { opacity: 1; visibility: visible; }
        
        .reactions-bar button {
            width: 40px; height: 40px; border-radius: 50%; border: none;
            background: transparent; font-size: 24px; cursor: pointer; transition: transform 0.2s;
        }
        
        .reactions-bar button:hover { transform: scale(1.3); }
        
        .floating-reaction {
            position: absolute; font-size: 48px; animation: floatUp 2s ease-out forwards;
            pointer-events: none; z-index: 100;
        }
        
        @keyframes floatUp {
            0% { opacity: 1; transform: translateY(0) scale(1); }
            100% { opacity: 0; transform: translateY(-150px) scale(1.5); }
        }
        
        .whiteboard-modal {
            position: fixed; top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.85); z-index: 300;
            display: none; flex-direction: column;
        }
        
        .whiteboard-modal.open { display: flex; }
        
        .whiteboard-header {
            display: flex; align-items: center; justify-content: space-between;
            padding: 12px 20px; background: rgba(0,0,0,0.5); backdrop-filter: blur(10px);
        }
        
        .whiteboard-header h3 {
            display: flex; align-items: center; gap: 10px;
            font-size: 16px; font-weight: 500;
        }
        
        .whiteboard-header h3 i { color: var(--primary); }
        
        .whiteboard-tools {
            display: flex; align-items: center; gap: 8px;
        }
        
        .whiteboard-tool-btn {
            width: 40px; height: 40px; border-radius: 8px; border: none;
            background: rgba(255,255,255,0.1); color: white; cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            font-size: 16px; transition: all 0.2s;
        }
        
        .whiteboard-tool-btn:hover { background: rgba(255,255,255,0.2); }
        .whiteboard-tool-btn.active { background: var(--primary); }
        
        .whiteboard-tool-btn .tooltip {
            position: absolute; bottom: 100%; left: 50%; transform: translateX(-50%);
            padding: 4px 8px; background: rgba(0,0,0,0.8); border-radius: 4px;
            font-size: 11px; white-space: nowrap; opacity: 0; visibility: hidden;
            transition: all 0.2s; margin-bottom: 6px;
        }
        
        .whiteboard-tool-btn:hover .tooltip { opacity: 1; visibility: visible; }
        
        .color-picker {
            display: flex; gap: 4px; padding: 4px 8px;
            background: rgba(255,255,255,0.1); border-radius: 8px;
        }
        
        .color-swatch {
            width: 24px; height: 24px; border-radius: 50%; border: 2px solid transparent;
            cursor: pointer; transition: all 0.2s;
        }
        
        .color-swatch:hover { transform: scale(1.15); }
        .color-swatch.active { border-color: white; transform: scale(1.15); }
        
        .size-slider {
            width: 80px; height: 4px; -webkit-appearance: none;
            background: rgba(255,255,255,0.3); border-radius: 2px; outline: none;
        }
        
        .size-slider::-webkit-slider-thumb {
            -webkit-appearance: none; width: 16px; height: 16px;
            border-radius: 50%; background: white; cursor: pointer;
        }
        
        .whiteboard-close {
            width: 40px; height: 40px; border-radius: 50%; border: none;
            background: rgba(255,255,255,0.1); color: white; cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            font-size: 18px; transition: all 0.2s;
        }
        
        .whiteboard-close:hover { background: var(--danger); }
        
        .whiteboard-canvas-container {
            flex: 1; display: flex; align-items: center; justify-content: center;
            padding: 20px; background: #1a1a2e;
        }
        
        #whiteboardCanvas {
            background: white; border-radius: 12px; cursor: crosshair;
            box-shadow: 0 8px 40px rgba(0,0,0,0.5);
            max-width: 100%; max-height: 100%;
        }
        
        .overlay {
            position: fixed; top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.5); z-index: 150; opacity: 0; visibility: hidden; transition: all 0.3s;
        }
        
        .overlay.show { opacity: 1; visibility: visible; }
        
        .floating-back {
            position: absolute; top: 12px; left: 12px; padding: 10px 16px;
            border-radius: 24px; border: none; background: rgba(0,0,0,0.5); color: white;
            cursor: pointer; font-size: 13px; display: flex; align-items: center;
            gap: 8px; z-index: 50;
        }
        
        .local-preview {
            position: absolute; bottom: 100px; right: 20px; width: 180px; aspect-ratio: 16/9;
            background: var(--bg-card); border-radius: 12px; overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,0.4); z-index: 50;
        }
        
        .local-preview video { width: 100%; height: 100%; object-fit: cover; transform: scaleX(-1); }
        
        .local-preview .preview-label {
            position: absolute; bottom: 8px; left: 8px; padding: 4px 8px;
            background: rgba(0,0,0,0.6); border-radius: 4px; font-size: 11px;
        }
        
        .local-preview.speaking {
            box-shadow: 0 0 0 3px var(--success), 0 4px 20px rgba(26, 178, 107, 0.4);
        }
        
        .waiting-screen {
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            height: 100%; text-align: center; padding: 20px;
        }
        
        .waiting-screen .room-icon {
            width: 120px; height: 120px; border-radius: 50%;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            display: flex; align-items: center; justify-content: center; margin-bottom: 24px;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse { 0%, 100% { transform: scale(1); } 50% { transform: scale(1.05); } }
        
        .waiting-screen .room-icon i { font-size: 48px; color: white; }
        .waiting-screen h2 { font-size: 24px; margin-bottom: 8px; }
        .waiting-screen p { color: var(--text-muted); margin-bottom: 24px; }
        
        .room-code-display {
            display: flex; align-items: center; gap: 16px; padding: 16px 24px;
            background: rgba(255,255,255,0.1); border-radius: 16px; margin-bottom: 24px;
        }
        
        .room-code-display .code {
            font-size: 32px; font-weight: 600; letter-spacing: 4px; color: var(--primary);
        }
        
        .join-btn {
            padding: 14px 32px; border: none; border-radius: 24px; background: var(--primary);
            color: white; font-size: 16px; font-weight: 500; cursor: pointer; transition: all 0.2s;
        }
        
        .join-btn:hover { background: var(--primary-dark); transform: scale(1.05); }
        
        @media (max-width: 768px) {
            .meet-header { padding: 8px 12px; }
            .meet-title { font-size: 14px; }
            .video-grid { padding: 8px; gap: 6px; }
            .video-tile { border-radius: 12px; max-width: 100%; min-width: 140px; aspect-ratio: 4/3; }
            .video-tile .avatar { width: 50px; height: 50px; font-size: 20px; }
            .meet-controls { gap: 8px; padding: 12px; }
            .control-btn { width: 48px; height: 48px; font-size: 18px; }
            .control-btn.end-call { width: 64px; }
            .control-btn .tooltip { display: none; }
            .side-panel { width: 100%; }
        }
    </style>
</head>
<body>
    <div class="meet-app" id="meetApp">
        <header class="meet-header">
            <div class="meet-logo">
                <div class="meet-logo-icon"><i class="fas fa-video"></i></div>
                <span class="meet-title"><?php echo htmlspecialchars($schoolName); ?></span>
            </div>
            <div style="display: flex; align-items: center; gap: 12px;">
                <div class="recording-indicator" id="recordingIndicator" style="display: none;">
                    <div class="rec-dot"></div>
                    <span>REC</span>
                </div>
                <div class="meet-timer" id="meetingTimer">
                    <div class="dot"></div>
                    <span id="timerDisplay">00:00</span>
                </div>
            </div>
        </header>
        
        <main class="meet-main" id="meetMain">
            <div class="video-grid" id="videoGrid">
                <div class="waiting-screen" id="waitingScreen">
                    <div class="room-icon"><i class="fas fa-video"></i></div>
                    <h2><?php echo htmlspecialchars($meetingId ?: 'Ready to Join'); ?></h2>
                    <p>Connecting to the meeting...</p>
                    <button class="join-btn" onclick="startConference()">
                        <i class="fas fa-video"></i> Join Now
                    </button>
                </div>
            </div>
            
            <button class="floating-back" onclick="goBack()">
                <i class="fas fa-arrow-left"></i> Leave
            </button>
            
            <div class="local-preview" id="localPreview" style="display: none;">
                <video id="localPreviewVideo" autoplay playsinline muted></video>
                <span class="preview-label">You</span>
            </div>
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
            
            <button class="control-btn chat" id="chatBtn" onclick="toggleChat()">
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
            
            <button class="control-btn whiteboard" id="whiteboardBtn" onclick="toggleWhiteboard()">
                <i class="fas fa-pen-fancy"></i>
                <span class="tooltip">Whiteboard</span>
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
    
    <div class="overlay" id="overlay" onclick="closeAllPanels(); if(whiteboardOpen) toggleWhiteboard();"></div>
    
    <div class="side-panel" id="chatPanel">
        <div class="side-panel-header">
            <h3>Chat</h3>
            <button class="side-panel-close" onclick="toggleChat()"><i class="fas fa-times"></i></button>
        </div>
        <div class="side-panel-content" id="chatMessages">
            <div class="chat-message system"><div class="text">Messages can be seen by everyone in the meeting</div></div>
        </div>
        <div class="chat-input-area">
            <input type="text" id="chatInput" placeholder="Send a message to everyone...">
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
    
    <div class="whiteboard-modal" id="whiteboardModal">
        <div class="whiteboard-header">
            <h3><i class="fas fa-pen-fancy"></i> Whiteboard</h3>
            <div class="whiteboard-tools">
                <div class="color-picker">
                    <div class="color-swatch active" style="background: #000000;" data-color="#000000"></div>
                    <div class="color-swatch" style="background: #ea4335;" data-color="#ea4335"></div>
                    <div class="color-swatch" style="background: #667eea;" data-color="#667eea"></div>
                    <div class="color-swatch" style="background: #1ab26b;" data-color="#1ab26b"></div>
                    <div class="color-swatch" style="background: #fbbc05;" data-color="#fbbc05"></div>
                </div>
                <input type="range" class="size-slider" id="brushSize" min="1" max="20" value="3" title="Brush Size">
                <button class="whiteboard-tool-btn active" id="penTool" onclick="setTool('pen')">
                    <i class="fas fa-pen"></i>
                    <span class="tooltip">Pen</span>
                </button>
                <button class="whiteboard-tool-btn" id="eraserTool" onclick="setTool('eraser')">
                    <i class="fas fa-eraser"></i>
                    <span class="tooltip">Eraser</span>
                </button>
                <button class="whiteboard-tool-btn" id="clearBtn" onclick="clearWhiteboard()">
                    <i class="fas fa-trash"></i>
                    <span class="tooltip">Clear All</span>
                </button>
            </div>
            <button class="whiteboard-close" onclick="toggleWhiteboard()"><i class="fas fa-times"></i></button>
        </div>
        <div class="whiteboard-canvas-container">
            <canvas id="whiteboardCanvas" style="width: 100vw; height: calc(100vh - 64px);"></canvas>
        </div>
    </div>
    
    <script>
        let localStream = null;
        let isMuted = false;
        let isCameraOff = false;
        let isHandRaised = false;
        let isInCall = false;
        let currentRoom = '<?php echo htmlspecialchars($meetingId); ?>';
        let currentUserName = '<?php echo htmlspecialchars($userName); ?>';
        let currentUserRole = '<?php echo htmlspecialchars($userRole); ?>';
        
        let participants = [];
        let chatMessages = [];
        let lastChatUpdate = 0;
        let chatPollInterval = null;
        let unreadCount = 0;
        let meetingStartTime = null;
        let timerInterval = null;
        let audioContext = null;
        let analyser = null;
        let isSpeaking = false;
        
        let whiteboardOpen = false;
        let whiteboardCanvas = null;
        let whiteboardCtx = null;
        let isDrawing = false;
        let currentTool = 'pen';
        let currentColor = '#000000';
        let brushSize = 3;
        let lastX = 0;
        let lastY = 0;
        let currentStroke = [];
        let whiteboardPollInterval = null;
        let lastWhiteboardUpdate = 0;
        let isWhiteboardHost = false;
        
        const roleColors = {
            'admin': '#ea4335', 'superadmin': '#fbbc05', 'teacher': '#667eea',
            'student': '#1ab26b', 'parent': '#fbbc05', 'guest': '#9ca3af', 'user': '#9ca3af'
        };
        
        const roleIcons = {
            'admin': 'crown', 'superadmin': 'crown', 'teacher': 'chalkboard-teacher',
            'student': 'user-graduate', 'parent': 'user-friends', 'guest': 'user', 'user': 'user'
        };
        
        function startConference() {
            isInCall = true;
            meetingStartTime = Date.now();
            
            document.getElementById('waitingScreen').style.display = 'none';
            document.getElementById('meetControls').style.display = 'flex';
            
            navigator.mediaDevices.getUserMedia({ video: true, audio: true })
                .then(stream => {
                    localStream = stream;
                    setupAudioAnalyser(stream);
                    showLocalPreview();
                    renderVideoGrid();
                })
                .catch(err => {
                    console.warn('Media access error:', err.message);
                    alert('Camera/microphone access denied. Please allow access to join the meeting.');
                    renderVideoGrid();
                });
            
            simulateParticipants();
            startTimer();
            startLiveChat();
            notifyJoin();
            
            chatPollInterval = setInterval(pollChat, 2000);
            setInterval(checkMeetingStatus, 5000);
        }
        
        function setupAudioAnalyser(stream) {
            try {
                audioContext = new (window.AudioContext || window.webkitAudioContext)();
                analyser = audioContext.createAnalyser();
                const source = audioContext.createMediaStreamSource(stream);
                source.connect(analyser);
                analyser.fftSize = 256;
                
                const gainNode = audioContext.createGain();
                gainNode.gain.value = 0.3;
                source.connect(gainNode);
                gainNode.connect(audioContext.destination);
                
                detectSpeaking();
            } catch (e) {
                console.warn('Audio analyser not supported:', e);
            }
        }
        
        function detectSpeaking() {
            if (!analyser || isMuted) {
                setTimeout(detectSpeaking, 100);
                return;
            }
            
            const dataArray = new Uint8Array(analyser.frequencyBinCount);
            analyser.getByteFrequencyData(dataArray);
            
            const average = dataArray.reduce((a, b) => a + b) / dataArray.length;
            const wasSpeaking = isSpeaking;
            isSpeaking = average > 20 && !isMuted;
            
            if (isSpeaking !== wasSpeaking) {
                updateSpeakingIndicator();
            }
            
            setTimeout(detectSpeaking, 50);
        }
        
        function updateSpeakingIndicator() {
            const tile = document.getElementById('tile-0');
            const preview = document.getElementById('localPreview');
            
            if (tile) tile.classList.toggle('speaking', isSpeaking && !isMuted);
            if (preview) preview.classList.toggle('speaking', isSpeaking && !isMuted);
        }
        
        function showLocalPreview() {
            const preview = document.getElementById('localPreview');
            const video = document.getElementById('localPreviewVideo');
            if (preview && video && localStream) {
                preview.style.display = 'block';
                video.srcObject = localStream;
            }
        }
        
        function simulateParticipants() {
            participants = [];
            const names = ['Student', 'Parent', 'Teacher', 'Guest'];
            const roles = ['student', 'parent', 'teacher', 'user'];
            
            const count = Math.floor(Math.random() * 4) + 2;
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
            const allParticipants = [
                { id: 0, name: 'You', role: currentUserRole, isMuted: isMuted, isHost: false, isDummy: false, isYou: true, stream: localStream },
                ...participants
            ];
            
            const count = allParticipants.length;
            grid.className = `video-grid count-${count > 6 ? 6 : count}`;
            
            let html = '';
            allParticipants.forEach(p => {
                const initials = p.name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
                const roleColor = roleColors[p.role] || '#667eea';
                const showVideo = p.isYou && localStream && !isCameraOff;
                
                html += `
                    <div class="video-tile ${p.isMuted ? '' : 'speaking'}" id="tile-${p.id}">
                        ${showVideo ? 
                            `<video id="video-${p.id}" autoplay playsinline muted class="local-video"></video>` :
                            `<div class="avatar" style="background: linear-gradient(135deg, ${roleColor}, ${roleColor}88);">${initials}</div>`
                        }
                        <div class="name-tag">
                            ${escapeHtml(p.name)}
                            ${p.isYou ? '<span class="badge badge-you">YOU</span>' : ''}
                            ${p.isDummy ? '<span class="badge badge-host">DEMO</span>' : ''}
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
                if (video) {
                    video.srcObject = localStream;
                    video.play().catch(() => {});
                }
            }
            
            updatePeopleList();
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
                        <div class="avatar-sm" style="background: linear-gradient(135deg, ${roleColor}, ${roleColor}88);">
                            <i class="fas fa-${icon}"></i>
                        </div>
                        <div class="info">
                            <div class="name">${escapeHtml(p.name)} ${p.isYou ? '(You)' : ''}</div>
                            <div class="role">${ucfirst(p.role)}</div>
                        </div>
                    </div>
                `;
            }).join('');
        }
        
        function toggleMic() {
            isMuted = !isMuted;
            if (localStream) localStream.getAudioTracks().forEach(track => track.enabled = !isMuted);
            
            const btn = document.getElementById('micBtn');
            btn.classList.toggle('muted', isMuted);
            btn.querySelector('i').className = `fas fa-microphone${isMuted ? '-slash' : ''}`;
            btn.querySelector('.tooltip').textContent = isMuted ? 'Unmute' : 'Mute';
            
            updateSpeakingIndicator();
            renderVideoGrid();
        }
        
        function toggleCamera() {
            isCameraOff = !isCameraOff;
            if (localStream) localStream.getVideoTracks().forEach(track => track.enabled = !isCameraOff);
            
            const btn = document.getElementById('cameraBtn');
            btn.classList.toggle('off', isCameraOff);
            btn.querySelector('i').className = `fas fa-video${isCameraOff ? '-slash' : ''}`;
            btn.querySelector('.tooltip').textContent = isCameraOff ? 'Turn on camera' : 'Turn off camera';
            
            const preview = document.getElementById('localPreview');
            if (preview) preview.style.display = isCameraOff ? 'none' : 'block';
            
            renderVideoGrid();
        }
        
        function toggleHand() {
            isHandRaised = !isHandRaised;
            const btn = document.getElementById('handBtn');
            btn.classList.toggle('active', isHandRaised);
            if (isHandRaised) showFloatingReaction('🙋', currentUserName);
            renderVideoGrid();
        }
        
        function toggleReactions() { document.getElementById('reactionsBar').classList.toggle('show'); }
        
        function sendReaction(emoji) {
            showFloatingReaction(emoji, currentUserName);
            document.getElementById('reactionsBar').classList.remove('show');
            fetch(`meeting_api.php?action=reaction&emoji=${encodeURIComponent(emoji)}&sender=${encodeURIComponent(currentUserName)}`).catch(() => {});
        }
        
        function showFloatingReaction(emoji, from) {
            const main = document.getElementById('meetMain');
            const reaction = document.createElement('div');
            reaction.className = 'floating-reaction';
            reaction.textContent = emoji;
            reaction.style.left = Math.random() * 60 + 20 + '%';
            reaction.style.bottom = '30%';
            reaction.title = from;
            main.appendChild(reaction);
            setTimeout(() => reaction.remove(), 2000);
        }
        
        function toggleChat() {
            const panel = document.getElementById('chatPanel');
            const overlay = document.getElementById('overlay');
            panel.classList.toggle('open');
            overlay.classList.toggle('show', panel.classList.contains('open'));
            if (panel.classList.contains('open')) {
                unreadCount = 0;
                document.getElementById('chatBadge').style.display = 'none';
                document.getElementById('peoplePanel').classList.remove('open');
            }
        }
        
        function togglePeople() {
            const panel = document.getElementById('peoplePanel');
            const overlay = document.getElementById('overlay');
            panel.classList.toggle('open');
            overlay.classList.toggle('show', panel.classList.contains('open'));
            if (panel.classList.contains('open')) document.getElementById('chatPanel').classList.remove('open');
        }
        
        function closeAllPanels() {
            document.getElementById('chatPanel').classList.remove('open');
            document.getElementById('peoplePanel').classList.remove('open');
            document.getElementById('overlay').classList.remove('show');
            document.getElementById('reactionsBar').classList.remove('show');
        }
        
        function sendChatMessage() {
            const input = document.getElementById('chatInput');
            const text = input.value.trim();
            if (text) {
                fetch(`meeting_api.php?action=send&sender=${encodeURIComponent(currentUserName)}&message=${encodeURIComponent(text)}&role=${encodeURIComponent(currentUserRole)}`)
                    .then(r => r.json())
                    .then(data => { if (data.success) input.value = ''; })
                    .catch(() => {});
            }
        }
        
        function checkMeetingStatus() {
            if (!isInCall) return;
            fetch(`meeting_api.php?action=status&meeting_id=${currentRoom}`)
                .then(r => r.json())
                .then(data => {
                    if (data.status === 'ended') {
                        alert('This meeting has ended.');
                        leaveMeeting();
                    }
                    if (data.is_recording) {
                        document.getElementById('recordingIndicator').style.display = 'flex';
                        document.getElementById('meetingTimer').classList.add('recording');
                    }
                })
                .catch(() => {});
        }
        
        function endCall() {
            if (confirm('Leave the meeting?')) leaveMeeting();
        }
        
        function goBack() {
            if (isInCall) endCall();
            else window.location.href = 'meeting.php';
        }
        
        function leaveMeeting() {
            if (localStream) localStream.getTracks().forEach(track => track.stop());
            if (chatPollInterval) clearInterval(chatPollInterval);
            if (timerInterval) clearInterval(timerInterval);
            fetch(`meeting_api.php?action=leave`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `meeting_id=${currentRoom}&user_name=${encodeURIComponent(currentUserName)}`
            }).catch(() => {});
            window.location.href = 'meeting.php';
        }
        
        function startTimer() {
            timerInterval = setInterval(() => {
                const elapsed = Math.floor((Date.now() - meetingStartTime) / 1000);
                const mins = Math.floor(elapsed / 60).toString().padStart(2, '0');
                const secs = (elapsed % 60).toString().padStart(2, '0');
                document.getElementById('timerDisplay').textContent = `${mins}:${secs}`;
            }, 1000);
        }
        
        function startLiveChat() {
            lastChatUpdate = Math.floor(Date.now() / 1000) - 10;
            pollChat();
        }
        
        function pollChat() {
            fetch(`meeting_api.php?action=get&since=${lastChatUpdate}`)
                .then(r => r.json())
                .then(data => {
                    if (data.messages && data.messages.length > 0) {
                        data.messages.forEach(msg => {
                            const exists = chatMessages.find(m => m.id === msg.id);
                            if (!exists) {
                                chatMessages.push({
                                    id: msg.id, sender: msg.sender, text: msg.text,
                                    time: new Date(msg.timestamp * 1000).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
                                    role: msg.role, isSystem: msg.isSystem
                                });
                                if (msg.sender !== currentUserName && !document.getElementById('chatPanel').classList.contains('open')) {
                                    unreadCount++;
                                    const badge = document.getElementById('chatBadge');
                                    badge.textContent = unreadCount > 99 ? '99+' : unreadCount;
                                    badge.style.display = 'flex';
                                }
                            }
                        });
                        renderChat();
                    }
                    if (data.reactions && data.reactions.length > 0) {
                        data.reactions.forEach(rxn => showFloatingReaction(rxn.emoji, rxn.sender));
                    }
                    if (data.last_update > lastChatUpdate) lastChatUpdate = data.last_update;
                })
                .catch(() => {});
        }
        
        function toggleWhiteboard() {
            whiteboardOpen = !whiteboardOpen;
            const modal = document.getElementById('whiteboardModal');
            const btn = document.getElementById('whiteboardBtn');
            
            if (whiteboardOpen) {
                modal.classList.add('open');
                btn.classList.add('active');
                closeAllPanels();
                initWhiteboard();
                startWhiteboardPolling();
            } else {
                modal.classList.remove('open');
                btn.classList.remove('active');
                stopWhiteboardPolling();
            }
        }
        
        function initWhiteboard() {
            whiteboardCanvas = document.getElementById('whiteboardCanvas');
            whiteboardCtx = whiteboardCanvas.getContext('2d');
            whiteboardCtx.lineCap = 'round';
            whiteboardCtx.lineJoin = 'round';
            
            whiteboardCanvas.width = window.innerWidth;
            whiteboardCanvas.height = window.innerHeight - 64;
            
            isWhiteboardHost = ['admin', 'superadmin', 'teacher'].includes(currentUserRole);
            
            if (!isWhiteboardHost) {
                document.getElementById('clearBtn').style.display = 'none';
                document.querySelectorAll('.color-picker').forEach(el => el.style.display = 'none');
                document.getElementById('brushSize').style.display = 'none';
                document.getElementById('eraserTool').style.display = 'none';
            }
            
            loadWhiteboard();
            
            whiteboardCanvas.addEventListener('mousedown', startDrawing);
            whiteboardCanvas.addEventListener('mousemove', draw);
            whiteboardCanvas.addEventListener('mouseup', stopDrawing);
            whiteboardCanvas.addEventListener('mouseout', stopDrawing);
            
            whiteboardCanvas.addEventListener('touchstart', handleTouchStart, { passive: false });
            whiteboardCanvas.addEventListener('touchmove', handleTouchMove, { passive: false });
            whiteboardCanvas.addEventListener('touchend', stopDrawing);
            
            document.querySelectorAll('.color-swatch').forEach(swatch => {
                swatch.addEventListener('click', function() {
                    document.querySelectorAll('.color-swatch').forEach(s => s.classList.remove('active'));
                    this.classList.add('active');
                    currentColor = this.dataset.color;
                    setTool('pen');
                });
            });
            
            document.getElementById('brushSize').addEventListener('input', function() {
                brushSize = parseInt(this.value);
            });
        }
        
        function handleTouchStart(e) {
            e.preventDefault();
            const touch = e.touches[0];
            const rect = whiteboardCanvas.getBoundingClientRect();
            startDrawing({ offsetX: touch.clientX - rect.left, offsetY: touch.clientY - rect.top });
        }
        
        function handleTouchMove(e) {
            e.preventDefault();
            const touch = e.touches[0];
            const rect = whiteboardCanvas.getBoundingClientRect();
            draw({ offsetX: touch.clientX - rect.left, offsetY: touch.clientY - rect.top });
        }
        
        function startDrawing(e) {
            isDrawing = true;
            [lastX, lastY] = [e.offsetX, e.offsetY];
            currentStroke = [{ x: lastX, y: lastY }];
        }
        
        function draw(e) {
            if (!isDrawing) return;
            
            const rect = whiteboardCanvas.getBoundingClientRect();
            const scaleX = whiteboardCanvas.width / rect.width;
            const scaleY = whiteboardCanvas.height / rect.height;
            const x = e.offsetX * scaleX;
            const y = e.offsetY * scaleY;
            
            whiteboardCtx.strokeStyle = currentTool === 'eraser' ? '#ffffff' : currentColor;
            whiteboardCtx.lineWidth = currentTool === 'eraser' ? brushSize * 3 : brushSize;
            
            whiteboardCtx.beginPath();
            whiteboardCtx.moveTo(lastX, lastY);
            whiteboardCtx.lineTo(x, y);
            whiteboardCtx.stroke();
            
            currentStroke.push({ x, y });
            [lastX, lastY] = [x, y];
        }
        
        function stopDrawing() {
            if (isDrawing && currentStroke.length > 1 && isWhiteboardHost) {
                sendWhiteboardStroke({
                    points: currentStroke,
                    color: currentTool === 'eraser' ? '#ffffff' : currentColor,
                    size: currentTool === 'eraser' ? brushSize * 3 : brushSize
                });
            }
            isDrawing = false;
            currentStroke = [];
        }
        
        function setTool(tool) {
            currentTool = tool;
            document.getElementById('penTool').classList.toggle('active', tool === 'pen');
            document.getElementById('eraserTool').classList.toggle('active', tool === 'eraser');
        }
        
        function sendWhiteboardStroke(stroke) {
            fetch('admin/whiteboard_api.php?action=add_stroke&role=' + encodeURIComponent(currentUserRole), {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'stroke=' + encodeURIComponent(JSON.stringify(stroke))
            }).catch(() => {});
        }
        
        function clearWhiteboard() {
            if (!isWhiteboardHost) return;
            if (!confirm('Clear the entire whiteboard?')) return;
            
            whiteboardCtx.fillStyle = '#ffffff';
            whiteboardCtx.fillRect(0, 0, whiteboardCanvas.width, whiteboardCanvas.height);
            
            fetch('admin/whiteboard_api.php?action=clear&role=' + encodeURIComponent(currentUserRole))
                .catch(() => {});
        }
        
        function loadWhiteboard() {
            whiteboardCtx.fillStyle = '#ffffff';
            whiteboardCtx.fillRect(0, 0, whiteboardCanvas.width, whiteboardCanvas.height);
            
            fetch('admin/whiteboard_api.php?action=get_all')
                .then(r => r.json())
                .then(data => {
                    if (data.strokes) {
                        data.strokes.forEach(stroke => {
                            if (stroke.points && stroke.points.length > 1) {
                                drawStroke(stroke);
                            }
                        });
                    }
                    lastWhiteboardUpdate = data.last_update || 0;
                })
                .catch(() => {});
        }
        
        function drawStroke(stroke) {
            if (!stroke.points || stroke.points.length < 2) return;
            
            whiteboardCtx.strokeStyle = stroke.color || '#000000';
            whiteboardCtx.lineWidth = stroke.size || 3;
            whiteboardCtx.beginPath();
            whiteboardCtx.moveTo(stroke.points[0].x, stroke.points[0].y);
            
            for (let i = 1; i < stroke.points.length; i++) {
                whiteboardCtx.lineTo(stroke.points[i].x, stroke.points[i].y);
            }
            whiteboardCtx.stroke();
        }
        
        function pollWhiteboard() {
            fetch('admin/whiteboard_api.php?action=get&since=' + lastWhiteboardUpdate)
                .then(r => r.json())
                .then(data => {
                    if (data.strokes && data.strokes.length > 0) {
                        data.strokes.forEach(stroke => drawStroke(stroke));
                    }
                    if (data.last_update > lastWhiteboardUpdate) {
                        lastWhiteboardUpdate = data.last_update;
                    }
                })
                .catch(() => {});
        }
        
        function startWhiteboardPolling() {
            if (whiteboardPollInterval) return;
            whiteboardPollInterval = setInterval(pollWhiteboard, 500);
        }
        
        function stopWhiteboardPolling() {
            if (whiteboardPollInterval) {
                clearInterval(whiteboardPollInterval);
                whiteboardPollInterval = null;
            }
        }
        
        function renderChat() {
            const container = document.getElementById('chatMessages');
            container.innerHTML = chatMessages.map(msg => {
                const isOwn = msg.sender === currentUserName;
                const roleColor = roleColors[msg.role] || '#667eea';
                if (msg.isSystem) return `<div class="chat-message system"><div class="text">${escapeHtml(msg.text)}</div></div>`;
                return `<div class="chat-message ${isOwn ? 'own' : ''}"><div class="sender" style="color: ${roleColor};">${escapeHtml(msg.sender)}</div><div class="text">${escapeHtml(msg.text)}</div><div class="time">${msg.time}</div></div>`;
            }).join('');
            container.scrollTop = container.scrollHeight;
        }
        
        function notifyJoin() {
            fetch(`meeting_api.php?action=join`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `meeting_id=${currentRoom}&user_name=${encodeURIComponent(currentUserName)}&user_role=${encodeURIComponent(currentUserRole)}`
            }).catch(() => {});
        }
        
        function ucfirst(str) { return str.charAt(0).toUpperCase() + str.slice(1); }
        
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        document.getElementById('chatInput')?.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') sendChatMessage();
        });
        
        window.addEventListener('beforeunload', function() {
            if (isInCall) {
                fetch(`meeting_api.php?action=leave`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `meeting_id=${currentRoom}&user_name=${encodeURIComponent(currentUserName)}`
                });
            }
        });
        
        if ('<?php echo $meetingId; ?>') {
            startConference();
        }
    </script>
</body>
</html>
