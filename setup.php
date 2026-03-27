<?php
/**
 * First-Time Setup Script
 * Generates secure random passwords for default accounts
 * Run ONCE after database import, then DELETE this file!
 */

$host = 'localhost';
$dbname = 'school_php_ai_db';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

echo "========================================\n";
echo "   School Management System Setup\n";
echo "========================================\n\n";

function generatePassword($length = 12) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%';
    $password = '';
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $password;
}

echo "Generating new passwords...\n\n";

$accounts = [
    ['username' => 'admin', 'role' => 'Admin', 'email' => 'admin@school.edu'],
    ['username' => 'superadmin', 'role' => 'Super Admin', 'email' => 'superadmin@school.edu'],
];

$newPasswords = [];

foreach ($accounts as $account) {
    $newPassword = generatePassword(12);
    $hashedPassword = md5($newPassword);
    
    $stmt = $pdo->prepare("UPDATE admins SET password = ? WHERE username = ?");
    $stmt->execute([$hashedPassword, $account['username']]);
    
    $newPasswords[$account['username']] = [
        'password' => $newPassword,
        'role' => $account['role'],
        'email' => $account['email']
    ];
    
    echo "✓ Updated password for: " . $account['username'] . " (" . $account['role'] . ")\n";
}

echo "\n";
echo "========================================\n";
echo "   NEW LOGIN CREDENTIALS\n";
echo "========================================\n\n";

foreach ($newPasswords as $username => $data) {
    echo "📋 " . $data['role'] . " (" . $username . ")\n";
    echo "   Password: " . $data['password'] . "\n";
    echo "   Email: " . $data['email'] . "\n\n";
}

echo "========================================\n";
echo "   IMPORTANT: SAVE THESE CREDENTIALS!\n";
echo "========================================\n\n";

echo "⚠️  Next steps:\n";
echo "1. Write down the passwords above\n";
echo "2. Delete this file (setup.php) from your server\n";
echo "3. Delete the optimized SQL file from public folder\n";
echo "4. Keep your credentials safe!\n\n";

echo "✅ Setup complete!\n";
?>
