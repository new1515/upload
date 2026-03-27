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
$schoolLogo = $settings['school_logo'] ?? '';

$schoolStartTime = $settings['school_start_time'] ?? '07:30';
$periodDuration = (int)($settings['period_duration'] ?? 40);
$break1Start = $settings['break1_start'] ?? '09:30';
$break1End = $settings['break1_end'] ?? '10:00';
$break2Start = $settings['break2_start'] ?? '12:00';
$break2End = $settings['break2_end'] ?? '12:40';
$schoolEndTime = $settings['school_end_time'] ?? '14:30';

function generateTimeSlots($start, $duration, $break1Start, $break1End, $break2Start, $break2End, $end) {
    $slots = [];
    $periodNames = ['1st', '2nd', '3rd', '4th', '5th', '6th', '7th', '8th', '9th', '10th', '11th', '12th'];
    $periodCount = 0;
    
    $currentTime = strtotime($start);
    $endTime = strtotime($end);
    
    while ($currentTime < $endTime) {
        $periodStart = date('H:i', $currentTime);
        $periodEnd = date('H:i', $currentTime + ($duration * 60));
        $periodEndTime = strtotime($periodEnd);
        
        if ($periodEndTime > $endTime) {
            break;
        }
        
        $isBreak1 = ($periodStart >= $break1Start && $periodStart < $break1End) || 
                    ($periodEndTime > strtotime($break1Start) && $currentTime < strtotime($break1End));
        $isBreak2 = ($periodStart >= $break2Start && $periodStart < $break2End) || 
                    ($periodEndTime > strtotime($break2Start) && $currentTime < strtotime($break2End));
        
        if ($isBreak1 || $isBreak2) {
            $breakName = $isBreak1 ? 'Morning Break' : 'Lunch Break';
            $slots[] = [
                'start' => $isBreak1 ? $break1Start : $break2Start,
                'end' => $isBreak1 ? $break1End : $break2End,
                'name' => $breakName,
                'is_break' => true
            ];
            
            $currentTime = strtotime($isBreak1 ? $break1End : $break2End);
            continue;
        }
        
        $periodName = isset($periodNames[$periodCount]) ? $periodNames[$periodCount] . ' Period' : ($periodCount + 1) . 'th Period';
        $slots[] = [
            'start' => $periodStart,
            'end' => $periodEnd,
            'name' => $periodName,
            'is_break' => false
        ];
        
        $periodCount++;
        $currentTime = strtotime($periodEnd);
        
        if ($currentTime >= strtotime($break1Start) && $currentTime < strtotime($break1End)) {
            $currentTime = strtotime($break1End);
        } elseif ($currentTime >= strtotime($break2Start) && $currentTime < strtotime($break2End)) {
            $currentTime = strtotime($break2End);
        }
    }
    
    return $slots;
}

$timeSlots = generateTimeSlots($schoolStartTime, $periodDuration, $break1Start, $break1End, $break2Start, $break2End, $schoolEndTime);

$message = '';
$selectedClass = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_slot'])) {
        $class_id = sanitize($_POST['class_id']);
        $subject_id = sanitize($_POST['subject_id']);
        $teacher_id = !empty($_POST['teacher_id']) ? sanitize($_POST['teacher_id']) : NULL;
        $day = sanitize($_POST['day_of_week']);
        $start_time = sanitize($_POST['start_time']);
        $end_time = sanitize($_POST['end_time']);
        
        $checkStmt = $pdo->prepare("SELECT id FROM timetable WHERE class_id = ? AND day_of_week = ? AND start_time = ?");
        $checkStmt->execute([$class_id, $day, $start_time]);
        if ($checkStmt->fetch()) {
            $message = '<div class="error-msg" style="background: #fee; color: #c00; padding: 12px; border-radius: 10px; margin-bottom: 15px;">A class already exists at this time slot!</div>';
        } else {
            $stmt = $pdo->prepare("INSERT INTO timetable (class_id, subject_id, teacher_id, day_of_week, start_time, end_time) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$class_id, $subject_id, $teacher_id, $day, $start_time, $end_time]);
            $newId = $pdo->lastInsertId();
            logActivity($pdo, 'create', "Added timetable slot for class ID: $class_id", $_SESSION['admin_username'] ?? 'admin', 'timetable', $newId);
            $message = '<div class="success-msg">Timetable slot added successfully!</div>';
            $selectedClass = $class_id;
        }
    }
    
    if (isset($_POST['update_slot'])) {
        $id = sanitize($_POST['id']);
        $subject_id = sanitize($_POST['subject_id']);
        $teacher_id = !empty($_POST['teacher_id']) ? sanitize($_POST['teacher_id']) : NULL;
        $start_time = sanitize($_POST['start_time']);
        $end_time = sanitize($_POST['end_time']);
        
        $stmt = $pdo->prepare("UPDATE timetable SET subject_id = ?, teacher_id = ?, start_time = ?, end_time = ? WHERE id = ?");
        $stmt->execute([$subject_id, $teacher_id, $start_time, $end_time, $id]);
        logActivity($pdo, 'update', "Updated timetable slot ID: $id", $_SESSION['admin_username'] ?? 'admin', 'timetable', $id);
        $message = '<div class="success-msg">Timetable slot updated successfully!</div>';
    }
    
    if (isset($_POST['delete_slot'])) {
        $id = sanitize($_POST['id']);
        $stmt = $pdo->prepare("DELETE FROM timetable WHERE id = ?");
        $stmt->execute([$id]);
        logActivity($pdo, 'delete', "Deleted timetable slot ID: $id", $_SESSION['admin_username'] ?? 'admin', 'timetable', $id);
        $message = '<div class="success-msg">Timetable slot deleted successfully!</div>';
    }
}

$classes = $pdo->query("SELECT * FROM classes ORDER BY class_name, section")->fetchAll();
$subjects = $pdo->query("SELECT * FROM subjects ORDER BY subject_name")->fetchAll();
$teachers = $pdo->query("SELECT * FROM teachers ORDER BY name")->fetchAll();

$timetable = [];
if ($selectedClass) {
    $stmt = $pdo->prepare("SELECT t.*, sub.subject_name, c.class_name, c.section, te.name as teacher_name 
        FROM timetable t 
        LEFT JOIN subjects sub ON t.subject_id = sub.id 
        LEFT JOIN classes c ON t.class_id = c.id 
        LEFT JOIN teachers te ON t.teacher_id = te.id 
        WHERE t.class_id = ? 
        ORDER BY FIELD(t.day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'), t.start_time");
    $stmt->execute([$selectedClass]);
    $timetable = $stmt->fetchAll();
}

$days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Timetable - School Management System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        .timetable-grid { display: grid; grid-template-columns: 100px repeat(5, 1fr); gap: 1px; background: #ddd; border-radius: 10px; overflow: hidden; margin-top: 20px; }
        .timetable-header { background: #4a90e2; color: white; padding: 12px 8px; text-align: center; font-weight: 600; font-size: 12px; }
        .timetable-time { background: #f8f9fa; padding: 10px 8px; text-align: center; font-size: 11px; font-weight: 600; }
        .timetable-cell { background: white; padding: 8px; min-height: 60px; font-size: 11px; position: relative; }
        .timetable-cell .slot { background: #e8f4fd; border-left: 3px solid #4a90e2; padding: 6px 8px; margin-bottom: 5px; border-radius: 4px; position: relative; }
        .timetable-cell .slot .actions { display: none; position: absolute; top: 2px; right: 2px; gap: 3px; }
        .timetable-cell .slot:hover .actions { display: flex; }
        .timetable-cell .slot .actions button { padding: 2px 6px; font-size: 9px; border: none; border-radius: 3px; cursor: pointer; }
        .timetable-cell .slot .actions .edit-btn { background: #4a90e2; color: white; }
        .timetable-cell .slot .actions .delete-btn { background: #e74c3c; color: white; }
        .break-row { background: #fff3cd; }
        .filter-section { background: white; padding: 20px; border-radius: 15px; margin-bottom: 20px; }
        .modify-section { background: white; padding: 20px; border-radius: 15px; margin-top: 20px; border: 2px solid #4a90e2; }
        .modify-section h3 { color: #4a90e2; margin-bottom: 15px; }
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; }
        .empty-slot { background: #f8f9fa; border: 1px dashed #ddd; min-height: 40px; }
    </style>
</head>
<body>
    <button class="mobile-menu-toggle">
        <i class="fas fa-bars"></i>
    </button>
    <div class="sidebar-overlay"></div>
    <nav class="sidebar">
        <div class="sidebar-brand"><i class="fas fa-graduation-cap"></i> School Admin</div>
        <ul class="sidebar-menu">
            <li><a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
            <li><a href="students.php"><i class="fas fa-user-graduate"></i> Students</a></li>
            <li><a href="parents.php"><i class="fas fa-users"></i> Parents</a></li>
            <li><a href="teachers.php"><i class="fas fa-chalkboard-teacher"></i> Teachers</a></li>
            <li><a href="subjects.php"><i class="fas fa-book"></i> Subjects</a></li>
            <li><a href="classes.php"><i class="fas fa-school"></i> Classes</a></li>
            <li><a href="attendance.php"><i class="fas fa-calendar-check"></i> Attendance</a></li>
            <li><a href="timetable.php" class="active"><i class="fas fa-clock"></i> Timetable</a></li>
            <li><a href="results.php"><i class="fas fa-chart-line"></i> Results</a></li>
            <li><a href="reportcards.php"><i class="fas fa-file-alt"></i> Report Cards</a></li>
            <li><a href="calendar.php"><i class="fas fa-calendar-alt"></i> Calendar</a></li>
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
        
        <div class="header">
            <h1><i class="fas fa-clock"></i> GES Timetable</h1>
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
                <a href="settings.php"><i class="fas fa-cog"></i></a>
                <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
        
        <div class="content">
            <?php echo $message; ?>
            
            <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 15px 20px; border-radius: 10px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                <div>
                    <strong><i class="fas fa-clock"></i> School Hours:</strong> 
                    <?php echo date('h:i A', strtotime($schoolStartTime)); ?> - <?php echo date('h:i A', strtotime($schoolEndTime)); ?> | 
                    <strong>Period:</strong> <?php echo $periodDuration; ?> min | 
                    <strong>Breaks:</strong> <?php echo date('h:i', strtotime($break1Start)); ?>-<?php echo date('h:i A', strtotime($break1End)); ?>, 
                    <?php echo date('h:i', strtotime($break2Start)); ?>-<?php echo date('h:i A', strtotime($break2End)); ?>
                </div>
                <a href="settings.php#school-hours" style="background: rgba(255,255,255,0.2); color: white; padding: 8px 15px; border-radius: 5px; text-decoration: none; font-size: 13px;">
                    <i class="fas fa-cog"></i> Change Hours
                </a>
            </div>
            
            <div class="filter-section">
                <h2 style="margin-bottom: 15px;"><i class="fas fa-filter"></i> Select Class</h2>
                <form method="GET" style="display: flex; gap: 15px; flex-wrap: wrap; align-items: flex-end;">
                    <div class="form-group" style="flex: 1; min-width: 250px; margin: 0;">
                        <label>Class</label>
                        <select name="class_id" required onchange="this.form.submit()">
                            <option value="">-- Select Class --</option>
                            <?php foreach ($classes as $c): ?>
                            <option value="<?php echo $c['id']; ?>" <?php echo $selectedClass == $c['id'] ? 'selected' : ''; ?>>
                                <?php echo $c['class_name'] . ' - Section ' . $c['section']; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </form>
            </div>
            
            <?php if ($selectedClass): ?>
            
            <div style="display: flex; gap: 10px; margin-bottom: 10px;">
                <button class="btn btn-primary" onclick="document.getElementById('modifySection').style.display='block'">
                    <i class="fas fa-edit"></i> Modify Timetable
                </button>
            </div>
            
            <div id="modifySection" class="modify-section" style="display: none;">
                <h3><i class="fas fa-plus-circle"></i> Add / Edit Timetable Slot</h3>
                <form method="POST" class="form-grid">
                    <input type="hidden" name="class_id" value="<?php echo $selectedClass; ?>">
                    <div class="form-group">
                        <label>Day</label>
                        <select name="day_of_week" required>
                            <?php foreach ($days as $d): ?>
                            <option value="<?php echo $d; ?>"><?php echo $d; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Subject</label>
                        <select name="subject_id" required>
                            <?php foreach ($subjects as $s): ?>
                            <option value="<?php echo $s['id']; ?>"><?php echo $s['subject_name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Teacher (Optional)</label>
                        <select name="teacher_id">
                            <option value="">-- Select Teacher --</option>
                            <?php foreach ($teachers as $t): ?>
                            <option value="<?php echo $t['id']; ?>"><?php echo $t['name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Start Time</label>
                        <input type="time" name="start_time" value="<?php echo $schoolStartTime; ?>" required>
                    </div>
                    <div class="form-group">
                        <label>End Time</label>
                        <input type="time" name="end_time" value="<?php echo date('H:i', strtotime($schoolStartTime) + ($periodDuration * 60)); ?>" required>
                    </div>
                    <div class="form-group" style="display: flex; align-items: flex-end;">
                        <button type="submit" name="add_slot" class="btn btn-success" style="width: 100%;">
                            <i class="fas fa-plus"></i> Add Slot
                        </button>
                    </div>
                </form>
                
                <?php if (!empty($timetable)): ?>
                <h4 style="margin-top: 25px; margin-bottom: 15px; color: #2c3e50;"><i class="fas fa-list"></i> Current Slots (Click Edit to modify)</h4>
                <div style="max-height: 200px; overflow-y: auto;">
                    <table style="width: 100%; border-collapse: collapse; font-size: 12px;">
                        <thead style="background: #f8f9fa;">
                            <tr>
                                <th style="padding: 8px; text-align: left;">Day</th>
                                <th style="padding: 8px; text-align: left;">Time</th>
                                <th style="padding: 8px; text-align: left;">Subject</th>
                                <th style="padding: 8px; text-align: left;">Teacher</th>
                                <th style="padding: 8px; text-align: center;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($timetable as $slot): ?>
                            <tr style="border-bottom: 1px solid #eee;">
                                <td style="padding: 8px;"><?php echo $slot['day_of_week']; ?></td>
                                <td style="padding: 8px;"><?php echo date('h:i A', strtotime($slot['start_time'])) . ' - ' . date('h:i A', strtotime($slot['end_time'])); ?></td>
                                <td style="padding: 8px;"><?php echo $slot['subject_name']; ?></td>
                                <td style="padding: 8px;"><?php echo $slot['teacher_name'] ?? '-'; ?></td>
                                <td style="padding: 8px; text-align: center;">
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this slot?');">
                                        <input type="hidden" name="id" value="<?php echo $slot['id']; ?>">
                                        <button type="submit" name="delete_slot" class="btn btn-danger btn-sm">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
            
            <?php
            $gridTimetable = [];
            foreach ($timetable as $slot) {
                $key = $slot['day_of_week'] . '_' . $slot['start_time'];
                $gridTimetable[$key] = $slot;
            }
            ?>
            
            <div class="table-container">
                <div class="table-header">
                    <h3><i class="fas fa-table"></i> Weekly Timetable View</h3>
                </div>
                <div class="timetable-grid">
                    <div class="timetable-header">Time</div>
                    <?php foreach ($days as $day): ?>
                    <div class="timetable-header"><?php echo $day; ?></div>
                    <?php endforeach; ?>
                    
                    <?php foreach ($timeSlots as $slot): ?>
                    <div class="timetable-time <?php echo $slot['is_break'] ? 'break-row' : ''; ?>">
                        <strong><?php echo $slot['name']; ?></strong><br>
                        <small><?php echo date('h:i', strtotime($slot['start'])); ?> - <?php echo date('h:i A', strtotime($slot['end'])); ?></small>
                    </div>
                    <?php foreach ($days as $day): ?>
                    <?php
                    $key = $day . '_' . $slot['start'];
                    $cellSlot = $gridTimetable[$key] ?? null;
                    ?>
                    <div class="timetable-cell <?php echo $slot['is_break'] ? 'break-row' : ''; ?>">
                        <?php if ($slot['is_break']): ?>
                        <div style="text-align: center; color: #856404; font-size: 10px;">
                            <i class="fas fa-coffee"></i> BREAK
                        </div>
                        <?php elseif ($cellSlot): ?>
                        <div class="slot">
                            <div class="subject" style="font-weight: 600; color: #4a90e2;"><?php echo $cellSlot['subject_name']; ?></div>
                            <?php if ($cellSlot['teacher_name']): ?>
                            <div style="color: #666; font-size: 10px;"><i class="fas fa-user"></i> <?php echo $cellSlot['teacher_name']; ?></div>
                            <?php endif; ?>
                            <div class="actions">
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this slot?');">
                                    <input type="hidden" name="id" value="<?php echo $cellSlot['id']; ?>">
                                    <button type="submit" name="delete_slot" class="delete-btn"><i class="fas fa-trash"></i></button>
                                </form>
                            </div>
                        </div>
                        <?php else: ?>
                        <div style="color: #ccc; text-align: center; font-size: 10px;">-</div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <?php elseif (!$selectedClass): ?>
            <div class="table-container" style="text-align: center; padding: 60px;">
                <i class="fas fa-clock" style="font-size: 50px; color: #ddd; margin-bottom: 20px;"></i>
                <p style="color: #888;">Select a class above to view or modify its timetable.</p>
            </div>
            <?php endif; ?>
        </div>
    </main>
    
    <script src="../assets/js/script.js"></script>
</body>
</html>
