<?php
// ตั้งค่า UTF-8 encoding และ error reporting
header('Content-Type: text/html; charset=UTF-8');
mb_internal_encoding('UTF-8');
ini_set('default_charset', 'UTF-8');
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

try {
    require_once 'config/database.php';
    require_once 'config.php';
    require_once 'includes/functions.php';
    
    // ดึงข้อมูลองค์กร
    $org_config = getOrganizationConfig();
} catch (Exception $e) {
    die('เกิดข้อผิดพลาดในการโหลดระบบ: ' . $e->getMessage());
}

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
                    <?= generateNavigation('index', $_SESSION['role'] ?? 'user', true) ?>
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
                <?= generateNavigation('index', $_SESSION['role'] ?? 'user', false) ?>
            </ul>
        </div>
        <div class="navbar-end">
            <div class="dropdown dropdown-end">
                <div tabindex="0" role="button" class="btn btn-ghost">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    สวัสดี, <?php echo htmlspecialchars($username); ?>
                    <span class="badge badge-sm ml-2 <?php 
                        echo $_SESSION['role'] === 'admin' ? 'badge-error' : 
                             ($_SESSION['role'] === 'manager' ? 'badge-warning' : 'badge-info'); 
                    ?>">
                        <?php 
                            echo $_SESSION['role'] === 'admin' ? 'ผู้ดูแลระบบ' : 
                                 ($_SESSION['role'] === 'manager' ? 'ผู้จัดการ' : 'ผู้ใช้'); 
                        ?>
                    </span>
                </div>
                <ul tabindex="0" class="dropdown-content menu bg-base-100 rounded-box z-[1] w-52 p-2 shadow">
                    <li><a href="profile.php" class="text-base-content">โปรไฟล์</a></li>
                    <li><a href="admin/telegram_settings.php" class="text-base-content"><i class="fab fa-telegram mr-1"></i>ตั้งค่า Telegram</a></li>
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
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="card-title flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            การจองวันนี้
                        </h3>
                        <div class="flex gap-2">
                            <div class="badge badge-primary"><?php echo date('d/m/Y'); ?></div>
                            <div class="badge badge-info"><?php echo count($today_bookings); ?> รายการ</div>
                        </div>
                    </div>
                    
                    <?php if (empty($today_bookings)): ?>
                        <div class="text-center py-8">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mx-auto text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <p class="text-gray-500 text-lg">ไม่มีการจองในวันนี้</p>
                            <a href="booking.php" class="btn btn-primary mt-4">จองห้องประชุม</a>
                        </div>
                    <?php else: ?>
                        <div class="space-y-3">
                            <?php 
                            // แยกการจองตามสถานะ
                            $approved_bookings = array_filter($today_bookings, function($b) { return $b['status'] === 'approved'; });
                            $pending_bookings_today = array_filter($today_bookings, function($b) { return $b['status'] === 'pending'; });
                            $other_bookings = array_filter($today_bookings, function($b) { return !in_array($b['status'], ['approved', 'pending']); });
                            
                            $current_time = date('H:i:s');
                            $current_datetime = date('Y-m-d H:i:s');
                            ?>
                            
                            <?php foreach ($today_bookings as $booking): ?>
                                <?php
                                // ตรวจสอบสถานะเวลา
                                $booking_start = $booking['booking_date'] . ' ' . $booking['start_time'];
                                $booking_end = $booking['booking_date'] . ' ' . $booking['end_time'];
                                $is_current = ($current_datetime >= $booking_start && $current_datetime <= $booking_end);
                                $is_upcoming = ($current_datetime < $booking_start);
                                $is_finished = ($current_datetime > $booking_end);
                                ?>
                                
                                <div class="card bg-base-200 border-l-4 <?php 
                                    echo $booking['status'] == 'approved' ? 'border-l-success' : 
                                         ($booking['status'] == 'pending' ? 'border-l-warning' : 'border-l-error'); 
                                ?>">
                                    <div class="card-body p-4">
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center gap-3">
                                                <!-- เวลาและสถานะเวลา -->
                                                <div class="text-center">
                                                    <div class="font-mono font-bold text-lg <?php 
                                                        echo $is_current ? 'text-error animate-pulse' : 
                                                             ($is_upcoming ? 'text-info' : 'text-gray-500'); 
                                                    ?>">
                                                        <?php echo date('H:i', strtotime($booking['start_time'])); ?>
                                                    </div>
                                                    <div class="text-xs opacity-70">ถึง</div>
                                                    <div class="font-mono font-bold text-lg <?php 
                                                        echo $is_current ? 'text-error animate-pulse' : 
                                                             ($is_upcoming ? 'text-info' : 'text-gray-500'); 
                                                    ?>">
                                                        <?php echo date('H:i', strtotime($booking['end_time'])); ?>
                                                    </div>
                                                </div>
                                                
                                                <!-- ข้อมูลการจอง -->
                                                <div class="flex-1">
                                                    <div class="flex items-center gap-2 mb-1">
                                                        <h4 class="font-semibold text-lg"><?php echo htmlspecialchars($booking['room_name']); ?></h4>
                                                        <?php if ($is_current): ?>
                                                            <div class="badge badge-error badge-sm animate-pulse">กำลังใช้งาน</div>
                                                        <?php elseif ($is_upcoming): ?>
                                                            <div class="badge badge-info badge-sm">กำลังจะเริ่ม</div>
                                                        <?php elseif ($is_finished): ?>
                                                            <div class="badge badge-neutral badge-sm">เสร็จสิ้น</div>
                                                        <?php endif; ?>
                                                    </div>
                                                    
                                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-2 text-sm">
                                                        <div class="flex items-center gap-1">
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-info" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                                            </svg>
                                                            <span class="font-medium"><?php echo htmlspecialchars($booking['fullname']); ?></span>
                                                        </div>
                                                        
                                                        <div class="flex items-center gap-1">
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-secondary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                                            </svg>
                                                            <span><?php echo htmlspecialchars($booking['department']); ?></span>
                                                        </div>
                                                        
                                                        <?php if (!empty($booking['purpose'])): ?>
                                                        <div class="flex items-start gap-1 md:col-span-2">
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-accent mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                            </svg>
                                                            <span class="text-xs opacity-80"><?php echo htmlspecialchars($booking['purpose']); ?></span>
                                                        </div>
                                                        <?php endif; ?>
                                                        
                                                        <?php if (!empty($booking['attendees'])): ?>
                                                        <div class="flex items-center gap-1">
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-warning" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                                            </svg>
                                                            <span><?php echo $booking['attendees']; ?> คน</span>
                                                        </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <!-- สถานะและการจัดการ -->
                                            <div class="text-right">
                                                <div class="badge badge-lg <?php 
                                                    echo $booking['status'] == 'approved' ? 'badge-success' : 
                                                         ($booking['status'] == 'pending' ? 'badge-warning' : 'badge-error'); 
                                                ?> mb-2">
                                                    <?php 
                                                        echo $booking['status'] == 'approved' ? 'อนุมัติแล้ว' : 
                                                             ($booking['status'] == 'pending' ? 'รออนุมัติ' : 'ไม่อนุมัติ'); 
                                                    ?>
                                                </div>
                                                
                                                <!-- ปุ่มจัดการสำหรับ admin/manager -->
                                                <?php if ($booking['status'] == 'pending' && ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'manager')): ?>
                                                <div class="flex gap-1">
                                                    <a href="approve_booking.php?id=<?php echo $booking['booking_id']; ?>&action=approve" 
                                                       class="btn btn-success btn-xs" onclick="return confirm('อนุมัติการจองนี้?')">
                                                        อนุมัติ
                                                    </a>
                                                    <a href="approve_booking.php?id=<?php echo $booking['booking_id']; ?>&action=reject" 
                                                       class="btn btn-error btn-xs" onclick="return confirm('ไม่อนุมัติการจองนี้?')">
                                                        ไม่อนุมัติ
                                                    </a>
                                                </div>
                                                <?php endif; ?>
                                                
                                                <!-- แสดงข้อมูลเวลาที่เหลือ -->
                                                <?php if ($is_upcoming): ?>
                                                    <?php
                                                    $time_diff = strtotime($booking_start) - strtotime($current_datetime);
                                                    $hours = floor($time_diff / 3600);
                                                    $minutes = floor(($time_diff % 3600) / 60);
                                                    ?>
                                                    <div class="text-xs text-info mt-1">
                                                        เริ่มใน <?php echo $hours > 0 ? $hours . ' ชม. ' : ''; ?><?php echo $minutes; ?> นาที
                                                    </div>
                                                <?php elseif ($is_current): ?>
                                                    <?php
                                                    $time_left = strtotime($booking_end) - strtotime($current_datetime);
                                                    $hours_left = floor($time_left / 3600);
                                                    $minutes_left = floor(($time_left % 3600) / 60);
                                                    ?>
                                                    <div class="text-xs text-error mt-1 animate-pulse">
                                                        เหลือ <?php echo $hours_left > 0 ? $hours_left . ' ชม. ' : ''; ?><?php echo $minutes_left; ?> นาที
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- สรุปสถิติ -->
                        <div class="divider"></div>
                        <div class="stats stats-vertical lg:stats-horizontal shadow">
                            <div class="stat">
                                <div class="stat-title">อนุมัติแล้ว</div>
                                <div class="stat-value text-success"><?php echo count($approved_bookings); ?></div>
                                <div class="stat-desc">รายการ</div>
                            </div>
                            <div class="stat">
                                <div class="stat-title">รออนุมัติ</div>
                                <div class="stat-value text-warning"><?php echo count($pending_bookings_today); ?></div>
                                <div class="stat-desc">รายการ</div>
                            </div>
                            <div class="stat">
                                <div class="stat-title">อื่นๆ</div>
                                <div class="stat-value text-neutral"><?php echo count($other_bookings); ?></div>
                                <div class="stat-desc">รายการ</div>
                            </div>
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

        <!-- Admin Dashboard สำหรับส่งสรุปการจอง -->
        <?php if ($_SESSION['role'] === 'admin'): ?>
        <div class="card bg-gradient-to-r from-purple-500 to-pink-500 text-white shadow-xl mt-6">
            <div class="card-body">
                <h3 class="card-title flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.367 2.684 3 3 0 00-5.367-2.684z" />
                    </svg>
                    Admin Dashboard - ส่งสรุปการจองผ่าน Telegram
                </h3>
                
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-4">
                    <!-- ส่วนเลือกวันที่และ User -->
                    <div class="bg-white/20 rounded-lg p-4 backdrop-blur-sm">
                        <h4 class="font-semibold mb-3 flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            ส่งสรุปการจองแบบกำหนดเอง
                        </h4>
                        
                        <form id="telegramSummaryForm" class="space-y-3">
                            <div class="grid grid-cols-2 gap-2">
                                <div class="form-control">
                                    <label class="label">
                                        <span class="label-text text-white">วันที่เริ่มต้น</span>
                                    </label>
                                    <input type="date" id="startDate" name="start_date" class="input input-bordered text-black" 
                                           value="<?php echo date('Y-m-d'); ?>" max="<?php echo date('Y-m-d', strtotime('+30 days')); ?>">
                                </div>
                                
                                <div class="form-control">
                                    <label class="label">
                                        <span class="label-text text-white">วันที่สิ้นสุด</span>
                                    </label>
                                    <input type="date" id="endDate" name="end_date" class="input input-bordered text-black" 
                                           value="<?php echo date('Y-m-d'); ?>" max="<?php echo date('Y-m-d', strtotime('+30 days')); ?>">
                                </div>
                            </div>
                            
                            <div class="form-control">
                                <label class="label">
                                    <span class="label-text text-white">ช่วงเวลาที่กำหนดไว้</span>
                                </label>
                                <div class="btn-group w-full">
                                    <button type="button" onclick="setDateRange('today')" class="btn btn-sm btn-outline text-white border-white/30 hover:bg-white/20">วันนี้</button>
                                    <button type="button" onclick="setDateRange('week')" class="btn btn-sm btn-outline text-white border-white/30 hover:bg-white/20">สัปดาห์นี้</button>
                                    <button type="button" onclick="setDateRange('month')" class="btn btn-sm btn-outline text-white border-white/30 hover:bg-white/20">เดือนนี้</button>
                                </div>
                            </div>
                            
                            <div class="form-control">
                                <label class="label">
                                    <span class="label-text text-white">เลือกผู้รับ</span>
                                </label>
                                <select id="recipient" name="recipient" class="select select-bordered text-black">
                                    <option value="all">ส่งให้ทุกคน</option>
                                    <option value="admins">ส่งให้ Admin เท่านั้น</option>
                                    <option value="managers">ส่งให้ Manager และ Admin</option>
                                    <option value="custom">เลือกผู้ใช้เฉพาะ</option>
                                </select>
                            </div>
                            
                            <div id="userSelector" class="form-control hidden">
                                <label class="label">
                                    <span class="label-text text-white">เลือกผู้ใช้</span>
                                </label>
                                <select multiple id="selectedUsers" name="selected_users[]" class="select select-bordered text-black h-32">
                                    <?php
                                    // ดึงรายชื่อผู้ใช้ทั้งหมด
                                    $stmt = $pdo->prepare("SELECT user_id, fullname, username, role FROM users WHERE is_active = 1 ORDER BY role DESC, fullname");
                                    $stmt->execute();
                                    $all_users = $stmt->fetchAll();
                                    
                                    foreach ($all_users as $user):
                                        $role_badge = $user['role'] === 'admin' ? '[Admin]' : 
                                                     ($user['role'] === 'manager' ? '[Manager]' : '[User]');
                                    ?>
                                        <option value="<?php echo $user['user_id']; ?>">
                                            <?php echo htmlspecialchars($user['fullname'] . ' ' . $role_badge); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-control">
                                <label class="label">
                                    <span class="label-text text-white">ประเภทรายงาน</span>
                                </label>
                                <select id="reportType" name="report_type" class="select select-bordered text-black">
                                    <option value="summary">สรุปทั่วไป</option>
                                    <option value="detailed">รายละเอียดครบถ้วน</option>
                                    <option value="pending_only">เฉพาะรออนุมัติ</option>
                                    <option value="approved_only">เฉพาะอนุมัติแล้ว</option>
                                </select>
                            </div>
                            
                            <button type="submit" class="btn btn-warning w-full" id="sendTelegramBtn">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                                </svg>
                                ส่งผ่าน Telegram
                            </button>
                        </form>
                    </div>
                    
                    <!-- ส่วนตัวอย่างข้อความ -->
                    <div class="bg-white/20 rounded-lg p-4 backdrop-blur-sm">
                        <h4 class="font-semibold mb-3 flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                            ตัวอย่างข้อความ
                        </h4>
                        
                        <div id="messagePreview" class="bg-black/30 rounded p-3 text-sm font-mono">
                            <div class="text-yellow-300">📊 สรุปการจองห้องประชุม</div>
                            <div class="text-blue-300">🏢 <?php echo $org_config['name']; ?></div>
                            <div class="text-green-300">📅 วันที่: <?php echo formatThaiDate(date('Y-m-d'), 'full'); ?></div>
                            <div class="mt-2">
                                <div class="text-white">📈 สถิติ:</div>
                                <div class="ml-2">
                                    <div>✅ อนุมัติแล้ว: <?php echo count(array_filter($today_bookings, function($b) { return $b['status'] === 'approved'; })); ?> รายการ</div>
                                    <div>⏳ รออนุมัติ: <?php echo count(array_filter($today_bookings, function($b) { return $b['status'] === 'pending'; })); ?> รายการ</div>
                                    <div>🔢 รวมทั้งหมด: <?php echo count($today_bookings); ?> รายการ</div>
                                </div>
                            </div>
                            <div class="mt-2 text-gray-300">⏰ ส่งเมื่อ: [เวลาปัจจุบัน]</div>
                        </div>
                        
                        <!-- Quick Actions -->
                        <div class="mt-4 space-y-2">
                            <button onclick="sendQuickSummary('today')" class="btn btn-sm btn-info w-full">
                                📊 ส่งสรุปวันนี้ (ด่วน)
                            </button>
                            <button onclick="sendQuickSummary('pending')" class="btn btn-sm btn-warning w-full">
                                ⏳ ส่งรายการรออนุมัติ (ด่วน)
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- สถิติการส่ง Telegram -->
                <div class="mt-6 grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="stat bg-white/20 rounded-lg text-center">
                        <div class="stat-title text-white/80">ส่งวันนี้</div>
                        <div class="stat-value text-2xl">0</div>
                        <div class="stat-desc text-white/60">ข้อความ</div>
                    </div>
                    <div class="stat bg-white/20 rounded-lg text-center">
                        <div class="stat-title text-white/80">ส่งสัปดาห์นี้</div>
                        <div class="stat-value text-2xl">0</div>
                        <div class="stat-desc text-white/60">ข้อความ</div>
                    </div>
                    <div class="stat bg-white/20 rounded-lg text-center">
                        <div class="stat-title text-white/80">ผู้รับทั้งหมด</div>
                        <div class="stat-value text-2xl"><?php echo count($all_users); ?></div>
                        <div class="stat-desc text-white/60">คน</div>
                    </div>
                    <div class="stat bg-white/20 rounded-lg text-center">
                        <div class="stat-title text-white/80">สถานะ Telegram</div>
                        <div class="stat-value text-lg text-success">✅</div>
                        <div class="stat-desc text-white/60">เชื่อมต่อแล้ว</div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
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
                    <a href="admin/reports.php" class="btn btn-accent">
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
        // Auto refresh every 5 minutes
        setTimeout(function() {
            location.reload();
        }, 300000);
        
        // จัดการฟอร์ม Telegram Summary
        document.addEventListener('DOMContentLoaded', function() {
            const recipientSelect = document.getElementById('recipient');
            const userSelector = document.getElementById('userSelector');
            const telegramForm = document.getElementById('telegramSummaryForm');
            const reportTypeSelect = document.getElementById('reportType');
            const messagePreview = document.getElementById('messagePreview');
            
            // แสดง/ซ่อน user selector
            if (recipientSelect) {
                recipientSelect.addEventListener('change', function() {
                    if (this.value === 'custom') {
                        userSelector.classList.remove('hidden');
                    } else {
                        userSelector.classList.add('hidden');
                    }
                    updateMessagePreview();
                });
            }
            
            // อัปเดตตัวอย่างข้อความ
            function updateMessagePreview() {
                const startDate = document.getElementById('startDate').value;
                const endDate = document.getElementById('endDate').value;
                const reportType = document.getElementById('reportType').value;
                const recipient = document.getElementById('recipient').value;
                
                let preview = `📊 สรุปการจองห้องประชุม\n`;
                preview += `🏢 <?php echo $org_config['name']; ?>\n`;
                
                if (startDate === endDate) {
                    preview += `📅 วันที่: ${formatDateThai(startDate)}\n\n`;
                } else {
                    preview += `📅 ช่วงวันที่: ${formatDateThai(startDate)} ถึง ${formatDateThai(endDate)}\n\n`;
                }
                
                switch(reportType) {
                    case 'summary':
                        preview += `📈 สถิติ:\n`;
                        preview += `✅ อนุมัติแล้ว: [จำนวน] รายการ\n`;
                        preview += `⏳ รออนุมัติ: [จำนวน] รายการ\n`;
                        preview += `🔢 รวมทั้งหมด: [จำนวน] รายการ`;
                        break;
                    case 'detailed':
                        preview += `📝 รายละเอียดแต่ละการจอง:\n`;
                        preview += `🏠 [ห้อง] | 🕐 [เวลา] | 👤 [ผู้จอง]\n`;
                        preview += `📋 [วัตถุประสงค์]\n`;
                        preview += `...และอื่นๆ`;
                        break;
                    case 'pending_only':
                        preview += `⏳ รายการรออนุมัติ:\n`;
                        preview += `🏠 [ห้อง] | 🕐 [เวลา] | 👤 [ผู้จอง]\n`;
                        preview += `📝 [วัตถุประสงค์]`;
                        break;
                    case 'approved_only':
                        preview += `✅ รายการอนุมัติแล้ว:\n`;
                        preview += `🏠 [ห้อง] | 🕐 [เวลา] | 👤 [ผู้จอง]\n`;
                        preview += `📝 [วัตถุประสงค์]`;
                        break;
                }
                
                preview += `\n\n👥 ส่งถึง: ${getRecipientText(recipient)}`;
                preview += `\n⏰ ส่งเมื่อ: ${new Date().toLocaleString('th-TH')}`;
                
                if (messagePreview) {
                    messagePreview.innerHTML = preview.replace(/\n/g, '<br>');
                }
            }
            
            function getRecipientText(recipient) {
                switch(recipient) {
                    case 'all': return 'ทุกคน';
                    case 'admins': return 'Admin เท่านั้น';
                    case 'managers': return 'Manager และ Admin';
                    case 'custom': return 'ผู้ใช้ที่เลือก';
                    default: return 'ไม่ระบุ';
                }
            }
            
            function formatDateThai(dateStr) {
                const date = new Date(dateStr);
                return date.toLocaleDateString('th-TH', {
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric',
                    weekday: 'long'
                });
            }
            
            // ฟังก์ชันสำหรับเลือกช่วงวันที่
            function setDateRange(type) {
                const today = new Date();
                const startDateInput = document.getElementById('startDate');
                const endDateInput = document.getElementById('endDate');
                
                let startDate, endDate;
                
                switch(type) {
                    case 'today':
                        startDate = endDate = today;
                        break;
                    case 'week':
                        // หาวันจันทร์ของสัปดาห์นี้
                        const dayOfWeek = today.getDay();
                        const daysToMonday = dayOfWeek === 0 ? 6 : dayOfWeek - 1;
                        startDate = new Date(today);
                        startDate.setDate(today.getDate() - daysToMonday);
                        endDate = new Date(startDate);
                        endDate.setDate(startDate.getDate() + 6);
                        break;
                    case 'month':
                        startDate = new Date(today.getFullYear(), today.getMonth(), 1);
                        endDate = new Date(today.getFullYear(), today.getMonth() + 1, 0);
                        break;
                }
                
                startDateInput.value = startDate.toISOString().split('T')[0];
                endDateInput.value = endDate.toISOString().split('T')[0];
                updateMessagePreview();
            }
            
            // Event listeners สำหรับอัปเดตตัวอย่าง
            ['startDate', 'endDate', 'reportType', 'recipient'].forEach(id => {
                const element = document.getElementById(id);
                if (element) {
                    element.addEventListener('change', updateMessagePreview);
                }
            });
            
            // ส่งฟอร์ม Telegram
            if (telegramForm) {
                telegramForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    const submitBtn = document.getElementById('sendTelegramBtn');
                    const originalText = submitBtn.innerHTML;
                    
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<span class="loading loading-spinner loading-sm"></span> กำลังส่ง...';
                    
                    const formData = new FormData(this);
                    
                    fetch('admin/send_telegram_summary.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('✅ ส่งข้อความสำเร็จ!\n\nส่งถึง: ' + data.recipients + ' คน\nเวลา: ' + new Date().toLocaleString('th-TH'));
                        } else {
                            alert('❌ เกิดข้อผิดพลาด: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('❌ เกิดข้อผิดพลาดในการส่งข้อความ');
                    })
                    .finally(() => {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = originalText;
                    });
                });
            }
            
            // เริ่มต้นตัวอย่างข้อความ
            updateMessagePreview();
        });
        
        // ฟังก์ชันส่งด่วน
        function sendQuickSummary(type) {
            const confirmMsg = type === 'today' ? 
                'ส่งสรุปการจองวันนี้ให้ทุกคน?' : 
                'ส่งรายการรออนุมัติให้ Manager และ Admin?';
                
            if (!confirm(confirmMsg)) return;
            
            const btn = event.target;
            const originalText = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<span class="loading loading-spinner loading-sm"></span> กำลังส่ง...';
            
            const formData = new FormData();
            formData.append('quick_type', type);
            formData.append('start_date', '<?php echo date('Y-m-d'); ?>');
            formData.append('end_date', '<?php echo date('Y-m-d'); ?>');
            
            fetch('admin/send_telegram_summary.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('✅ ส่งข้อความสำเร็จ!\n\nส่งถึง: ' + data.recipients + ' คน');
                } else {
                    alert('❌ เกิดข้อผิดพลาด: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('❌ เกิดข้อผิดพลาดในการส่งข้อความ');
            })
            .finally(() => {
                btn.disabled = false;
                btn.innerHTML = originalText;
            });
        }
    </script>
</body>
</html>