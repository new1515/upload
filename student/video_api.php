<?php
require_once '../config/database.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? 'list';

switch ($action) {
    case 'list':
        $category = isset($_GET['category']) ? sanitize($_GET['category']) : '';
        $subject = isset($_GET['subject']) ? sanitize($_GET['subject']) : '';
        $class = isset($_GET['class']) ? sanitize($_GET['class']) : '';
        $search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
        
        $sql = "SELECT * FROM video_lessons WHERE status = 'active'";
        $params = [];
        
        if ($category) {
            $sql .= " AND category = ?";
            $params[] = $category;
        }
        if ($subject) {
            $sql .= " AND subject LIKE ?";
            $params[] = "%$subject%";
        }
        if ($class) {
            $sql .= " AND (class = ? OR class = '')";
            $params[] = $class;
        }
        if ($search) {
            $sql .= " AND (title LIKE ? OR description LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        
        $sql .= " ORDER BY created_at DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $videos = $stmt->fetchAll();
        
        echo json_encode(['success' => true, 'videos' => $videos]);
        break;
        
    case 'get':
        $id = (int)($_GET['id'] ?? 0);
        $stmt = $pdo->prepare("SELECT * FROM video_lessons WHERE id = ? AND status = 'active'");
        $stmt->execute([$id]);
        $video = $stmt->fetch();
        
        if ($video) {
            echo json_encode(['success' => true, 'video' => $video]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Video not found']);
        }
        break;
        
    case 'increment_views':
        $id = (int)($_GET['id'] ?? 0);
        $stmt = $pdo->prepare("UPDATE video_lessons SET views = views + 1 WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['success' => true]);
        break;
        
    case 'progress':
        $userId = (int)($_SESSION['student_id'] ?? 0);
        $videoId = (int)($_GET['video_id'] ?? 0);
        $progress = (int)($_GET['progress'] ?? 0);
        
        if ($userId && $videoId) {
            $stmt = $pdo->prepare("INSERT INTO video_progress (video_id, user_id, progress, completed) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE progress = VALUES(progress), completed = VALUES(completed), last_watched = CURRENT_TIMESTAMP");
            $completed = $progress >= 90 ? 1 : 0;
            $stmt->execute([$videoId, $userId, $progress, $completed]);
        }
        echo json_encode(['success' => true]);
        break;
        
    case 'comments':
        $videoId = (int)($_GET['video_id'] ?? 0);
        $stmt = $pdo->prepare("SELECT * FROM video_comments WHERE video_id = ? ORDER BY created_at DESC");
        $stmt->execute([$videoId]);
        $comments = $stmt->fetchAll();
        echo json_encode(['success' => true, 'comments' => $comments]);
        break;
        
    case 'add_comment':
        $videoId = (int)($_POST['video_id'] ?? 0);
        $comment = sanitize($_POST['comment'] ?? '');
        $userId = (int)($_SESSION['student_id'] ?? 0);
        $userRole = 'student';
        $userName = $_SESSION['student_name'] ?? 'Student';
        
        if ($videoId && $comment && $userId) {
            $stmt = $pdo->prepare("INSERT INTO video_comments (video_id, user_id, user_role, user_name, comment) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$videoId, $userId, $userRole, $userName, $comment]);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Invalid data']);
        }
        break;
        
    default:
        echo json_encode(['error' => 'Unknown action']);
}
