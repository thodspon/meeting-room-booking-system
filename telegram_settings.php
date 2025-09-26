<?php
session_start();
require_once 'config/database.php';
require_once 'config.php';
require_once 'includes/functions.php';

// ตรวจสอบการเข้าสู่ระบบ
if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$current_user = getCurrentUser();
$user_id = $current_user['id'];
$is_admin = isAdmin($current_user['role']);

// ดึงการตั้งค่า Telegram ปัจจุบันของผู้ใช้
$user_telegram_config = getUserTelegramConfig($user_id);
$system_telegram_config = getTelegramConfig();

$message = '';
$message_type = '';

// จัดการการส่งฟอร์ม
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'save_settings':
                $token = trim($_POST['telegram_token']);
                $chat_id = trim($_POST['chat_id']);
                $enabled = isset($_POST['enabled']) ? true : false;
                
                // ตรวจสอบข้อมูล
                if (empty($token) || empty($chat_id)) {
                    $message = 'กรุณากรอก Token และ Chat ID';
                    $message_type = 'error';
                } else {
                    // บันทึกการตั้งค่า
                    if (saveUserTelegramConfig($user_id, $token, $chat_id, $enabled)) {
                        $message = 'บันทึกการตั้งค่าสำเร็จ';
                        $message_type = 'success';
                        // อัพเดตข้อมูลในตัวแปร
                        $user_telegram_config = getUserTelegramConfig($user_id);
                    } else {
                        $message = 'เกิดข้อผิดพลาดในการบันทึก';
                        $message_type = 'error';
                    }
                }
                break;
                
            case 'test_message':
                $token = trim($_POST['test_token']);
                $chat_id = trim($_POST['test_chat_id']);
                
                if (empty($token) || empty($chat_id)) {
                    $message = 'กรุณากรอก Token และ Chat ID เพื่อทดสอบ';
                    $message_type = 'error';
                } else {
                    $test_result = testTelegramMessage($token, $chat_id);
                    if ($test_result['success']) {
                        $message = $test_result['message'];
                        $message_type = 'success';
                    } else {
                        $message = 'การทดสอบล้มเหลว: ' . $test_result['error'];
                        $message_type = 'error';
                    }
                }
                break;
                
            case 'save_system_settings':
                // เฉพาะ Admin เท่านั้น
                if (!$is_admin) {
                    $message = 'คุณไม่มีสิทธิ์ในการแก้ไขการตั้งค่าระบบ';
                    $message_type = 'error';
                    break;
                }
                
                $system_token = trim($_POST['system_token']);
                $system_chat_id = trim($_POST['system_chat_id']);
                $system_enabled = isset($_POST['system_enabled']) ? true : false;
                
                // ตรวจสอบข้อมูล
                if (empty($system_token) || empty($system_chat_id)) {
                    $message = 'กรุณากรอก Token และ Chat ID สำหรับระบบ';
                    $message_type = 'error';
                } else {
                    // บันทึกการตั้งค่าระบบ
                    if (saveSystemTelegramConfig($system_token, $system_chat_id, $system_enabled)) {
                        $message = 'บันทึกการตั้งค่าระบบสำเร็จ';
                        $message_type = 'success';
                        // รีโหลดการตั้งค่าระบบ
                        $system_telegram_config = getTelegramConfig();
                    } else {
                        $message = 'เกิดข้อผิดพลาดในการบันทึกการตั้งค่าระบบ';
                        $message_type = 'error';
                    }
                }
                break;
        }
    }
}

$org_config = getOrganizationConfig();
$page_title = 'ตั้งค่า Telegram';
?>
<!DOCTYPE html>
<html lang="th" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?> - <?= $org_config['name'] ?></title>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@3.9.4/dist/full.css" rel="stylesheet" type="text/css" />
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body class="min-h-screen bg-base-200">
    <!-- Navigation -->
    <div class="navbar bg-primary text-primary-content shadow-lg">
        <div class="flex-1">
            <a href="index.php" class="btn btn-ghost text-xl flex items-center gap-2">
                <?php if (file_exists($org_config['logo_path'])): ?>
                    <img src="<?= $org_config['logo_path'] ?>" alt="Logo" class="w-8 h-8 object-contain">
                <?php endif; ?>
                <i class="fas fa-calendar-alt"></i>
                <?= $org_config['sub_title'] ?>
            </a>
        </div>
        <div class="flex-none">
            <div class="dropdown dropdown-end">
                <div tabindex="0" role="button" class="btn btn-ghost">
                    <i class="fas fa-user mr-2"></i>
                    <?= htmlspecialchars($current_user['full_name']) ?>
                    <i class="fas fa-chevron-down ml-2"></i>
                </div>
                <ul tabindex="0" class="menu menu-sm dropdown-content mt-3 z-[1] p-2 shadow bg-base-100 rounded-box w-52 text-base-content">
                    <li><a href="profile.php"><i class="fas fa-user-edit mr-2"></i>โปรไฟล์</a></li>
                    <li><a href="telegram_settings.php" class="active"><i class="fab fa-telegram mr-2"></i>ตั้งค่า Telegram</a></li>
                    <li><a href="my_bookings.php"><i class="fas fa-calendar-check mr-2"></i>การจองของฉัน</a></li>
                    <li><hr></li>
                    <li><a href="logout.php"><i class="fas fa-sign-out-alt mr-2"></i>ออกจากระบบ</a></li>
                </ul>
            </div>
        </div>
    </div>

    <div class="container mx-auto px-4 py-8">
        <!-- Breadcrumb -->
        <div class="text-sm breadcrumbs mb-6">
            <ul>
                <li><a href="index.php"><i class="fas fa-home mr-1"></i>หน้าหลัก</a></li>
                <li><a href="profile.php"><i class="fas fa-user mr-1"></i>โปรไฟล์</a></li>
                <li><i class="fab fa-telegram mr-1"></i>ตั้งค่า Telegram</li>
            </ul>
        </div>

        <!-- Alert Message -->
        <?php if ($message): ?>
        <div class="alert <?= $message_type == 'success' ? 'alert-success' : 'alert-error' ?> mb-6">
            <i class="fas <?= $message_type == 'success' ? 'fa-check-circle' : 'fa-exclamation-circle' ?>"></i>
            <span><?= htmlspecialchars($message) ?></span>
        </div>
        <?php endif; ?>

        <?php if ($is_admin): ?>
        <!-- Admin Panel -->
        <div class="card bg-gradient-to-r from-purple-500 to-pink-500 text-white shadow-xl mb-6">
            <div class="card-body">
                <h2 class="card-title text-white">
                    <i class="fas fa-crown mr-2"></i>
                    การตั้งค่าระบบ (Admin)
                </h2>
                <p class="text-white/90 text-sm mb-4">
                    ตั้งค่า Telegram เริ่มต้นสำหรับผู้ใช้ที่ยังไม่ได้ตั้งค่าส่วนตัว
                </p>
                
                <form method="POST" class="space-y-4">
                    <input type="hidden" name="action" value="save_system_settings">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="form-control">
                            <label class="label">
                                <span class="label-text text-white font-semibold">
                                    <i class="fas fa-key mr-1"></i>
                                    System Token
                                </span>
                            </label>
                            <input 
                                type="text" 
                                name="system_token" 
                                value="<?= htmlspecialchars($system_telegram_config['default_token']) ?>" 
                                placeholder="Token สำหรับระบบ" 
                                class="input input-bordered w-full text-black" 
                                required
                            >
                        </div>
                        
                        <div class="form-control">
                            <label class="label">
                                <span class="label-text text-white font-semibold">
                                    <i class="fas fa-hashtag mr-1"></i>
                                    System Chat ID
                                </span>
                            </label>
                            <input 
                                type="text" 
                                name="system_chat_id" 
                                value="<?= htmlspecialchars($system_telegram_config['default_chat_id']) ?>" 
                                placeholder="Chat ID สำหรับระบบ" 
                                class="input input-bordered w-full text-black" 
                                required
                            >
                        </div>
                    </div>
                    
                    <div class="form-control">
                        <label class="cursor-pointer label justify-start">
                            <input 
                                type="checkbox" 
                                name="system_enabled" 
                                class="checkbox checkbox-secondary mr-3" 
                                <?= $system_telegram_config['enabled'] ? 'checked' : '' ?>
                            >
                            <span class="label-text text-white">
                                <i class="fas fa-toggle-on mr-1"></i>
                                เปิดใช้งานระบบ Telegram
                            </span>
                        </label>
                    </div>
                    
                    <div class="card-actions">
                        <button type="submit" class="btn btn-secondary">
                            <i class="fas fa-cog mr-2"></i>
                            บันทึกการตั้งค่าระบบ
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- การตั้งค่า Telegram -->
            <div class="card bg-base-100 shadow-xl">
                <div class="card-body">
                    <h2 class="card-title text-primary">
                        <i class="fab fa-telegram mr-2"></i>
                        การตั้งค่า Telegram
                    </h2>
                    <p class="text-sm text-base-content/70 mb-4">
                        ตั้งค่า Telegram Bot สำหรับรับข้อความแจ้งเตือนและ 2FA ของคุณ
                    </p>

                    <form method="POST" class="space-y-4">
                        <input type="hidden" name="action" value="save_settings">
                        
                        <div class="form-control">
                            <label class="label">
                                <span class="label-text font-semibold">
                                    <i class="fas fa-key mr-1"></i>
                                    Telegram Bot Token
                                </span>
                            </label>
                            <input 
                                type="text" 
                                name="telegram_token" 
                                value="<?= htmlspecialchars($user_telegram_config['token']) ?>" 
                                placeholder="123456789:ABCDefghijklmn..." 
                                class="input input-bordered w-full" 
                                required
                            >
                            <label class="label">
                                <span class="label-text-alt text-info">
                                    <i class="fas fa-info-circle mr-1"></i>
                                    ได้รับจาก @BotFather ใน Telegram
                                </span>
                            </label>
                        </div>

                        <div class="form-control">
                            <label class="label">
                                <span class="label-text font-semibold">
                                    <i class="fas fa-hashtag mr-1"></i>
                                    Chat ID
                                </span>
                            </label>
                            <input 
                                type="text" 
                                name="chat_id" 
                                value="<?= htmlspecialchars($user_telegram_config['chat_id']) ?>" 
                                placeholder="123456789 หรือ -100123456789" 
                                class="input input-bordered w-full" 
                                required
                            >
                            <label class="label">
                                <span class="label-text-alt text-info">
                                    <i class="fas fa-info-circle mr-1"></i>
                                    ID ของคุณหรือกลุ่มที่ต้องการรับข้อความ
                                </span>
                            </label>
                        </div>

                        <div class="form-control">
                            <label class="cursor-pointer label justify-start">
                                <input 
                                    type="checkbox" 
                                    name="enabled" 
                                    class="checkbox checkbox-primary mr-3" 
                                    <?= $user_telegram_config['enabled'] ? 'checked' : '' ?>
                                >
                                <span class="label-text">
                                    <i class="fas fa-bell mr-1"></i>
                                    เปิดใช้งาน Telegram
                                </span>
                            </label>
                        </div>

                        <div class="card-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save mr-2"></i>
                                บันทึกการตั้งค่า
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- ทดสอบการส่งข้อความ -->
            <div class="card bg-base-100 shadow-xl">
                <div class="card-body">
                    <h2 class="card-title text-accent">
                        <i class="fas fa-paper-plane mr-2"></i>
                        ทดสอบการส่งข้อความ
                    </h2>
                    <p class="text-sm text-base-content/70 mb-4">
                        ทดสอบการเชื่อมต่อ Telegram Bot ก่อนบันทึกการตั้งค่า
                    </p>

                    <form method="POST" class="space-y-4">
                        <input type="hidden" name="action" value="test_message">
                        
                        <div class="form-control">
                            <label class="label">
                                <span class="label-text font-semibold">
                                    <i class="fas fa-key mr-1"></i>
                                    Token (สำหรับทดสอบ)
                                </span>
                            </label>
                            <input 
                                type="text" 
                                name="test_token" 
                                value="<?= htmlspecialchars($user_telegram_config['token']) ?>" 
                                placeholder="123456789:ABCDef..." 
                                class="input input-bordered w-full" 
                                required
                            >
                        </div>

                        <div class="form-control">
                            <label class="label">
                                <span class="label-text font-semibold">
                                    <i class="fas fa-hashtag mr-1"></i>
                                    Chat ID (สำหรับทดสอบ)
                                </span>
                            </label>
                            <input 
                                type="text" 
                                name="test_chat_id" 
                                value="<?= htmlspecialchars($user_telegram_config['chat_id']) ?>" 
                                placeholder="123456789" 
                                class="input input-bordered w-full" 
                                required
                            >
                        </div>

                        <div class="card-actions">
                            <button type="submit" class="btn btn-accent">
                                <i class="fas fa-paper-plane mr-2"></i>
                                ทดสอบส่งข้อความ
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- คู่มือการใช้งาน -->
        <div class="card bg-base-100 shadow-xl mt-6">
            <div class="card-body">
                <h2 class="card-title text-info">
                    <i class="fas fa-book mr-2"></i>
                    คู่มือการตั้งค่า Telegram Bot
                </h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-4">
                    <div>
                        <h3 class="font-semibold text-lg mb-3">
                            <i class="fas fa-robot mr-2"></i>
                            วิธีสร้าง Telegram Bot
                        </h3>
                        <ol class="list-decimal list-inside space-y-2 text-sm">
                            <li>เปิด Telegram และค้นหา <code class="bg-base-200 px-2 py-1 rounded">@BotFather</code></li>
                            <li>ส่งคำสั่ง <code class="bg-base-200 px-2 py-1 rounded">/newbot</code></li>
                            <li>ตั้งชื่อ Bot ของคุณ</li>
                            <li>ตั้ง Username สำหรับ Bot (ต้องลงท้ายด้วย "bot")</li>
                            <li>คัดลอก Token ที่ได้รับมาใส่ในช่องด้านบน</li>
                        </ol>
                    </div>
                    
                    <div>
                        <h3 class="font-semibold text-lg mb-3">
                            <i class="fas fa-hashtag mr-2"></i>
                            วิธีหา Chat ID
                        </h3>
                        <ol class="list-decimal list-inside space-y-2 text-sm">
                            <li>ค้นหา <code class="bg-base-200 px-2 py-1 rounded">@userinfobot</code> ใน Telegram</li>
                            <li>ส่งข้อความอะไรก็ได้ให้ Bot</li>
                            <li>Bot จะตอบกลับ Chat ID ของคุณ</li>
                            <li>หรือค้นหา <code class="bg-base-200 px-2 py-1 rounded">@RawDataBot</code> เพื่อดูข้อมูลทั้งหมด</li>
                            <li>นำ ID ที่ได้มาใส่ในช่องด้านบน</li>
                        </ol>
                    </div>
                </div>

                <div class="alert alert-info mt-4">
                    <i class="fas fa-lightbulb"></i>
                    <div>
                        <h4 class="font-semibold">เคล็ดลับ:</h4>
                        <ul class="text-sm mt-1 space-y-1">
                            <li>• หากต้องการรับข้อความในกลุ่ม ให้เพิ่ม Bot เข้ากลุ่มก่อน</li>
                            <li>• Chat ID ของกลุ่มจะเป็นเลขลบ เช่น -100123456789</li>
                            <li>• ใช้ปุ่ม "ทดสอบส่งข้อความ" เพื่อตรวจสอบการตั้งค่าก่อนบันทึก</li>
                            <?php if ($is_admin): ?>
                            <li>• <strong>Admin:</strong> การตั้งค่าระบบจะเป็นค่าเริ่มต้นสำหรับผู้ใช้ใหม่</li>
                            <li>• <strong>Admin:</strong> ผู้ใช้สามารถตั้งค่า Telegram ส่วนตัวได้ โดยจะใช้แทนค่าระบบ</li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer footer-center p-10 bg-primary text-primary-content mt-10">
        <aside>
            <p class="font-bold text-lg"><?= $org_config['name'] ?></p>
            <p><?= $org_config['address'] ?></p>
            <p>โทร: <?= $org_config['phone'] ?> | อีเมล: <?= $org_config['email'] ?></p>
            <?php 
            $version_info = include 'version.php';
            echo "<p class='text-sm opacity-75'>เวอร์ชัน " . $version_info['version'] . " " . $version_info['edition'] . " | " . $version_info['team'] . "</p>";
            echo "<p class='text-sm opacity-75'>พัฒนาโดย " . $version_info['developer_name'] . " " . $version_info['developer_position'] . "</p>";
            ?>
        </aside>
    </footer>

    <script>
        // Auto-fill test fields when main fields change
        document.addEventListener('DOMContentLoaded', function() {
            const tokenInput = document.querySelector('input[name="telegram_token"]');
            const chatIdInput = document.querySelector('input[name="chat_id"]');
            const testTokenInput = document.querySelector('input[name="test_token"]');
            const testChatIdInput = document.querySelector('input[name="test_chat_id"]');
            
            if (tokenInput && testTokenInput) {
                tokenInput.addEventListener('input', function() {
                    testTokenInput.value = this.value;
                });
            }
            
            if (chatIdInput && testChatIdInput) {
                chatIdInput.addEventListener('input', function() {
                    testChatIdInput.value = this.value;
                });
            }
        });
    </script>
</body>
</html>