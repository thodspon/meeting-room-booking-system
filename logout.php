<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

if (isset($_SESSION['user_id'])) {
    logActivity($pdo, $_SESSION['user_id'], 'logout', "User logged out");
    
    // Send logout notification
    $message = "🚪 ออกจากระบบ\n\n";
    $message .= "👤 ผู้ใช้: {$_SESSION['fullname']} ({$_SESSION['username']})\n";
    $message .= "⏰ " . date('d/m/Y H:i:s');
    
    sendTelegramMessage($message);
}

// Destroy session
session_destroy();

// Redirect to login page
header('Location: login.php');
exit();
?>