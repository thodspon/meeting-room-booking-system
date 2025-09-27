<?php
// Set timezone for Thailand
date_default_timezone_set('Asia/Bangkok');

// Telegram configuration - กรุณาแก้ไขให้ตรงกับ Bot ของคุณ
define('TELEGRAM_TOKEN', '8293088704:AAGFTG5djH-eNgmf9nKir0x4tzB0L6td8Yg');
define('TELEGRAM_CHAT_ID', '6124231421');

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
        'admin' => ['manage_users', 'manage_rooms', 'approve_bookings', 'view_reports', 'manage_system', 'user_activity', 'system_settings'],
        'manager' => ['approve_bookings', 'view_reports', 'manage_rooms', 'user_activity'],
        'user' => ['create_booking', 'view_own_bookings', 'view_calendar']
    ];
    
    return in_array($permission, $permissions[$user['role']] ?? []);
}

/**
 * Get navigation menu items based on user role
 */
function getNavigationMenu($user_role) {
    $menu_items = [
        'index' => [
            'name' => 'หน้าหลัก',
            'url' => 'index.php',
            'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6',
            'roles' => ['admin', 'manager', 'user']
        ],
        'booking' => [
            'name' => 'จองห้องประชุม',
            'url' => 'booking.php',
            'icon' => 'M12 6v6m0 0v6m0-6h6m-6 0H6',
            'roles' => ['admin', 'manager', 'user']
        ],
        'calendar' => [
            'name' => 'ปฏิทินการจอง',
            'url' => 'calendar.php',
            'icon' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
            'roles' => ['admin', 'manager', 'user']
        ],
        'my_bookings' => [
            'name' => 'การจองของฉัน',
            'url' => 'my_bookings.php',
            'icon' => 'M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2',
            'roles' => ['admin', 'manager', 'user']
        ],
        'rooms' => [
            'name' => 'จัดการห้องประชุม',
            'url' => 'rooms.php',
            'icon' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4',
            'roles' => ['admin', 'manager']
        ],
        'reports' => [
            'name' => 'รายงาน',
            'url' => 'reports.php',
            'icon' => 'M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
            'roles' => ['admin', 'manager']
        ],
        'user_activity' => [
            'name' => 'กิจกรรมผู้ใช้',
            'url' => 'user_activity.php',
            'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
            'roles' => ['admin', 'manager']
        ],
        'users' => [
            'name' => 'จัดการผู้ใช้',
            'url' => 'users.php',
            'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z',
            'roles' => ['admin']
        ],
        'telegram_settings' => [
            'name' => 'ตั้งค่า Telegram',
            'url' => 'telegram_settings.php',
            'icon' => 'M8.29 20.251c7.547 0 11.675-6.253 11.675-11.675 0-.178 0-.355-.012-.53A8.348 8.348 0 0022 5.92a8.19 8.19 0 01-2.357.646 4.118 4.118 0 001.804-2.27 8.224 8.224 0 01-2.605.996 4.107 4.107 0 00-6.993 3.743 11.65 11.65 0 01-8.457-4.287 4.106 4.106 0 001.27 5.477A4.072 4.072 0 012.8 9.713v.052a4.105 4.105 0 003.292 4.022 4.095 4.095 0 01-1.853.07 4.108 4.108 0 003.834 2.85A8.233 8.233 0 012 18.407a11.616 11.616 0 006.29 1.84',
            'roles' => ['admin']
        ]
    ];
    
    $filtered_menu = [];
    foreach ($menu_items as $key => $item) {
        if (in_array($user_role, $item['roles'])) {
            $filtered_menu[$key] = $item;
        }
    }
    
    return $filtered_menu;
}

/**
 * Generate navigation HTML
 */
function generateNavigation($current_page = '', $user_role = 'user', $mobile = false) {
    $menu_items = getNavigationMenu($user_role);
    $html = '';
    
    foreach ($menu_items as $key => $item) {
        $active_class = ($current_page === $key) ? 'active' : '';
        $class = $mobile ? 'text-base-content' : '';
        
        $html .= '<li>';
        $html .= '<a href="' . $item['url'] . '" class="' . $active_class . ' ' . $class . '">';
        
        if (!$mobile && isset($item['icon'])) {
            $html .= '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">';
            $html .= '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="' . $item['icon'] . '" />';
            $html .= '</svg>';
        }
        
        $html .= $item['name'];
        $html .= '</a>';
        $html .= '</li>';
    }
    
    return $html;
}

/**
 * Generate random password
 */
function generatePassword($length = 8) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    return substr(str_shuffle($chars), 0, $length);
}






?>
