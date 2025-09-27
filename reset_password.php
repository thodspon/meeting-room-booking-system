<?php
/**
 * Password Reset Handler
 * Meeting Room Booking System v2.3
 * 
 * จัดการการรีเซ็ตรหัสผ่านผ่าน Telegram
 * 
 * @author นายทศพล อุทก
 * @organization โรงพยาบาลร้อยเอ็ด
 * @since 2025-09-26
 */

session_start();
require_once 'config/database.php';
require_once 'config.php';
require_once 'includes/functions.php';

// Prevent direct access
if (!isset($_POST['action'])) {
    header('Location: forgot_password.php');
    exit();
}

$response = ['success' => false, 'message' => ''];

try {
    switch ($_POST['action']) {
        case 'request_reset':
            $username = trim($_POST['username']);
            
            if (empty($username)) {
                throw new Exception('กรุณากรอกชื่อผู้ใช้');
            }
            
            // ค้นหาผู้ใช้
            $stmt = $pdo->prepare("
                SELECT user_id, username, fullname, email, role 
                FROM users 
                WHERE username = ? AND is_active = 1
            ");
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            
            if (!$user) {
                throw new Exception('ไม่พบชื่อผู้ใช้ในระบบ');
            }
            
            // สร้างรหัสรีเซ็ต
            $reset_code = sprintf('%06d', mt_rand(100000, 999999));
            $expires_at = date('Y-m-d H:i:s', strtotime('+15 minutes'));
            
            // บันทึกรหัสรีเซ็ต
            $stmt = $pdo->prepare("
                INSERT INTO password_resets (user_id, reset_code, expires_at, created_at) 
                VALUES (?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE 
                reset_code = VALUES(reset_code), 
                expires_at = VALUES(expires_at), 
                created_at = NOW(), 
                used_at = NULL
            ");
            $stmt->execute([$user['user_id'], $reset_code, $expires_at]);
            
            // เตรียมข้อความ Telegram
            $org_config = getOrganizationConfig();
            $telegram_message = "🔐 <b>รีเซ็ตรหัสผ่าน</b> - {$org_config['name']}\n\n";
            $telegram_message .= "สวัสดี <b>{$user['fullname']}</b>\n\n";
            $telegram_message .= "คุณได้ขอรีเซ็ตรหัสผ่านสำหรับบัญชี: <code>{$user['username']}</code>\n\n";
            $telegram_message .= "🔢 <b>รหัสรีเซ็ต:</b> <code>{$reset_code}</code>\n\n";
            $telegram_message .= "⏰ รหัสนี้จะหมดอายุใน <b>15 นาที</b>\n";
            $telegram_message .= "📅 วันที่: " . formatThaiDate(date('Y-m-d')) . " เวลา " . date('H:i') . " น.\n\n";
            $telegram_message .= "🔒 หากคุณไม่ได้ขอรีเซ็ตรหัสผ่าน กรุณาแจ้งผู้ดูแลระบบทันที\n\n";
            $telegram_message .= "💡 กรอกรหัสนี้ในหน้าเว็บไซต์เพื่อสร้างรหัสผ่านใหม่";
            
            // ส่งผ่าน Telegram
            $sent = false;
            $error_message = '';
            
            // ลองส่งผ่านการตั้งค่าผู้ใช้ก่อน
            $user_telegram = getUserTelegramConfig($user['user_id']);
            if ($user_telegram['enabled'] && !empty($user_telegram['token']) && !empty($user_telegram['chat_id'])) {
                $result = sendTelegramMessageWithConfig($telegram_message, $user_telegram['token'], $user_telegram['chat_id']);
                if ($result['ok'] ?? false) {
                    $sent = true;
                } else {
                    $error_message = $result['description'] ?? 'ไม่สามารถส่งข้อความได้';
                }
            }
            
            // หากส่งผ่านผู้ใช้ไม่ได้ ให้ลองส่งผ่านระบบ
            if (!$sent) {
                $system_telegram = getTelegramConfig();
                if ($system_telegram['enabled'] && !empty($system_telegram['default_token']) && !empty($system_telegram['default_chat_id'])) {
                    $result = sendTelegramMessageWithConfig($telegram_message, $system_telegram['default_token'], $system_telegram['default_chat_id']);
                    if ($result['ok'] ?? false) {
                        $sent = true;
                    } else {
                        $error_message = $result['description'] ?? 'ไม่สามารถส่งข้อความได้';
                    }
                }
            }
            
            if (!$sent) {
                throw new Exception('ไม่สามารถส่งรหัสรีเซ็ตได้: ' . $error_message);
            }
            
            $response['success'] = true;
            $response['message'] = 'ส่งรหัสรีเซ็ตไปยัง Telegram แล้ว กรุณาตรวจสอบข้อความ';
            $response['redirect'] = 'forgot_password.php?step=verify&username=' . urlencode($username);
            
            break;
            
        case 'verify_reset':
            $username = trim($_POST['username']);
            $reset_code = trim($_POST['reset_code']);
            $new_password = trim($_POST['new_password']);
            $confirm_password = trim($_POST['confirm_password']);
            
            // Validation
            if (empty($username) || empty($reset_code) || empty($new_password) || empty($confirm_password)) {
                throw new Exception('กรุณากรอกข้อมูลให้ครบถ้วน');
            }
            
            if ($new_password !== $confirm_password) {
                throw new Exception('รหัสผ่านใหม่ไม่ตรงกัน');
            }
            
            if (strlen($new_password) < 6) {
                throw new Exception('รหัสผ่านต้องมีอย่างน้อย 6 ตัวอักษร');
            }
            
            // ตรวจสอบรหัสรีเซ็ต
            $stmt = $pdo->prepare("
                SELECT pr.*, u.user_id, u.username, u.fullname 
                FROM password_resets pr
                JOIN users u ON pr.user_id = u.user_id
                WHERE u.username = ? AND pr.reset_code = ? 
                AND pr.expires_at > NOW() AND pr.used_at IS NULL
                AND u.is_active = 1
            ");
            $stmt->execute([$username, $reset_code]);
            $reset_record = $stmt->fetch();
            
            if (!$reset_record) {
                throw new Exception('รหัสรีเซ็ตไม่ถูกต้องหรือหมดอายุแล้ว');
            }
            
            // อัพเดตรหัสผ่าน
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            
            $pdo->beginTransaction();
            
            try {
                // อัพเดตรหัสผ่าน
                $stmt = $pdo->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE user_id = ?");
                $stmt->execute([$hashed_password, $reset_record['user_id']]);
                
                // ทำเครื่องหมายว่าใช้รหัสรีเซ็ตแล้ว
                $stmt = $pdo->prepare("UPDATE password_resets SET used_at = NOW() WHERE user_id = ? AND reset_code = ?");
                $stmt->execute([$reset_record['user_id'], $reset_code]);
                
                $pdo->commit();
                
                // ส่งการแจ้งเตือน
                $org_config = getOrganizationConfig();
                $notification_message = "✅ <b>รีเซ็ตรหัสผ่านสำเร็จ</b> - {$org_config['name']}\n\n";
                $notification_message .= "สวัสดี <b>{$reset_record['fullname']}</b>\n\n";
                $notification_message .= "🔐 รหัสผ่านของบัญชี: <code>{$reset_record['username']}</code> ได้รับการเปลี่ยนแปลงเรียบร้อยแล้ว\n\n";
                $notification_message .= "📅 วันที่: " . formatThaiDate(date('Y-m-d')) . " เวลา " . date('H:i') . " น.\n\n";
                $notification_message .= "🔒 หากคุณไม่ได้เปลี่ยนรหัสผ่าน กรุณาติดต่อผู้ดูแลระบบทันที\n\n";
                $notification_message .= "💡 ตอนนี้คุณสามารถเข้าสู่ระบบด้วยรหัสผ่านใหม่ได้แล้ว";
                
                // ส่งการแจ้งเตือน
                $user_telegram = getUserTelegramConfig($reset_record['user_id']);
                if ($user_telegram['enabled'] && !empty($user_telegram['token']) && !empty($user_telegram['chat_id'])) {
                    sendTelegramMessageWithConfig($notification_message, $user_telegram['token'], $user_telegram['chat_id']);
                } else {
                    $system_telegram = getTelegramConfig();
                    if ($system_telegram['enabled']) {
                        sendTelegramMessageWithConfig($notification_message, $system_telegram['default_token'], $system_telegram['default_chat_id']);
                    }
                }
                
                $response['success'] = true;
                $response['message'] = 'เปลี่ยนรหัสผ่านเรียบร้อยแล้ว กรุณาเข้าสู่ระบบด้วยรหัสผ่านใหม่';
                $response['redirect'] = 'forgot_password.php?step=success';
                
            } catch (Exception $e) {
                $pdo->rollBack();
                throw new Exception('เกิดข้อผิดพลาดในการอัพเดตรหัสผ่าน: ' . $e->getMessage());
            }
            
            break;
            
        default:
            throw new Exception('การดำเนินการไม่ถูกต้อง');
    }
    
    // ส่งข้อมูลเป็น JSON หากเป็น AJAX request
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode($response);
        exit();
    }
    
    // Redirect สำหรับ form submission ปกติ
    if ($response['success'] && isset($response['redirect'])) {
        header('Location: ' . $response['redirect']);
        exit();
    }
    
} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = $e->getMessage();
    
    // Log error
    error_log("Password reset error: " . $e->getMessage());
    
    // ส่งข้อมูลเป็น JSON หากเป็น AJAX request
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode($response);
        exit();
    }
    
    // Redirect กลับไปหน้าเดิมพร้อม error message
    $redirect_url = 'forgot_password.php?error=' . urlencode($response['message']);
    if (isset($_POST['username'])) {
        $redirect_url .= '&username=' . urlencode($_POST['username']);
    }
    if (isset($_POST['action']) && $_POST['action'] === 'verify_reset') {
        $redirect_url .= '&step=verify';
    }
    
    header('Location: ' . $redirect_url);
    exit();
}
?>