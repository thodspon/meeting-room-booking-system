<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit();
}

$username = trim($_POST['username']);
$password = $_POST['password'];
$step = $_POST['step'] ?? 'login';

try {
    if ($step === 'login') {
        // Step 1: Validate username and password
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND is_active = 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password'])) {
            logActivity($pdo, null, 'failed_login', "Failed login attempt for username: {$username}");
            header('Location: login.php?error=invalid');
            exit();
        }

        // Clean up expired 2FA codes for this user
        $stmt = $pdo->prepare("DELETE FROM two_factor_codes WHERE user_id = ? AND expires_at < NOW()");
        $stmt->execute([$user['user_id']]);
        
        // Also mark any unused codes as expired (extra safety)
        $stmt = $pdo->prepare("UPDATE two_factor_codes SET used = 1 WHERE user_id = ? AND used = 0 AND expires_at < NOW()");
        $stmt->execute([$user['user_id']]);
        
        // Clean up expired 2FA codes for this user
        $stmt = $pdo->prepare("DELETE FROM two_factor_codes WHERE user_id = ? AND expires_at < NOW()");
        $stmt->execute([$user['user_id']]);
        
        // Also mark any unused codes as expired (extra safety)
        $stmt = $pdo->prepare("UPDATE two_factor_codes SET used = 1 WHERE user_id = ? AND used = 0 AND expires_at < NOW()");
        $stmt->execute([$user['user_id']]);
        
        // Generate and send 2FA code
        $code = generate2FACode();
        
        // Store 2FA code in database using MySQL CURRENT_TIMESTAMP + INTERVAL
        $stmt = $pdo->prepare("INSERT INTO two_factor_codes (user_id, code, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 5 MINUTE))");
        $stmt->execute([$user['user_id'], $code]);

        // Send 2FA code via Telegram
        // ตรวจสอบว่าผู้ใช้ตั้งค่า Telegram ไว้หรือไม่
        if (!empty($user['telegram_chat_id']) && !empty($user['telegram_token']) && $user['telegram_enabled']) {
            // ส่งไปยัง Telegram ของผู้ใช้เอง
            $message = "🔐 รหัส 2FA สำหรับเข้าสู่ระบบ\n\n";
            $message .= "👤 ผู้ใช้: {$user['fullname']} ({$user['username']})\n";
            $message .= "🔑 รหัส: {$code}\n";
            $message .= "⏰ หมดอายุใน 5 นาที\n";
            $message .= "🌐 IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'Unknown');
            
            $telegram_result = sendTelegramMessageToUser($user['telegram_token'], $user['telegram_chat_id'], $message);
        } else {
            // ใช้ระบบเริ่มต้น (send2FACode เดิม)
            $telegram_result = send2FACode($user['username'], $code, $user['user_id']);
        }

        if ($telegram_result && $telegram_result['ok']) {
            logActivity($pdo, $user['user_id'], '2fa_code_sent', "2FA code sent for user: {$username}");
            header("Location: login.php?step=2fa&username=" . urlencode($username) . "&success=1");
        } else {
            logActivity($pdo, $user['user_id'], '2fa_send_failed', "Failed to send 2FA code for user: {$username}");
            header('Location: login.php?error=telegram');
        }
        exit();

    } elseif ($step === '2fa') {
        // Step 2: Validate 2FA code
        $two_factor_code = $_POST['two_factor_code'];

        // Get user
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND is_active = 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if (!$user) {
            header('Location: login.php?error=invalid');
            exit();
        }

        // Validate 2FA code using MySQL NOW() for consistency
        $stmt = $pdo->prepare("
            SELECT *, TIMESTAMPDIFF(SECOND, NOW(), expires_at) as remaining_seconds 
            FROM two_factor_codes 
            WHERE user_id = ? AND code = ? AND used = 0 
            ORDER BY created_at DESC LIMIT 1
        ");
        $stmt->execute([$user['user_id'], $two_factor_code]);
        $code_record = $stmt->fetch();

        if (!$code_record || $code_record['remaining_seconds'] < 0) {
            // Debug: Log more details about why 2FA failed
            $debug_stmt = $pdo->prepare("
                SELECT code, expires_at, used, created_at,
                       CASE WHEN expires_at > NOW() THEN 'ยังไม่หมดอายุ' ELSE 'หมดอายุแล้ว' END as status,
                       TIMESTAMPDIFF(SECOND, NOW(), expires_at) as remaining_seconds,
                       NOW() as current_mysql_time
                FROM two_factor_codes 
                WHERE user_id = ? ORDER BY created_at DESC LIMIT 3
            ");
            $debug_stmt->execute([$user['user_id']]);
            $all_codes = $debug_stmt->fetchAll();
            
            $debug_message = "Invalid 2FA code attempt for user: {$username}, input code: {$two_factor_code}";
            
            if ($code_record) {
                $debug_message .= ", found matching code but expired (remaining: {$code_record['remaining_seconds']} seconds)";
            } else {
                $debug_message .= ", no matching unused code found";
            }
            
            $debug_message .= ". Recent codes: ";
            foreach ($all_codes as $i => $c) {
                $debug_message .= "[{$i}] code:{$c['code']}, status:{$c['status']}, remaining:{$c['remaining_seconds']}s, used:" . ($c['used'] ? 'Y' : 'N') . " ";
            }
            
            logActivity($pdo, $user['user_id'], 'invalid_2fa', $debug_message);
            header("Location: login.php?step=2fa&username=" . urlencode($username) . "&error=2fa");
            exit();
        }

        // Mark code as used
        $stmt = $pdo->prepare("UPDATE two_factor_codes SET used = 1 WHERE id = ?");
        $stmt->execute([$code_record['id']]);

        // Update last login using MySQL NOW()
        $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?");
        $stmt->execute([$user['user_id']]);

        // Set session
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['fullname'] = $user['fullname'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['department'] = $user['department'];

        // Log successful login
        logActivity($pdo, $user['user_id'], 'login', "Successful login");

        // Send login notification to user's own Telegram
        if (!empty($user['telegram_chat_id']) && !empty($user['telegram_token']) && $user['telegram_enabled']) {
            $message = "🔐 การเข้าสู่ระบบ\n\n";
            $message .= "👤 ผู้ใช้: {$user['fullname']} ({$user['username']})\n";
            $message .= "🏥 หน่วยงาน: {$user['department']}\n";
            $message .= "🌐 IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'Unknown') . "\n";
            $message .= "⏰ " . date('d/m/Y H:i:s');
            
            // ส่งไปยัง Telegram ของ user นั้นๆ
            sendTelegramMessageToUser($user['telegram_token'], $user['telegram_chat_id'], $message);
        }

        header('Location: index.php');
        exit();
    }

} catch (Exception $e) {
    error_log("Auth error: " . $e->getMessage());
    header('Location: login.php?error=system');
    exit();
}
?>