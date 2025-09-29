<?php
session_start();
require_once '../config/database.php';
require_once '../config.php';
require_once '../includes/functions.php';

// ดึงข้อมูลองค์กร
$org_config = getOrganizationConfig();

// ตรวจสอบการ login
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

// ตรวจสอบสิทธิ์
if (!checkPermission($pdo, $_SESSION['user_id'], 'manage_users')) {
    header('Location: ../index.php?error=permission');
    exit();
}

$error = '';
$success = '';

// ประมวลผลฟอร์ม
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $action = $_POST['action'] ?? '';
        
        if ($action === 'add') {
            $username = trim($_POST['username']);
            $password = $_POST['password'];
            $fullname = trim($_POST['fullname']);
            $email = trim($_POST['email']);
            $phone = trim($_POST['phone']);
            $department = trim($_POST['department']);
            $position = trim($_POST['position']);
            $role = $_POST['role'];
            
            if (empty($username) || empty($password) || empty($fullname)) {
                throw new Exception('กรุณากรอกข้อมูลที่จำเป็นให้ครบถ้วน');
            }
            
            // ตรวจสอบ username ซ้ำ
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetchColumn() > 0) {
                throw new Exception('ชื่อผู้ใช้นี้มีอยู่แล้ว');
            }
            
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = $pdo->prepare("
                INSERT INTO users (username, password, fullname, email, phone, department, position, role) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$username, $hashed_password, $fullname, $email, $phone, $department, $position, $role]);
            
            logActivity($pdo, $_SESSION['user_id'], 'add_user', "Added user: {$username} ({$fullname})");
            $success = 'เพิ่มผู้ใช้งานเรียบร้อยแล้ว';
            
        } elseif ($action === 'edit') {
            $user_id = intval($_POST['user_id']);
            $username = trim($_POST['username']);
            $fullname = trim($_POST['fullname']);
            $email = trim($_POST['email']);
            $phone = trim($_POST['phone']);
            $department = trim($_POST['department']);
            $position = trim($_POST['position']);
            $role = $_POST['role'];
            $password = $_POST['password'];
            
            if (empty($username) || empty($fullname)) {
                throw new Exception('กรุณากรอกข้อมูลที่จำเป็นให้ครบถ้วน');
            }
            
            // ตรวจสอบ username ซ้ำ (ยกเว้นตัวเอง)
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? AND user_id != ?");
            $stmt->execute([$username, $user_id]);
            if ($stmt->fetchColumn() > 0) {
                throw new Exception('ชื่อผู้ใช้นี้มีอยู่แล้ว');
            }
            
            if (!empty($password)) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("
                    UPDATE users 
                    SET username = ?, password = ?, fullname = ?, email = ?, phone = ?, department = ?, position = ?, role = ? 
                    WHERE user_id = ?
                ");
                $stmt->execute([$username, $hashed_password, $fullname, $email, $phone, $department, $position, $role, $user_id]);
            } else {
                $stmt = $pdo->prepare("
                    UPDATE users 
                    SET username = ?, fullname = ?, email = ?, phone = ?, department = ?, position = ?, role = ? 
                    WHERE user_id = ?
                ");
                $stmt->execute([$username, $fullname, $email, $phone, $department, $position, $role, $user_id]);
            }
            
            logActivity($pdo, $_SESSION['user_id'], 'edit_user', "Edited user ID: {$user_id}");
            $success = 'แก้ไขผู้ใช้งานเรียบร้อยแล้ว';
            
        } elseif ($action === 'toggle_status') {
            $user_id = intval($_POST['user_id']);
            $is_active = intval($_POST['is_active']);
            
            // ป้องกันการปิดใช้งานตัวเอง
            if ($user_id == $_SESSION['user_id']) {
                throw new Exception('ไม่สามารถปิดใช้งานบัญชีตัวเองได้');
            }
            
            $stmt = $pdo->prepare("UPDATE users SET is_active = ? WHERE user_id = ?");
            $stmt->execute([$is_active, $user_id]);
            
            $status = $is_active ? 'activated' : 'deactivated';
            logActivity($pdo, $_SESSION['user_id'], 'toggle_user_status', "User ID {$user_id} {$status}");
            $success = $is_active ? 'เปิดใช้งานผู้ใช้แล้ว' : 'ปิดใช้งานผู้ใช้แล้ว';
        }
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// ดึงข้อมูลผู้ใช้
$stmt = $pdo->prepare("SELECT * FROM users ORDER BY created_at DESC");
$stmt->execute();
$users = $stmt->fetchAll();

// ดึงข้อมูลผู้ใช้สำหรับแก้ไข
$edit_user = null;
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->execute([$edit_id]);
    $edit_user = $stmt->fetch();
}
?>

<!DOCTYPE html>
<html lang="th" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการผู้ใช้งาน - ระบบจองห้องประชุม</title>
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
            <div class="dropdown">
                <div tabindex="0" role="button" class="btn btn-ghost lg:hidden">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h8m-8 6h16" />
                    </svg>
                </div>
                <ul tabindex="0" class="menu menu-sm dropdown-content bg-base-100 rounded-box z-[1] mt-3 w-52 p-2 shadow">
                    <?= generateNavigation('users', $_SESSION['role'] ?? 'user', true) ?>
                </ul>
            </div>
            <a class="btn btn-ghost text-xl flex items-center gap-2" href="../index.php">
                <?php if (file_exists('../' . $org_config['logo_path'])): ?>
                    <img src="../<?= $org_config['logo_path'] ?>" alt="Logo" class="w-8 h-8 object-contain">
                <?php endif; ?>
                <?= $org_config['sub_title'] ?>
            </a>
        </div>
        <div class="navbar-center hidden lg:flex">
            <ul class="menu menu-horizontal px-1">
                <?= generateNavigation('users', $_SESSION['role'] ?? 'user', false) ?>
            </ul>
        </div>
        <div class="navbar-end">
            <div class="dropdown dropdown-end">
                <div tabindex="0" role="button" class="btn btn-ghost">
                    สวัสดี, <?php echo htmlspecialchars($_SESSION['username']); ?>
                </div>
                <ul tabindex="0" class="dropdown-content menu bg-base-100 rounded-box z-[1] w-52 p-2 shadow">
                    <li><a href="../profile.php" class="text-base-content">โปรไฟล์</a></li>
                    <li><a href="../version_info.php" class="text-base-content">ข้อมูลระบบ</a></li>
                    <li><a href="../logout.php" class="text-base-content">ออกจากระบบ</a></li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container mx-auto p-4">
        <div class="breadcrumbs text-sm mb-4">
            <ul>
                <li><a href="index.php">หน้าหลัก</a></li>
                <li>จัดการผู้ใช้งาน</li>
            </ul>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- ฟอร์มเพิ่ม/แก้ไขผู้ใช้ -->
            <div class="lg:col-span-1">
                <div class="card bg-base-100 shadow-xl">
                    <div class="card-body">
                        <h3 class="card-title">
                            <?php echo $edit_user ? 'แก้ไขผู้ใช้งาน' : 'เพิ่มผู้ใช้งาน'; ?>
                        </h3>
                        
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
                            <input type="hidden" name="action" value="<?php echo $edit_user ? 'edit' : 'add'; ?>">
                            <?php if ($edit_user): ?>
                                <input type="hidden" name="user_id" value="<?php echo $edit_user['user_id']; ?>">
                            <?php endif; ?>

                            <div class="form-control">
                                <label class="label">
                                    <span class="label-text">ชื่อผู้ใช้ *</span>
                                </label>
                                <input type="text" name="username" class="input input-bordered" required 
                                       value="<?php echo $edit_user ? htmlspecialchars($edit_user['username']) : ''; ?>" 
                                       placeholder="กรอกชื่อผู้ใช้">
                            </div>

                            <div class="form-control">
                                <label class="label">
                                    <span class="label-text">
                                        <?php echo $edit_user ? 'รหัสผ่านใหม่' : 'รหัสผ่าน *'; ?>
                                    </span>
                                </label>
                                <input type="password" name="password" class="input input-bordered" 
                                       <?php echo !$edit_user ? 'required' : ''; ?>
                                       placeholder="<?php echo $edit_user ? 'เว้นว่างหากไม่ต้องการเปลี่ยน' : 'กรอกรหัสผ่าน'; ?>">
                            </div>

                            <div class="form-control">
                                <label class="label">
                                    <span class="label-text">ชื่อ-นามสกุล *</span>
                                </label>
                                <input type="text" name="fullname" class="input input-bordered" required 
                                       value="<?php echo $edit_user ? htmlspecialchars($edit_user['fullname']) : ''; ?>" 
                                       placeholder="กรอกชื่อ-นามสกุล">
                            </div>

                            <div class="form-control">
                                <label class="label">
                                    <span class="label-text">อีเมล</span>
                                </label>
                                <input type="email" name="email" class="input input-bordered" 
                                       value="<?php echo $edit_user ? htmlspecialchars($edit_user['email']) : ''; ?>" 
                                       placeholder="กรอกอีเมล">
                            </div>

                            <div class="form-control">
                                <label class="label">
                                    <span class="label-text">เบอร์โทรศัพท์</span>
                                </label>
                                <input type="tel" name="phone" class="input input-bordered" 
                                       value="<?php echo $edit_user ? htmlspecialchars($edit_user['phone']) : ''; ?>" 
                                       placeholder="กรอกเบอร์โทรศัพท์">
                            </div>

                            <div class="form-control">
                                <label class="label">
                                    <span class="label-text">หน่วยงาน</span>
                                </label>
                                <input type="text" name="department" class="input input-bordered" 
                                       value="<?php echo $edit_user ? htmlspecialchars($edit_user['department']) : ''; ?>" 
                                       placeholder="กรอกหน่วยงาน">
                            </div>

                            <div class="form-control">
                                <label class="label">
                                    <span class="label-text">ตำแหน่ง</span>
                                </label>
                                <input type="text" name="position" class="input input-bordered" 
                                       value="<?php echo $edit_user ? htmlspecialchars($edit_user['position']) : ''; ?>" 
                                       placeholder="กรอกตำแหน่ง">
                            </div>

                            <div class="form-control">
                                <label class="label">
                                    <span class="label-text">บทบาท</span>
                                </label>
                                <select name="role" class="select select-bordered" required>
                                    <option value="">เลือกบทบาท</option>
                                    <option value="user" <?php echo ($edit_user && $edit_user['role'] === 'user') ? 'selected' : ''; ?>>ผู้ใช้งานทั่วไป</option>
                                    <option value="manager" <?php echo ($edit_user && $edit_user['role'] === 'manager') ? 'selected' : ''; ?>>ผู้จัดการ</option>
                                    <option value="admin" <?php echo ($edit_user && $edit_user['role'] === 'admin') ? 'selected' : ''; ?>>ผู้ดูแลระบบ</option>
                                </select>
                            </div>

                            <div class="form-control mt-6">
                                <button type="submit" class="btn btn-primary">
                                    <?php echo $edit_user ? 'บันทึกการแก้ไข' : 'เพิ่มผู้ใช้งาน'; ?>
                                </button>
                                <?php if ($edit_user): ?>
                                    <a href="users.php" class="btn btn-ghost mt-2">ยกเลิก</a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- การตั้งค่าระบบ -->
            <div class="lg:col-span-2">
                <div class="card bg-gradient-to-r from-info to-accent text-white shadow-xl mb-6">
                    <div class="card-body">
                        <h3 class="card-title">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            การตั้งค่าระบบ
                        </h3>
                        <p class="mb-4">จัดการการตั้งค่าต่างๆ ของระบบ</p>
                        <div class="card-actions">
                            <a href="organization_config.php" class="btn btn-outline btn-sm">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                                ตั้งค่าข้อมูลองค์กร
                            </a>
                            <a href="version_info.php" class="btn btn-outline btn-sm">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                ข้อมูลเวอร์ชัน
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- รายการผู้ใช้งาน -->
            <div class="lg:col-span-2">
                <div class="card bg-base-100 shadow-xl">
                    <div class="card-body">
                        <h3 class="card-title">รายการผู้ใช้งาน</h3>
                        
                        <div class="overflow-x-auto">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>ผู้ใช้</th>
                                        <th>อีเมล</th>
                                        <th>หน่วยงาน</th>
                                        <th>บทบาท</th>
                                        <th>สถานะ</th>
                                        <th>เข้าสู่ระบบล่าสุด</th>
                                        <th>จัดการ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $user): ?>
                                        <tr class="<?php echo !$user['is_active'] ? 'opacity-50' : ''; ?>">
                                            <td>
                                                <div class="flex items-center space-x-3">
                                                    <div class="avatar placeholder">
                                                        <div class="bg-neutral text-neutral-content rounded-full w-8">
                                                            <span class="text-xs"><?php echo substr($user['fullname'], 0, 2); ?></span>
                                                        </div>
                                                    </div>
                                                    <div>
                                                        <div class="font-bold"><?php echo htmlspecialchars($user['fullname']); ?></div>
                                                        <div class="text-sm opacity-50"><?php echo htmlspecialchars($user['username']); ?></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                                            <td>
                                                <div><?php echo htmlspecialchars($user['department']); ?></div>
                                                <div class="text-sm opacity-50"><?php echo htmlspecialchars($user['position']); ?></div>
                                            </td>
                                            <td>
                                                <div class="badge <?php 
                                                    echo $user['role'] === 'admin' ? 'badge-error' : 
                                                         ($user['role'] === 'manager' ? 'badge-warning' : 'badge-info'); 
                                                ?>">
                                                    <?php 
                                                        echo $user['role'] === 'admin' ? 'ผู้ดูแลระบบ' : 
                                                             ($user['role'] === 'manager' ? 'ผู้จัดการ' : 'ผู้ใช้'); 
                                                    ?>
                                                </div>
                                            </td>
                                            <td>
                                                <?php if ($user['user_id'] != $_SESSION['user_id']): ?>
                                                    <form method="POST" class="inline">
                                                        <input type="hidden" name="action" value="toggle_status">
                                                        <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                                                        <input type="hidden" name="is_active" value="<?php echo $user['is_active'] ? 0 : 1; ?>">
                                                        <button type="submit" class="btn btn-xs <?php echo $user['is_active'] ? 'btn-success' : 'btn-error'; ?>">
                                                            <?php echo $user['is_active'] ? 'ใช้งาน' : 'ระงับ'; ?>
                                                        </button>
                                                    </form>
                                                <?php else: ?>
                                                    <span class="badge badge-success">ใช้งาน</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($user['last_login']): ?>
                                                    <div class="text-sm"><?php echo formatThaiDate(date('Y-m-d', strtotime($user['last_login']))); ?></div>
                                                    <div class="text-xs opacity-50"><?php echo date('H:i', strtotime($user['last_login'])); ?></div>
                                                <?php else: ?>
                                                    <span class="text-gray-500">ยังไม่เคยเข้าสู่ระบบ</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="dropdown dropdown-end">
                                                    <div tabindex="0" role="button" class="btn btn-ghost btn-xs">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z" />
                                                        </svg>
                                                    </div>
                                                    <ul tabindex="0" class="dropdown-content menu bg-base-100 rounded-box z-[1] w-32 p-2 shadow">
                                                        <li><a href="users.php?edit=<?php echo $user['user_id']; ?>">แก้ไข</a></li>
                                                        <li><a href="user_activity.php?user_id=<?php echo $user['user_id']; ?>">ดูกิจกรรม</a></li>
                                                        <?php if ($user['user_id'] != $_SESSION['user_id']): ?>
                                                            <li><a href="reset_password.php?user_id=<?php echo $user['user_id']; ?>" class="text-warning">รีเซ็ตรหัสผ่าน</a></li>
                                                        <?php endif; ?>
                                                    </ul>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- สถิติผู้ใช้งาน -->
                <div class="card bg-base-100 shadow-xl mt-6">
                    <div class="card-body">
                        <h3 class="card-title">สถิติผู้ใช้งาน</h3>
                        <?php
                        $user_stats = [
                            'total' => count($users),
                            'active' => count(array_filter($users, fn($u) => $u['is_active'])),
                            'admin' => count(array_filter($users, fn($u) => $u['role'] === 'admin')),
                            'manager' => count(array_filter($users, fn($u) => $u['role'] === 'manager')),
                            'user' => count(array_filter($users, fn($u) => $u['role'] === 'user'))
                        ];
                        ?>
                        
                        <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                            <div class="stat">
                                <div class="stat-title">ทั้งหมด</div>
                                <div class="stat-value text-primary"><?php echo $user_stats['total']; ?></div>
                            </div>
                            <div class="stat">
                                <div class="stat-title">ใช้งาน</div>
                                <div class="stat-value text-success"><?php echo $user_stats['active']; ?></div>
                            </div>
                            <div class="stat">
                                <div class="stat-title">ผู้ดูแลระบบ</div>
                                <div class="stat-value text-error"><?php echo $user_stats['admin']; ?></div>
                            </div>
                            <div class="stat">
                                <div class="stat-title">ผู้จัดการ</div>
                                <div class="stat-value text-warning"><?php echo $user_stats['manager']; ?></div>
                            </div>
                            <div class="stat">
                                <div class="stat-title">ผู้ใช้ทั่วไป</div>
                                <div class="stat-value text-info"><?php echo $user_stats['user']; ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <?php require_once '../version.php'; echo getSystemFooter(); ?>
</body>
</html>