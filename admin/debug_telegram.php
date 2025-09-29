<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../config/database.php';
require_once '../config.php';
require_once '../includes/functions.php';

echo "<h2>🔍 Debug Telegram Permissions</h2>";

// Test different roles
$test_roles = ['admin', 'manager', 'user'];

foreach ($test_roles as $role) {
    $_SESSION['user_id'] = 1;
    $_SESSION['role'] = $role;
    $_SESSION['username'] = 'test_' . $role;
    
    echo "<h3>👤 Testing Role: <strong>{$role}</strong></h3>";
    
    // Check permission
    $has_permission = in_array($_SESSION['role'], ['admin', 'manager']);
    echo "Permission check: " . ($has_permission ? '✅ ผ่าน' : '❌ ไม่ผ่าน') . "<br>";
    
    if ($has_permission) {
        // Test telegram config access
        try {
            $telegram_config = getTelegramConfig();
            echo "Telegram config access: ✅ ใช้ได้<br>";
            echo "Telegram enabled: " . ($telegram_config['enabled'] ? 'Yes' : 'No') . "<br>";
        } catch (Exception $e) {
            echo "Telegram config access: ❌ Error: " . $e->getMessage() . "<br>";
        }
        
        // Test user telegram settings
        try {
            $user_telegram = getUserTelegramConfig($_SESSION['user_id']);
            echo "User Telegram config: ✅ ใช้ได้<br>";
            echo "User verified: " . ($user_telegram['verified'] ? 'Yes' : 'No') . "<br>";
        } catch (Exception $e) {
            echo "User Telegram config: ❌ Error: " . $e->getMessage() . "<br>";
        }
        
        // Test recipient query
        echo "<h4>📋 Recipient Options Test:</h4>";
        $recipients = ['all', 'admins', 'managers', 'custom'];
        
        foreach ($recipients as $recipient) {
            echo "<strong>{$recipient}:</strong> ";
            try {
                switch($recipient) {
                    case 'all':
                        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE is_active = 1");
                        break;
                    case 'admins':
                        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE is_active = 1 AND role = 'admin'");
                        break;
                    case 'managers':
                        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE is_active = 1 AND role IN ('admin', 'manager')");
                        break;
                    case 'custom':
                        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE is_active = 1 AND user_id = ?");
                        $stmt->execute([1]);
                        echo $stmt->fetchColumn() . " users<br>";
                        continue 2;
                }
                $stmt->execute();
                echo $stmt->fetchColumn() . " users<br>";
            } catch (Exception $e) {
                echo "❌ Error: " . $e->getMessage() . "<br>";
            }
        }
    }
    
    echo "<hr>";
}

echo "<h3>🧪 Test Telegram Send Permission</h3>";

// Mock manager session
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'manager';
$_SESSION['username'] = 'test_manager';

// Simulate send request
$test_data = [
    'start_date' => date('Y-m-d'),
    'end_date' => date('Y-m-d'),
    'report_type' => 'summary',
    'recipient' => 'managers'
];

echo "<strong>Mock Manager Send Test:</strong><br>";
echo "Session Role: " . $_SESSION['role'] . "<br>";
echo "Permission Check: " . (in_array($_SESSION['role'], ['admin', 'manager']) ? '✅ ผ่าน' : '❌ ไม่ผ่าน') . "<br>";

// Test recipient retrieval for managers
try {
    $stmt = $pdo->prepare("SELECT user_id, username, fullname, role FROM users WHERE is_active = 1 AND role IN ('admin', 'manager')");
    $stmt->execute();
    $recipients = $stmt->fetchAll();
    echo "Recipients found: " . count($recipients) . " users<br>";
    foreach ($recipients as $user) {
        echo "- {$user['fullname']} ({$user['role']})<br>";
    }
} catch (Exception $e) {
    echo "❌ Recipient query error: " . $e->getMessage() . "<br>";
}

echo "<br><a href='reports.php'>🔗 กลับไป Reports</a>";
?>