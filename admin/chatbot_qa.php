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
$schoolName = $settings['school_name'] ?? 'School Management System';

$message = '';

if (isset($_POST['add_qa'])) {
    $keywords = sanitize($_POST['keywords']);
    $answer = sanitize($_POST['answer']);
    $category = sanitize($_POST['category']);
    
    $stmt = $pdo->prepare("INSERT INTO chatbot_qa (keywords, answer, category) VALUES (?, ?, ?)");
    $stmt->execute([$keywords, $answer, $category]);
    logActivity($pdo, 'create', "Added chatbot Q&A: $keywords", $_SESSION['admin_username'] ?? 'admin', 'chatbot_qa', $pdo->lastInsertId());
    $message = '<div class="success-msg"><i class="fas fa-check-circle"></i> Q&A added successfully!</div>';
}

if (isset($_POST['update_qa'])) {
    $id = (int)$_POST['id'];
    $keywords = sanitize($_POST['keywords']);
    $answer = sanitize($_POST['answer']);
    $category = sanitize($_POST['category']);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    $stmt = $pdo->prepare("UPDATE chatbot_qa SET keywords = ?, answer = ?, category = ?, is_active = ? WHERE id = ?");
    $stmt->execute([$keywords, $answer, $category, $is_active, $id]);
    logActivity($pdo, 'update', "Updated chatbot Q&A ID: $id", $_SESSION['admin_username'] ?? 'admin', 'chatbot_qa', $id);
    $message = '<div class="success-msg"><i class="fas fa-check-circle"></i> Q&A updated successfully!</div>';
}

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $pdo->prepare("DELETE FROM chatbot_qa WHERE id = ?")->execute([$id]);
    logActivity($pdo, 'delete', "Deleted chatbot Q&A ID: $id", $_SESSION['admin_username'] ?? 'admin', 'chatbot_qa', $id);
    $message = '<div class="success-msg"><i class="fas fa-check-circle"></i> Q&A deleted successfully!</div>';
}

$categories = $pdo->query("SELECT DISTINCT category FROM chatbot_qa WHERE category != '' ORDER BY category")->fetchAll(PDO::FETCH_COLUMN);
$qaList = $pdo->query("SELECT * FROM chatbot_qa ORDER BY category, id DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chatbot Q&A - <?php echo $schoolName; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        .qa-card { background: white; border-radius: 10px; padding: 15px; margin-bottom: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .qa-card.inactive { opacity: 0.6; border-left: 4px solid #ccc; }
        .qa-card.active { border-left: 4px solid #27ae60; }
        .keyword-tag { display: inline-block; background: #e8f4fd; color: #2980b9; padding: 3px 10px; border-radius: 15px; font-size: 12px; margin: 2px; }
        .category-badge { padding: 4px 12px; border-radius: 15px; font-size: 11px; font-weight: 600; }
        .cat-general { background: #f0f4ff; color: #4a90e2; }
        .cat-admission { background: #fef5e7; color: #e67e22; }
        .cat-fees { background: #e8f5e9; color: #27ae60; }
        .cat-contact { background: #f3e5f5; color: #9b59b6; }
        .cat-academic { background: #fce4ec; color: #e91e63; }
        .cat-help { background: #fff3e0; color: #ff9800; }
        .answer-preview { background: #f8f9fa; padding: 10px; border-radius: 8px; margin-top: 10px; font-size: 13px; color: #666; }
        .variables-hint { background: #fff3cd; padding: 10px; border-radius: 8px; margin-bottom: 15px; font-size: 12px; }
        .variables-hint strong { color: #856404; }
    </style>
</head>
<body>
    <button class="mobile-menu-toggle"><i class="fas fa-bars"></i></button>
    <div class="sidebar-overlay"></div>
    <nav class="sidebar">
        <div class="sidebar-brand"><i class="fas fa-school"></i> <?php echo $schoolName; ?></div>
        <ul class="sidebar-menu">
            <li><a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
            <li><a href="chatbot_qa.php" class="active"><i class="fas fa-robot"></i> Chatbot Q&A</a></li>
            <li><a href="chatbot.php"><i class="fas fa-comment-dots"></i> Test Chatbot</a></li>
            <li><a href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
            <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </nav>
    
    <main class="main-content">
        <div class="header">
            <h1><i class="fas fa-robot"></i> Chatbot Q&A Manager</h1>
        </div>
        
        <div class="content">
            <?php echo $message; ?>
            
            <div class="variables-hint">
                <strong><i class="fas fa-info-circle"></i> Available Variables:</strong><br>
                <code>[SCHOOL_NAME]</code> = <?php echo $schoolName; ?><br>
                <code>[SCHOOL_PHONE]</code> = <?php echo $settings['school_phone'] ?? ''; ?><br>
                <code>[SCHOOL_EMAIL]</code> = <?php echo $settings['school_email'] ?? ''; ?><br>
                <code>[SCHOOL_ADDRESS]</code> = <?php echo $settings['school_address'] ?? ''; ?>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div class="table-container">
                    <div class="table-header">
                        <h3><i class="fas fa-plus-circle"></i> Add New Q&A</h3>
                    </div>
                    <form method="POST" style="padding: 20px;">
                        <div class="form-group">
                            <label>Keywords (comma separated)</label>
                            <input type="text" name="keywords" placeholder="e.g., hello, hi, hey, good morning" required>
                            <small style="color: #888;">User messages containing these words will trigger this answer</small>
                        </div>
                        <div class="form-group">
                            <label>Category</label>
                            <select name="category">
                                <option value="general">General</option>
                                <option value="admission">Admission</option>
                                <option value="fees">Fees</option>
                                <option value="contact">Contact</option>
                                <option value="academic">Academic</option>
                                <option value="help">Help</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Answer</label>
                            <textarea name="answer" rows="5" placeholder="The answer the chatbot should give..." required></textarea>
                            <small style="color: #888;">Use [SCHOOL_NAME], [SCHOOL_PHONE], etc. for dynamic info</small>
                        </div>
                        <button type="submit" name="add_qa" class="btn btn-primary" style="width: 100%;">
                            <i class="fas fa-save"></i> Add Q&A
                        </button>
                    </form>
                </div>
                
                <div class="table-container">
                    <div class="table-header">
                        <h3><i class="fas fa-list"></i> Current Q&A List (<?php echo count($qaList); ?>)</h3>
                    </div>
                    <div style="padding: 15px; max-height: 500px; overflow-y: auto;">
                        <?php foreach ($qaList as $qa): ?>
                        <div class="qa-card <?php echo $qa['is_active'] ? 'active' : 'inactive'; ?>">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                                <div>
                                    <span class="category-badge cat-<?php echo $qa['category']; ?>"><?php echo ucfirst($qa['category']); ?></span>
                                    <?php if (!$qa['is_active']): ?>
                                    <span style="background: #eee; color: #888; padding: 2px 8px; border-radius: 10px; font-size: 10px; margin-left: 5px;">Disabled</span>
                                    <?php endif; ?>
                                    <div style="margin: 8px 0;">
                                        <?php foreach (explode(',', $qa['keywords']) as $kw): ?>
                                        <span class="keyword-tag"><?php echo trim($kw); ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                    <div class="answer-preview">
                                        <i class="fas fa-comment-dots"></i> <?php echo nl2br(htmlspecialchars(substr($qa['answer'], 0, 150))); ?><?php echo strlen($qa['answer']) > 150 ? '...' : ''; ?>
                                    </div>
                                </div>
                                <div style="display: flex; gap: 5px;">
                                    <button class="btn btn-sm btn-primary modal-trigger" data-modal="editQA<?php echo $qa['id']; ?>">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <a href="?delete=<?php echo $qa['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this Q&A?')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </div>
                            
                            <div id="editQA<?php echo $qa['id']; ?>" class="modal">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h3><i class="fas fa-edit"></i> Edit Q&A</h3>
                                        <button class="modal-close">&times;</button>
                                    </div>
                                    <form method="POST">
                                        <input type="hidden" name="id" value="<?php echo $qa['id']; ?>">
                                        <div class="form-group">
                                            <label>Keywords</label>
                                            <input type="text" name="keywords" value="<?php echo htmlspecialchars($qa['keywords']); ?>" required>
                                        </div>
                                        <div class="form-group">
                                            <label>Category</label>
                                            <select name="category">
                                                <option value="general" <?php echo $qa['category'] == 'general' ? 'selected' : ''; ?>>General</option>
                                                <option value="admission" <?php echo $qa['category'] == 'admission' ? 'selected' : ''; ?>>Admission</option>
                                                <option value="fees" <?php echo $qa['category'] == 'fees' ? 'selected' : ''; ?>>Fees</option>
                                                <option value="contact" <?php echo $qa['category'] == 'contact' ? 'selected' : ''; ?>>Contact</option>
                                                <option value="academic" <?php echo $qa['category'] == 'academic' ? 'selected' : ''; ?>>Academic</option>
                                                <option value="help" <?php echo $qa['category'] == 'help' ? 'selected' : ''; ?>>Help</option>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label>Answer</label>
                                            <textarea name="answer" rows="5" required><?php echo htmlspecialchars($qa['answer']); ?></textarea>
                                        </div>
                                        <div class="form-group">
                                            <label>
                                                <input type="checkbox" name="is_active" value="1" <?php echo $qa['is_active'] ? 'checked' : ''; ?>>
                                                Active (enable this Q&A)
                                            </label>
                                        </div>
                                        <button type="submit" name="update_qa" class="btn btn-primary" style="width: 100%;">
                                            <i class="fas fa-save"></i> Update Q&A
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <div class="table-container" style="margin-top: 25px;">
                <div class="table-header">
                    <h3><i class="fas fa-lightbulb"></i> Tips for Good Q&A</h3>
                </div>
                <div style="padding: 20px; background: #f8f9fa; border-radius: 10px;">
                    <ul style="margin: 0; padding-left: 20px; line-height: 2;">
                        <li>Use <strong>comma-separated keywords</strong> that users might type (e.g., "fees, school fees, tuition")</li>
                        <li>Keywords should be <strong>lowercase</strong> - the chatbot converts messages to lowercase automatically</li>
                        <li>Use <strong>variables</strong> like [SCHOOL_NAME] for dynamic information from settings</li>
                        <li>Keep answers <strong>concise but informative</strong></li>
                        <li>Categorize Q&A to keep them organized (General, Admission, Fees, Contact, Academic)</li>
                        <li>Add multiple similar Q&A with different keywords for better matching</li>
                        <li>Use <strong>line breaks (\n)</strong> in answers for better readability</li>
                    </ul>
                </div>
            </div>
        </div>
    </main>
    
    <script src="../assets/js/script.js"></script>
</body>
</html>
