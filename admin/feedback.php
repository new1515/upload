<?php
require_once '../config/database.php';

if (!isLoggedIn()) {
    redirect('../login.php');
}

$message = '';

if (isset($_POST['update_status'])) {
    $id = $_POST['id'];
    $status = $_POST['status'];
    $stmt = $pdo->prepare("UPDATE feedback SET status = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$status, $id]);
    $message = '<div class="success-msg">Status updated successfully!</div>';
}

if (isset($_POST['delete_feedback'])) {
    $id = $_POST['id'];
    $stmt = $pdo->prepare("DELETE FROM feedback WHERE id = ?");
    $stmt->execute([$id]);
    $message = '<div class="success-msg">Feedback deleted successfully!</div>';
}

if (isset($_POST['send_reply'])) {
    $id = $_POST['id'];
    $reply = sanitize($_POST['reply'] ?? '');
    if (!empty($reply)) {
        $stmt = $pdo->prepare("UPDATE feedback SET reply = ?, status = 'replied', updated_at = NOW() WHERE id = ?");
        $stmt->execute([$reply, $id]);
        $message = '<div class="success-msg">Reply sent successfully!</div>';
    }
}

$status_filter = $_GET['status'] ?? 'all';
$where = $status_filter !== 'all' ? "WHERE status = '$status_filter'" : '';
$feedback = $pdo->query("SELECT * FROM feedback $where ORDER BY created_at DESC")->fetchAll();

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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback & Suggestions - School Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .page-header h1 {
            color: var(--dark);
            font-size: 24px;
        }
        
        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 5px 20px var(--shadow);
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            color: white;
        }
        
        .stat-icon.pending { background: #f39c12; }
        .stat-icon.replied { background: #27ae60; }
        .stat-icon.resolved { background: #3498db; }
        
        .stat-info h3 {
            font-size: 24px;
            margin-bottom: 5px;
        }
        
        .stat-info p {
            font-size: 13px;
            color: var(--gray);
        }
        
        .filter-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .filter-tab {
            padding: 8px 20px;
            border-radius: 20px;
            background: white;
            color: var(--gray);
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            transition: all 0.3s;
            border: 2px solid transparent;
        }
        
        .filter-tab:hover, .filter-tab.active {
            background: var(--primary);
            color: white;
        }
        
        .feedback-table {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px var(--shadow);
            overflow: hidden;
        }
        
        .feedback-table table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .feedback-table th {
            background: var(--primary);
            color: white;
            padding: 15px;
            text-align: left;
            font-size: 13px;
        }
        
        .feedback-table td {
            padding: 15px;
            border-bottom: 1px solid #eee;
            font-size: 13px;
        }
        
        .feedback-table tr:hover {
            background: #f8f9fa;
        }
        
        .status-badge {
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 11px;
            font-weight: 600;
        }
        
        .status-badge.pending {
            background: #fef3cd;
            color: #856404;
        }
        
        .status-badge.replied {
            background: #d4edda;
            color: #155724;
        }
        
        .status-badge.resolved {
            background: #cce5ff;
            color: #004085;
        }
        
        .status-form {
            display: flex;
            gap: 5px;
        }
        
        .status-form select {
            padding: 5px 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 12px;
        }
        
        .status-form button {
            padding: 5px 10px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 12px;
        }
        
        .action-btn {
            padding: 5px 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 12px;
            margin-right: 5px;
        }
        
        .action-btn.view {
            background: #3498db;
            color: white;
        }
        
        .action-btn.delete {
            background: #e74c3c;
            color: white;
        }
        
.feedback-detail {
            border-top: 1px solid #eee;
            padding-top: 15px;
            margin-top: 15px;
        }
        
        .badge {
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 12px;
            color: white;
            display: inline-block;
        }
        .badge-high { background: #e74c3c; }
        .badge-warning { background: #f39c12; }
        .badge-success { background: #27ae60; }
        
        .action-btn.reply-btn {
            background: #27ae60;
            color: white;
            padding: 6px 12px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 12px;
        }
        .action-btn.reply-btn:hover {
            background: #219a52;
        }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            align-items: center;
            justify-content: center;
        }
        
        .modal.show {
            display: flex;
        }
        
        .modal-content {
            background: white;
            border-radius: 15px;
            padding: 30px;
            max-width: 600px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .modal-header h2 {
            color: #2c3e50;
            margin: 0;
        }
        
        .modal-close {
            background: none;
            border: none;
            font-size: 28px;
            cursor: pointer;
            color: #999;
        }
        
        .modal-close:hover {
            color: #333;
        }
    </style>
</head>
<body>
    <main class="main-content">
        <div class="page-header">
            <h1><i class="fas fa-comment-dots"></i> Feedback & Suggestions</h1>
            <a href="../feedback.php" target="_blank" class="btn btn-primary">
                <i class="fas fa-external-link-alt"></i> View Public Page
            </a>
        </div>
        
        <?php echo $message; ?>
        
        <div class="stats-row">
            <?php
            $total = $pdo->query("SELECT COUNT(*) FROM feedback")->fetchColumn();
            $pending = $pdo->query("SELECT COUNT(*) FROM feedback WHERE status = 'pending'")->fetchColumn();
            $replied = $pdo->query("SELECT COUNT(*) FROM feedback WHERE status = 'replied'")->fetchColumn();
            $resolved = $pdo->query("SELECT COUNT(*) FROM feedback WHERE status = 'resolved'")->fetchColumn();
            ?>
            <div class="stat-card">
                <div class="stat-icon" style="background: var(--primary);">
                    <i class="fas fa-comments"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $total; ?></h3>
                    <p>Total Feedback</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon pending">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $pending; ?></h3>
                    <p>Pending</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon replied">
                    <i class="fas fa-reply"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $replied; ?></h3>
                    <p>Replied</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon resolved">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $resolved; ?></h3>
                    <p>Resolved</p>
                </div>
            </div>
        </div>
        
        <div class="filter-tabs">
            <a href="feedback.php" class="filter-tab <?php echo $status_filter === 'all' ? 'active' : ''; ?>">All</a>
            <a href="feedback.php?status=pending" class="filter-tab <?php echo $status_filter === 'pending' ? 'active' : ''; ?>">Pending</a>
            <a href="feedback.php?status=replied" class="filter-tab <?php echo $status_filter === 'replied' ? 'active' : ''; ?>">Replied</a>
            <a href="feedback.php?status=resolved" class="filter-tab <?php echo $status_filter === 'resolved' ? 'active' : ''; ?>">Resolved</a>
        </div>
        
        <div class="feedback-table">
            <?php if (empty($feedback)): ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <p>No feedback found</p>
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Name</th>
                            <th>Role</th>
                            <th>Category</th>
                            <th>Message</th>
                            <th>Reply</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($feedback as $f): ?>
                            <tr>
                                <td><?php echo date('M d, Y', strtotime($f['created_at'])); ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($f['name']); ?></strong>
                                    <?php if ($f['email']): ?>
                                        <br><small><?php echo htmlspecialchars($f['email']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><span style="text-transform: capitalize;"><?php echo htmlspecialchars($f['role']); ?></span></td>
                                <td><?php echo htmlspecialchars($f['subject']); ?></td>
                                <td>
                                    <div style="max-width: 200px; max-height: 60px; overflow: hidden; text-overflow: ellipsis;">
                                        <?php echo htmlspecialchars($f['message']); ?>
                                    </div>
                                    <button class="action-btn view" onclick="viewFeedback(<?php echo $f['id']; ?>)" style="margin-top: 5px;">View Full</button>
                                </td>
                                <td>
                                    <?php if (!empty($f['reply'])): ?>
                                        <span style="color: green;"><i class="fas fa-check"></i> Replied</span>
                                    <?php else: ?>
                                        <button class="action-btn reply-btn" onclick="showReplyForm(<?php echo $f['id']; ?>)"><i class="fas fa-reply"></i> Reply</button>
                                        <div id="reply-form-<?php echo $f['id']; ?>" style="display: none; margin-top: 10px;">
                                            <form method="POST">
                                                <input type="hidden" name="id" value="<?php echo $f['id']; ?>">
                                                <textarea name="reply" placeholder="Type your reply..." required style="width: 100%; padding: 8px; border: 2px solid #ddd; border-radius: 5px; min-height: 80px;"></textarea>
                                                <button type="submit" name="send_reply" class="btn btn-primary" style="margin-top: 5px; padding: 8px 15px;">Send</button>
                                                <button type="button" class="btn" style="margin-top: 5px; padding: 8px 15px;" onclick="hideReplyForm(<?php echo $f['id']; ?>)">Cancel</button>
                                            </form>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <form method="POST" class="status-form">
                                        <input type="hidden" name="id" value="<?php echo $f['id']; ?>">
                                        <select name="status">
                                            <option value="pending" <?php echo $f['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                            <option value="replied" <?php echo $f['status'] === 'replied' ? 'selected' : ''; ?>>Replied</option>
                                            <option value="resolved" <?php echo $f['status'] === 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                                        </select>
                                        <button type="submit" name="update_status"><i class="fas fa-check"></i></button>
                                    </form>
                                </td>
                                <td>
                                    <form method="POST" onsubmit="return confirm('Delete this feedback?');">
                                        <input type="hidden" name="id" value="<?php echo $f['id']; ?>">
                                        <button type="submit" name="delete_feedback" class="action-btn delete"><i class="fas fa-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </main>
    
    <div class="modal" id="feedbackModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Feedback Details</h2>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <div id="feedbackContent"></div>
        </div>
    </div>
    
    <script>
        const feedbackData = <?php echo json_encode($feedback); ?>;
        
        function viewFeedback(id) {
            console.log('viewFeedback called with id:', id);
            const feedback = feedbackData.find(f => f.id == id);
            console.log('Found feedback:', feedback);
            if (feedback) {
                let replyHtml = '';
                if (feedback.reply) {
                    replyHtml = `
                        <div style="margin-top: 20px; padding: 15px; background: #e8f5e9; border-radius: 10px; border-left: 4px solid #4caf50;">
                            <strong><i class="fas fa-reply"></i> Your Reply:</strong><br><br>
                            ${feedback.reply.replace(/\n/g, '<br>')}
                        </div>
                    `;
                } else {
                    replyHtml = `
                        <div style="margin-top: 20px; padding: 15px; background: #f5f5f5; border-radius: 10px;">
                            <strong><i class="fas fa-paper-plane"></i> Send Reply:</strong>
                            <form method="POST" action="feedback.php" style="margin-top: 10px;" onsubmit="this.submit(); this.disabled=true;">
                                <input type="hidden" name="id" value="${feedback.id}">
                                <textarea name="reply" placeholder="Type your reply here..." required style="width: 100%; padding: 10px; border: 2px solid #ddd; border-radius: 8px; min-height: 100px; font-family: inherit;"></textarea>
                                <button type="submit" name="send_reply" class="btn btn-primary" style="margin-top: 10px;"><i class="fas fa-paper-plane"></i> Send Reply</button>
                            </form>
                        </div>
                    `;
                }
                document.getElementById('feedbackContent').innerHTML = `
                    <div style="margin-bottom: 15px;">
                        <strong>From:</strong> ${feedback.name} <br>
                        <strong>Email:</strong> ${feedback.email || 'Not provided'} <br>
                        <strong>Role:</strong> ${feedback.role} <br>
                        <strong>Category:</strong> ${feedback.subject} <br>
                        <strong>Date:</strong> ${new Date(feedback.created_at).toLocaleString()} <br>
                        <strong>Status:</strong> <span class="badge badge-${feedback.status === 'pending' ? 'high' : feedback.status === 'replied' ? 'warning' : 'success'}">${feedback.status}</span>
                    </div>
                    <div class="feedback-detail">
                        <strong>Message:</strong><br><br>
                        ${feedback.message.replace(/\n/g, '<br>')}
                    </div>
                    ${replyHtml}
                `;
                const modal = document.getElementById('feedbackModal');
                console.log('Modal element:', modal);
                modal.style.display = 'flex';
                modal.classList.add('show');
                console.log('Modal display set to:', modal.style.display);
            }
        }
        
        function closeModal() {
            var modal = document.getElementById('feedbackModal');
            modal.classList.remove('show');
            modal.style.display = 'none';
        }
        
        document.getElementById('feedbackModal').addEventListener('click', function(e) {
            if (e.target === this) closeModal();
        });
        
        function showReplyForm(id) {
            document.getElementById('reply-form-' + id).style.display = 'block';
        }
        
        function hideReplyForm(id) {
            document.getElementById('reply-form-' + id).style.display = 'none';
        }
    </script>
</body>
</html>
