<?php
$host = 'localhost';
$dbname = 'school_php_ai_db';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function isLoggedIn() {
    return isset($_SESSION['admin_id']);
}

function redirect($url) {
    header("Location: $url");
    exit();
}

function logActivity($pdo, $actionType, $description, $username, $recordType = '', $recordId = 0) {
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $stmt = $pdo->prepare("INSERT INTO activity_history (action_type, description, username, record_type, record_id, ip_address) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$actionType, $description, $username, $recordType, $recordId, $ipAddress]);
}

function getRecordsPerPage($pdo) {
    $stmt = $pdo->query("SELECT setting_value FROM school_settings WHERE setting_key = 'records_per_page'");
    $result = $stmt->fetch();
    return $result ? (int)$result['setting_value'] : 50;
}

function paginate($pdo, $table, $where = '1=1', $params = [], $orderBy = 'id DESC', $page = 1) {
    $perPage = getRecordsPerPage($pdo);
    $page = max(1, (int)$page);
    $offset = ($page - 1) * $perPage;
    
    $countSql = "SELECT COUNT(*) FROM $table WHERE $where";
    $stmt = $pdo->prepare($countSql);
    $stmt->execute($params);
    $totalRecords = (int)$stmt->fetchColumn();
    $totalPages = ceil($totalRecords / $perPage);
    
    $dataSql = "SELECT * FROM $table WHERE $where ORDER BY $orderBy LIMIT $perPage OFFSET $offset";
    $stmt = $pdo->prepare($dataSql);
    $stmt->execute($params);
    $records = $stmt->fetchAll();
    
    return [
        'records' => $records,
        'total' => $totalRecords,
        'pages' => $totalPages,
        'current' => $page,
        'per_page' => $perPage
    ];
}

function paginationLinks($currentPage, $totalPages, $baseUrl = '', $params = []) {
    if ($totalPages <= 1) return '';
    
    $html = '<div class="pagination">';
    
    if ($currentPage > 1) {
        $params['page'] = $currentPage - 1;
        $queryString = http_build_query($params);
        $html .= '<a href="?' . $queryString . '" class="pagination-btn"><i class="fas fa-chevron-left"></i> Prev</a>';
    }
    
    $start = max(1, $currentPage - 2);
    $end = min($totalPages, $currentPage + 2);
    
    if ($start > 1) {
        $params['page'] = 1;
        $queryString = http_build_query($params);
        $html .= '<a href="?' . $queryString . '" class="pagination-btn">1</a>';
        if ($start > 2) {
            $html .= '<span class="pagination-ellipsis">...</span>';
        }
    }
    
    for ($i = $start; $i <= $end; $i++) {
        $params['page'] = $i;
        $queryString = http_build_query($params);
        $active = $i == $currentPage ? ' active' : '';
        $html .= '<a href="?' . $queryString . '" class="pagination-btn' . $active . '">' . $i . '</a>';
    }
    
    if ($end < $totalPages) {
        if ($end < $totalPages - 1) {
            $html .= '<span class="pagination-ellipsis">...</span>';
        }
        $params['page'] = $totalPages;
        $queryString = http_build_query($params);
        $html .= '<a href="?' . $queryString . '" class="pagination-btn">' . $totalPages . '</a>';
    }
    
    if ($currentPage < $totalPages) {
        $params['page'] = $currentPage + 1;
        $queryString = http_build_query($params);
        $html .= '<a href="?' . $queryString . '" class="pagination-btn">Next <i class="fas fa-chevron-right"></i></a>';
    }
    
    $html .= '</div>';
    
    return $html;
}

function runCleanup($pdo) {
    $stmt = $pdo->query("SELECT setting_value FROM school_settings WHERE setting_key = 'auto_cleanup_enabled'");
    $result = $stmt->fetch();
    if (!$result || $result['setting_value'] != '1') return;
    
    $activityDays = 180;
    $stmt = $pdo->query("SELECT setting_value FROM school_settings WHERE setting_key = 'activity_history_days'");
    $result = $stmt->fetch();
    if ($result) $activityDays = (int)$result['setting_value'];
    
    $chatbotDays = 30;
    $stmt = $pdo->query("SELECT setting_value FROM school_settings WHERE setting_key = 'chatbot_messages_days'");
    $result = $stmt->fetch();
    if ($result) $chatbotDays = (int)$result['setting_value'];
    
    $stmt = $pdo->prepare("DELETE FROM activity_history WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)");
    $stmt->execute([$activityDays]);
    $activityDeleted = $stmt->rowCount();
    
    $stmt = $pdo->prepare("DELETE FROM chatbot_messages WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY) AND is_resolved = 1");
    $stmt->execute([$chatbotDays]);
    $chatbotDeleted = $stmt->rowCount();
    
    if ($activityDeleted > 0 || $chatbotDeleted > 0) {
        $stmt = $pdo->prepare("INSERT INTO cleanup_logs (cleanup_type, records_deleted, description, executed_by) VALUES (?, ?, ?, ?)");
        $stmt->execute(['auto', $activityDeleted + $chatbotDeleted, "Auto-cleanup: $activityDeleted activity, $chatbotDeleted chatbot", 'SYSTEM']);
    }
}

if (php_sapi_name() !== 'cli' && session_status() == PHP_SESSION_NONE) {
    session_start();
    
    if (rand(1, 100) == 1) {
        runCleanup($pdo);
    }
}
?>
