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

// กำหนดเดือนและปีที่จะแสดง
$current_month = isset($_GET['month']) ? intval($_GET['month']) : date('n');
$current_year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');

// ตรวจสอบค่าที่ส่งมา
if ($current_month < 1 || $current_month > 12) {
    $current_month = date('n');
}

// คำนวณเดือนก่อนหน้าและถัดไป
$prev_month = $current_month == 1 ? 12 : $current_month - 1;
$prev_year = $current_month == 1 ? $current_year - 1 : $current_year;
$next_month = $current_month == 12 ? 1 : $current_month + 1;
$next_year = $current_month == 12 ? $current_year + 1 : $current_year;

// สร้างวันที่เริ่มต้นและสิ้นสุดของเดือน
$first_day = date('Y-m-01', mktime(0, 0, 0, $current_month, 1, $current_year));
$last_day = date('Y-m-t', mktime(0, 0, 0, $current_month, 1, $current_year));

// ดึงข้อมูลการจองในเดือนนี้
$stmt = $pdo->prepare("
    SELECT b.*, r.room_name, u.fullname 
    FROM bookings b 
    JOIN rooms r ON b.room_id = r.room_id 
    JOIN users u ON b.user_id = u.user_id 
    WHERE b.booking_date BETWEEN ? AND ? 
    ORDER BY b.booking_date, b.start_time
");
$stmt->execute([$first_day, $last_day]);
$bookings = $stmt->fetchAll();

// จัดกลุ่มการจองตามวันที่
$booking_by_date = [];
foreach ($bookings as $booking) {
    $date = $booking['booking_date'];
    if (!isset($booking_by_date[$date])) {
        $booking_by_date[$date] = [];
    }
    $booking_by_date[$date][] = $booking;
}

// ข้อมูลเดือนภาษาไทย
$thai_months = [
    1 => 'มกราคม', 2 => 'กุมภาพันธ์', 3 => 'มีนาคม', 4 => 'เมษายน',
    5 => 'พฤษภาคม', 6 => 'มิถุนายน', 7 => 'กรกฎาคม', 8 => 'สิงหาคม',
    9 => 'กันยายน', 10 => 'ตุลาคม', 11 => 'พฤศจิกายน', 12 => 'ธันวาคม'
];

$thai_days = ['อา', 'จ', 'อ', 'พ', 'พฤ', 'ศ', 'ส'];

// คำนวณวันแรกของเดือนว่าเป็นวันอะไร (0 = อาทิตย์)
$first_day_of_week = date('w', mktime(0, 0, 0, $current_month, 1, $current_year));
$days_in_month = date('t', mktime(0, 0, 0, $current_month, 1, $current_year));
?>

<!DOCTYPE html>
<html lang="th" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ปฏิทินการจอง - ระบบจองห้องประชุม</title>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.12.10/dist/full.min.css" rel="stylesheet" type="text/css" />
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        * {
            font-family: 'Prompt', sans-serif;
        }
        .calendar-day {
            min-height: 120px;
            border: 1px solid #e5e7eb;
        }
        .booking-item {
            font-size: 10px;
            padding: 1px 4px;
            margin: 1px 0;
            border-radius: 3px;
            cursor: pointer;
        }
        .booking-approved { background-color: #dcfce7; color: #166534; }
        .booking-pending { background-color: #fef3c7; color: #92400e; }
        .booking-rejected { background-color: #fee2e2; color: #991b1b; }
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
                    <li><a href="reports.php" class="text-base-content">รายงาน</a></li>
                </ul>
            </div>
            <a class="btn btn-ghost text-xl flex items-center gap-2" href="index.php">
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
                <li><a href="calendar.php" class="active">ปฏิทินการจอง</a></li>
                <li><a href="my_bookings.php">การจองของฉัน</a></li>
                <li><a href="reports.php">รายงาน</a></li>
            </ul>
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
                <li>ปฏิทินการจอง</li>
            </ul>
        </div>

        <!-- Calendar Header -->
        <div class="card bg-base-100 shadow-xl">
            <div class="card-body">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="card-title text-2xl">
                        ปฏิทินการจอง - <?php echo $thai_months[$current_month] . ' ' . ($current_year + 543); ?>
                    </h2>
                    <div class="flex gap-2">
                        <a href="?month=<?php echo $prev_month; ?>&year=<?php echo $prev_year; ?>" 
                           class="btn btn-outline btn-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                            </svg>
                            ก่อนหน้า
                        </a>
                        <a href="?" class="btn btn-primary btn-sm">วันนี้</a>
                        <a href="?month=<?php echo $next_month; ?>&year=<?php echo $next_year; ?>" 
                           class="btn btn-outline btn-sm">
                            ถัดไป
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </a>
                    </div>
                </div>

                <!-- Legend -->
                <div class="flex flex-wrap gap-4 mb-4 text-sm">
                    <div class="flex items-center gap-2">
                        <div class="w-4 h-4 bg-green-200 rounded"></div>
                        <span>อนุมัติแล้ว</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="w-4 h-4 bg-yellow-200 rounded"></div>
                        <span>รออนุมัติ</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="w-4 h-4 bg-red-200 rounded"></div>
                        <span>ไม่อนุมัติ</span>
                    </div>
                </div>

                <!-- Calendar Grid -->
                <div class="grid grid-cols-7 gap-0 border border-gray-300">
                    <!-- Day Headers -->
                    <?php foreach ($thai_days as $day): ?>
                        <div class="bg-primary text-primary-content text-center py-2 font-semibold border border-gray-300">
                            <?php echo $day; ?>
                        </div>
                    <?php endforeach; ?>

                    <!-- Empty cells for days before the first day of month -->
                    <?php for ($i = 0; $i < $first_day_of_week; $i++): ?>
                        <div class="calendar-day bg-gray-100"></div>
                    <?php endfor; ?>

                    <!-- Days of the month -->
                    <?php for ($day = 1; $day <= $days_in_month; $day++): ?>
                        <?php
                        $current_date = sprintf('%04d-%02d-%02d', $current_year, $current_month, $day);
                        $is_today = $current_date == date('Y-m-d');
                        $day_bookings = isset($booking_by_date[$current_date]) ? $booking_by_date[$current_date] : [];
                        ?>
                        <div class="calendar-day <?php echo $is_today ? 'bg-blue-50 border-blue-300' : 'bg-white'; ?> p-2">
                            <div class="font-semibold <?php echo $is_today ? 'text-blue-600' : ''; ?>">
                                <?php echo $day; ?>
                                <?php if ($is_today): ?>
                                    <span class="text-xs bg-blue-500 text-white px-1 rounded">วันนี้</span>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Bookings for this day -->
                            <?php foreach ($day_bookings as $booking): ?>
                                <div class="booking-item booking-<?php echo $booking['status']; ?>" 
                                     title="<?php echo htmlspecialchars($booking['room_name'] . ' - ' . $booking['fullname'] . ' (' . date('H:i', strtotime($booking['start_time'])) . '-' . date('H:i', strtotime($booking['end_time'])) . ')', ENT_QUOTES, 'UTF-8'); ?>">
                                    <?php echo date('H:i', strtotime($booking['start_time'])); ?> 
                                    <?php echo htmlspecialchars(mb_substr($booking['room_name'], 0, 10, 'UTF-8'), ENT_QUOTES, 'UTF-8'); ?>
                                    <?php if (mb_strlen($booking['room_name'], 'UTF-8') > 10) echo '...'; ?>
                                </div>
                            <?php endforeach; ?>

                            <!-- Add booking button for future dates -->
                            <?php if ($current_date >= date('Y-m-d')): ?>
                                <div class="mt-1">
                                    <a href="booking.php?date=<?php echo $current_date; ?>" 
                                       class="text-xs text-blue-600 hover:text-blue-800">+ จอง</a>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endfor; ?>

                    <!-- Fill remaining cells -->
                    <?php
                    $total_cells = $first_day_of_week + $days_in_month;
                    $remaining_cells = 42 - $total_cells; // 6 rows * 7 days = 42
                    for ($i = 0; $i < $remaining_cells; $i++):
                    ?>
                        <div class="calendar-day bg-gray-100"></div>
                    <?php endfor; ?>
                </div>
            </div>
        </div>

        <!-- Today's Bookings Detail -->
        <?php
        $today = date('Y-m-d');
        $today_bookings = isset($booking_by_date[$today]) ? $booking_by_date[$today] : [];
        ?>
        
        <?php if (!empty($today_bookings)): ?>
        <div class="card bg-base-100 shadow-xl mt-6">
            <div class="card-body">
                <h3 class="card-title">การจองวันนี้ (<?php echo formatThaiDate($today); ?>)</h3>
                <div class="overflow-x-auto">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>เวลา</th>
                                <th>ห้องประชุม</th>
                                <th>ผู้จอง</th>
                                <th>วัตถุประสงค์</th>
                                <th>สถานะ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($today_bookings as $booking): ?>
                                <tr>
                                    <td><?php echo date('H:i', strtotime($booking['start_time'])) . ' - ' . date('H:i', strtotime($booking['end_time'])); ?></td>
                                    <td><?php echo htmlspecialchars($booking['room_name']); ?></td>
                                    <td><?php echo htmlspecialchars($booking['fullname']); ?></td>
                                    <td class="max-w-xs truncate"><?php echo htmlspecialchars($booking['purpose']); ?></td>
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
            </div>
        </div>
        <?php endif; ?>

        <!-- Quick Actions -->
        <div class="card bg-base-100 shadow-xl mt-6">
            <div class="card-body">
                <h3 class="card-title">เมนูด่วน</h3>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
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
                    <a href="reports.php" class="btn btn-accent">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                        รายงาน
                    </a>
                    <a href="rooms.php" class="btn btn-info">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                        จัดการห้องประชุม
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <?php require_once 'version.php'; echo getSystemFooter(); ?>

    <script>
        // Add click handler for booking items
        document.querySelectorAll('.booking-item').forEach(item => {
            item.addEventListener('click', function() {
                // You can add modal or redirect to booking details
                console.log('Booking clicked:', this.title);
            });
        });
    </script>
</body>
</html>