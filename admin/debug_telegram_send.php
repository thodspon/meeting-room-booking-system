<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../config/database.php';
require_once '../config.php';
require_once '../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

echo "=== DEBUG TELEGRAM SEND ===\n";

// Debug session
echo "Session Info:\n";
echo "- User ID: " . ($_SESSION['user_id'] ?? 'Not set') . "\n";
echo "- Role: " . ($_SESSION['role'] ?? 'Not set') . "\n";
echo "- Username: " . ($_SESSION['username'] ?? 'Not set') . "\n";

// Check permission
$has_permission = isset($_SESSION['user_id']) && in_array($_SESSION['role'], ['admin', 'manager']);
echo "- Permission: " . ($has_permission ? 'GRANTED' : 'DENIED') . "\n\n";

if (!$has_permission) {
    echo "ERROR: Permission denied\n";
    exit;
}

// Debug POST data
echo "POST Data:\n";
foreach ($_POST as $key => $value) {
    if (is_array($value)) {
        echo "- {$key}: " . implode(', ', $value) . "\n";
    } else {
        echo "- {$key}: {$value}\n";
    }
}

// Test recipient retrieval
$recipient = $_POST['recipient'] ?? 'all';
echo "\nRecipient Test ({$recipient}):\n";

try {
    switch($recipient) {
        case 'all':
            $stmt = $pdo->prepare("SELECT user_id, username, fullname, role, telegram_chat_id, telegram_enabled FROM users WHERE is_active = 1");
            break;
        case 'admins':
            $stmt = $pdo->prepare("SELECT user_id, username, fullname, role, telegram_chat_id, telegram_enabled FROM users WHERE is_active = 1 AND role = 'admin'");
            break;
        case 'managers':
            $stmt = $pdo->prepare("SELECT user_id, username, fullname, role, telegram_chat_id, telegram_enabled FROM users WHERE is_active = 1 AND role IN ('admin', 'manager')");
            break;
        case 'custom':
            $selected_users = $_POST['selected_users'] ?? [];
            if (empty($selected_users)) {
                echo "ERROR: No users selected\n";
                exit;
            }
            $placeholders = str_repeat('?,', count($selected_users) - 1) . '?';
            $stmt = $pdo->prepare("SELECT user_id, username, fullname, role, telegram_chat_id, telegram_enabled FROM users WHERE is_active = 1 AND user_id IN ({$placeholders})");
            $stmt->execute($selected_users);
            $recipients = $stmt->fetchAll();
            break;
    }
    
    if ($recipient !== 'custom') {
        $stmt->execute();
        $recipients = $stmt->fetchAll();
    }
    
    echo "Recipients found: " . count($recipients) . "\n";
    
    $telegram_ready = 0;
    foreach ($recipients as $user) {
        $has_chat_id = !empty($user['telegram_chat_id']);
        $has_token = !empty($user['telegram_token']);
        $is_enabled = $user['telegram_enabled'];
        $telegram_ready_user = $has_chat_id && $has_token && $is_enabled;
        
        echo "- {$user['fullname']} ({$user['role']}):\n";
        echo "  * Chat ID: " . ($has_chat_id ? 'มี' : 'ไม่มี') . "\n";
        echo "  * Token: " . ($has_token ? 'มี' : 'ไม่มี') . "\n";
        echo "  * Enabled: " . ($is_enabled ? 'เปิด' : 'ปิด') . "\n";
        echo "  * Status: " . ($telegram_ready_user ? 'READY' : 'NOT_READY') . "\n";
        
        if ($telegram_ready_user) $telegram_ready++;
    }
    
    echo "Telegram ready users: {$telegram_ready}\n";
    
    if ($telegram_ready == 0) {
        echo "WARNING: No users have Telegram configured!\n";
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

echo "\n=== END DEBUG ===\n";
?>