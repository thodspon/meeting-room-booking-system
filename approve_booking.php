<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// ตรวจสอบการ login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// ตรวจสอบสิทธิ์
if (!checkPermission($pdo, $_SESSION['user_id'], 'approve_bookings')) {
    header('Location: index.php?error=permission');
    exit();
}

$booking_id = intval($_GET['id'] ?? 0);
$action = $_GET['action'] ?? '';

if (!$booking_id || !in_array($action, ['approve', 'reject'])) {
    header('Location: index.php?error=invalid');
    exit();
}

try {
    // ดึงข้อมูลการจอง
    $stmt = $pdo->prepare("
        SELECT b.*, r.room_name, u.fullname, u.department, u.email 
        FROM bookings b 
        JOIN rooms r ON b.room_id = r.room_id 
        JOIN users u ON b.user_id = u.user_id 
        WHERE b.booking_id = ? AND b.status = 'pending'
    ");
    $stmt->execute([$booking_id]);
    $booking = $stmt->fetch();

    if (!$booking) {
        header('Location: index.php?error=not_found');
        exit();
    }

    // อัพเดทสถานะ
    $new_status = $action === 'approve' ? 'approved' : 'rejected';
    $stmt = $pdo->prepare("
        UPDATE bookings 
        SET status = ?, approved_by = ?, approved_at = NOW() 
        WHERE booking_id = ?
    ");
    $stmt->execute([$new_status, $_SESSION['user_id'], $booking_id]);

    // ส่งการแจ้งเตือน
    sendBookingNotification($booking, $action === 'approve' ? 'approved' : 'rejected');

    // Log activity
    logActivity($pdo, $_SESSION['user_id'], $action . '_booking', 
        "{$action} booking ID: {$booking_id} for user: {$booking['fullname']}");

    $message = $action === 'approve' ? 'อนุมัติการจองเรียบร้อยแล้ว' : 'ไม่อนุมัติการจอง';
    header("Location: index.php?success=" . urlencode($message));

} catch (Exception $e) {
    error_log("Approve booking error: " . $e->getMessage());
    header('Location: index.php?error=system');
}
exit();
?>