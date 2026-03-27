<?php
require_once 'config/database.php';

if (!isset($_SESSION['teacher_id'])) {
    die('Unauthorized');
}

$classId = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;

if (!$classId) {
    echo '<p style="color: #999; text-align: center;">Select a class to see students</p>';
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM students WHERE class_id = ? ORDER BY name");
$stmt->execute([$classId]);
$students = $stmt->fetchAll();

if (empty($students)) {
    echo '<p style="color: #999; text-align: center;">No students found in this class</p>';
    exit;
}
?>

<h4 style="margin: 20px 0 15px; color: #11998e;"><i class="fas fa-list"></i> Enter Scores</h4>

<table class="score-table">
    <thead>
        <tr>
            <th>#</th>
            <th>Student Name</th>
            <th>Score</th>
            <th>Remark</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($students as $i => $student): ?>
        <tr>
            <td><?php echo $i + 1; ?></td>
            <td><?php echo htmlspecialchars($student['name']); ?></td>
            <td>
                <input type="number" name="scores[<?php echo $student['id']; ?>][score]" min="0" placeholder="Score">
            </td>
            <td>
                <input type="text" name="scores[<?php echo $student['id']; ?>][remark]" placeholder="Optional remark">
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<button type="submit" name="save_test" class="btn btn-success" style="width: 100%; margin-top: 20px; padding: 15px;">
    <i class="fas fa-save"></i> Save Test Results
</button>
