<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../config/database.php';
require_once '../config.php';
require_once '../includes/functions.php';

// Mock admin session
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'admin';
$_SESSION['username'] = 'admin';

header('Content-Type: application/json; charset=utf-8');

echo "=== DEBUG REPORTS TELEGRAM SEND ===\n";

// Simulate POST data
$_POST = [
    'start_date' => date('Y-m-d'),
    'end_date' => date('Y-m-d'),
    'report_type' => 'summary',
    'recipient' => 'managers'
];

// Debug session
echo "1. Session Check:\n";
echo "- User ID: " . $_SESSION['user_id'] . "\n";
echo "- Role: " . $_SESSION['role'] . "\n";
echo "- Permission: " . (in_array($_SESSION['role'], ['admin', 'manager']) ? 'GRANTED' : 'DENIED') . "\n";

// Debug POST data
echo "\n2. POST Data:\n";
foreach ($_POST as $key => $value) {
    echo "- {$key}: {$value}\n";
}

// Include functions from send_telegram_summary.php
function getRecipientsList($pdo, $recipient, $selected_users = []) {
    switch ($recipient) {
        case 'all':
            $stmt = $pdo->prepare("SELECT user_id, username, fullname, role, telegram_chat_id, telegram_token, telegram_enabled FROM users WHERE is_active = 1");
            $stmt->execute();
            break;
        case 'admins':
            $stmt = $pdo->prepare("SELECT user_id, username, fullname, role, telegram_chat_id, telegram_token, telegram_enabled FROM users WHERE is_active = 1 AND role = 'admin'");
            $stmt->execute();
            break;
        case 'managers':
            $stmt = $pdo->prepare("SELECT user_id, username, fullname, role, telegram_chat_id, telegram_token, telegram_enabled FROM users WHERE is_active = 1 AND role IN ('admin', 'manager')");
            $stmt->execute();
            break;
        case 'custom':
            if (empty($selected_users)) {
                return [];
            }
            $placeholders = str_repeat('?,', count($selected_users) - 1) . '?';
            $stmt = $pdo->prepare("SELECT user_id, username, fullname, role, telegram_chat_id, telegram_token, telegram_enabled FROM users WHERE is_active = 1 AND user_id IN ({$placeholders})");
            $stmt->execute($selected_users);
            break;
        default:
            return [];
    }
    
    return $stmt->fetchAll();
}

try {
    // Get recipients
    $recipient = $_POST['recipient'];
    $selected_users = $_POST['selected_users'] ?? [];
    
    echo "\n3. Recipients Query ({$recipient}):\n";
    $recipients_list = getRecipientsList($pdo, $recipient, $selected_users);
    echo "- Found: " . count($recipients_list) . " users\n";
    
    foreach ($recipients_list as $user) {
        $has_telegram = !empty($user['telegram_chat_id']) && !empty($user['telegram_token']) && $user['telegram_enabled'];
        echo "- {$user['fullname']} ({$user['role']}): " . ($has_telegram ? 'TELEGRAM_READY' : 'NO_TELEGRAM') . "\n";
        
        if ($has_telegram) {
            echo "  * Chat ID: " . substr($user['telegram_chat_id'], 0, 10) . "...\n";
            echo "  * Token: " . substr($user['telegram_token'], 0, 20) . "...\n";
            echo "  * Enabled: " . ($user['telegram_enabled'] ? 'Yes' : 'No') . "\n";
        }
    }
    
    // Test message creation
    echo "\n4. Message Generation:\n";
    $org_config = getOrganizationConfig();
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $report_type = $_POST['report_type'];
    
    $date_range = $start_date === $end_date ? formatThaiDate($start_date) : formatThaiDate($start_date) . ' ถึง ' . formatThaiDate($end_date);
    
    $message = "📊 สรุปการจองห้องประชุม\n\n";
    $message .= "🏢 " . $org_config['name'] . "\n";
    $message .= "📅 วันที่: " . $date_range . "\n";
    $message .= "📋 ประเภท: " . ($report_type === 'summary' ? 'สรุปทั่วไป' : $report_type) . "\n";
    $message .= "👥 ส่งให้: " . ($recipient === 'managers' ? 'Manager และ Admin' : $recipient) . "\n";
    $message .= "⏰ ส่งเมื่อ: " . date('d/m/Y H:i:s') . "\n\n";
    
    // Get booking stats
    $stmt = $pdo->prepare("SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
        FROM bookings WHERE booking_date BETWEEN ? AND ?");
    $stmt->execute([$start_date, $end_date]);
    $stats = $stmt->fetch();
    
    $message .= "📈 สถิติการจอง:\n";
    $message .= "✅ อนุมัติแล้ว: " . $stats['approved'] . " รายการ\n";
    $message .= "⏳ รออนุมัติ: " . $stats['pending'] . " รายการ\n";
    $message .= "❌ ไม่อนุมัติ: " . $stats['rejected'] . " รายการ\n";
    $message .= "🔢 รวมทั้งหมด: " . $stats['total'] . " รายการ";
    
    echo "- Message Length: " . strlen($message) . " characters\n";
    echo "- Message Preview:\n" . str_replace("\n", "\\n\n", substr($message, 0, 200)) . "...\n";
    
    // Test sending
    echo "\n5. Telegram Sending Test:\n";
    $success_count = 0;
    $total_count = count($recipients_list);
    $errors = [];
    
    foreach ($recipients_list as $user) {
        echo "\n--- Testing: {$user['fullname']} ---\n";
        
        if (empty($user['telegram_chat_id']) || empty($user['telegram_token']) || !$user['telegram_enabled']) {
            $error = "NO_TELEGRAM_CONFIG";
            $errors[] = "ข้าม: {$user['fullname']} - ยังไม่ได้ตั้งค่า Telegram";
            echo "Result: SKIP ({$error})\n";
            continue;
        }
        
        echo "Sending to:\n";
        echo "- Token: " . substr($user['telegram_token'], 0, 20) . "...\n";
        echo "- Chat ID: " . $user['telegram_chat_id'] . "\n";
        
        $result = sendTelegramMessageToUser($user['telegram_token'], $user['telegram_chat_id'], $message);
        
        echo "Response:\n";
        echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
        
        if ($result['ok'] ?? false) {
            $success_count++;
            echo "Result: SUCCESS\n";
        } else {
            $error_msg = $result['description'] ?? 'Unknown error';
            $errors[] = "ส่งไม่สำเร็จ: {$user['fullname']} - {$error_msg}";
            echo "Result: FAILED ({$error_msg})\n";
        }
    }
    
    echo "\n6. Final Summary:\n";
    echo "- Total Users: {$total_count}\n";
    echo "- Successful: {$success_count}\n";
    echo "- Failed: " . (count($errors)) . "\n";
    
    if (!empty($errors)) {
        echo "\nErrors:\n";
        foreach ($errors as $error) {
            echo "- {$error}\n";
        }
    }
    
} catch (Exception $e) {
    echo "\nERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}

echo "\n=== END DEBUG ===\n";
?>