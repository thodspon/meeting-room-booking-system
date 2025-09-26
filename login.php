<?php require_once 'config.php'; $org_config = getOrganizationConfig(); ?>
<!DOCTYPE html>
<html lang="th" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ - <?= $org_config['name'] ?></title>
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
    </style>
</head>
<body class="bg-gradient min-h-screen flex items-center justify-center thai-text">
    <div class="card w-full max-w-md bg-base-100 shadow-2xl">
        <div class="card-body">
            <div class="text-center mb-6">
                <?php if (file_exists($org_config['logo_path'])): ?>
                    <div class="mb-4">
                        <img src="<?= $org_config['logo_path'] ?>" alt="<?= $org_config['name'] ?>" class="w-20 h-20 mx-auto object-contain">
                    </div>
                <?php endif; ?>
                <h1 class="text-3xl font-bold text-primary"><?= $org_config['sub_title'] ?></h1>
                <p class="text-base-content/70 mt-2"><?= $org_config['name'] ?></p>
            </div>

            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-error mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>
                        <?php 
                            if ($_GET['error'] == 'invalid') {
                                echo 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง';
                            } elseif ($_GET['error'] == 'inactive') {
                                echo 'บัญชีผู้ใช้ถูกระงับ';
                            } elseif ($_GET['error'] == '2fa') {
                                echo 'รหัส 2FA ไม่ถูกต้องหรือหมดอายุ กรุณาขอรหัสใหม่';
                            } elseif ($_GET['error'] == 'telegram') {
                                echo 'ไม่สามารถส่งรหัส 2FA ได้ กรุณาติดต่อผู้ดูแลระบบ';
                            } elseif ($_GET['error'] == 'system') {
                                echo 'เกิดข้อผิดพลาดของระบบ กรุณาลองใหม่อีกครั้ง';
                            } else {
                                echo 'เกิดข้อผิดพลาด';
                            }
                        ?>
                    </span>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>ส่งรหัส 2FA ไปยัง Telegram แล้ว กรุณาตรวจสอบ</span>
                </div>
            <?php endif; ?>

            <form action="auth.php" method="POST" id="loginForm">
                <div class="form-control">
                    <label class="label">
                        <span class="label-text">ชื่อผู้ใช้</span>
                    </label>
                    <input 
                        type="text" 
                        name="username" 
                        placeholder="กรอกชื่อผู้ใช้" 
                        class="input input-bordered" 
                        required 
                        autocomplete="username"
                        value="<?php echo isset($_GET['username']) ? htmlspecialchars($_GET['username']) : ''; ?>"
                    />
                </div>

                <div class="form-control">
                    <label class="label">
                        <span class="label-text">รหัสผ่าน</span>
                    </label>
                    <input 
                        type="password" 
                        name="password" 
                        placeholder="กรอกรหัสผ่าน" 
                        class="input input-bordered" 
                        required 
                        autocomplete="current-password"
                    />
                </div>

                <?php if (isset($_GET['step']) && $_GET['step'] == '2fa'): ?>
                <div class="form-control">
                    <label class="label">
                        <span class="label-text">รหัส 2FA</span>
                    </label>
                    <input 
                        type="text" 
                        name="two_factor_code" 
                        placeholder="กรอกรหัส 6 หลักจาก Telegram" 
                        class="input input-bordered text-center text-lg" 
                        required 
                        maxlength="6"
                        pattern="[0-9]{6}"
                        autocomplete="one-time-code"
                    />
                    <input type="hidden" name="step" value="2fa">
                    <input type="hidden" name="username" value="<?php echo htmlspecialchars($_GET['username']); ?>">
                    <label class="label">
                        <span class="label-text-alt text-info">💡 รหัสจะหมดอายุใน 5 นาที หากไม่ได้รับ กรุณา<a href="login.php" class="link">ขอรหัสใหม่</a></span>
                    </label>
                </div>
                <?php endif; ?>

                <div class="form-control mt-6">
                    <button type="submit" class="btn btn-primary">
                        <?php echo (isset($_GET['step']) && $_GET['step'] == '2fa') ? 'ยืนยันรหัส' : 'เข้าสู่ระบบ'; ?>
                    </button>
                </div>
            </form>

            <div class="divider">หรือ</div>

            <div class="flex flex-col gap-3">
                <!-- ปุ่มดูการจองสาธารณะ -->
                <a href="public_calendar.php" class="btn btn-outline btn-info hover:btn-info transition-all duration-300">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    ดูปฏิทินการจองสาธารณะ
                </a>
                
                <!-- ข้อมูลเพิ่มเติมเกี่ยวกับปุ่มสาธารณะ -->
                <div class="text-center">
                    <p class="text-xs text-base-content/60 mb-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 inline mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        ดูปฏิทินการจองได้โดยไม่ต้องเข้าสู่ระบบ
                    </p>
                </div>
                
                <!-- ลิงก์ลืมรหัสผ่าน -->
                <div class="text-center">
                    <a href="forgot_password.php" class="link link-primary">ลืมรหัสผ่าน?</a>
                </div>
            </div>

            <div class="text-center mt-4">
                <p class="text-sm text-base-content/60">
                    หากยังไม่มีบัญชี กรุณาติดต่อผู้ดูแลระบบ
                </p>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div class="fixed bottom-0 left-0 right-0 text-center p-4 text-white/80">
        <?php require_once 'version.php'; $info = getSystemInfo(); $team = getDeveloperTeam(); ?>
        <p class="text-sm">
            <?php echo $info['developer']; ?>
        </p>
        <p class="text-xs mt-1 text-blue-200">
            ทีมพัฒนา: <?php echo $team; ?>
        </p>
        <p class="text-xs mt-1">
            <?php echo $info['version']; ?> วันที่ <?php echo date('d'); ?> <?php echo getThaiMonth(date('n')); ?> <?php echo (date('Y') + 543); ?>
        </p>
    </div>

    <script>
        // Auto focus on 2FA input if present
        document.addEventListener('DOMContentLoaded', function() {
            const twoFactorInput = document.querySelector('input[name="two_factor_code"]');
            if (twoFactorInput) {
                twoFactorInput.focus();
                
                // Auto submit when 6 digits are entered
                twoFactorInput.addEventListener('input', function() {
                    if (this.value.length === 6) {
                        document.getElementById('loginForm').submit();
                    }
                });
            }
        });

        // Prevent form resubmission
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
    </script>
</body>
</html>