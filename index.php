<?php
// ตั้งค่า UTF-8 encoding
header('Content-Type: text/html; charset=UTF-8');
mb_internal_encoding('UTF-8');
ini_set('default_charset', 'UTF-8');

session_start();
require_once 'config/database.php';
require_once 'config.php';
require_once 'includes/functions.php';

// ดึงข้อมูลองค์กร
$org_config = getOrganizationConfig();

// ตรวจสอบการ login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

// ดึงข้อมูลการจองวันนี้
$today = date('Y-m-d');
$stmt = $pdo->prepare("
    SELECT b.*, r.room_name, u.fullname, u.department 
    FROM bookings b 
    JOIN rooms r ON b.room_id = r.room_id 
    JOIN users u ON b.user_id = u.user_id 
    WHERE DATE(b.booking_date) = ? 
    ORDER BY b.start_time
");
$stmt->execute([$today]);
$today_bookings = $stmt->fetchAll();

// ดึงข้อมูลการจองที่รออนุมัติ
$stmt = $pdo->prepare("
    SELECT b.*, r.room_name, u.fullname, u.department 
    FROM bookings b 
    JOIN rooms r ON b.room_id = r.room_id 
    JOIN users u ON b.user_id = u.user_id 
    WHERE b.status = 'pending' 
    ORDER BY b.created_at DESC
");
$stmt->execute();
$pending_bookings = $stmt->fetchAll();

// ดึงข้อมูลการจองสำหรับปฏิทินแสดงในหน้าแรก (7 วันข้างหน้า)
$week_start = date('Y-m-d');
$week_end = date('Y-m-d', strtotime('+6 days'));
$stmt = $pdo->prepare("
    SELECT b.*, r.room_name, u.fullname 
    FROM bookings b 
    JOIN rooms r ON b.room_id = r.room_id 
    JOIN users u ON b.user_id = u.user_id 
    WHERE b.booking_date BETWEEN ? AND ? 
    ORDER BY b.booking_date, b.start_time
");
$stmt->execute([$week_start, $week_end]);
$week_bookings = $stmt->fetchAll();

// จัดกลุ่มการจองตามวันที่
$booking_by_date = [];
foreach ($week_bookings as $booking) {
    $date = $booking['booking_date'];
    if (!isset($booking_by_date[$date])) {
        $booking_by_date[$date] = [];
    }
    $booking_by_date[$date][] = $booking;
}
?>

<!DOCTYPE html>
<html lang="th" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $org_config['sub_title'] ?> - <?= $org_config['name'] ?></title>
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
                    <li><a href="index.php" class="text-base-content">หน้าหลัก</a></li>
                    <li><a href="booking.php" class="text-base-content">จองห้องประชุม</a></li>
                    <li><a href="calendar.php" class="text-base-content">ปฏิทินการจอง</a></li>
                    <li><a href="my_bookings.php" class="text-base-content">การจองของฉัน</a></li>
                    <li><a href="rooms.php" class="text-base-content">จัดการห้องประชุม</a></li>
                    <li><a href="reports.php" class="text-base-content">รายงาน</a></li>
                    <li><a href="user_activity.php" class="text-base-content">กิจกรรมผู้ใช้</a></li>
                    <li><a href="users.php" class="text-base-content">จัดการผู้ใช้</a></li>
                </ul>
            </div>
            <a class="btn btn-ghost text-xl flex items-center gap-2">
                <?php if (file_exists($org_config['logo_path'])): ?>
                    <img src="<?= $org_config['logo_path'] ?>" alt="Logo" class="w-8 h-8 object-contain">
                <?php endif; ?>
                <?= $org_config['sub_title'] ?>
            </a>
        </div>
        <div class="navbar-center hidden lg:flex">
            <ul class="menu menu-horizontal px-1">
                <li><a href="index.php">หน้าหลัก</a></li>
                <li><a href="booking.php">จองห้องประชุม</a></li>
                <li><a href="calendar.php">ปฏิทินการจอง</a></li>
                <li><a href="my_bookings.php">การจองของฉัน</a></li>
                <li><a href="rooms.php">จัดการห้องประชุม</a></li>
                <li><a href="reports.php">รายงาน</a></li>
                <li><a href="user_activity.php">กิจกรรมผู้ใช้</a></li>
                <li><a href="users.php">จัดการผู้ใช้</a></li>
            </ul>
        </div>
        <div class="navbar-end">
            <div class="dropdown dropdown-end">
                <div tabindex="0" role="button" class="btn btn-ghost">
                    สวัสดี, <?php echo htmlspecialchars($username); ?>
                </div>
                <ul tabindex="0" class="dropdown-content menu bg-base-100 rounded-box z-[1] w-52 p-2 shadow">
                    <li><a href="profile.php" class="text-base-content">โปรไฟล์</a></li>
                    <li><a href="telegram_settings.php" class="text-base-content"><i class="fab fa-telegram mr-1"></i>ตั้งค่า Telegram</a></li>
                    <li><a href="my_bookings.php" class="text-base-content">การจองของฉัน</a></li>
                    <li><hr></li>
                    <li><a href="version_info.php" class="text-base-content">ข้อมูลระบบ</a></li>
                    <li><a href="logout.php" class="text-base-content">ออกจากระบบ</a></li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container mx-auto p-4">
        <!-- Welcome Card -->
        <div class="card bg-base-100 shadow-xl mb-6">
            <div class="card-body">
                <h2 class="card-title text-2xl">ยินดีต้อนรับสู่<?= $org_config['sub_title'] ?></h2>
                <p><?= $org_config['name'] ?></p>
                <div class="stats shadow mt-4">
                    <div class="stat">
                        <div class="stat-title">วันที่</div>
                        <div class="stat-value text-primary"><?php echo date('d/m/Y'); ?></div>
                        <div class="stat-desc"><?php 
                            $thai_days = [
                                'Sunday' => 'อาทิตย์', 'Monday' => 'จันทร์', 'Tuesday' => 'อังคาร',
                                'Wednesday' => 'พุธ', 'Thursday' => 'พฤหัสบดี', 'Friday' => 'ศุกร์', 'Saturday' => 'เสาร์'
                            ];
                            echo $thai_days[date('l')];
                        ?></div>
                    </div>
                    <div class="stat">
                        <div class="stat-title">การจองวันนี้</div>
                        <div class="stat-value text-secondary"><?php echo count($today_bookings); ?></div>
                        <div class="stat-desc">รายการ</div>
                    </div>
                    <div class="stat">
                        <div class="stat-title">รออนุมัติ</div>
                        <div class="stat-value text-warning"><?php echo count($pending_bookings); ?></div>
                        <div class="stat-desc">รายการ</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ปฏิทินการจอง 7 วันข้างหน้า -->
        <div class="card bg-base-100 shadow-xl mb-6">
            <div class="card-body">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="card-title">ปฏิทินการจอง 7 วันข้างหน้า</h3>
                    <a href="calendar.php" class="btn btn-primary btn-sm">ดูปฏิทินเต็ม</a>
                </div>
                
                <div class="grid grid-cols-7 gap-2">
                    <?php for ($i = 0; $i < 7; $i++): ?>
                        <?php
                        $current_date = date('Y-m-d', strtotime("+$i days"));
                        $day_name = date('D', strtotime($current_date));
                        $day_thai = [
                            'Sun' => 'อา', 'Mon' => 'จ', 'Tue' => 'อ', 'Wed' => 'พ',
                            'Thu' => 'พฤ', 'Fri' => 'ศ', 'Sat' => 'ส'
                        ];
                        $day_bookings = isset($booking_by_date[$current_date]) ? $booking_by_date[$current_date] : [];
                        $is_today = $current_date == date('Y-m-d');
                        ?>
                        <div class="card bg-base-200 border <?php echo $is_today ? 'border-primary bg-primary/10' : ''; ?>">
                            <div class="card-body p-3">
                                <div class="text-center">
                                    <div class="font-semibold <?php echo $is_today ? 'text-primary' : ''; ?>">
                                        <?php echo $day_thai[$day_name]; ?>
                                    </div>
                                    <div class="text-lg font-bold <?php echo $is_today ? 'text-primary' : ''; ?>">
                                        <?php echo date('j', strtotime($current_date)); ?>
                                    </div>
                                    <?php if ($is_today): ?>
                                        <div class="text-xs bg-primary text-primary-content px-1 rounded">วันนี้</div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="mt-2 space-y-1">
                                    <?php foreach (array_slice($day_bookings, 0, 3) as $booking): ?>
                                        <div class="text-xs p-1 rounded <?php 
                                            echo $booking['status'] == 'approved' ? 'bg-success text-success-content' : 
                                                 ($booking['status'] == 'pending' ? 'bg-warning text-warning-content' : 'bg-error text-error-content'); 
                                        ?>">
                                            <?php echo date('H:i', strtotime($booking['start_time'])); ?>
                                            <?php echo htmlspecialchars(mb_substr($booking['room_name'], 0, 8, 'UTF-8'), ENT_QUOTES, 'UTF-8'); ?>
                                        </div>
                                    <?php endforeach; ?>
                                    
                                    <?php if (count($day_bookings) > 3): ?>
                                        <div class="text-xs text-center text-gray-500">
                                            +<?php echo count($day_bookings) - 3; ?> อื่นๆ
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if (empty($day_bookings)): ?>
                                        <div class="text-xs text-center text-gray-400">ว่าง</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endfor; ?>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- การจองวันนี้ -->
            <div class="card bg-base-100 shadow-xl">
                <div class="card-body">
                    <h3 class="card-title">การจองวันนี้</h3>
                    <?php if (empty($today_bookings)): ?>
                        <div class="text-center py-8">
                            <p class="text-gray-500">ไม่มีการจองในวันนี้</p>
                        </div>
                    <?php else: ?>
                        <div class="overflow-x-auto">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>เวลา</th>
                                        <th>ห้อง</th>
                                        <th>ผู้จอง</th>
                                        <th>สถานะ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($today_bookings as $booking): ?>
                                        <tr>
                                            <td><?php echo date('H:i', strtotime($booking['start_time'])) . '-' . date('H:i', strtotime($booking['end_time'])); ?></td>
                                            <td><?php echo htmlspecialchars($booking['room_name']); ?></td>
                                            <td><?php echo htmlspecialchars($booking['fullname']); ?></td>
                                            <td>
                                                <div class="badge <?php 
                                                    echo $booking['status'] == 'approved' ? 'badge-success' : 
                                                         ($booking['status'] == 'pending' ? 'badge-warning' : 'badge-error'); 
                                                ?>">
                                                    <?php 
                                                        echo $booking['status'] == 'approved' ? 'อนุมัติ' : 
                                                             ($booking['status'] == 'pending' ? 'รออนุมัติ' : 'ไม่อนุมัติ'); 
                                                    ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- การจองที่รออนุมัติ -->
            <div class="card bg-base-100 shadow-xl">
                <div class="card-body">
                    <h3 class="card-title">การจองที่รออนุมัติ</h3>
                    <?php if (empty($pending_bookings)): ?>
                        <div class="text-center py-8">
                            <p class="text-gray-500">ไม่มีการจองที่รออนุมัติ</p>
                        </div>
                    <?php else: ?>
                        <div class="overflow-x-auto">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>วันที่</th>
                                        <th>เวลา</th>
                                        <th>ห้อง</th>
                                        <th>ผู้จอง</th>
                                        <th>จัดการ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pending_bookings as $booking): ?>
                                        <tr>
                                            <td><?php echo date('d/m/Y', strtotime($booking['booking_date'])); ?></td>
                                            <td><?php echo date('H:i', strtotime($booking['start_time'])) . '-' . date('H:i', strtotime($booking['end_time'])); ?></td>
                                            <td><?php echo htmlspecialchars($booking['room_name']); ?></td>
                                            <td><?php echo htmlspecialchars($booking['fullname']); ?></td>
                                            <td>
                                                <div class="btn-group">
                                                    <a href="approve_booking.php?id=<?php echo $booking['booking_id']; ?>&action=approve" class="btn btn-success btn-xs">อนุมัติ</a>
                                                    <a href="approve_booking.php?id=<?php echo $booking['booking_id']; ?>&action=reject" class="btn btn-error btn-xs">ไม่อนุมัติ</a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="card bg-base-100 shadow-xl mt-6">
            <div class="card-body">
                <h3 class="card-title">เมนูใช้งานด่วน</h3>
                <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                    <a href="booking.php" class="btn btn-primary">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                        จองห้องประชุม
                    </a>
                    <a href="my_bookings.php" class="btn btn-secondary">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                        การจองของฉัน
                    </a>
                    <a href="calendar.php" class="btn btn-info">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        ปฏิทินการจอง
                    </a>
                    <a href="public_calendar.php" class="btn btn-success" target="_blank">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                        ปฏิทินสาธารณะ
                    </a>
                    <a href="reports.php" class="btn btn-accent">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                        รายงาน
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <?php require_once 'version.php'; echo getSystemFooter(); ?>

    <script>
        // Auto refresh every 30 seconds
        setTimeout(function(){
            location.reload();
        }, 30000);
    </script>
</body>
</html>