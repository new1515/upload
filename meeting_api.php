<?php
require_once 'config/database.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS `meetings` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `meeting_id` VARCHAR(20) NOT NULL UNIQUE,
        `host_id` INT DEFAULT 0,
        `host_name` VARCHAR(100) NOT NULL,
        `host_role` VARCHAR(50) DEFAULT 'admin',
        `title` VARCHAR(255) NOT NULL,
        `scheduled_at` DATETIME NULL,
        `started_at` DATETIME NULL,
        `ended_at` DATETIME NULL,
        `status` ENUM('scheduled','live','ended') DEFAULT 'scheduled',
        `is_recording` TINYINT(1) DEFAULT 0,
        `recording_started_at` DATETIME NULL,
        `recording_stopped_at` DATETIME NULL,
        `participants` INT DEFAULT 0,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS `meeting_participants` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `meeting_id` VARCHAR(20) NOT NULL,
        `user_name` VARCHAR(100) NOT NULL,
        `user_role` VARCHAR(50) DEFAULT 'guest',
        `is_host` TINYINT(1) DEFAULT 0,
        `admitted` TINYINT(1) DEFAULT 1,
        `joined_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        `left_at` DATETIME NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch (PDOException $e) {}

function generateMeetingId() {
    $chars = 'abcdefghijklmnpqrstuvwxyz23456789';
    $id = '';
    for ($i = 0; $i < 10; $i++) {
        $id .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $id;
}

switch ($action) {
    case 'create':
        $hostId = isset($_POST['host_id']) ? (int)$_POST['host_id'] : 0;
        $hostName = sanitize($_POST['host_name'] ?? $_GET['host_name'] ?? '');
        $hostRole = sanitize($_POST['host_role'] ?? $_GET['host_role'] ?? 'admin');
        $title = sanitize($_POST['title'] ?? $_GET['title'] ?? 'Video Meeting');
        $scheduledAt = isset($_POST['scheduled_at']) ? sanitize($_POST['scheduled_at']) : null;
        
        if (!$hostName) {
            echo json_encode(['success' => false, 'error' => 'Host name required']);
            exit;
        }
        
        $meetingId = generateMeetingId();
        
        try {
            $stmt = $pdo->prepare("INSERT INTO meetings (meeting_id, host_id, host_name, host_role, title, scheduled_at, status, created_at) VALUES (?, ?, ?, ?, ?, ?, 'scheduled', NOW())");
            $stmt->execute([$meetingId, $hostId, $hostName, $hostRole, $title, $scheduledAt]);
        } catch (PDOException $e) {
            $meetingId = 'demo-' . generateMeetingId();
        }
        
        echo json_encode([
            'success' => true,
            'meeting_id' => $meetingId,
            'join_url' => "meeting.php?id=$meetingId"
        ]);
        break;
        
    case 'get':
        $meetingId = sanitize($_GET['meeting_id'] ?? '');
        
        if (!$meetingId) {
            echo json_encode(['success' => false, 'error' => 'Meeting ID required']);
            exit;
        }
        
        $stmt = $pdo->prepare("SELECT * FROM meetings WHERE meeting_id = ?");
        $stmt->execute([$meetingId]);
        $meeting = $stmt->fetch();
        
        if (!$meeting) {
            echo json_encode(['success' => false, 'error' => 'Meeting not found']);
            exit;
        }
        
        echo json_encode(['success' => true, 'meeting' => $meeting]);
        break;
        
    case 'join':
        $meetingId = sanitize($_POST['meeting_id'] ?? '');
        $userName = sanitize($_POST['user_name'] ?? '');
        $userRole = sanitize($_POST['user_role'] ?? 'guest');
        
        if (!$meetingId || !$userName) {
            echo json_encode(['success' => false, 'error' => 'Missing required fields']);
            exit;
        }
        
        $stmt = $pdo->prepare("UPDATE meetings SET participants = participants + 1 WHERE meeting_id = ?");
        $stmt->execute([$meetingId]);
        
        $stmt = $pdo->prepare("INSERT INTO meeting_participants (meeting_id, user_name, user_role, joined_at) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$meetingId, $userName, $userRole]);
        
        echo json_encode(['success' => true]);
        break;
        
    case 'leave':
        $meetingId = sanitize($_POST['meeting_id'] ?? '');
        $userName = sanitize($_POST['user_name'] ?? '');
        
        if ($meetingId && $userName) {
            $stmt = $pdo->prepare("UPDATE meetings SET participants = GREATEST(0, participants - 1) WHERE meeting_id = ?");
            $stmt->execute([$meetingId]);
        }
        
        echo json_encode(['success' => true]);
        break;
        
    case 'start':
        $meetingId = sanitize($_POST['meeting_id'] ?? '');
        
        $stmt = $pdo->prepare("UPDATE meetings SET status = 'live', started_at = NOW() WHERE meeting_id = ?");
        $stmt->execute([$meetingId]);
        
        echo json_encode(['success' => true]);
        break;
        
    case 'end':
        $meetingId = sanitize($_POST['meeting_id'] ?? '');
        
        $stmt = $pdo->prepare("UPDATE meetings SET status = 'ended', ended_at = NOW() WHERE meeting_id = ?");
        $stmt->execute([$meetingId]);
        
        echo json_encode(['success' => true]);
        break;
        
    case 'record':
        $meetingId = sanitize($_POST['meeting_id'] ?? '');
        $action = sanitize($_POST['record_action'] ?? '');
        
        if ($action === 'start') {
            $stmt = $pdo->prepare("UPDATE meetings SET is_recording = 1, recording_started_at = NOW() WHERE meeting_id = ?");
            $stmt->execute([$meetingId]);
        } elseif ($action === 'stop') {
            $stmt = $pdo->prepare("UPDATE meetings SET is_recording = 0, recording_stopped_at = NOW() WHERE meeting_id = ?");
            $stmt->execute([$meetingId]);
        }
        
        echo json_encode(['success' => true]);
        break;
        
    case 'status':
        $meetingId = sanitize($_GET['meeting_id'] ?? '');
        
        $stmt = $pdo->prepare("SELECT status, is_recording, participants FROM meetings WHERE meeting_id = ?");
        $stmt->execute([$meetingId]);
        $meeting = $stmt->fetch();
        
        echo json_encode([
            'success' => true,
            'status' => $meeting['status'] ?? 'not_found',
            'is_recording' => (bool)($meeting['is_recording'] ?? false),
            'participants' => (int)($meeting['participants'] ?? 0)
        ]);
        break;
        
    case 'admit':
        $participantId = isset($_POST['participant_id']) ? (int)$_POST['participant_id'] : 0;
        
        $stmt = $pdo->prepare("UPDATE meeting_participants SET admitted = 1 WHERE id = ?");
        $stmt->execute([$participantId]);
        
        echo json_encode(['success' => true]);
        break;
        
    case 'waiting_list':
        $meetingId = sanitize($_GET['meeting_id'] ?? '');
        
        $stmt = $pdo->prepare("SELECT * FROM meeting_participants WHERE meeting_id = ? AND admitted = 0 ORDER BY joined_at ASC");
        $stmt->execute([$meetingId]);
        $waiting = $stmt->fetchAll();
        
        echo json_encode(['success' => true, 'waiting' => $waiting]);
        break;
        
    case 'send':
        $sender = sanitize($_GET['sender'] ?? $_POST['sender'] ?? 'Guest');
        $message = sanitize($_GET['message'] ?? $_POST['message'] ?? '');
        $role = sanitize($_GET['role'] ?? $_POST['role'] ?? 'guest');
        $room = sanitize($_GET['room'] ?? $_POST['room'] ?? 'general');
        
        if (!$message) {
            echo json_encode(['success' => false, 'error' => 'Message required']);
            exit;
        }
        
        $msgId = time() . rand(100, 999);
        $timestamp = time();
        
        $chatFile = __DIR__ . '/admin/chat_data.json';
        $chatData = file_exists($chatFile) ? json_decode(file_get_contents($chatFile), true) : ['messages' => [], 'reactions' => [], 'last_update' => 0];
        
        $chatData['messages'][] = [
            'id' => $msgId,
            'sender' => $sender,
            'text' => $message,
            'role' => $role,
            'timestamp' => $timestamp,
            'isSystem' => false,
            'room' => $room
        ];
        $chatData['last_update'] = $timestamp;
        
        file_put_contents($chatFile, json_encode($chatData));
        
        echo json_encode(['success' => true, 'id' => $msgId]);
        break;
        
    case 'reaction':
        $emoji = sanitize($_GET['emoji'] ?? $_POST['emoji'] ?? '');
        $sender = sanitize($_GET['sender'] ?? $_POST['sender'] ?? 'Guest');
        $room = sanitize($_GET['room'] ?? $_POST['room'] ?? 'general');
        
        $chatFile = __DIR__ . '/admin/chat_data.json';
        $chatData = file_exists($chatFile) ? json_decode(file_get_contents($chatFile), true) : ['messages' => [], 'reactions' => [], 'last_update' => 0];
        
        $chatData['reactions'][] = [
            'emoji' => $emoji,
            'sender' => $sender,
            'timestamp' => time(),
            'room' => $room
        ];
        
        if (count($chatData['reactions']) > 50) {
            $chatData['reactions'] = array_slice($chatData['reactions'], -50);
        }
        
        $chatData['last_update'] = time();
        file_put_contents($chatFile, json_encode($chatData));
        
        echo json_encode(['success' => true]);
        break;
        
    case 'get':
        $since = isset($_GET['since']) ? (int)$_GET['since'] : 0;
        $room = sanitize($_GET['room'] ?? 'general');
        
        $chatFile = __DIR__ . '/admin/chat_data.json';
        $chatData = file_exists($chatFile) ? json_decode(file_get_contents($chatFile), true) : ['messages' => [], 'reactions' => [], 'last_update' => 0];
        
        $messages = array_filter($chatData['messages'], function($m) use ($since, $room) {
            return $m['timestamp'] > $since && (!isset($m['room']) || $m['room'] === $room);
        });
        
        $reactions = array_filter($chatData['reactions'], function($r) use ($since, $room) {
            return $r['timestamp'] > $since && (!isset($r['room']) || $r['room'] === $room);
        });
        
        echo json_encode([
            'success' => true,
            'messages' => array_values($messages),
            'reactions' => array_values($reactions),
            'last_update' => $chatData['last_update']
        ]);
        break;
        
    default:
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
}
