<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

echo "<h1>ทดสอบระบบ</h1>";

// ทดสอบ session
echo "<h2>Session Information</h2>";
if (isset($_SESSION['user_id'])) {
    echo "✅ ผู้ใช้ล็อกอินแล้ว: User ID = " . $_SESSION['user_id'] . "<br>";
    echo "Username: " . ($_SESSION['username'] ?? 'ไม่มี') . "<br>";
} else {
    echo "❌ ผู้ใช้ยังไม่ล็อกอิน<br>";
}

// ทดสอบการเชื่อมต่อฐานข้อมูล
echo "<h2>Database Connection</h2>";
try {
    require_once 'config/database.php';
    echo "✅ เชื่อมต่อฐานข้อมูลสำเร็จ<br>";
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $result = $stmt->fetch();
    echo "✅ พบผู้ใช้ในระบบ: " . $result['count'] . " คน<br>";
    
} catch (Exception $e) {
    echo "❌ ปัญหาฐานข้อมูล: " . $e->getMessage() . "<br>";
}

// ทดสอบ config
echo "<h2>Configuration</h2>";
try {
    require_once 'config.php';
    $org_config = getOrganizationConfig();
    echo "✅ ข้อมูลองค์กร: " . $org_config['name'] . "<br>";
} catch (Exception $e) {
    echo "❌ ปัญหา config: " . $e->getMessage() . "<br>";
}

// ทดสอบ functions
echo "<h2>Functions</h2>";
try {
    require_once 'includes/functions.php';
    echo "✅ โหลด functions.php สำเร็จ<br>";
    
    if (function_exists('formatThaiDate')) {
        echo "✅ formatThaiDate(): " . formatThaiDate(date('Y-m-d')) . "<br>";
    }
    
    if (function_exists('sendTelegramMessageWithConfig')) {
        echo "✅ พบฟังก์ชัน sendTelegramMessageWithConfig<br>";
    }
    
} catch (Exception $e) {
    echo "❌ ปัญหา functions: " . $e->getMessage() . "<br>";
}

echo "<h2>Navigation</h2>";
echo '<a href="login.php" target="_blank">ทดสอบ login.php</a><br>';
echo '<a href="forgot_password.php" target="_blank">ทดสอบ forgot_password.php</a><br>';
echo '<a href="index.php" target="_blank">ทดสอบ index.php</a><br>';

// แสดง PHP info สำคัญ
echo "<h2>PHP Info</h2>";
echo "PHP Version: " . phpversion() . "<br>";
echo "Memory Limit: " . ini_get('memory_limit') . "<br>";
echo "Error Reporting: " . error_reporting() . "<br>";
echo "Display Errors: " . ini_get('display_errors') . "<br>";
?>