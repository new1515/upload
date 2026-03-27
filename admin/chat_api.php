<?php
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');

$chatFile = __DIR__ . '/chat_data.json';

function readChatData() {
    global $chatFile;
    if (file_exists($chatFile)) {
        $data = json_decode(file_get_contents($chatFile), true);
        if (!$data) return ['messages' => [], 'reactions' => [], 'last_update' => time()];
        return $data;
    }
    return ['messages' => [], 'reactions' => [], 'last_update' => time()];
}

function writeChatData($data) {
    global $chatFile;
    file_put_contents($chatFile, json_encode($data));
}

$data = readChatData();
$action = $_GET['action'] ?? 'get';

switch ($action) {
    case 'get':
        $since = isset($_GET['since']) ? (int)$_GET['since'] : 0;
        $newMessages = array_filter($data['messages'], function($m) use ($since) {
            return $m['timestamp'] > $since;
        });
        $newReactions = array_filter($data['reactions'], function($r) use ($since) {
            return $r['timestamp'] > $since;
        });
        echo json_encode([
            'messages' => array_values($newMessages),
            'reactions' => array_values($newReactions),
            'last_update' => $data['last_update'],
            'total_messages' => count($data['messages'])
        ]);
        break;
        
    case 'send':
        $sender = isset($_GET['sender']) ? strip_tags($_GET['sender']) : 'Anonymous';
        $message = isset($_GET['message']) ? strip_tags($_GET['message']) : '';
        $role = isset($_GET['role']) ? strip_tags($_GET['role']) : 'user';
        
        if (!empty($message) && strlen($message) <= 500) {
            $data['messages'][] = [
                'id' => uniqid(),
                'sender' => $sender,
                'role' => $role,
                'text' => $message,
                'timestamp' => time()
            ];
            $data['last_update'] = time();
            writeChatData($data);
            
            $data['messages'] = array_slice($data['messages'], -100);
            writeChatData($data);
            
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Invalid message']);
        }
        break;
        
    case 'reaction':
        $emoji = isset($_GET['emoji']) ? strip_tags($_GET['emoji']) : '';
        $sender = isset($_GET['sender']) ? strip_tags($_GET['sender']) : 'Anonymous';
        
        if (!empty($emoji)) {
            $data['reactions'][] = [
                'id' => uniqid(),
                'emoji' => $emoji,
                'sender' => $sender,
                'timestamp' => time()
            ];
            $data['last_update'] = time();
            
            $data['reactions'] = array_slice($data['reactions'], -50);
            writeChatData($data);
            
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Invalid emoji']);
        }
        break;
        
    case 'join':
        $sender = isset($_GET['sender']) ? strip_tags($_GET['sender']) : 'Anonymous';
        $role = isset($_GET['role']) ? strip_tags($_GET['role']) : 'user';
        
        $data['messages'][] = [
            'id' => uniqid(),
            'sender' => 'System',
            'role' => 'system',
            'text' => $sender . ' (' . ucfirst($role) . ') joined the conference',
            'timestamp' => time(),
            'isSystem' => true
        ];
        $data['last_update'] = time();
        writeChatData($data);
        echo json_encode(['success' => true]);
        break;
        
    case 'leave':
        $sender = isset($_GET['sender']) ? strip_tags($_GET['sender']) : 'Anonymous';
        $role = isset($_GET['role']) ? strip_tags($_GET['role']) : 'user';
        
        $data['messages'][] = [
            'id' => uniqid(),
            'sender' => 'System',
            'role' => 'system',
            'text' => $sender . ' (' . ucfirst($role) . ') left the conference',
            'timestamp' => time(),
            'isSystem' => true
        ];
        $data['last_update'] = time();
        writeChatData($data);
        echo json_encode(['success' => true]);
        break;
        
    case 'clear':
        $data = ['messages' => [], 'reactions' => [], 'last_update' => time()];
        writeChatData($data);
        echo json_encode(['success' => true]);
        break;
        
    default:
        echo json_encode(['error' => 'Unknown action']);
}
