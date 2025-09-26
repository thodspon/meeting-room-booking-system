<?php
session_start();

// ตั้งค่า session เพื่อจำลองการ login
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'admin';
$_SESSION['fullname'] = 'ผู้ดูแลระบบ';
$_SESSION['role'] = 'admin';
$_SESSION['department'] = 'IT';

echo "<!DOCTYPE html>";
echo "<html>";
echo "<head><title>Test Login Session</title></head>";
echo "<body>";
echo "<h1>Login Session สร้างสำเร็จ</h1>";
echo "<p>คุณสามารถเข้าใช้งานระบบได้แล้ว</p>";
echo "<p>User ID: " . $_SESSION['user_id'] . "</p>";
echo "<p>Username: " . $_SESSION['username'] . "</p>";
echo "<p>Role: " . $_SESSION['role'] . "</p>";
echo "<p><a href='index.php'>ไปหน้าหลัก</a></p>";
echo "<p><a href='telegram_settings.php'>ไปหน้าตั้งค่า Telegram</a></p>";
echo "</body>";
echo "</html>";
?>