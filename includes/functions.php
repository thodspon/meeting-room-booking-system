<?php
// Set timezone for Thailand
date_default_timezone_set('Asia/Bangkok');

// Telegram configuration - กรุณาแก้ไขให้ตรงกับ Bot ของคุณ
define('TELEGRAM_TOKEN', '');
define('TELEGRAM_CHAT_ID', '');

/**
 * Send message to Telegram (ใช้การตั้งค่าเดิม)
 */
function sendTelegramMessage($message) {
    return sendTelegramMessageWithConfig($message, TELEGRAM_TOKEN, TELEGRAM_CHAT_ID);
}

/**
 * Send message to Telegram with custom configuration
 */
function sendTelegramMessageWithConfig($message, $token, $chat_id) {
    if (empty($token) || empty($chat_id)) {
        return ['ok' => false, 'description' => 'Token หรือ Chat ID ไม่ถูกต้อง'];
    }
    
    $url = "https://api.telegram.org/bot{$token}/sendMessage";
    $data = [
        'chat_id' => $chat_id,
        'text' => $message,
        'parse_mode' => 'HTML'
    ];
    
    $options = [
        'http' => [
            'header' => "Content-type: application/x-www-form-urlencoded\r\n",
            'method' => 'POST',
            'content' => http_build_query($data),
            'timeout' => 30
        ]
    ];
    
    $context = stream_context_create($options);
    $result = @file_get_contents($url, false, $context);
    
    if ($result === false) {
        return ['ok' => false, 'description' => 'ไม่สามารถเชื่อมต่อ Telegram API ได้'];
    }
    
    return json_decode($result, true);
}

/**
 * Generate 2FA code
 */
function generate2FACode() {
    return sprintf("%06d", mt_rand(1, 999999));
}

/**
 * Send 2FA code via Telegram
 */
function send2FACode($username, $code, $user_id = null) {
    // ใช้ค่าเริ่มต้นหากไม่มี config
    $org_name = 'ระบบจองห้องประชุม';
    $token = TELEGRAM_TOKEN;
    $chat_id = TELEGRAM_CHAT_ID;
    
    // พยายามโหลด config ถ้าเป็นไปได้
    if (function_exists('getOrganizationConfig')) {
        $org_config = getOrganizationConfig();
        $org_name = $org_config['name'] ?? $org_name;
    }
    
    // พยายามใช้การตั้งค่า Telegram ขั้นสูง ถ้าเป็นไปได้
    if (function_exists('getTelegramConfig') && function_exists('getUserTelegramConfig') && $user_id) {
        $telegram_config = getTelegramConfig();
        $user_telegram = getUserTelegramConfig($user_id);
        
        if (!empty($user_telegram['token']) && !empty($user_telegram['chat_id']) && $user_telegram['enabled']) {
            $token = $user_telegram['token'];
            $chat_id = $user_telegram['chat_id'];
        } elseif (!empty($telegram_config['default_token']) && !empty($telegram_config['default_chat_id'])) {
            $token = $telegram_config['default_token'];
            $chat_id = $telegram_config['default_chat_id'];
        }
    }
    
    $message = "🔐 รหัสยืนยันตัวตน (2FA)\n";
    $message .= "📋 " . $org_name . "\n\n";
    $message .= "ผู้ใช้: {$username}\n";
    $message .= "รหัส: <b>{$code}</b>\n\n";
    $message .= "รหัสนี้จะหมดอายุใน 5 นาที\n";
    $message .= "⏰ " . date('d/m/Y H:i:s') . "\n\n";
    
    // เพิ่ม URL ของเว็บไซต์
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $base_url = $protocol . "://" . $host . dirname($_SERVER['REQUEST_URI'] ?? '/');
    $message .= "🌐 เข้าสู่ระบบที่: " . $base_url . "/login.php";
    
    return sendTelegramMessageWithConfig($message, $token, $chat_id);
}

/**
 * Log user activity
 */
function logActivity($pdo, $user_id, $action, $details = '') {
    try {
        $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, details, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            $user_id,
            $action,
            $details,
            $_SERVER['REMOTE_ADDR'] ?? '',
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);
        return true;
    } catch (Exception $e) {
        error_log("Log activity error: " . $e->getMessage());
        return false;
    }
}

/**
 * Check if booking time conflicts
 */
function checkBookingConflict($pdo, $room_id, $booking_date, $start_time, $end_time, $exclude_booking_id = null) {
    $sql = "SELECT COUNT(*) FROM bookings 
            WHERE room_id = ? 
              AND booking_date = ? 
              AND status != 'cancelled'
              AND ((start_time <= ? AND end_time > ?) OR (start_time < ? AND end_time >= ?))";
    
    $params = [$room_id, $booking_date, $start_time, $start_time, $end_time, $end_time];
    
    if ($exclude_booking_id) {
        $sql .= " AND booking_id != ?";
        $params[] = $exclude_booking_id;
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchColumn() > 0;
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Get current user information
 */
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    require_once 'config/database.php';
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ? AND is_active = 1");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            return [
                'id' => $user['user_id'],
                'username' => $user['username'],
                'fullname' => $user['fullname'],
                'email' => $user['email'],
                'role' => $user['role'],
                'department' => $user['department'],
                'phone' => $user['phone'],
                'is_active' => $user['is_active'],
                'created_at' => $user['created_at']
            ];
        }
        
        return null;
    } catch (Exception $e) {
        error_log("Get current user error: " . $e->getMessage());
        return null;
    }
}

/**
 * Send booking notification
 */
function sendBookingNotification($booking_data, $type = 'new') {
    $messages = [
        'new' => "📅 การจองห้องประชุมใหม่",
        'approved' => "✅ อนุมัติการจองห้องประชุม",
        'rejected' => "❌ ไม่อนุมัติการจองห้องประชุม",
        'cancelled' => "🚫 ยกเลิกการจองห้องประชุม"
    ];
    
    $message = $messages[$type] . "\n\n";
    $message .= "🏢 ห้อง: {$booking_data['room_name']}\n";
    $message .= "👤 ผู้จอง: {$booking_data['fullname']}\n";
    $message .= "🏥 หน่วยงาน: {$booking_data['department']}\n";
    $message .= "📅 วันที่: " . date('d/m/Y', strtotime($booking_data['booking_date'])) . "\n";
    $message .= "🕐 เวลา: " . date('H:i', strtotime($booking_data['start_time'])) . " - " . date('H:i', strtotime($booking_data['end_time'])) . "\n";
    
    if (!empty($booking_data['purpose'])) {
        $message .= "📝 วัตถุประสงค์: {$booking_data['purpose']}\n";
    }
    
    $message .= "\n⏰ " . date('d/m/Y H:i:s');
    
    return sendTelegramMessage($message);
}

/**
 * Format Thai date
 */
function formatThaiDate($date, $format = 'd/m/Y') {
    $thai_months = [
        1 => 'มกราคม', 2 => 'กุมภาพันธ์', 3 => 'มีนาคม', 4 => 'เมษายน',
        5 => 'พฤษภาคม', 6 => 'มิถุนายน', 7 => 'กรกฎาคม', 8 => 'สิงหาคม',
        9 => 'กันยายน', 10 => 'ตุลาคม', 11 => 'พฤศจิกายน', 12 => 'ธันวาคม'
    ];
    
    $thai_days = [
        'Sunday' => 'อาทิตย์', 'Monday' => 'จันทร์', 'Tuesday' => 'อังคาร',
        'Wednesday' => 'พุธ', 'Thursday' => 'พฤหัสบดี', 'Friday' => 'ศุกร์', 'Saturday' => 'เสาร์'
    ];
    
    $timestamp = strtotime($date);
    $day = date('j', $timestamp);
    $month = $thai_months[date('n', $timestamp)];
    $year = date('Y', $timestamp) + 543;
    $day_name = $thai_days[date('l', $timestamp)];
    
    if ($format == 'd/m/Y') {
        return $day . '/' . date('m', $timestamp) . '/' . $year;
    } elseif ($format == 'full') {
        return 'วัน' . $day_name . 'ที่ ' . $day . ' ' . $month . ' พ.ศ. ' . $year;
    }
    
    return date($format, $timestamp);
}

/**
 * Sanitize input
 */
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate time format
 */
function validateTime($time) {
    return preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $time);
}

/**
 * Check user permission
 */
function checkPermission($pdo, $user_id, $permission) {
    $stmt = $pdo->prepare("SELECT role FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    if (!$user) return false;
    
    $permissions = [
        'admin' => ['manage_users', 'manage_rooms', 'approve_bookings', 'view_reports', 'manage_system'],
        'manager' => ['approve_bookings', 'view_reports', 'manage_rooms'],
        'user' => ['create_booking', 'view_own_bookings']
    ];
    
    return in_array($permission, $permissions[$user['role']] ?? []);
}

/**
 * Generate random password
 */
function generatePassword($length = 8) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    return substr(str_shuffle($chars), 0, $length);
}






?>
