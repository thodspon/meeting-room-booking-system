<?php
/**
 * หน้าการตั้งค่าองค์กร
 * สำหรับผู้ดูแลระบบเท่านั้น
 */

session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'version.php';

// ตรวจสอบการ login และสิทธิ์ admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: index.php?error=permission');
    exit();
}

// จัดการการบันทึกข้อมูล
if ($_POST['action'] === 'save' && $_POST) {
    $config_content = generateConfigFile($_POST);
    if (file_put_contents('config.php', $config_content)) {
        $success_message = "บันทึกการตั้งค่าเรียบร้อยแล้ว";
        // รีโหลดข้อมูลใหม่
        require_once 'config.php';
    } else {
        $error_message = "ไม่สามารถบันทึกการตั้งค่าได้";
    }
}

// ดึงข้อมูลปัจจุบัน
$config = getOrganizationConfig();

/**
 * สร้างเนื้อหาไฟล์ config.php ใหม่
 */
function generateConfigFile($data) {
    $config_template = '<?php
/**
 * ไฟล์การตั้งค่าองค์กร - Organization Configuration
 * ระบบจองห้องประชุม
 * 
 * *** แก้ไขชื่อองค์กรได้ที่นี่ ***
 * เปลี่ยนข้อมูลในไฟล์นี้เพื่อปรับแต่งให้เหมาะกับหน่วยงานของคุณ
 * 
 * อัพเดตล่าสุด: ' . date('Y-m-d H:i:s') . '
 */

// ============================================
// การตั้งค่าข้อมูลองค์กร
// ============================================

// ชื่อหน่วยงาน/องค์กร (แก้ไขได้ตามต้องการ)
$organization_config = [
    \'name\' => \'' . addslashes($data['name']) . '\',
    \'name_english\' => \'' . addslashes($data['name_english']) . '\',
    \'address\' => \'' . addslashes($data['address']) . '\',
    \'phone\' => \'' . addslashes($data['phone']) . '\',
    \'email\' => \'' . addslashes($data['email']) . '\',
    \'website\' => \'' . addslashes($data['website']) . '\',
    
    // ข้อมูลสำหรับ Header ของเอกสาร
    \'logo_path\' => \'' . addslashes($data['logo_path']) . '\',
    \'header_title\' => \'' . addslashes($data['header_title']) . '\',
    \'sub_title\' => \'' . addslashes($data['sub_title']) . '\'
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
    return $organization_config[\'name\'];
}

/**
 * ดึงชื่อองค์กรภาษาอังกฤษ
 */
function getOrganizationNameEnglish() {
    global $organization_config;
    return $organization_config[\'name_english\'];
}

/**
 * ดึงข้อมูลติดต่อ
 */
function getOrganizationContact() {
    global $organization_config;
    return [
        \'address\' => $organization_config[\'address\'],
        \'phone\' => $organization_config[\'phone\'],
        \'email\' => $organization_config[\'email\'],
        \'website\' => $organization_config[\'website\']
    ];
}

/**
 * ดึงข้อมูลสำหรับ Header
 */
function getOrganizationHeader() {
    global $organization_config;
    return [
        \'logo_path\' => $organization_config[\'logo_path\'],
        \'header_title\' => $organization_config[\'header_title\'],
        \'sub_title\' => $organization_config[\'sub_title\']
    ];
}
?>';
    
    return $config_template;
}
?>

<!DOCTYPE html>
<html lang="th" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>การตั้งค่าองค์กร - ระบบจองห้องประชุม</title>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.12.10/dist/full.min.css" rel="stylesheet" type="text/css" />
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        * {
            font-family: 'Prompt', sans-serif;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <div class="navbar bg-primary text-primary-content">
        <div class="navbar-start">
            <a class="btn btn-ghost text-xl flex items-center gap-2" href="index.php">
                <?php if (file_exists($org_config['logo_path'])): ?>
                    <img src="<?= $org_config['logo_path'] ?>" alt="Logo" class="w-8 h-8 object-contain">
                <?php endif; ?>
                <?= $org_config['sub_title'] ?>
            </a>
        </div>
        <div class="navbar-center">
            <span class="text-lg font-semibold">การตั้งค่าองค์กร</span>
        </div>
        <div class="navbar-end">
            <div class="dropdown dropdown-end">
                <div tabindex="0" role="button" class="btn btn-ghost">
                    สวัสดี, <?php echo htmlspecialchars($_SESSION['username']); ?>
                </div>
                <ul tabindex="0" class="dropdown-content menu bg-base-100 rounded-box z-[1] w-52 p-2 shadow">
                    <li><a href="index.php" class="text-base-content">กลับหน้าหลัก</a></li>
                    <li><a href="logout.php" class="text-base-content">ออกจากระบบ</a></li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container mx-auto p-4">
        <div class="breadcrumbs text-sm mb-4">
            <ul>
                <li><a href="index.php">หน้าหลัก</a></li>
                <li><a href="users.php">จัดการระบบ</a></li>
                <li>การตั้งค่าองค์กร</li>
            </ul>
        </div>

        <!-- แสดงข้อความสถานะ -->
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span><?php echo $success_message; ?></span>
            </div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-error mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span><?php echo $error_message; ?></span>
            </div>
        <?php endif; ?>

        <!-- การตั้งค่าองค์กร -->
        <div class="card bg-base-100 shadow-xl">
            <div class="card-body">
                <h2 class="card-title text-2xl mb-6">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                    การตั้งค่าข้อมูลองค์กร
                </h2>

                <form method="POST" class="space-y-6">
                    <input type="hidden" name="action" value="save">
                    
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- ข้อมูลพื้นฐาน -->
                        <div class="space-y-4">
                            <h3 class="text-lg font-semibold text-primary">ข้อมูลพื้นฐาน</h3>
                            
                            <div class="form-control">
                                <label class="label">
                                    <span class="label-text">ชื่อองค์กร (ภาษาไทย) *</span>
                                </label>
                                <input type="text" name="name" class="input input-bordered" 
                                       value="<?php echo htmlspecialchars($config['name']); ?>" required>
                            </div>
                            
                            <div class="form-control">
                                <label class="label">
                                    <span class="label-text">ชื่อองค์กร (ภาษาอังกฤษ)</span>
                                </label>
                                <input type="text" name="name_english" class="input input-bordered" 
                                       value="<?php echo htmlspecialchars($config['name_english']); ?>">
                            </div>
                            
                            <div class="form-control">
                                <label class="label">
                                    <span class="label-text">ที่อยู่</span>
                                </label>
                                <textarea name="address" class="textarea textarea-bordered" rows="3"><?php echo htmlspecialchars($config['address']); ?></textarea>
                            </div>
                            
                            <div class="form-control">
                                <label class="label">
                                    <span class="label-text">หมายเลขโทรศัพท์</span>
                                </label>
                                <input type="text" name="phone" class="input input-bordered" 
                                       value="<?php echo htmlspecialchars($config['phone']); ?>">
                            </div>
                        </div>
                        
                        <!-- ข้อมูลติดต่อและการแสดงผล -->
                        <div class="space-y-4">
                            <h3 class="text-lg font-semibold text-secondary">ข้อมูลติดต่อและการแสดงผล</h3>
                            
                            <div class="form-control">
                                <label class="label">
                                    <span class="label-text">อีเมล</span>
                                </label>
                                <input type="email" name="email" class="input input-bordered" 
                                       value="<?php echo htmlspecialchars($config['email']); ?>">
                            </div>
                            
                            <div class="form-control">
                                <label class="label">
                                    <span class="label-text">เว็บไซต์</span>
                                </label>
                                <input type="url" name="website" class="input input-bordered" 
                                       value="<?php echo htmlspecialchars($config['website']); ?>">
                            </div>
                            
                            <div class="form-control">
                                <label class="label">
                                    <span class="label-text">เส้นทางโลโก้</span>
                                </label>
                                <input type="text" name="logo_path" class="input input-bordered" 
                                       value="<?php echo htmlspecialchars($config['logo_path']); ?>">
                                <label class="label">
                                    <span class="label-text-alt">ตัวอย่าง: assets/images/logo.png</span>
                                </label>
                            </div>
                            
                            <div class="form-control">
                                <label class="label">
                                    <span class="label-text">หัวข้อหลัก (สำหรับเอกสาร)</span>
                                </label>
                                <input type="text" name="header_title" class="input input-bordered" 
                                       value="<?php echo htmlspecialchars($config['header_title']); ?>">
                            </div>
                            
                            <div class="form-control">
                                <label class="label">
                                    <span class="label-text">หัวข้อรอง</span>
                                </label>
                                <input type="text" name="sub_title" class="input input-bordered" 
                                       value="<?php echo htmlspecialchars($config['sub_title']); ?>">
                            </div>
                        </div>
                    </div>
                    
                    <!-- ปุ่มบันทึก -->
                    <div class="card-actions justify-end mt-8">
                        <a href="users.php" class="btn btn-ghost">ยกเลิก</a>
                        <button type="submit" class="btn btn-primary">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            บันทึกการตั้งค่า
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- คู่มือการใช้งาน -->
        <div class="card bg-base-100 shadow-xl mt-6">
            <div class="card-body">
                <h3 class="card-title text-lg">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    คู่มือการใช้งาน
                </h3>
                
                <div class="prose max-w-none">
                    <h4>วิธีเปลี่ยนชื่อองค์กร:</h4>
                    <ol>
                        <li><strong>ผ่านหน้าเว็บนี้:</strong> แก้ไขข้อมูลในฟอร์มข้างบนและกดบันทึก</li>
                        <li><strong>แก้ไขไฟล์โดยตรง:</strong> เปิดไฟล์ <code>config.php</code> และแก้ไขข้อมูลใน <code>$organization_config</code></li>
                    </ol>
                    
                    <h4>ตัวอย่างชื่อองค์กรที่นิยม:</h4>
                    <ul>
                        <li>โรงพยาบาลร้อยเอ็ด</li>
                        <li>สำนักงานสาธารณสุขจังหวัดร้อยเอ็ด</li>
                        <li>ศูนย์บริการสาธารณสุข</li>
                        <li>คลินิกแพทย์ครอบครัว</li>
                        <li>โรงพยาบาลส่งเสริมสุขภาพตำบล</li>
                        <li>ศูนย์สุขภาพชุมชน</li>
                    </ul>
                    
                    <div class="alert alert-info mt-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span><strong>หมายเหตุ:</strong> การเปลี่ยนแปลงจะมีผลทันทีกับทุกหน้าในระบบ รวมถึง Footer, รายงาน และเอกสารต่างๆ</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <?php echo getSystemFooter(); ?>
</body>
</html>