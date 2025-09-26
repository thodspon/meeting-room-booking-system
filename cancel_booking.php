<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// ตรวจสอบการ login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$booking_id = intval($_GET['id'] ?? 0);

if (!$booking_id) {
    header('Location: my_bookings.php');
    exit();
}

try {
    // ดึงข้อมูลการจอง
    $stmt = $pdo->prepare("
        SELECT b.*, r.room_name 
        FROM bookings b 
        JOIN rooms r ON b.room_id = r.room_id 
        WHERE b.booking_id = ? AND b.user_id = ? AND b.status = 'pending'
    ");
    $stmt->execute([$booking_id, $user_id]);
    $booking = $stmt->fetch();

    if (!$booking) {
        header('Location: my_bookings.php?error=not_found');
        exit();
    }

    // อัพเดทสถานะเป็น cancelled
    $stmt = $pdo->prepare("UPDATE bookings SET status = 'cancelled' WHERE booking_id = ?");
    $stmt->execute([$booking_id]);

    // ส่งการแจ้งเตือน
    $booking_data = array_merge($booking, [
        'fullname' => $_SESSION['fullname'],
        'department' => $_SESSION['department']
    ]);
    sendBookingNotification($booking_data, 'cancelled');

    // Log activity
    logActivity($pdo, $user_id, 'cancel_booking', "Cancelled booking ID: {$booking_id}");

    header('Location: my_bookings.php?success=cancelled');

} catch (Exception $e) {
    error_log("Cancel booking error: " . $e->getMessage());
    header('Location: my_bookings.php?error=system');
}
exit();
?>