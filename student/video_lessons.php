<?php
require_once '../config/database.php';

if (!isset($_SESSION['student_id'])) {
    redirect('../login.php');
}

$userId = $_SESSION['student_id'] ?? 0;

$settings = [];
$stmt = $pdo->query("SELECT * FROM school_settings");
while ($row = $stmt->fetch()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
$schoolName = $settings['school_name'] ?? 'School Management System';
$schoolLogo = $settings['school_logo'] ?? '';

$stmt = $pdo->query("SELECT * FROM video_lessons WHERE status = 'active' ORDER BY created_at DESC");
$videos = $stmt->fetchAll();

$categories = $pdo->query("SELECT DISTINCT category FROM video_lessons WHERE status = 'active'")->fetchAll(PDO::FETCH_COLUMN);
$subjects = $pdo->query("SELECT DISTINCT subject FROM video_lessons WHERE subject != '' AND status = 'active'")->fetchAll(PDO::FETCH_COLUMN);

$completedCount = 0;
if ($userId) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM video_progress WHERE user_id = ? AND completed = 1");
    $stmt->execute([$userId]);
    $completedCount = $stmt->fetchColumn();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Video Lessons - Student Portal</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        :root {
            --primary: #667eea;
            --primary-dark: #764ba2;
            --success: #1ab26b;
            --danger: #ea4335;
            --warning: #fbbc05;
        }
        body { font-family: 'Segoe UI', sans-serif; background: #f5f7fa; }
        .content { padding: 20px; }
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; flex-wrap: wrap; gap: 16px; }
        .page-header h1 { font-size: 28px; color: #333; display: flex; align-items: center; gap: 12px; }
        .progress-overview { display: flex; gap: 20px; background: white; padding: 16px 24px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .progress-item { text-align: center; }
        .progress-item .number { font-size: 24px; font-weight: bold; color: var(--primary); }
        .progress-item .label { font-size: 12px; color: #666; }
        .filters { display: flex; gap: 12px; margin-bottom: 24px; flex-wrap: wrap; align-items: center; }
        .filters select, .filters input { padding: 10px 14px; border: 1px solid #ddd; border-radius: 8px; font-size: 14px; }
        .filters input { flex: 1; min-width: 200px; }
        .filters button { padding: 10px 16px; border: none; border-radius: 8px; cursor: pointer; }
        .btn-primary { background: var(--primary); color: white; }
        .category-tabs { display: flex; gap: 8px; margin-bottom: 24px; flex-wrap: wrap; }
        .category-tab { padding: 8px 16px; border: none; border-radius: 20px; cursor: pointer; background: white; color: #666; font-size: 13px; transition: all 0.2s; }
        .category-tab:hover { background: #eee; }
        .category-tab.active { background: var(--primary); color: white; }
        .video-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 24px; }
        .video-card { background: white; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.08); transition: all 0.3s; cursor: pointer; }
        .video-card:hover { transform: translateY(-8px); box-shadow: 0 8px 25px rgba(0,0,0,0.15); }
        .video-thumb { width: 100%; height: 200px; background: #1a1a2e; position: relative; overflow: hidden; }
        .video-thumb img { width: 100%; height: 100%; object-fit: cover; }
        .video-thumb .play-overlay { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 60px; height: 60px; background: rgba(255,255,255,0.9); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 24px; color: var(--primary); transition: all 0.2s; }
        .video-card:hover .play-overlay { transform: translate(-50%, -50%) scale(1.1); background: var(--primary); color: white; }
        .video-thumb .duration { position: absolute; bottom: 10px; right: 10px; background: rgba(0,0,0,0.8); color: white; padding: 4px 8px; border-radius: 4px; font-size: 12px; }
        .video-thumb .category-badge { position: absolute; top: 10px; left: 10px; background: var(--primary); color: white; padding: 4px 10px; border-radius: 4px; font-size: 11px; font-weight: 500; }
        .video-info { padding: 20px; }
        .video-info h3 { font-size: 16px; margin-bottom: 8px; color: #333; font-weight: 600; }
        .video-info p { font-size: 13px; color: #888; margin-bottom: 12px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; line-height: 1.5; }
        .video-meta { display: flex; justify-content: space-between; align-items: center; font-size: 12px; color: #999; }
        .video-meta .views { display: flex; align-items: center; gap: 4px; }
        .progress-bar { height: 4px; background: #eee; border-radius: 2px; margin-top: 12px; overflow: hidden; }
        .progress-bar .fill { height: 100%; background: var(--success); border-radius: 2px; transition: width 0.3s; }
        .video-card.completed .progress-bar .fill { background: var(--success); width: 100%; }
        .video-card.completed::after { content: '✓ Completed'; position: absolute; top: 10px; right: 10px; background: var(--success); color: white; padding: 4px 10px; border-radius: 4px; font-size: 11px; }
        .video-card { position: relative; }
        .empty-state { text-align: center; padding: 60px 20px; color: #999; }
        .empty-state i { font-size: 64px; margin-bottom: 20px; color: #ddd; }
        .empty-state h3 { font-size: 20px; margin-bottom: 10px; color: #666; }
        .modal { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.9); z-index: 1000; }
        .modal.open { display: flex; align-items: center; justify-content: center; flex-direction: column; padding: 20px; }
        .modal-content { width: 100%; max-width: 1000px; background: #1a1a2e; border-radius: 12px; overflow: hidden; }
        .modal video { width: 100%; display: block; background: black; }
        .modal-info { padding: 20px; background: white; }
        .modal-info h2 { margin-bottom: 10px; font-size: 20px; }
        .modal-info p { color: #666; margin-bottom: 16px; }
        .comments-section { background: #f9f9f9; padding: 20px; border-radius: 8px; margin-top: 16px; }
        .comments-section h4 { margin-bottom: 12px; }
        .comment { display: flex; gap: 12px; padding: 12px 0; border-bottom: 1px solid #eee; }
        .comment .avatar { width: 36px; height: 36px; border-radius: 50%; background: var(--primary); color: white; display: flex; align-items: center; justify-content: center; }
        .comment .content { flex: 1; }
        .comment .name { font-weight: 600; font-size: 13px; }
        .comment .text { font-size: 14px; color: #555; margin-top: 4px; }
        .comment .time { font-size: 11px; color: #999; }
        .comment-form { display: flex; gap: 12px; margin-top: 16px; }
        .comment-form input { flex: 1; padding: 10px 14px; border: 1px solid #ddd; border-radius: 8px; }
        .comment-form button { padding: 10px 20px; background: var(--primary); color: white; border: none; border-radius: 8px; cursor: pointer; }
        .close-modal { position: absolute; top: 20px; right: 20px; width: 40px; height: 40px; border-radius: 50%; background: rgba(255,255,255,0.2); color: white; border: none; font-size: 24px; cursor: pointer; }
        @media (max-width: 768px) {
            .video-grid { grid-template-columns: 1fr; }
            .page-header { flex-direction: column; align-items: flex-start; }
            .progress-overview { width: 100%; justify-content: space-around; }
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
            <i class="fas fa-user-graduate"></i> Student Portal
        </div>
        <ul class="sidebar-menu">
            <li><a href="index.php"><i class="fas fa-home"></i> Dashboard</a></li>
            <li><a href="video_conference.php"><i class="fas fa-video"></i> Video Conference</a></li>
            <li><a href="announcements.php"><i class="fas fa-bullhorn"></i> Announcements</a></li>
            <li><a href="results.php"><i class="fas fa-chart-line"></i> My Results</a></li>
            <li><a href="reportcard.php"><i class="fas fa-file-alt"></i> Report Card</a></li>
            <li><a href="lessons.php"><i class="fas fa-book-open"></i> Lessons</a></li>
            <li><a href="video_lessons.php" class="active"><i class="fas fa-video"></i> Video Lessons</a></li>
            <li><a href="video_uploads.php"><i class="fas fa-upload"></i> Video Uploads</a></li>
            <li><a href="feedback.php"><i class="fas fa-comment-dots"></i> Feedback</a></li>
            <li><a href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
            <li><a href="../logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </nav>
    
    <main class="main-content">
        <div class="header">
            <h1><i class="fas fa-play-circle"></i> Video Lessons</h1>
            <div class="header-right">
                <div class="theme-switcher">
                    <button class="theme-btn" data-theme="blue" title="Blue"></button>
                    <button class="theme-btn" data-theme="green" title="Green"></button>
                    <button class="theme-btn" data-theme="purple" title="Purple"></button>
                    <button class="theme-btn" data-theme="red" title="Red"></button>
                    <button class="theme-btn" data-theme="orange" title="Orange"></button>
                    <button class="theme-btn" data-theme="dark" title="Dark"></button>
                    <button class="theme-btn" data-theme="ocean" title="Ocean"></button>
                    <button class="theme-btn" data-theme="sunset" title="Sunset"></button>
                </div>
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
            <div class="page-header">
                <h1><i class="fas fa-play-circle"></i> Video Lessons</h1>
                <div class="progress-overview">
                    <div class="progress-item">
                        <div class="number"><?php echo count($videos); ?></div>
                        <div class="label">Total Videos</div>
                    </div>
                    <div class="progress-item">
                        <div class="number"><?php echo $completedCount; ?></div>
                        <div class="label">Completed</div>
                    </div>
                    <div class="progress-item">
                        <div class="number"><?php echo count($videos) - $completedCount; ?></div>
                        <div class="label">Remaining</div>
                    </div>
                </div>
            </div>
            
            <div class="category-tabs" id="categoryTabs">
                <button class="category-tab active" data-category="">All</button>
                <?php foreach ($categories as $cat): ?>
                    <button class="category-tab" data-category="<?php echo htmlspecialchars($cat); ?>"><?php echo htmlspecialchars($cat); ?></button>
                <?php endforeach; ?>
            </div>
            
            <div class="filters">
                <input type="text" id="searchInput" placeholder="Search videos..." onkeyup="filterVideos()">
                <select id="subjectFilter" onchange="filterVideos()">
                    <option value="">All Subjects</option>
                    <?php foreach ($subjects as $sub): ?>
                        <option value="<?php echo htmlspecialchars($sub); ?>"><?php echo htmlspecialchars($sub); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="video-grid" id="videoGrid">
                <?php foreach ($videos as $video): ?>
                    <div class="video-card <?php echo ($video['views'] > 0 ? 'completed' : ''); ?>" 
                         data-id="<?php echo $video['id']; ?>"
                         data-title="<?php echo strtolower(htmlspecialchars($video['title'])); ?>"
                         data-category="<?php echo htmlspecialchars($video['category']); ?>"
                         data-subject="<?php echo htmlspecialchars($video['subject']); ?>"
                         onclick="openVideo(<?php echo $video['id']; ?>)">
                        <div class="video-thumb">
                            <?php if ($video['thumbnail_path']): ?>
                                <img src="../<?php echo htmlspecialchars($video['thumbnail_path']); ?>" alt="<?php echo htmlspecialchars($video['title']); ?>">
                            <?php endif; ?>
                            <div class="play-overlay"><i class="fas fa-play"></i></div>
                            <span class="category-badge"><?php echo htmlspecialchars($video['category']); ?></span>
                            <?php if ($video['duration']): ?>
                                <span class="duration"><?php echo gmdate("H:i:s", $video['duration']); ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="video-info">
                            <h3><?php echo htmlspecialchars($video['title']); ?></h3>
                            <p><?php echo htmlspecialchars($video['description'] ?? 'Learn more about ' . $video['title']); ?></p>
                            <div class="video-meta">
                                <span class="views"><i class="fas fa-eye"></i> <?php echo $video['views']; ?> views</span>
                                <span><i class="fas fa-calendar"></i> <?php echo date('M d', strtotime($video['created_at'])); ?></span>
                            </div>
                            <div class="progress-bar">
                                <div class="fill" style="width: <?php echo min(100, $video['views'] * 10); ?>%"></div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <?php if (empty($videos)): ?>
                <div class="empty-state">
                    <i class="fas fa-video-slash"></i>
                    <h3>No Videos Available</h3>
                    <p>There are no video lessons available yet. Check back later!</p>
                </div>
            <?php endif; ?>
        </div>
    </main>
    
    <div class="modal" id="videoModal">
        <button class="close-modal" onclick="closeVideo()">&times;</button>
        <div class="modal-content">
            <video id="mainVideo" controls>
                <source src="" type="video/mp4">
                Your browser does not support the video tag.
            </video>
            <div class="modal-info">
                <h2 id="videoTitle">Video Title</h2>
                <p id="videoDescription">Video description goes here...</p>
                <div id="videoMeta" style="font-size:13px;color:#888;"></div>
                <div class="comments-section">
                    <h4><i class="fas fa-comments"></i> Comments</h4>
                    <div id="commentsList"></div>
                    <div class="comment-form">
                        <input type="text" id="commentInput" placeholder="Write a comment...">
                        <button onclick="addComment()"><i class="fas fa-paper-plane"></i></button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="../assets/js/script.js"></script>
    <script>
        let currentVideoId = null;
        
        function openVideo(id) {
            currentVideoId = id;
            fetch('video_api.php?action=get&id=' + id)
                .then(r => r.json())
                .then(data => {
                    if (data.video) {
                        document.getElementById('videoTitle').textContent = data.video.title;
                        document.getElementById('videoDescription').textContent = data.video.description || 'No description available.';
                        document.getElementById('videoMeta').innerHTML = '<i class="fas fa-eye"></i> ' + data.video.views + ' views | <i class="fas fa-calendar"></i> ' + new Date(data.video.created_at).toLocaleDateString() + ' | <i class="fas fa-folder"></i> ' + data.video.category;
                        document.getElementById('mainVideo').src = '../' + data.video.video_path;
                        document.getElementById('videoModal').classList.add('open');
                        loadComments(id);
                        
                        fetch('video_api.php?action=increment_views&id=' + id);
                    }
                });
        }
        
        function closeVideo() {
            document.getElementById('videoModal').classList.remove('open');
            document.getElementById('mainVideo').pause();
            document.getElementById('mainVideo').src = '';
        }
        
        function loadComments(videoId) {
            fetch('video_api.php?action=comments&video_id=' + videoId)
                .then(r => r.json())
                .then(data => {
                    const list = document.getElementById('commentsList');
                    if (data.comments && data.comments.length > 0) {
                        list.innerHTML = data.comments.map(c => '<div class="comment"><div class="avatar">' + c.user_name.charAt(0).toUpperCase() + '</div><div class="content"><div class="name">' + c.user_name + ' <span class="time">' + new Date(c.created_at).toLocaleDateString() + '</span></div><div class="text">' + c.comment + '</div></div></div>').join('');
                    } else {
                        list.innerHTML = '<p style="color:#999;font-size:14px;">No comments yet. Be the first to comment!</p>';
                    }
                });
        }
        
        function addComment() {
            const input = document.getElementById('commentInput');
            const comment = input.value.trim();
            if (!comment || !currentVideoId) return;
            
            fetch('video_api.php?action=add_comment', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'video_id=' + currentVideoId + '&comment=' + encodeURIComponent(comment)
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    input.value = '';
                    loadComments(currentVideoId);
                }
            });
        }
        
        document.getElementById('commentInput').addEventListener('keypress', (e) => {
            if (e.key === 'Enter') addComment();
        });
        
        document.querySelectorAll('.category-tab').forEach(tab => {
            tab.addEventListener('click', function() {
                document.querySelectorAll('.category-tab').forEach(t => t.classList.remove('active'));
                this.classList.add('active');
                filterVideos();
            });
        });
        
        function filterVideos() {
            const search = document.getElementById('searchInput').value.toLowerCase();
            const category = document.querySelector('.category-tab.active').dataset.category;
            const subject = document.getElementById('subjectFilter').value;
            
            document.querySelectorAll('.video-card').forEach(card => {
                const matchSearch = card.dataset.title.includes(search);
                const matchCategory = !category || card.dataset.category === category;
                const matchSubject = !subject || card.dataset.subject.includes(subject);
                
                card.style.display = (matchSearch && matchCategory && matchSubject) ? 'block' : 'none';
            });
        }
        
        document.getElementById('videoModal').addEventListener('click', (e) => {
            if (e.target.id === 'videoModal') closeVideo();
        });
    </script>
</body>
</html>
