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
if (!checkPermission($pdo, $_SESSION['user_id'], 'manage_rooms')) {
    header('Location: ../index.php?error=permission');
    exit();
}

$error = '';
$success = '';

// ประมวลผลฟอร์ม
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $action = isset($_POST['action']) ? $_POST['action'] : '';
        
        if ($action === 'add') {
            $room_name = trim($_POST['room_name']);
            $room_code = trim($_POST['room_code']);
            $capacity = intval($_POST['capacity']);
            $location = trim($_POST['location']);
            $description = trim($_POST['description']);
            $equipment = trim($_POST['equipment']);
            $room_color = trim($_POST['room_color']) ?: '#3b82f6';
            
            if (empty($room_name) || empty($room_code) || $capacity <= 0) {
                throw new Exception('กรุณากรอกข้อมูลให้ครบถ้วน');
            }
            
            $stmt = $pdo->prepare("INSERT INTO rooms (room_name, room_code, capacity, location, description, equipment, room_color) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$room_name, $room_code, $capacity, $location, $description, $equipment, $room_color]);
            
            logActivity($pdo, $_SESSION['user_id'], 'add_room', "Added room: {$room_name} ({$room_code})");
            $success = 'เพิ่มห้องประชุมเรียบร้อยแล้ว';
            
        } elseif ($action === 'edit') {
            $room_id = intval($_POST['room_id']);
            $room_name = trim($_POST['room_name']);
            $room_code = trim($_POST['room_code']);
            $capacity = intval($_POST['capacity']);
            $location = trim($_POST['location']);
            $description = trim($_POST['description']);
            $equipment = trim($_POST['equipment']);
            $room_color = trim($_POST['room_color']) ?: '#3b82f6';
            
            if (empty($room_name) || empty($room_code) || $capacity <= 0) {
                throw new Exception('กรุณากรอกข้อมูลให้ครบถ้วน');
            }
            
            $stmt = $pdo->prepare("UPDATE rooms SET room_name = ?, room_code = ?, capacity = ?, location = ?, description = ?, equipment = ?, room_color = ? WHERE room_id = ?");
            $stmt->execute([$room_name, $room_code, $capacity, $location, $description, $equipment, $room_color, $room_id]);
            
            logActivity($pdo, $_SESSION['user_id'], 'edit_room', "Edited room ID: {$room_id}");
            $success = 'แก้ไขห้องประชุมเรียบร้อยแล้ว';
            
        } elseif ($action === 'toggle_status') {
            $room_id = intval($_POST['room_id']);
            $is_active = intval($_POST['is_active']);
            
            $stmt = $pdo->prepare("UPDATE rooms SET is_active = ? WHERE room_id = ?");
            $stmt->execute([$is_active, $room_id]);
            
            $status = $is_active ? 'activated' : 'deactivated';
            logActivity($pdo, $_SESSION['user_id'], 'toggle_room_status', "Room ID {$room_id} {$status}");
            $success = $is_active ? 'เปิดใช้งานห้องประชุมแล้ว' : 'ปิดใช้งานห้องประชุมแล้ว';
        }
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// ดึงข้อมูลห้องประชุม
$stmt = $pdo->prepare("SELECT * FROM rooms ORDER BY room_name");
$stmt->execute();
$rooms = $stmt->fetchAll();

// ดึงข้อมูลห้องสำหรับแก้ไข
$edit_room = null;
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $stmt = $pdo->prepare("SELECT * FROM rooms WHERE room_id = ?");
    $stmt->execute([$edit_id]);
    $edit_room = $stmt->fetch();
}
?>

<!DOCTYPE html>
<html lang="th" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการห้องประชุม - ระบบจองห้องประชุม</title>
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
                    <?= generateNavigation('rooms', isset($_SESSION['role']) ? $_SESSION['role'] : 'user', true) ?>
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
                <?= generateNavigation('rooms', isset($_SESSION['role']) ? $_SESSION['role'] : 'user', false) ?>
            </ul>
        </div>
        <div class="navbar-end">
            <div class="dropdown dropdown-end">
                <div tabindex="0" role="button" class="btn btn-ghost">
                    สวัสดี, <?php echo htmlspecialchars($_SESSION['username']); ?>
                </div>
                <ul tabindex="0" class="dropdown-content menu bg-base-100 rounded-box z-[1] w-52 p-2 shadow">
                    <li><a href="../profile.php" class="text-base-content">โปรไฟล์</a></li>
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
                <li>จัดการห้องประชุม</li>
            </ul>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- ฟอร์มเพิ่ม/แก้ไขห้องประชุม -->
            <div class="lg:col-span-1">
                <div class="card bg-base-100 shadow-xl">
                    <div class="card-body">
                        <h3 class="card-title">
                            <?php echo $edit_room ? 'แก้ไขห้องประชุม' : 'เพิ่มห้องประชุม'; ?>
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
                            <input type="hidden" name="action" value="<?php echo $edit_room ? 'edit' : 'add'; ?>">
                            <?php if ($edit_room): ?>
                                <input type="hidden" name="room_id" value="<?php echo $edit_room['room_id']; ?>">
                            <?php endif; ?>

                            <div class="form-control">
                                <label class="label">
                                    <span class="label-text">ชื่อห้องประชุม</span>
                                </label>
                                <input type="text" name="room_name" class="input input-bordered" required 
                                       value="<?php echo $edit_room ? htmlspecialchars($edit_room['room_name']) : ''; ?>" 
                                       placeholder="กรอกชื่อห้องประชุม">
                            </div>

                            <div class="form-control">
                                <label class="label">
                                    <span class="label-text">รหัสห้อง</span>
                                </label>
                                <input type="text" name="room_code" class="input input-bordered" required 
                                       value="<?php echo $edit_room ? htmlspecialchars($edit_room['room_code']) : ''; ?>" 
                                       placeholder="เช่น R001">
                            </div>

                            <div class="form-control">
                                <label class="label">
                                    <span class="label-text">ความจุ (ที่นั่ง)</span>
                                </label>
                                <input type="number" name="capacity" class="input input-bordered" required min="1" max="200"
                                       value="<?php echo $edit_room ? $edit_room['capacity'] : ''; ?>" 
                                       placeholder="จำนวนที่นั่ง">
                            </div>

                            <div class="form-control">
                                <label class="label">
                                    <span class="label-text">สถานที่</span>
                                </label>
                                <input type="text" name="location" class="input input-bordered"
                                       value="<?php echo $edit_room ? htmlspecialchars($edit_room['location']) : ''; ?>" 
                                       placeholder="ตำแหน่งห้องประชุม">
                            </div>

                            <div class="form-control">
                                <label class="label">
                                    <span class="label-text">คำอธิบาย</span>
                                </label>
                                <textarea name="description" class="textarea textarea-bordered" rows="2" 
                                          placeholder="คำอธิบายห้องประชุม"><?php echo $edit_room ? htmlspecialchars($edit_room['description']) : ''; ?></textarea>
                            </div>

                            <div class="form-control">
                                <label class="label">
                                    <span class="label-text">อุปกรณ์</span>
                                </label>
                                <textarea name="equipment" class="textarea textarea-bordered" rows="3" 
                                          placeholder="รายการอุปกรณ์ในห้องประชุม"><?php echo $edit_room ? htmlspecialchars($edit_room['equipment']) : ''; ?></textarea>
                            </div>

                            <div class="form-control">
                                <label class="label">
                                    <span class="label-text">สีประจำห้อง</span>
                                </label>
                                <div class="flex gap-2 items-center">
                                    <input type="color" name="room_color" class="input input-bordered w-20 h-12 p-1" 
                                           value="<?php echo $edit_room ? htmlspecialchars($edit_room['room_color']) : '#3b82f6'; ?>">
                                    <div class="text-sm opacity-70">เลือกสีที่จะแสดงในปฏิทินการจอง</div>
                                </div>
                                <div class="flex gap-2 mt-2">
                                    <button type="button" class="btn btn-xs" style="background-color: #ef4444; color: white;" onclick="document.querySelector('input[name=room_color]').value='#ef4444'">#ef4444</button>
                                    <button type="button" class="btn btn-xs" style="background-color: #10b981; color: white;" onclick="document.querySelector('input[name=room_color]').value='#10b981'">#10b981</button>
                                    <button type="button" class="btn btn-xs" style="background-color: #f59e0b; color: white;" onclick="document.querySelector('input[name=room_color]').value='#f59e0b'">#f59e0b</button>
                                    <button type="button" class="btn btn-xs" style="background-color: #8b5cf6; color: white;" onclick="document.querySelector('input[name=room_color]').value='#8b5cf6'">#8b5cf6</button>
                                    <button type="button" class="btn btn-xs" style="background-color: #3b82f6; color: white;" onclick="document.querySelector('input[name=room_color]').value='#3b82f6'">#3b82f6</button>
                                </div>
                            </div>

                            <div class="form-control mt-6">
                                <button type="submit" class="btn btn-primary">
                                    <?php echo $edit_room ? 'บันทึกการแก้ไข' : 'เพิ่มห้องประชุม'; ?>
                                </button>
                                <?php if ($edit_room): ?>
                                    <a href="rooms.php" class="btn btn-ghost mt-2">ยกเลิก</a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- รายการห้องประชุม -->
            <div class="lg:col-span-2">
                <div class="card bg-base-100 shadow-xl">
                    <div class="card-body">
                        <h3 class="card-title">รายการห้องประชุม</h3>
                        
                        <div class="overflow-x-auto">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>รหัส</th>
                                        <th>ชื่อห้อง</th>
                                        <th>ความจุ</th>
                                        <th>สถานที่</th>
                                        <th>สี</th>
                                        <th>สถานะ</th>
                                        <th>จัดการ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($rooms as $room): ?>
                                        <tr class="<?php echo !$room['is_active'] ? 'opacity-50' : ''; ?>">
                                            <td class="font-mono"><?php echo htmlspecialchars($room['room_code']); ?></td>
                                            <td>
                                                <div class="font-bold"><?php echo htmlspecialchars($room['room_name']); ?></div>
                                                <?php if ($room['description']): ?>
                                                    <div class="text-sm opacity-50"><?php echo htmlspecialchars($room['description']); ?></div>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo $room['capacity']; ?> ที่นั่ง</td>
                                            <td><?php echo htmlspecialchars($room['location']); ?></td>
                                            <td>
                                                <div class="flex items-center gap-2">
                                                    <div class="w-6 h-6 rounded border-2 border-gray-300" style="background-color: <?php echo htmlspecialchars(isset($room['room_color']) ? $room['room_color'] : '#3b82f6'); ?>"></div>
                                                    <span class="text-xs font-mono"><?php echo htmlspecialchars(isset($room['room_color']) ? $room['room_color'] : '#3b82f6'); ?></span>
                                                </div>
                                            </td>
                                            <td>
                                                <form method="POST" class="inline">
                                                    <input type="hidden" name="action" value="toggle_status">
                                                    <input type="hidden" name="room_id" value="<?php echo $room['room_id']; ?>">
                                                    <input type="hidden" name="is_active" value="<?php echo $room['is_active'] ? 0 : 1; ?>">
                                                    <button type="submit" class="btn btn-xs <?php echo $room['is_active'] ? 'btn-success' : 'btn-error'; ?>">
                                                        <?php echo $room['is_active'] ? 'เปิดใช้งาน' : 'ปิดใช้งาน'; ?>
                                                    </button>
                                                </form>
                                            </td>
                                            <td>
                                                <div class="btn-group">
                                                    <a href="rooms.php?edit=<?php echo $room['room_id']; ?>" class="btn btn-primary btn-xs">แก้ไข</a>
                                                    <a href="room_bookings.php?room_id=<?php echo $room['room_id']; ?>" class="btn btn-info btn-xs">ดูการจอง</a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- สถิติการใช้งานห้องประชุม -->
                <div class="card bg-base-100 shadow-xl mt-6">
                    <div class="card-body">
                        <h3 class="card-title">สถิติการใช้งานห้องประชุม (30 วันที่ผ่านมา)</h3>
                        <?php
                        $stmt = $pdo->prepare("
                            SELECT r.room_name, COUNT(b.booking_id) as booking_count,
                                   COUNT(CASE WHEN b.status = 'approved' THEN 1 END) as approved_count
                            FROM rooms r 
                            LEFT JOIN bookings b ON r.room_id = b.room_id 
                                AND b.booking_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                            GROUP BY r.room_id, r.room_name 
                            ORDER BY booking_count DESC
                        ");
                        $stmt->execute();
                        $room_stats = $stmt->fetchAll();
                        ?>
                        
                        <div class="overflow-x-auto">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>ห้องประชุม</th>
                                        <th>การจองทั้งหมด</th>
                                        <th>อนุมัติแล้ว</th>
                                        <th>อัตราการอนุมัติ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($room_stats as $stat): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($stat['room_name']); ?></td>
                                            <td><?php echo $stat['booking_count']; ?></td>
                                            <td><?php echo $stat['approved_count']; ?></td>
                                            <td>
                                                <?php 
                                                $rate = $stat['booking_count'] > 0 ? 
                                                       round(($stat['approved_count'] / $stat['booking_count']) * 100, 1) : 0;
                                                echo $rate . '%';
                                                ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <?php require_once 'version.php'; echo getSystemFooter(); ?>
</body>
</html>