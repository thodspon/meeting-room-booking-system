<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// ตรวจสอบการ login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// ดึงข้อมูลผู้ใช้
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

$error = '';
$success = '';

// ประมวลผลการแก้ไขโปรไฟล์
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $fullname = trim($_POST['fullname']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        $department = trim($_POST['department']);
        $position = trim($_POST['position']);
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        if (empty($fullname)) {
            throw new Exception('กรุณากรอกชื่อ-นามสกุล');
        }
        
        // ตรวจสอบรหัสผ่านเก่า
        if (!empty($new_password)) {
            if (empty($current_password)) {
                throw new Exception('กรุณากรอกรหัสผ่านเก่า');
            }
            
            if (!password_verify($current_password, $user['password'])) {
                throw new Exception('รหัสผ่านเก่าไม่ถูกต้อง');
            }
            
            if ($new_password !== $confirm_password) {
                throw new Exception('รหัสผ่านใหม่ไม่ตรงกัน');
            }
            
            if (strlen($new_password) < 6) {
                throw new Exception('รหัสผ่านใหม่ต้องมีอย่างน้อย 6 ตัวอักษร');
            }
            
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            
            $stmt = $pdo->prepare("
                UPDATE users 
                SET fullname = ?, email = ?, phone = ?, department = ?, position = ?, password = ?
                WHERE user_id = ?
            ");
            $stmt->execute([$fullname, $email, $phone, $department, $position, $hashed_password, $user_id]);
        } else {
            $stmt = $pdo->prepare("
                UPDATE users 
                SET fullname = ?, email = ?, phone = ?, department = ?, position = ?
                WHERE user_id = ?
            ");
            $stmt->execute([$fullname, $email, $phone, $department, $position, $user_id]);
        }
        
        // อัพเดทข้อมูล session
        $_SESSION['fullname'] = $fullname;
        $_SESSION['department'] = $department;
        
        logActivity($pdo, $user_id, 'update_profile', "Updated profile");
        $success = 'อัพเดทข้อมูลโปรไฟล์เรียบร้อยแล้ว';
        
        // รีเฟรชข้อมูลผู้ใช้
        $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// สถิติการใช้งาน
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_bookings,
        COUNT(CASE WHEN status = 'approved' THEN 1 END) as approved_bookings,
        COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_bookings,
        COUNT(CASE WHEN status = 'rejected' THEN 1 END) as rejected_bookings
    FROM bookings 
    WHERE user_id = ?
");
$stmt->execute([$user_id]);
$stats = $stmt->fetch();

// การจองล่าสุด
$stmt = $pdo->prepare("
    SELECT b.*, r.room_name 
    FROM bookings b 
    JOIN rooms r ON b.room_id = r.room_id 
    WHERE b.user_id = ? 
    ORDER BY b.created_at DESC 
    LIMIT 5
");
$stmt->execute([$user_id]);
$recent_bookings = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="th" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>โปรไฟล์ - ระบบจองห้องประชุม</title>
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
        <div class="navbar-end">
            <div class="dropdown dropdown-end">
                <div tabindex="0" role="button" class="btn btn-ghost">
                    สวัสดี, <?php echo htmlspecialchars($_SESSION['username']); ?>
                </div>
                <ul tabindex="0" class="dropdown-content menu bg-base-100 rounded-box z-[1] w-52 p-2 shadow">
                    <li><a href="profile.php" class="text-base-content">โปรไฟล์</a></li>
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
                <li>โปรไฟล์</li>
            </ul>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- ข้อมูลโปรไฟล์ -->
            <div class="lg:col-span-2">
                <div class="card bg-base-100 shadow-xl">
                    <div class="card-body">
                        <h3 class="card-title">ข้อมูลโปรไฟล์</h3>
                        
                        <?php if ($error): ?>
                            <div class="alert alert-error">
                                <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span><?php echo htmlspecialchars($error); ?></span>
                            </div>
                        <?php endif; ?>

                        <?php if ($success): ?>
                            <div class="alert alert-success">
                                <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span><?php echo htmlspecialchars($success); ?></span>
                            </div>
                        <?php endif; ?>

                        <form method="POST">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="form-control">
                                    <label class="label">
                                        <span class="label-text">ชื่อผู้ใช้</span>
                                    </label>
                                    <input type="text" class="input input-bordered" 
                                           value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
                                </div>

                                <div class="form-control">
                                    <label class="label">
                                        <span class="label-text">ชื่อ-นามสกุล *</span>
                                    </label>
                                    <input type="text" name="fullname" class="input input-bordered" required 
                                           value="<?php echo htmlspecialchars($user['fullname']); ?>">
                                </div>

                                <div class="form-control">
                                    <label class="label">
                                        <span class="label-text">อีเมล</span>
                                    </label>
                                    <input type="email" name="email" class="input input-bordered" 
                                           value="<?php echo htmlspecialchars($user['email']); ?>">
                                </div>

                                <div class="form-control">
                                    <label class="label">
                                        <span class="label-text">เบอร์โทรศัพท์</span>
                                    </label>
                                    <input type="tel" name="phone" class="input input-bordered" 
                                           value="<?php echo htmlspecialchars($user['phone']); ?>">
                                </div>

                                <div class="form-control">
                                    <label class="label">
                                        <span class="label-text">หน่วยงาน</span>
                                    </label>
                                    <input type="text" name="department" class="input input-bordered" 
                                           value="<?php echo htmlspecialchars($user['department']); ?>">
                                </div>

                                <div class="form-control">
                                    <label class="label">
                                        <span class="label-text">ตำแหน่ง</span>
                                    </label>
                                    <input type="text" name="position" class="input input-bordered" 
                                           value="<?php echo htmlspecialchars($user['position']); ?>">
                                </div>
                            </div>

                            <div class="divider">เปลี่ยนรหัสผ่าน</div>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div class="form-control">
                                    <label class="label">
                                        <span class="label-text">รหัสผ่านเก่า</span>
                                    </label>
                                    <input type="password" name="current_password" class="input input-bordered" 
                                           placeholder="กรอกรหัสผ่านเก่า">
                                </div>

                                <div class="form-control">
                                    <label class="label">
                                        <span class="label-text">รหัสผ่านใหม่</span>
                                    </label>
                                    <input type="password" name="new_password" class="input input-bordered" 
                                           placeholder="กรอกรหัสผ่านใหม่">
                                </div>

                                <div class="form-control">
                                    <label class="label">
                                        <span class="label-text">ยืนยันรหัสผ่านใหม่</span>
                                    </label>
                                    <input type="password" name="confirm_password" class="input input-bordered" 
                                           placeholder="ยืนยันรหัสผ่านใหม่">
                                </div>
                            </div>

                            <div class="form-control mt-6">
                                <button type="submit" class="btn btn-primary">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                    บันทึกการเปลี่ยนแปลง
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- สถิติและข้อมูลเพิ่มเติม -->
            <div class="lg:col-span-1">
                <!-- ข้อมูลบัญชี -->
                <div class="card bg-base-100 shadow-xl mb-6">
                    <div class="card-body">
                        <h3 class="card-title">ข้อมูลบัญชี</h3>
                        
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span>บทบาท:</span>
                                <span class="badge <?php 
                                    echo $user['role'] === 'admin' ? 'badge-error' : 
                                         ($user['role'] === 'manager' ? 'badge-warning' : 'badge-info'); 
                                ?>">
                                    <?php 
                                        echo $user['role'] === 'admin' ? 'ผู้ดูแลระบบ' : 
                                             ($user['role'] === 'manager' ? 'ผู้จัดการ' : 'ผู้ใช้'); 
                                    ?>
                                </span>
                            </div>
                            <div class="flex justify-between">
                                <span>สถานะ:</span>
                                <span class="badge <?php echo $user['is_active'] ? 'badge-success' : 'badge-error'; ?>">
                                    <?php echo $user['is_active'] ? 'ใช้งาน' : 'ระงับ'; ?>
                                </span>
                            </div>
                            <div class="flex justify-between">
                                <span>สมาชิกเมื่อ:</span>
                                <span><?php echo formatThaiDate(date('Y-m-d', strtotime($user['created_at']))); ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span>เข้าสู่ระบบล่าสุด:</span>
                                <span>
                                    <?php echo $user['last_login'] ? formatThaiDate(date('Y-m-d', strtotime($user['last_login']))) : 'ยังไม่เคยเข้าสู่ระบบ'; ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- สถิติการจอง -->
                <div class="card bg-base-100 shadow-xl mb-6">
                    <div class="card-body">
                        <h3 class="card-title">สถิติการจอง</h3>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div class="stat">
                                <div class="stat-title">ทั้งหมด</div>
                                <div class="stat-value text-primary"><?php echo $stats['total_bookings']; ?></div>
                            </div>
                            <div class="stat">
                                <div class="stat-title">อนุมัติ</div>
                                <div class="stat-value text-success"><?php echo $stats['approved_bookings']; ?></div>
                            </div>
                            <div class="stat">
                                <div class="stat-title">รออนุมัติ</div>
                                <div class="stat-value text-warning"><?php echo $stats['pending_bookings']; ?></div>
                            </div>
                            <div class="stat">
                                <div class="stat-title">ไม่อนุมัติ</div>
                                <div class="stat-value text-error"><?php echo $stats['rejected_bookings']; ?></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- การจองล่าสุด -->
                <div class="card bg-base-100 shadow-xl">
                    <div class="card-body">
                        <h3 class="card-title">การจองล่าสุด</h3>
                        
                        <?php if (empty($recent_bookings)): ?>
                            <p class="text-gray-500 text-center py-4">ยังไม่มีการจอง</p>
                        <?php else: ?>
                            <div class="space-y-2">
                                <?php foreach ($recent_bookings as $booking): ?>
                                    <div class="border rounded-lg p-3">
                                        <div class="flex justify-between items-start">
                                            <div>
                                                <div class="font-semibold"><?php echo htmlspecialchars($booking['room_name']); ?></div>
                                                <div class="text-sm text-gray-600">
                                                    <?php echo formatThaiDate($booking['booking_date']); ?>
                                                </div>
                                                <div class="text-sm text-gray-600">
                                                    <?php echo date('H:i', strtotime($booking['start_time'])); ?> - 
                                                    <?php echo date('H:i', strtotime($booking['end_time'])); ?>
                                                </div>
                                            </div>
                                            <div class="badge <?php 
                                                echo $booking['status'] == 'approved' ? 'badge-success' : 
                                                     ($booking['status'] == 'pending' ? 'badge-warning' : 'badge-error'); 
                                            ?> badge-sm">
                                                <?php 
                                                    echo $booking['status'] == 'approved' ? 'อนุมัติ' : 
                                                         ($booking['status'] == 'pending' ? 'รออนุมัติ' : 'ไม่อนุมัติ'); 
                                                ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <div class="text-center mt-4">
                                <a href="my_bookings.php" class="btn btn-primary btn-sm">ดูทั้งหมด</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <?php require_once 'version.php'; echo getSystemFooter(); ?>
</body>
</html>