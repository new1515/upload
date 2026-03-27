<?php
require_once '../config/database.php';

$settings = [];
$stmt = $pdo->query("SELECT * FROM school_settings");
while ($row = $stmt->fetch()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
$schoolName = $settings['school_name'] ?? 'School Management System';
$schoolLogo = $settings['school_logo'] ?? '';

if (!isLoggedIn()) {
    redirect('../login.php');
}

$message = '';

if (isset($_POST['add_event'])) {
    $title = sanitize($_POST['title']);
    $description = sanitize($_POST['description']);
    $event_type = sanitize($_POST['event_type']);
    $start_date = sanitize($_POST['start_date']);
    $end_date = !empty($_POST['end_date']) ? sanitize($_POST['end_date']) : NULL;
    
    $stmt = $pdo->prepare("INSERT INTO academic_calendar (title, description, event_type, start_date, end_date) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$title, $description, $event_type, $start_date, $end_date]);
    $newId = $pdo->lastInsertId();
    logActivity($pdo, 'create', "Added calendar event: $title", $_SESSION['admin_username'] ?? 'admin', 'calendar', $newId);
    $message = '<div class="success-msg">Event added successfully!</div>';
}

if (isset($_POST['update_event'])) {
    $id = sanitize($_POST['id']);
    $title = sanitize($_POST['title']);
    $description = sanitize($_POST['description']);
    $event_type = sanitize($_POST['event_type']);
    $start_date = sanitize($_POST['start_date']);
    $end_date = !empty($_POST['end_date']) ? sanitize($_POST['end_date']) : NULL;
    
    $stmt = $pdo->prepare("UPDATE academic_calendar SET title = ?, description = ?, event_type = ?, start_date = ?, end_date = ? WHERE id = ?");
    $stmt->execute([$title, $description, $event_type, $start_date, $end_date, $id]);
    logActivity($pdo, 'update', "Updated calendar event: $title (ID: $id)", $_SESSION['admin_username'] ?? 'admin', 'calendar', $id);
    $message = '<div class="success-msg">Event updated successfully!</div>';
}

if (isset($_GET['delete'])) {
    $id = sanitize($_GET['delete']);
    $stmt = $pdo->prepare("SELECT title FROM academic_calendar WHERE id = ?");
    $stmt->execute([$id]);
    $event = $stmt->fetch();
    $eventTitle = $event['title'] ?? 'Unknown';
    $stmt = $pdo->prepare("DELETE FROM academic_calendar WHERE id = ?");
    $stmt->execute([$id]);
    logActivity($pdo, 'delete', "Deleted calendar event: $eventTitle (ID: $id)", $_SESSION['admin_username'] ?? 'admin', 'calendar', $id);
    $message = '<div class="success-msg">Event deleted successfully!</div>';
}

$events = $pdo->query("SELECT * FROM academic_calendar ORDER BY start_date ASC")->fetchAll();

$eventTypes = [
    'term' => ['label' => 'Term', 'color' => '#4a90e2', 'icon' => 'fa-calendar'],
    'holiday' => ['label' => 'Holiday', 'color' => '#e74c3c', 'icon' => 'fa-umbrella-beach'],
    'exam' => ['label' => 'Exam', 'color' => '#f39c12', 'icon' => 'fa-file-alt'],
    'event' => ['label' => 'Event', 'color' => '#27ae60', 'icon' => 'fa-calendar-star'],
    'meeting' => ['label' => 'Meeting', 'color' => '#9b59b6', 'icon' => 'fa-users'],
    'other' => ['label' => 'Other', 'color' => '#95a5a6', 'icon' => 'fa-calendar-day']
];

$currentMonth = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('m');
$currentYear = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');

$prevMonth = $currentMonth == 1 ? 12 : $currentMonth - 1;
$prevYear = $currentMonth == 1 ? $currentYear - 1 : $currentYear;
$nextMonth = $currentMonth == 12 ? 1 : $currentMonth + 1;
$nextYear = $currentMonth == 12 ? $currentYear + 1 : $currentYear;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Academic Calendar - School Management System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        .calendar-container {
            display: grid;
            grid-template-columns: 280px 1fr;
            gap: 25px;
        }
        .calendar-sidebar {
            background: #fff;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        .calendar-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #eee;
        }
        .calendar-nav h3 {
            font-size: 18px;
            color: #2c3e50;
        }
        .calendar-nav a {
            color: #4a90e2;
            text-decoration: none;
            padding: 5px 10px;
            border-radius: 5px;
            transition: background 0.3s;
        }
        .calendar-nav a:hover {
            background: #f0f4f8;
        }
        .event-list {
            list-style: none;
            max-height: 500px;
            overflow-y: auto;
        }
        .event-list li {
            padding: 12px;
            margin-bottom: 10px;
            border-radius: 8px;
            background: #f8f9fa;
            border-left: 4px solid;
            cursor: pointer;
            transition: transform 0.2s;
        }
        .event-list li:hover {
            transform: translateX(5px);
        }
        .event-list li .event-title {
            font-weight: 600;
            font-size: 14px;
            margin-bottom: 4px;
        }
        .event-list li .event-date {
            font-size: 12px;
            color: #666;
        }
        .calendar-main {
            background: #fff;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 5px;
        }
        .calendar-day-header {
            text-align: center;
            font-weight: 600;
            padding: 10px;
            color: #666;
            font-size: 12px;
        }
        .calendar-day {
            min-height: 80px;
            border: 1px solid #eee;
            border-radius: 8px;
            padding: 5px;
            font-size: 13px;
        }
        .calendar-day.other-month {
            background: #f8f9fa;
            color: #ccc;
        }
        .calendar-day.today {
            background: rgba(74, 144, 226, 0.1);
            border-color: #4a90e2;
        }
        .calendar-day .day-number {
            font-weight: 600;
            margin-bottom: 5px;
        }
        .day-events {
            font-size: 10px;
        }
        .day-events span {
            display: block;
            padding: 2px 5px;
            margin-bottom: 2px;
            border-radius: 3px;
            color: white;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .legend {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        .legend-item {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 12px;
        }
        .legend-color {
            width: 15px;
            height: 15px;
            border-radius: 3px;
        }
        .type-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 15px;
            color: white;
            font-size: 12px;
            font-weight: 500;
        }
        @media (max-width: 992px) {
            .calendar-container {
                grid-template-columns: 1fr;
            }
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
            <i class="fas fa-graduation-cap"></i>
            School Admin
        </div>
        <ul class="sidebar-menu">
            <li><a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
            <li><a href="students.php"><i class="fas fa-user-graduate"></i> Students</a></li>
            <li><a href="parents.php"><i class="fas fa-users"></i> Parents</a></li>
            <li><a href="teachers.php"><i class="fas fa-chalkboard-teacher"></i> Teachers</a></li>
            <li><a href="subjects.php"><i class="fas fa-book"></i> Subjects</a></li>
            <li><a href="classes.php"><i class="fas fa-school"></i> Classes</a></li>
            <li><a href="results.php"><i class="fas fa-chart-line"></i> Results</a></li>
            <li><a href="reportcards.php"><i class="fas fa-file-alt"></i> Report Cards</a></li>
            <li><a href="calendar.php" class="active"><i class="fas fa-calendar-alt"></i> Calendar</a></li>
            <li><a href="announcements.php"><i class="fas fa-bullhorn"></i> Announcements</a></li>
            <li><a href="fees.php"><i class="fas fa-money-bill"></i> School Fees</a></li>
            <li><a href="chatbot.php"><i class="fas fa-robot"></i> AI Chatbot</a></li>
            <li><a href="chatbot_qa.php"><i class="fas fa-database"></i> Chatbot Q&A</a></li>
            <li><a href="lessons.php"><i class="fas fa-book-open"></i> Lessons</a></li>
            <li><a href="history.php"><i class="fas fa-history"></i> History</a></li>
            <li><a href="admins.php"><i class="fas fa-user-shield"></i> Manage Admins</a></li>
            <li><a href="validate.php"><i class="fas fa-clipboard-check"></i> Validate System</a></li>
            <li><a href="user_access.php"><i class="fas fa-key"></i> User Access</a></li>
            <li><a href="reset_password.php"><i class="fas fa-key"></i> Reset Password</a></li>
            <li><a href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
            <li><a href="../logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </nav>
    
    <main class="main-content">
        <div class="header">
            <h1><i class="fas fa-calendar-alt"></i> Academic Calendar</h1>
            <div class="header-right">
                <div class="theme-switcher">
                    <button class="theme-btn active" data-theme="blue" title="Blue"></button>
                    <button class="theme-btn" data-theme="green" title="Green"></button>
                    <button class="theme-btn" data-theme="purple" title="Purple"></button>
                    <button class="theme-btn" data-theme="red" title="Red"></button>
                    <button class="theme-btn" data-theme="orange" title="Orange"></button>
                    <button class="theme-btn" data-theme="teal" title="Teal"></button>
                    <button class="theme-btn" data-theme="pink" title="Pink"></button>
                    <button class="theme-btn" data-theme="gold" title="Gold"></button>
                    <button class="theme-btn" data-theme="dark" title="Dark"></button>
                    <button class="theme-btn" data-theme="ocean" title="Ocean"></button>
                    <button class="theme-btn" data-theme="sky" title="Sky"></button>
                    <button class="theme-btn" data-theme="emerald" title="Emerald"></button>
                    <button class="theme-btn" data-theme="violet" title="Violet"></button>
                </div>
                <a href="settings.php" title="Settings"><i class="fas fa-cog"></i></a>
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
            <?php echo $message; ?>
            
            <div class="table-container" style="margin-bottom: 25px;">
                <button class="btn btn-primary modal-trigger" data-modal="addModal">
                    <i class="fas fa-plus"></i> Add Event
                </button>
            </div>
            
            <div class="calendar-container">
                <div class="calendar-sidebar">
                    <div class="calendar-nav">
                        <a href="?month=<?php echo $prevMonth; ?>&year=<?php echo $prevYear; ?>"><i class="fas fa-chevron-left"></i></a>
                        <h3><?php echo date('F Y', mktime(0, 0, 0, $currentMonth, 1, $currentYear)); ?></h3>
                        <a href="?month=<?php echo $nextMonth; ?>&year=<?php echo $nextYear; ?>"><i class="fas fa-chevron-right"></i></a>
                    </div>
                    <ul class="event-list">
                        <?php 
                        $monthEvents = array_filter($events, function($e) use ($currentMonth, $currentYear) {
                            return date('m', strtotime($e['start_date'])) == $currentMonth && date('Y', strtotime($e['start_date'])) == $currentYear;
                        });
                        if (empty($monthEvents)): ?>
                            <li style="background: #f8f9fa; border-color: #ccc;">
                                <span class="event-title">No events this month</span>
                            </li>
                        <?php else: ?>
                            <?php foreach ($monthEvents as $event): ?>
                            <li style="border-color: <?php echo $eventTypes[$event['event_type']]['color']; ?>;">
                                <div class="event-title"><?php echo $event['title']; ?></div>
                                <div class="event-date">
                                    <i class="fas fa-calendar"></i> 
                                    <?php echo date('M d', strtotime($event['start_date'])); ?>
                                    <?php echo $event['end_date'] ? ' - ' . date('M d', strtotime($event['end_date'])) : ''; ?>
                                </div>
                                <span class="type-badge" style="background: <?php echo $eventTypes[$event['event_type']]['color']; ?>; margin-top: 5px;">
                                    <?php echo $eventTypes[$event['event_type']]['label']; ?>
                                </span>
                            </li>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </ul>
                </div>
                
                <div class="calendar-main">
                    <div class="calendar-grid">
                        <?php
                        $days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
                        foreach ($days as $day) {
                            echo '<div class="calendar-day-header">' . $day . '</div>';
                        }
                        
                        $firstDay = date('w', mktime(0, 0, 0, $currentMonth, 1, $currentYear));
                        $daysInMonth = date('t', mktime(0, 0, 0, $currentMonth, 1, $currentYear));
                        $daysInPrevMonth = date('t', mktime(0, 0, 0, $currentMonth - 1, 1, $currentYear));
                        
                        for ($i = $firstDay - 1; $i >= 0; $i--) {
                            $day = $daysInPrevMonth - $i;
                            echo '<div class="calendar-day other-month"><div class="day-number">' . $day . '</div></div>';
                        }
                        
                        for ($day = 1; $day <= $daysInMonth; $day++) {
                            $isToday = $day == date('j') && $currentMonth == date('m') && $currentYear == date('Y') ? 'today' : '';
                            echo '<div class="calendar-day ' . $isToday . '"><div class="day-number">' . $day . '</div>';
                            echo '<div class="day-events">';
                            
                            foreach ($events as $event) {
                                $eventStart = strtotime($event['start_date']);
                                $eventEnd = $event['end_date'] ? strtotime($event['end_date']) : $eventStart;
                                $currentDate = mktime(0, 0, 0, $currentMonth, $day, $currentYear);
                                
                                if ($currentDate >= $eventStart && $currentDate <= $eventEnd) {
                                    echo '<span style="background: ' . $eventTypes[$event['event_type']]['color'] . ';" title="' . $event['title'] . '">' . $event['title'] . '</span>';
                                }
                            }
                            
                            echo '</div></div>';
                        }
                        
                        $remaining = 42 - ($firstDay + $daysInMonth);
                        for ($day = 1; $day <= $remaining; $day++) {
                            echo '<div class="calendar-day other-month"><div class="day-number">' . $day . '</div></div>';
                        }
                        ?>
                    </div>
                    
                    <div class="legend">
                        <?php foreach ($eventTypes as $type => $info): ?>
                        <div class="legend-item">
                            <div class="legend-color" style="background: <?php echo $info['color']; ?>;"></div>
                            <span><?php echo $info['label']; ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <div class="table-container" style="margin-top: 25px;">
                <div class="table-header">
                    <h2>All Events</h2>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Title</th>
                            <th>Type</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($events as $event): ?>
                        <tr>
                            <td><?php echo $event['id']; ?></td>
                            <td><?php echo $event['title']; ?></td>
                            <td><span class="type-badge" style="background: <?php echo $eventTypes[$event['event_type']]['color']; ?>;"><?php echo $eventTypes[$event['event_type']]['label']; ?></span></td>
                            <td><?php echo date('M d, Y', strtotime($event['start_date'])); ?></td>
                            <td><?php echo $event['end_date'] ? date('M d, Y', strtotime($event['end_date'])) : '-'; ?></td>
                            <td class="action-btns">
                                <button class="edit modal-trigger" data-modal="editModal<?php echo $event['id']; ?>">Edit</button>
                                <a href="?delete=<?php echo $event['id']; ?>" class="delete delete-btn">Delete</a>
                            </td>
                        </tr>
                        
                        <div id="editModal<?php echo $event['id']; ?>" class="modal">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h3>Edit Event</h3>
                                    <button class="modal-close">&times;</button>
                                </div>
                                <form method="POST">
                                    <input type="hidden" name="id" value="<?php echo $event['id']; ?>">
                                    <div class="form-group">
                                        <label>Title</label>
                                        <input type="text" name="title" value="<?php echo $event['title']; ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Description</label>
                                        <textarea name="description" rows="3"><?php echo $event['description']; ?></textarea>
                                    </div>
                                    <div class="form-group">
                                        <label>Event Type</label>
                                        <select name="event_type" required>
                                            <?php foreach ($eventTypes as $type => $info): ?>
                                                <option value="<?php echo $type; ?>" <?php echo $event['event_type'] == $type ? 'selected' : ''; ?>><?php echo $info['label']; ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Start Date</label>
                                        <input type="date" name="start_date" value="<?php echo $event['start_date']; ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label>End Date</label>
                                        <input type="date" name="end_date" value="<?php echo $event['end_date']; ?>">
                                    </div>
                                    <button type="submit" name="update_event" class="btn btn-primary" style="width: 100%;">Update Event</button>
                                </form>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
    
    <div id="addModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Add New Event</h3>
                <button class="modal-close">&times;</button>
            </div>
            <form method="POST">
                <div class="form-group">
                    <label>Title</label>
                    <input type="text" name="title" required>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" rows="3"></textarea>
                </div>
                <div class="form-group">
                    <label>Event Type</label>
                    <select name="event_type" required>
                        <?php foreach ($eventTypes as $type => $info): ?>
                            <option value="<?php echo $type; ?>"><?php echo $info['label']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Start Date</label>
                    <input type="date" name="start_date" required>
                </div>
                <div class="form-group">
                    <label>End Date (optional)</label>
                    <input type="date" name="end_date">
                </div>
                <button type="submit" name="add_event" class="btn btn-primary" style="width: 100%;">Add Event</button>
            </form>
        </div>
    </div>
    
    <script src="../assets/js/script.js"></script>
</body>
</html>
