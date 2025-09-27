<?php
// Simplified forgot_password.php for testing
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once 'config/database.php';
require_once 'config.php';

$org_config = getOrganizationConfig();
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['username'])) {
    $username = trim($_POST['username']);
    
    if (empty($username)) {
        $message = 'กรุณากรอกชื่อผู้ใช้';
        $message_type = 'error';
    } else {
        // ค้นหาผู้ใช้
        $stmt = $pdo->prepare("SELECT user_id, username, fullname FROM users WHERE username = ? AND is_active = 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if ($user) {
            $message = 'พบผู้ใช้: ' . $user['fullname'] . ' (ในการใช้งานจริงจะส่งรหัสรีเซ็ตไปยัง Telegram)';
            $message_type = 'success';
        } else {
            $message = 'ไม่พบชื่อผู้ใช้ในระบบ';
            $message_type = 'error';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ลืมรหัสผ่าน - <?= $org_config['name'] ?></title>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.12.10/dist/full.min.css" rel="stylesheet" type="text/css" />
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Sarabun', sans-serif; }
    </style>
</head>
<body class="bg-gradient-to-br from-blue-500 to-purple-600 min-h-screen flex items-center justify-center">
    <div class="card w-full max-w-md bg-white shadow-2xl">
        <div class="card-body">
            <h2 class="card-title text-center mb-4">ลืมรหัสผ่าน</h2>
            
            <?php if ($message): ?>
                <div class="alert <?= $message_type == 'success' ? 'alert-success' : 'alert-error' ?> mb-4">
                    <?= $message ?>
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-control">
                    <label class="label">
                        <span class="label-text">ชื่อผู้ใช้</span>
                    </label>
                    <input type="text" name="username" class="input input-bordered" required 
                           value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                </div>
                
                <div class="form-control mt-6">
                    <button type="submit" class="btn btn-primary">ส่งรหัสรีเซ็ต</button>
                </div>
            </form>
            
            <div class="text-center mt-4">
                <a href="login.php" class="link link-primary">กลับไปหน้าเข้าสู่ระบบ</a>
            </div>
            
            <div class="mt-4 text-sm text-gray-600">
                <p><strong>หมายเหตุ:</strong> นี่เป็นเวอร์ชันทดสอบ</p>
                <p>เวอร์ชันเต็มจะส่งรหัสรีเซ็ตผ่าน Telegram</p>
            </div>
        </div>
    </div>
</body>
</html>