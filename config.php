<?php
/**
 * ไฟล์การตั้งค่าองค์กร - Organization Configuration
 * ระบบจองห้องประชุม
 * 
 * *** แก้ไขชื่อองค์กรได้ที่นี่ ***
 * เปลี่ยนข้อมูลในไฟล์นี้เพื่อปรับแต่งให้เหมาะกับหน่วยงานของคุณ
 * 
 * อัพเดตล่าสุด: 2025-09-26 11:31:31
 */

// ============================================
// การตั้งค่าข้อมูลองค์กร
// ============================================

// ชื่อหน่วยงาน/องค์กร (แก้ไขได้ตามต้องการ)
$organization_config = [
    'name' => 'หน่วยงานของคุณ',
    'name_english' => 'Your Organization Name',
    'address' => 'ที่อยู่ของหน่วยงาน',
    'phone' => '0X-XXXX-XXXX',
    'email' => 'contact@yourorg.com',
    'website' => 'https://www.yourorg.com',
    
    // ข้อมูลสำหรับ Header ของเอกสาร
    'logo_path' => 'assets/images/logo.png',
    'header_title' => 'หน่วยงานของคุณ',
    'sub_title' => 'ระบบจองห้องประชุมออนไลน์'
];

// ============================================
// ฟังก์ชันสำหรับเรียกใช้ข้อมูลองค์กร
// ============================================

/**
 * ดึงข้อมูลองค์กรทั้งหมด
 */
function getOrganizationConfig() {
    global $organization_config;
    return $organization_config;
}

/**
 * ดึงชื่อองค์กร
 */
function getOrganizationName() {
    global $organization_config;
    return $organization_config['name'];
}

/**
 * ดึงชื่อองค์กรภาษาอังกฤษ
 */
function getOrganizationNameEnglish() {
    global $organization_config;
    return $organization_config['name_english'];
}

/**
 * ดึงข้อมูลติดต่อ
 */
function getOrganizationContact() {
    global $organization_config;
    return [
        'address' => $organization_config['address'],
        'phone' => $organization_config['phone'],
        'email' => $organization_config['email'],
        'website' => $organization_config['website']
    ];
}

/**
 * ดึงข้อมูลสำหรับ Header
 */
function getOrganizationHeader() {
    global $organization_config;
    return [
        'logo_path' => $organization_config['logo_path'],
        'header_title' => $organization_config['header_title'],
        'sub_title' => $organization_config['sub_title']
    ];
}
// Telegram configuration
$telegram_config = [
    'enabled' => true,
    'default_token' => '',
    'default_chat_id' => '',
    'notification_enabled' => true,
    'booking_notifications' => true,
    'system_notifications' => true
];

/**
 * ดึงการตั้งค่า Telegram ของระบบ
 */
function getTelegramConfig() {
    global $telegram_config;
    return $telegram_config;
}

/**
 * ดึงการตั้งค่า Telegram ของผู้ใช้ (Simulation - ใช้ค่าเริ่มต้นก่อน)
 * ในอนาคตจะเชื่อมต่อกับฐานข้อมูล
 */
function getUserTelegramConfig($user_id) {
    // ส่งค่าเริ่มต้นสำหรับผู้ใช้ (ใช้การตั้งค่าระบบ)
    global $telegram_config;
    return [
        'user_id' => $user_id,
        'enabled' => false, // ปิดใช้งานส่วนตัวเป็นค่าเริ่มต้น
        'token' => '',
        'chat_id' => '',
        'use_system_default' => true
    ];
}

/**
 * บันทึกการตั้งค่า Telegram ของผู้ใช้ (Simulation)
 */
function saveUserTelegramConfig($user_id, $token, $chat_id, $enabled = true) {
    // ในอนาคตจะบันทึกลงฐานข้อมูล
    // ตอนนี้ส่งคืน true เพื่อให้ทำงานได้
    return true;
}

/**
 * ทดสอบการส่งข้อความ Telegram
 */
function testTelegramMessage($token, $chat_id, $message = null) {
    if (empty($token) || empty($chat_id)) {
        return [
            'success' => false, 
            'error' => 'Token หรือ Chat ID ไม่ถูกต้อง'
        ];
    }
    
    if ($message === null) {
        $message = "🧪 ทดสอบการเชื่อมต่อ Telegram\n⏰ " . date('d/m/Y H:i:s');
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
        return [
            'success' => false, 
            'error' => 'ไม่สามารถเชื่อมต่อ Telegram API ได้'
        ];
    }
    
    $response = json_decode($result, true);
    
    if (isset($response['ok']) && $response['ok']) {
        return [
            'success' => true,
            'message' => 'ส่งข้อความทดสอบสำเร็จ! ตรวจสอบใน Telegram ของคุณ'
        ];
    } else {
        $error_msg = isset($response['description']) ? $response['description'] : 'ข้อผิดพลาดไม่ทราบสาเหตุ';
        return [
            'success' => false,
            'error' => $error_msg
        ];
    }
}

/**
 * บันทึกการตั้งค่า Telegram ของระบบ (สำหรับ Admin)
 */
function saveSystemTelegramConfig($token, $chat_id, $enabled = true) {
    // ในอนาคตจะบันทึกลงฐานข้อมูล
    // ตอนนี้ส่งคืน true เพื่อให้ทำงานได้
    return true;
}

/**
 * ตรวจสอบว่ามีสิทธิ์ admin หรือไม่
 */
function isAdmin($role) {
    return in_array($role, ['admin', 'superuser']);
}

?>