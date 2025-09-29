<?php
/**
 * หน้าแรกของระบบ - ตรวจสอบการ login และ redirect ตามสถานะ
 * Meeting Room Booking System
 */

session_start();

// ตรวจสอบว่า user login แล้วหรือไม่
if (isset($_SESSION['user_id']) && isset($_SESSION['username'])) {
    // ถ้า login แล้วให้ไปหน้า dashboard หลัก
    header('Location: index.php');
    exit();
} else {
    // ถ้ายัง login ให้ไปหน้า login
    header('Location: login.php');
    exit();
}
?>