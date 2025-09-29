<?php
session_start();

// Mock login สำหรับทดสอบ
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
    $_SESSION['username'] = 'admin';
    $_SESSION['fullname'] = 'ทดสอบ Admin';
    $_SESSION['role'] = 'admin';
}

echo "Session ถูกตั้งค่าแล้ว:<br>";
echo "User ID: " . $_SESSION['user_id'] . "<br>";
echo "Username: " . $_SESSION['username'] . "<br>";
echo "Role: " . $_SESSION['role'] . "<br><br>";

echo '<a href="test_telegram_form.php">ไปทดสอบ Telegram Form</a><br>';
echo '<a href="reports.php">ไปหน้า Reports</a>';
?>