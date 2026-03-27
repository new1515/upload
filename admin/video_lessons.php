<?php
require_once '../config/database.php';

if (!isLoggedIn()) {
    redirect('../login.php');
}

$userRole = $_SESSION['role'] ?? 'guest';
$userId = $_SESSION['admin_id'] ?? $_SESSION['teacher_id'] ?? 0;
$userName = $_SESSION['admin_username'] ?? $_SESSION['teacher_username'] ?? 'User';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['upload_video'])) {
        $title = sanitize($_POST['title'] ?? '');
        $description = sanitize($_POST['description'] ?? '');
        $category = sanitize($_POST['category'] ?? 'General');
        $subject = sanitize($_POST['subject'] ?? '');
        $class = sanitize($_POST['class'] ?? '');
        
        if (empty($title)) {
            $error = 'Please enter a video title';
        } elseif (empty($_FILES['video_file']['name'])) {
            $error = 'Please select a video file';
        } else {
            $uploadDir = '../uploads/videos/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            $videoName = time() . '_' . basename($_FILES['video_file']['name']);
            $videoPath = $uploadDir . $videoName;
            
            $allowedTypes = ['video/mp4', 'video/webm', 'video/ogg', 'video/quicktime', 'video/x-msvideo'];
            $fileType = mime_content_type($_FILES['video_file']['tmp_name']);
            
            if (!in_array($fileType, $allowedTypes)) {
                $error = 'Invalid video format. Please upload MP4, WebM, or MOV files.';
            } elseif (move_uploaded_file($_FILES['video_file']['tmp_name'], $videoPath)) {
                $thumbnailPath = '';
                if (isset($_FILES['thumbnail']['name']) && !empty($_FILES['thumbnail']['name'])) {
                    $thumbName = time() . '_thumb_' . basename($_FILES['thumbnail']['name']);
                    $thumbPath = $uploadDir . $thumbName;
                    if (move_uploaded_file($_FILES['thumbnail']['tmp_name'], $thumbPath)) {
                        $thumbnailPath = 'uploads/videos/' . $thumbName;
                    }
                }
                
                $stmt = $pdo->prepare("INSERT INTO video_lessons (title, description, video_path, thumbnail_path, category, subject, class, uploaded_by, uploaded_by_role) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                
                if ($stmt->execute([$title, $description, 'uploads/videos/' . $videoName, $thumbnailPath, $category, $subject, $class, $userId, $userRole])) {
                    $message = 'Video uploaded successfully!';
                } else {
                    $error = 'Failed to save video information';
                }
            } else {
                $error = 'Failed to upload video file';
            }
        }
    }
    
    if (isset($_POST['delete_video'])) {
        $videoId = (int)$_POST['video_id'];
        $stmt = $pdo->prepare("SELECT video_path FROM video_lessons WHERE id = ?");
        $stmt->execute([$videoId]);
        $video = $stmt->fetch();
        
        if ($video) {
            $fullPath = '../' . $video['video_path'];
            if (file_exists($fullPath)) {
                unlink($fullPath);
            }
            
            $stmt = $pdo->prepare("DELETE FROM video_lessons WHERE id = ?");
            if ($stmt->execute([$videoId])) {
                $message = 'Video deleted successfully!';
            }
        }
    }
}

$stmt = $pdo->query("SELECT * FROM video_lessons WHERE status = 'active' ORDER BY created_at DESC");
$videos = $stmt->fetchAll();

$categories = $pdo->query("SELECT DISTINCT category FROM video_lessons")->fetchAll(PDO::FETCH_COLUMN);
$subjects = $pdo->query("SELECT DISTINCT subject FROM video_lessons WHERE subject != ''")->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Video Lessons - <?php echo $schoolName ?? 'School Management'; ?></title>
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
        body { font-family: 'Segoe UI', sans-serif; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .header h1 { font-size: 24px; color: #333; }
        .btn { padding: 10px 20px; border: none; border-radius: 8px; cursor: pointer; font-size: 14px; display: inline-flex; align-items: center; gap: 8px; }
        .btn-primary { background: var(--primary); color: white; }
        .btn-danger { background: var(--danger); color: white; }
        .btn-success { background: var(--success); color: white; }
        .card { background: white; border-radius: 12px; padding: 24px; margin-bottom: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .card h2 { margin-bottom: 20px; color: #333; font-size: 18px; }
        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; margin-bottom: 6px; font-weight: 500; color: #555; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 10px 12px; border: 1px solid #ddd; border-radius: 8px; font-size: 14px; }
        .form-group textarea { min-height: 100px; resize: vertical; }
        .file-input { padding: 8px; border: 2px dashed #ddd; border-radius: 8px; text-align: center; cursor: pointer; }
        .file-input:hover { border-color: var(--primary); }
        .video-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; margin-top: 20px; }
        .video-card { background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1); transition: transform 0.2s; }
        .video-card:hover { transform: translateY(-5px); }
        .video-thumb { width: 100%; height: 180px; background: #333; position: relative; }
        .video-thumb img { width: 100%; height: 100%; object-fit: cover; }
        .video-thumb .duration { position: absolute; bottom: 8px; right: 8px; background: rgba(0,0,0,0.8); color: white; padding: 2px 6px; border-radius: 4px; font-size: 12px; }
        .video-info { padding: 16px; }
        .video-info h3 { font-size: 16px; margin-bottom: 8px; color: #333; }
        .video-info p { font-size: 13px; color: #666; margin-bottom: 8px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
        .video-meta { display: flex; justify-content: space-between; align-items: center; font-size: 12px; color: #999; }
        .video-actions { display: flex; gap: 8px; }
        .video-actions button { padding: 6px 12px; border: none; border-radius: 6px; cursor: pointer; font-size: 12px; }
        .alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; }
        .alert-success { background: #d4edda; color: #155724; }
        .alert-danger { background: #f8d7da; color: #721c24; }
        .modal { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center; }
        .modal.open { display: flex; }
        .modal-content { background: white; border-radius: 12px; padding: 24px; max-width: 600px; width: 90%; max-height: 90vh; overflow-y: auto; }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .modal-header h2 { margin: 0; }
        .close-btn { background: none; border: none; font-size: 24px; cursor: pointer; color: #666; }
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 16px; margin-bottom: 20px; }
        .stat-card { background: white; border-radius: 12px; padding: 20px; text-align: center; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .stat-card .icon { font-size: 32px; margin-bottom: 10px; color: var(--primary); }
        .stat-card .number { font-size: 28px; font-weight: bold; color: #333; }
        .stat-card .label { font-size: 13px; color: #666; }
        .filters { display: flex; gap: 12px; margin-bottom: 20px; flex-wrap: wrap; }
        .filters select, .filters input { padding: 8px 12px; border: 1px solid #ddd; border-radius: 8px; }
        .filters input { flex: 1; }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="header">
            <h1><i class="fas fa-video"></i> Video Lessons</h1>
            <button class="btn btn-primary" onclick="openUploadModal()">
                <i class="fas fa-upload"></i> Upload Video
            </button>
        </div>
        
        <div class="stats">
            <div class="stat-card">
                <div class="icon"><i class="fas fa-video"></i></div>
                <div class="number"><?php echo count($videos); ?></div>
                <div class="label">Total Videos</div>
            </div>
            <div class="stat-card">
                <div class="icon"><i class="fas fa-eye"></i></div>
                <div class="number"><?php echo array_sum(array_column($videos, 'views')); ?></div>
                <div class="label">Total Views</div>
            </div>
            <div class="stat-card">
                <div class="icon"><i class="fas fa-folder"></i></div>
                <div class="number"><?php echo count($categories); ?></div>
                <div class="label">Categories</div>
            </div>
        </div>
        
        <div class="filters">
            <input type="text" id="searchInput" placeholder="Search videos..." onkeyup="filterVideos()">
            <select id="categoryFilter" onchange="filterVideos()">
                <option value="">All Categories</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo htmlspecialchars($cat); ?>"><?php echo htmlspecialchars($cat); ?></option>
                <?php endforeach; ?>
            </select>
            <select id="subjectFilter" onchange="filterVideos()">
                <option value="">All Subjects</option>
                <?php foreach ($subjects as $sub): ?>
                    <option value="<?php echo htmlspecialchars($sub); ?>"><?php echo htmlspecialchars($sub); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="video-grid" id="videoGrid">
            <?php foreach ($videos as $video): ?>
                <div class="video-card" data-title="<?php echo strtolower(htmlspecialchars($video['title'])); ?>" data-category="<?php echo htmlspecialchars($video['category']); ?>" data-subject="<?php echo htmlspecialchars($video['subject']); ?>">
                    <div class="video-thumb">
                        <?php if ($video['thumbnail_path']): ?>
                            <img src="../<?php echo htmlspecialchars($video['thumbnail_path']); ?>" alt="<?php echo htmlspecialchars($video['title']); ?>">
                        <?php else: ?>
                            <div style="display:flex;align-items:center;justify-content:center;height:100%;color:#666;font-size:48px;">
                                <i class="fas fa-play-circle"></i>
                            </div>
                        <?php endif; ?>
                        <?php if ($video['duration']): ?>
                            <span class="duration"><?php echo gmdate("H:i:s", $video['duration']); ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="video-info">
                        <h3><?php echo htmlspecialchars($video['title']); ?></h3>
                        <p><?php echo htmlspecialchars($video['description'] ?? ''); ?></p>
                        <div class="video-meta">
                            <span><i class="fas fa-eye"></i> <?php echo $video['views']; ?> views</span>
                            <span><i class="fas fa-calendar"></i> <?php echo date('M d, Y', strtotime($video['created_at'])); ?></span>
                        </div>
                        <div class="video-actions" style="margin-top:12px;">
                            <button class="btn-primary" onclick="previewVideo(<?php echo $video['id']; ?>)"><i class="fas fa-play"></i> Preview</button>
                            <a href="<?php echo htmlspecialchars($video['video_path']); ?>" download class="btn-success" style="text-decoration:none;padding:6px 12px;border-radius:6px;color:white;"><i class="fas fa-download"></i></a>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this video?')">
                                <input type="hidden" name="video_id" value="<?php echo $video['id']; ?>">
                                <button type="submit" name="delete_video" class="btn-danger" style="padding:6px 12px;border:none;border-radius:6px;"><i class="fas fa-trash"></i></button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <?php if (empty($videos)): ?>
                <div style="grid-column:1/-1;text-align:center;padding:40px;color:#999;">
                    <i class="fas fa-video" style="font-size:48px;margin-bottom:16px;"></i>
                    <p>No videos uploaded yet. Click "Upload Video" to add your first video lesson.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="modal" id="uploadModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-upload"></i> Upload Video</h2>
                <button class="close-btn" onclick="closeUploadModal()">&times;</button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label>Video Title *</label>
                    <input type="text" name="title" required placeholder="Enter video title">
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" placeholder="Enter video description"></textarea>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                    <div class="form-group">
                        <label>Category</label>
                        <select name="category">
                            <option value="General">General</option>
                            <option value="Mathematics">Mathematics</option>
                            <option value="Science">Science</option>
                            <option value="English">English</option>
                            <option value="History">History</option>
                            <option value="Geography">Geography</option>
                            <option value="Computer">Computer</option>
                            <option value="Art">Art</option>
                            <option value="Music">Music</option>
                            <option value="Sports">Sports</option>
                            <option value="Tutorials">Tutorials</option>
                            <option value="Exam Prep">Exam Prep</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Subject</label>
                        <input type="text" name="subject" placeholder="e.g., Algebra, Biology">
                    </div>
                </div>
                <div class="form-group">
                    <label>Class/Level</label>
                    <select name="class">
                        <option value="">All Classes</option>
                        <option value="Nursery">Nursery</option>
                        <option value="KG 1">KG 1</option>
                        <option value="KG 2">KG 2</option>
                        <option value="Primary 1">Primary 1</option>
                        <option value="Primary 2">Primary 2</option>
                        <option value="Primary 3">Primary 3</option>
                        <option value="Primary 4">Primary 4</option>
                        <option value="Primary 5">Primary 5</option>
                        <option value="JSS 1">JSS 1</option>
                        <option value="JSS 2">JSS 2</option>
                        <option value="JSS 3">JSS 3</option>
                        <option value="SSS 1">SSS 1</option>
                        <option value="SSS 2">SSS 2</option>
                        <option value="SSS 3">SSS 3</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Video File *</label>
                    <input type="file" name="video_file" accept="video/*" class="file-input" required>
                    <small style="color:#666;">Supported formats: MP4, WebM, MOV (Max 500MB)</small>
                </div>
                <div class="form-group">
                    <label>Thumbnail (Optional)</label>
                    <input type="file" name="thumbnail" accept="image/*" class="file-input">
                </div>
                <div style="display:flex;gap:12px;justify-content:flex-end;margin-top:20px;">
                    <button type="button" class="btn" onclick="closeUploadModal()" style="background:#eee;color:#333;">Cancel</button>
                    <button type="submit" name="upload_video" class="btn btn-primary"><i class="fas fa-upload"></i> Upload</button>
                </div>
            </form>
        </div>
    </div>
    
    <div class="modal" id="previewModal">
        <div class="modal-content" style="max-width:900px;">
            <div class="modal-header">
                <h2 id="previewTitle">Video Preview</h2>
                <button class="close-btn" onclick="closePreviewModal()">&times;</button>
            </div>
            <video id="previewVideo" controls style="width:100%;border-radius:8px;"></video>
            <div id="previewInfo" style="margin-top:16px;"></div>
        </div>
    </div>
    
    <script>
        function openUploadModal() {
            document.getElementById('uploadModal').classList.add('open');
        }
        
        function closeUploadModal() {
            document.getElementById('uploadModal').classList.remove('open');
        }
        
        function previewVideo(id) {
            fetch('video_api.php?action=get&id=' + id)
                .then(r => r.json())
                .then(data => {
                    if (data.video) {
                        document.getElementById('previewTitle').textContent = data.video.title;
                        document.getElementById('previewVideo').src = '../' + data.video.video_path;
                        document.getElementById('previewInfo').innerHTML = '<p><strong>Description:</strong> ' + (data.video.description || 'No description') + '</p><p><strong>Views:</strong> ' + data.video.views + '</p>';
                        document.getElementById('previewModal').classList.add('open');
                        
                        fetch('video_api.php?action=increment_views&id=' + id);
                    }
                });
        }
        
        function closePreviewModal() {
            document.getElementById('previewModal').classList.remove('open');
            document.getElementById('previewVideo').pause();
        }
        
        function filterVideos() {
            const search = document.getElementById('searchInput').value.toLowerCase();
            const category = document.getElementById('categoryFilter').value;
            const subject = document.getElementById('subjectFilter').value;
            
            document.querySelectorAll('.video-card').forEach(card => {
                const matchSearch = card.dataset.title.includes(search);
                const matchCategory = !category || card.dataset.category === category;
                const matchSubject = !subject || card.dataset.subject === subject;
                
                card.style.display = (matchSearch && matchCategory && matchSubject) ? 'block' : 'none';
            });
        }
        
        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    modal.classList.remove('open');
                }
            });
        });
    </script>
</body>
</html>
