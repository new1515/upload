<?php
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');

$whiteboardFile = __DIR__ . '/whiteboard_data.json';

function readWhiteboardData() {
    global $whiteboardFile;
    if (file_exists($whiteboardFile)) {
        $data = json_decode(file_get_contents($whiteboardFile), true);
        if (!$data) return ['strokes' => [], 'last_update' => time(), 'cleared_at' => 0, 'is_open' => false, 'opened_by' => ''];
        return $data;
    }
    return ['strokes' => [], 'last_update' => time(), 'cleared_at' => 0, 'is_open' => false, 'opened_by' => ''];
}

function writeWhiteboardData($data) {
    global $whiteboardFile;
    file_put_contents($whiteboardFile, json_encode($data));
}

$data = readWhiteboardData();
$action = $_GET['action'] ?? 'get';

switch ($action) {
    case 'get':
        $since = isset($_GET['since']) ? (int)$_GET['since'] : 0;
        $newStrokes = array_filter($data['strokes'], function($s) use ($since) {
            return $s['timestamp'] > $since;
        });
        echo json_encode([
            'strokes' => array_values($newStrokes),
            'last_update' => $data['last_update'],
            'cleared_at' => $data['cleared_at'],
            'total_strokes' => count($data['strokes']),
            'is_open' => $data['is_open'] ?? false,
            'opened_by' => $data['opened_by'] ?? ''
        ]);
        break;
        
    case 'add_stroke':
        $stroke = isset($_GET['stroke']) ? json_decode($_GET['stroke'], true) : null;
        $role = isset($_GET['role']) ? strip_tags($_GET['role']) : 'guest';
        
        if ($stroke && is_array($stroke) && ($role === 'admin' || $role === 'superadmin' || $role === 'teacher')) {
            $data['strokes'][] = [
                'id' => uniqid(),
                'points' => $stroke['points'] ?? [],
                'color' => $stroke['color'] ?? '#ffffff',
                'size' => $stroke['size'] ?? 3,
                'timestamp' => time()
            ];
            $data['last_update'] = time();
            
            if (count($data['strokes']) > 5000) {
                $data['strokes'] = array_slice($data['strokes'], -5000);
            }
            writeWhiteboardData($data);
            
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Invalid stroke or unauthorized']);
        }
        break;
        
    case 'clear':
        $role = isset($_GET['role']) ? strip_tags($_GET['role']) : 'guest';
        
        if ($role === 'admin' || $role === 'superadmin' || $role === 'teacher') {
            $data['strokes'] = [];
            $data['last_update'] = time();
            $data['cleared_at'] = time();
            $data['is_open'] = false;
            $data['opened_by'] = '';
            writeWhiteboardData($data);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Only host can clear whiteboard']);
        }
        break;
        
    case 'get_all':
        echo json_encode([
            'strokes' => $data['strokes'],
            'last_update' => $data['last_update'],
            'cleared_at' => $data['cleared_at'],
            'is_open' => $data['is_open'] ?? false,
            'opened_by' => $data['opened_by'] ?? ''
        ]);
        break;
        
    case 'open':
        $role = isset($_GET['role']) ? strip_tags($_GET['role']) : 'guest';
        $name = isset($_GET['name']) ? strip_tags($_GET['name']) : 'Host';
        
        if ($role === 'admin' || $role === 'superadmin' || $role === 'teacher') {
            $data['is_open'] = true;
            $data['opened_by'] = $name;
            $data['last_update'] = time();
            writeWhiteboardData($data);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Only host can open whiteboard']);
        }
        break;
        
    case 'close':
        $role = isset($_GET['role']) ? strip_tags($_GET['role']) : 'guest';
        
        if ($role === 'admin' || $role === 'superadmin' || $role === 'teacher') {
            $data['is_open'] = false;
            $data['opened_by'] = '';
            $data['last_update'] = time();
            writeWhiteboardData($data);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Only host can close whiteboard']);
        }
        break;
        
    default:
        echo json_encode(['error' => 'Unknown action']);
}
