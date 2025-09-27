<?php
// ตั้งค่า error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

try {
    require_once 'config/database.php';
    require_once 'config.php';
    require_once 'includes/functions.php';
    
    // Get organization config
    $org_config = getOrganizationConfig();
} catch (Exception $e) {
    die('เกิดข้อผิดพลาดในการโหลดระบบ: ' . $e->getMessage());
}

$message = '';
$message_type = '';
$step = $_GET['step'] ?? 'request';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'request_reset':
                $username = trim($_POST['username']);
                
                if (empty($username)) {
                    $message = 'กรุณากรอกชื่อผู้ใช้';
                    $message_type = 'error';
                } else {
                    try {
                        // ค้นหาผู้ใช้
                        $stmt = $pdo->prepare("SELECT user_id, username, fullname, email FROM users WHERE username = ? AND is_active = 1");
                        $stmt->execute([$username]);
                        $user = $stmt->fetch();
                        
                        if ($user) {
                            // ตรวจสอบว่าตาราง password_resets มีอยู่หรือไม่
                            try {
                                $pdo->query("SELECT 1 FROM password_resets LIMIT 1");
                            } catch (PDOException $e) {
                                // สร้างตารางถ้ายังไม่มี
                                $pdo->exec("
                                    CREATE TABLE IF NOT EXISTS password_resets (
                                        user_id INT PRIMARY KEY,
                                        reset_code VARCHAR(6) NOT NULL,
                                        expires_at TIMESTAMP NOT NULL,
                                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                                        used_at TIMESTAMP NULL,
                                        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
                                    )
                                ");
                            }
                            
                            // สร้างรหัสรีเซ็ต 6 หลัก
                            $reset_code = sprintf('%06d', mt_rand(100000, 999999));
                            $expires_at = date('Y-m-d H:i:s', strtotime('+15 minutes'));
                            
                            // บันทึกรหัสรีเซ็ตในฐานข้อมูล
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
                        
                            // ส่งรหัสผ่าน Telegram
                            $telegram_message = "🔐 รีเซ็ตรหัสผ่าน - " . $org_config['name'] . "\n\n";
                            $telegram_message .= "สวัสดี " . $user['fullname'] . "\n\n";
                            $telegram_message .= "คุณได้ขอรีเซ็ตรหัสผ่านสำหรับบัญชี: " . $user['username'] . "\n\n";
                            $telegram_message .= "🔢 รหัสรีเซ็ต: <b>" . $reset_code . "</b>\n\n";
                            $telegram_message .= "⏰ รหัสนี้จะหมดอายุใน 15 นาที\n";
                            $telegram_message .= "📅 วันที่: " . formatThaiDate(date('Y-m-d')) . " เวลา " . date('H:i') . " น.\n\n";
                            $telegram_message .= "🔒 หากคุณไม่ได้ขอรีเซ็ตรหัสผ่าน กรุณาแจ้งผู้ดูแลระบบ\n\n";
                            $telegram_message .= "💡 กรอกรหัสนี้ในหน้าเว็บไซต์เพื่อสร้างรหัสผ่านใหม่";
                        
                            // ลองส่งผ่านการตั้งค่าผู้ใช้ก่อน
                            $sent = false;
                            $error_detail = '';
                            
                            if (function_exists('getUserTelegramConfig')) {
                                $user_telegram = getUserTelegramConfig($user['user_id']);
                                if ($user_telegram && $user_telegram['enabled'] && !empty($user_telegram['token']) && !empty($user_telegram['chat_id'])) {
                                    $result = sendTelegramMessageWithConfig($telegram_message, $user_telegram['token'], $user_telegram['chat_id']);
                                    if ($result && isset($result['ok']) && $result['ok']) {
                                        $sent = true;
                                    } else {
                                        $error_detail = 'User config failed: ' . (isset($result['description']) ? $result['description'] : 'Unknown error');
                                    }
                                }
                            }
                            
                            // หากส่งผ่านการตั้งค่าผู้ใช้ไม่ได้ ให้ลองส่งผ่านระบบ
                            if (!$sent) {
                                if (function_exists('getTelegramConfig')) {
                                    $system_telegram = getTelegramConfig();
                                    if ($system_telegram && $system_telegram['enabled'] && !empty($system_telegram['default_token']) && !empty($system_telegram['default_chat_id'])) {
                                        $result = sendTelegramMessageWithConfig($telegram_message, $system_telegram['default_token'], $system_telegram['default_chat_id']);
                                        if ($result && isset($result['ok']) && $result['ok']) {
                                            $sent = true;
                                        } else {
                                            $error_detail = 'System config failed: ' . (isset($result['description']) ? $result['description'] : 'Unknown error');
                                        }
                                    }
                                }
                                
                                // ลองใช้การตั้งค่าเริ่มต้นจาก constants
                                if (!$sent && defined('TELEGRAM_TOKEN') && defined('TELEGRAM_CHAT_ID')) {
                                    $result = sendTelegramMessageWithConfig($telegram_message, TELEGRAM_TOKEN, TELEGRAM_CHAT_ID);
                                    if ($result && isset($result['ok']) && $result['ok']) {
                                        $sent = true;
                                    } else {
                                        $error_detail = 'Default config failed: ' . (isset($result['description']) ? $result['description'] : 'Unknown error');
                                    }
                                }
                            }
                        
                            if ($sent) {
                                $message = 'ส่งรหัสรีเซ็ตไปยัง Telegram แล้ว กรุณาตรวจสอบข้อความ';
                                $message_type = 'success';
                                $step = 'verify';
                            } else {
                                $message = 'ไม่สามารถส่งรหัสรีเซ็ตได้ กรุณาติดต่อผู้ดูแลระบบ';
                                if (!empty($error_detail)) {
                                    $message .= ' (รายละเอียด: ' . $error_detail . ')';
                                }
                                $message_type = 'error';
                            }
                        } else {
                            $message = 'ไม่พบชื่อผู้ใช้ในระบบ';
                            $message_type = 'error';
                        }
                    } catch (Exception $e) {
                        $message = 'เกิดข้อผิดพลาดในระบบ: ' . $e->getMessage();
                        $message_type = 'error';
                    }
                }
                break;
                
            case 'verify_reset':
                $username = trim($_POST['username']);
                $reset_code = trim($_POST['reset_code']);
                $new_password = trim($_POST['new_password']);
                $confirm_password = trim($_POST['confirm_password']);
                
                if (empty($username) || empty($reset_code) || empty($new_password) || empty($confirm_password)) {
                    $message = 'กรุณากรอกข้อมูลให้ครบถ้วน';
                    $message_type = 'error';
                } elseif ($new_password !== $confirm_password) {
                    $message = 'รหัสผ่านใหม่ไม่ตรงกัน';
                    $message_type = 'error';
                } elseif (strlen($new_password) < 6) {
                    $message = 'รหัสผ่านต้องมีอย่างน้อย 6 ตัวอักษร';
                    $message_type = 'error';
                } else {
                    try {
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
                    
                        if ($reset_record) {
                            // อัพเดตรหัสผ่านใหม่
                            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                            
                            $stmt = $pdo->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE user_id = ?");
                            $update_success = $stmt->execute([$hashed_password, $reset_record['user_id']]);
                            
                            if ($update_success) {
                                // ทำเครื่องหมายว่าใช้รหัสรีเซ็ตแล้ว
                                $stmt = $pdo->prepare("UPDATE password_resets SET used_at = NOW() WHERE user_id = ? AND reset_code = ?");
                                $stmt->execute([$reset_record['user_id'], $reset_code]);
                            
                                // ส่งการแจ้งเตือนผ่าน Telegram
                                $notification_message = "✅ รีเซ็ตรหัสผ่านสำเร็จ - " . $org_config['name'] . "\n\n";
                                $notification_message .= "สวัสดี " . $reset_record['fullname'] . "\n\n";
                                $notification_message .= "🔐 รหัสผ่านของบัญชี: " . $reset_record['username'] . " ได้รับการเปลี่ยนแปลงเรียบร้อยแล้ว\n\n";
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
                            
                            $message = 'เปลี่ยนรหัสผ่านเรียบร้อยแล้ว กรุณาเข้าสู่ระบบด้วยรหัสผ่านใหม่';
                            $message_type = 'success';
                            $step = 'success';
                        } else {
                            $message = 'เกิดข้อผิดพลาดในการอัพเดตรหัสผ่าน';
                            $message_type = 'error';
                        }
                    } else {
                            $message = 'รหัสรีเซ็ตไม่ถูกต้องหรือหมดอายุแล้ว';
                            $message_type = 'error';
                        }
                    } catch (Exception $e) {
                        $message = 'เกิดข้อผิดพลาดในการรีเซ็ตรหัสผ่าน: ' . $e->getMessage();
                        $message_type = 'error';
                    }
                }
                break;
        }
    }
}

// หากมี username จาก URL ให้เก็บไว้
$username = $_GET['username'] ?? $_POST['username'] ?? '';
?>

<!DOCTYPE html>
<html lang="th" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ลืมรหัสผ่าน - <?= $org_config['name'] ?></title>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.12.10/dist/full.min.css" rel="stylesheet" type="text/css" />
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Thai Font Support -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body, html {
            font-family: 'Sarabun', 'Prompt', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            -webkit-font-feature-settings: "liga";
            font-feature-settings: "liga";
        }
        
        .thai-text {
            font-family: 'Sarabun', 'Prompt', sans-serif;
            line-height: 1.6;
        }
        
        h1, h2, h3, h4, h5, h6 {
            font-family: 'Prompt', 'Sarabun', sans-serif;
            font-weight: 600;
        }
        
        .btn {
            font-family: 'Sarabun', 'Prompt', sans-serif;
            font-weight: 500;
        }
        
        .label-text {
            font-family: 'Sarabun', 'Prompt', sans-serif;
            font-weight: 500;
        }
        
        input, textarea, select {
            font-family: 'Sarabun', 'Prompt', sans-serif;
        }
        
        .bg-gradient {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .card {
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.95);
        }
        
        .step-indicator {
            display: flex;
            justify-content: center;
            margin-bottom: 2rem;
        }
        
        .step {
            display: flex;
            align-items: center;
            padding: 0.5rem 1rem;
            border-radius: 2rem;
            margin: 0 0.5rem;
            font-size: 0.875rem;
            font-weight: 500;
        }
        
        .step.active {
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            color: white;
        }
        
        .step.inactive {
            background: #f1f5f9;
            color: #64748b;
        }
        
        .step.completed {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
        }
    </style>
</head>
<body class="bg-gradient min-h-screen flex items-center justify-center thai-text">
    <div class="card w-full max-w-md bg-base-100 shadow-2xl">
        <div class="card-body">
            <div class="text-center mb-6">
                <?php if (file_exists($org_config['logo_path'])): ?>
                    <div class="mb-4">
                        <img src="<?= $org_config['logo_path'] ?>" alt="<?= $org_config['name'] ?>" class="w-16 h-16 mx-auto object-contain">
                    </div>
                <?php endif; ?>
                <h1 class="text-2xl font-bold text-primary">รีเซ็ตรหัสผ่าน</h1>
                <p class="text-base-content/70 mt-2"><?= $org_config['name'] ?></p>
            </div>

            <!-- Step Indicator -->
            <div class="step-indicator">
                <div class="step <?= $step === 'request' ? 'active' : ($step === 'verify' || $step === 'success' ? 'completed' : 'inactive') ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    ขอรหัส
                </div>
                <div class="step <?= $step === 'verify' ? 'active' : ($step === 'success' ? 'completed' : 'inactive') ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    ยืนยัน
                </div>
                <div class="step <?= $step === 'success' ? 'completed' : 'inactive' ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    เสร็จสิ้น
                </div>
            </div>

            <!-- Alert Messages -->
            <?php if ($message): ?>
                <div class="alert <?= $message_type == 'success' ? 'alert-success' : 'alert-error' ?> mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24">
                        <?php if ($message_type == 'success'): ?>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        <?php else: ?>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        <?php endif; ?>
                    </svg>
                    <span><?= htmlspecialchars($message) ?></span>
                </div>
            <?php endif; ?>

            <?php if ($step === 'request'): ?>
                <!-- Request Reset Form -->
                <form action="" method="POST">
                    <input type="hidden" name="action" value="request_reset">
                    
                    <div class="form-control mb-4">
                        <label class="label">
                            <span class="label-text">ชื่อผู้ใช้</span>
                        </label>
                        <input 
                            type="text" 
                            name="username" 
                            placeholder="กรอกชื่อผู้ใช้ของคุณ" 
                            class="input input-bordered" 
                            required 
                            value="<?= htmlspecialchars($username) ?>"
                        />
                        <label class="label">
                            <span class="label-text-alt text-info">
                                💡 ระบบจะส่งรหัสรีเซ็ตไปยัง Telegram ของคุณ
                            </span>
                        </label>
                    </div>

                    <div class="form-control mb-6">
                        <button type="submit" class="btn btn-primary">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                            ส่งรหัสรีเซ็ต
                        </button>
                    </div>
                </form>

            <?php elseif ($step === 'verify'): ?>
                <!-- Verify Reset Form -->
                <form action="" method="POST" id="resetForm">
                    <input type="hidden" name="action" value="verify_reset">
                    <input type="hidden" name="username" value="<?= htmlspecialchars($username) ?>">
                    
                    <div class="form-control mb-4">
                        <label class="label">
                            <span class="label-text">รหัสรีเซ็ต</span>
                        </label>
                        <input 
                            type="text" 
                            name="reset_code" 
                            placeholder="กรอกรหัส 6 หลักจาก Telegram" 
                            class="input input-bordered text-center text-lg" 
                            required 
                            maxlength="6"
                            pattern="[0-9]{6}"
                            autocomplete="one-time-code"
                        />
                        <label class="label">
                            <span class="label-text-alt text-warning">
                                ⏰ รหัสจะหมดอายุใน 15 นาที
                            </span>
                        </label>
                    </div>

                    <div class="form-control mb-4">
                        <label class="label">
                            <span class="label-text">รหัสผ่านใหม่</span>
                        </label>
                        <input 
                            type="password" 
                            name="new_password" 
                            placeholder="กรอกรหัสผ่านใหม่" 
                            class="input input-bordered" 
                            required 
                            minlength="6"
                        />
                    </div>

                    <div class="form-control mb-6">
                        <label class="label">
                            <span class="label-text">ยืนยันรหัสผ่านใหม่</span>
                        </label>
                        <input 
                            type="password" 
                            name="confirm_password" 
                            placeholder="กรอกรหัสผ่านใหม่อีกครั้ง" 
                            class="input input-bordered" 
                            required 
                            minlength="6"
                        />
                    </div>

                    <div class="form-control mb-4">
                        <button type="submit" class="btn btn-primary">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            รีเซ็ตรหัสผ่าน
                        </button>
                    </div>
                </form>

                <div class="text-center">
                    <a href="?step=request" class="link link-primary text-sm">
                        ขอรหัสใหม่
                    </a>
                </div>

            <?php elseif ($step === 'success'): ?>
                <!-- Success State -->
                <div class="text-center py-8">
                    <div class="mb-6">
                        <div class="w-16 h-16 mx-auto bg-success rounded-full flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                    </div>
                    <h3 class="text-xl font-bold text-success mb-2">รีเซ็ตรหัสผ่านสำเร็จ!</h3>
                    <p class="text-base-content/70 mb-6">
                        ตอนนี้คุณสามารถเข้าสู่ระบบด้วยรหัสผ่านใหม่ได้แล้ว
                    </p>
                    <a href="login.php" class="btn btn-primary">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                        </svg>
                        เข้าสู่ระบบ
                    </a>
                </div>
            <?php endif; ?>

            <?php if ($step !== 'success'): ?>
                <div class="divider">หรือ</div>

                <div class="text-center">
                    <a href="login.php" class="link link-primary">กลับไปหน้าเข้าสู่ระบบ</a>
                </div>

                <div class="text-center mt-4">
                    <p class="text-sm text-base-content/60">
                        หากมีปัญหา กรุณาติดต่อผู้ดูแลระบบ
                    </p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Footer -->
    <div class="fixed bottom-0 left-0 right-0 text-center p-4 text-white/80">
        <?php require_once 'version.php'; $info = getSystemInfo(); ?>
        <p class="text-xs">
            <?= $info['version'] ?> | <?= $info['developer'] ?>
        </p>
    </div>

    <script>
        // Auto focus on reset code input
        document.addEventListener('DOMContentLoaded', function() {
            const resetCodeInput = document.querySelector('input[name="reset_code"]');
            if (resetCodeInput) {
                resetCodeInput.focus();
            }
            
            // Password confirmation validation
            const passwordInput = document.querySelector('input[name="new_password"]');
            const confirmInput = document.querySelector('input[name="confirm_password"]');
            
            if (passwordInput && confirmInput) {
                function validatePasswords() {
                    if (confirmInput.value && passwordInput.value !== confirmInput.value) {
                        confirmInput.setCustomValidity('รหัสผ่านไม่ตรงกัน');
                    } else {
                        confirmInput.setCustomValidity('');
                    }
                }
                
                passwordInput.addEventListener('input', validatePasswords);
                confirmInput.addEventListener('input', validatePasswords);
            }
        });

        // Prevent form resubmission
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
    </script>
</body>
</html>