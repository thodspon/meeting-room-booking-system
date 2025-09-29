<?php
session_start();
require_once '../config/database.php';
require_once '../config.php';
require_once '../includes/functions.php';

// ตรวจสอบการ login
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

// Get organization config
$org_config = getOrganizationConfig();

// รับ room_id จาก URL
$room_id = isset($_GET['room_id']) ? intval($_GET['room_id']) : 0;

if ($room_id <= 0) {
    header('Location: rooms.php?error=invalid_room');
    exit();
}

// ดึงข้อมูลห้องประชุม
try {
    $stmt = $pdo->prepare("SELECT * FROM rooms WHERE room_id = ? AND is_active = 1");
    $stmt->execute([$room_id]);
    $room = $stmt->fetch();
    
    if (!$room) {
        header('Location: rooms.php?error=room_not_found');
        exit();
    }
} catch (PDOException $e) {
    header('Location: rooms.php?error=database');
    exit();
}

// กำหนดช่วงวันที่เริ่มต้น
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01'); // วันแรกของเดือน
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t'); // วันสุดท้ายของเดือน
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

// ดึงข้อมูลการจองของห้อง
$sql = "
    SELECT b.*, u.fullname, u.department, u.position, a.fullname as approved_by_name
    FROM bookings b 
    JOIN users u ON b.user_id = u.user_id 
    LEFT JOIN users a ON b.approved_by = a.user_id
    WHERE b.room_id = ? AND b.booking_date BETWEEN ? AND ?
";

$params = [$room_id, $start_date, $end_date];

if ($status_filter) {
    $sql .= " AND b.status = ?";
    $params[] = $status_filter;
}

$sql .= " ORDER BY b.booking_date DESC, b.start_time DESC";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $bookings = $stmt->fetchAll();
} catch (PDOException $e) {
    $bookings = [];
    $error_message = "เกิดข้อผิดพลาดในการดึงข้อมูล: " . $e->getMessage();
}

// สถิติการจอง
$stats = [
    'total' => count($bookings),
    'approved' => count(array_filter($bookings, function($b) { return $b['status'] === 'approved'; })),
    'pending' => count(array_filter($bookings, function($b) { return $b['status'] === 'pending'; })),
    'rejected' => count(array_filter($bookings, function($b) { return $b['status'] === 'rejected'; })),
    'cancelled' => count(array_filter($bookings, function($b) { return $b['status'] === 'cancelled'; }))
];

$page_title = 'การจองห้อง: ' . $room['room_name'];
?>

<!DOCTYPE html>
<html lang="th" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?> - <?= $org_config['name'] ?></title>
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
        
        .card-title, .stat-title {
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
        
        .navbar {
            font-family: 'Prompt', 'Sarabun', sans-serif;
        }
        
        .breadcrumbs {
            font-family: 'Sarabun', 'Prompt', sans-serif;
        }
        
        .table th, .table td {
            font-family: 'Sarabun', 'Prompt', sans-serif;
        }
        
        .badge {
            font-family: 'Sarabun', 'Prompt', sans-serif;
            font-weight: 500;
        }
        
        .room-color-indicator {
            width: 1rem;
            height: 1rem;
            border-radius: 50%;
            display: inline-block;
            margin-right: 0.5rem;
            border: 2px solid #fff;
            box-shadow: 0 0 0 1px rgba(0,0,0,0.1);
        }
        
        @media print {
            .navbar, .breadcrumbs, .btn, footer, .btn-group { 
                display: none !important; 
            }
            .card { 
                box-shadow: none !important; 
                border: 1px solid #ccc; 
                break-inside: avoid;
            }
        }
    </style>
</head>
<body class="thai-text">
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
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    สวัสดี, <?php echo htmlspecialchars($_SESSION['username']); ?>
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
                    <li><a href="version_info.php" class="text-base-content">ข้อมูลระบบ</a></li>
                    <li><a href="logout.php" class="text-base-content">ออกจากระบบ</a></li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container mx-auto p-4">
        <div class="breadcrumbs text-sm mb-4">
            <ul>
                <li><a href="../index.php">หน้าหลัก</a></li>
                <li><a href="rooms.php">จัดการห้องประชุม</a></li>
                <li>การจองห้อง: <?= htmlspecialchars($room['room_name']) ?></li>
            </ul>
        </div>

        <!-- ข้อมูลห้องประชุม -->
        <div class="card bg-base-100 shadow-xl mb-6">
            <div class="card-body">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <?php if (!empty($room['room_color'])): ?>
                            <div class="room-color-indicator" style="background-color: <?= htmlspecialchars($room['room_color']) ?>"></div>
                        <?php endif; ?>
                        <div>
                            <h2 class="card-title text-2xl">
                                <?= htmlspecialchars($room['room_name']) ?>
                                <span class="badge badge-outline"><?= htmlspecialchars($room['room_code']) ?></span>
                            </h2>
                            <p class="text-base-content/70">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 inline mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                <?= htmlspecialchars($room['location']) ?>
                                
                                <?php if ($room['capacity']): ?>
                                    <span class="ml-4">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 inline mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                        </svg>
                                        จุได้ <?= $room['capacity'] ?> คน
                                    </span>
                                <?php endif; ?>
                            </p>
                            <?php if ($room['description']): ?>
                                <p class="text-sm mt-2"><?= htmlspecialchars($room['description']) ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <a href="booking.php?room_id=<?= $room['room_id'] ?>" class="btn btn-primary">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                            </svg>
                            จองห้องนี้
                        </a>
                        <a href="rooms.php" class="btn btn-ghost">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                            </svg>
                            กลับ
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- ฟิลเตอร์ -->
        <div class="card bg-base-100 shadow-xl mb-6">
            <div class="card-body">
                <h3 class="card-title">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.707A1 1 0 013 7V4z" />
                    </svg>
                    ตัวกรองข้อมูล
                </h3>
                
                <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <input type="hidden" name="room_id" value="<?= $room_id ?>">
                    
                    <div class="form-control">
                        <label class="label">
                            <span class="label-text">วันที่เริ่มต้น</span>
                        </label>
                        <input type="date" name="start_date" class="input input-bordered" 
                               value="<?= htmlspecialchars($start_date) ?>">
                    </div>
                    
                    <div class="form-control">
                        <label class="label">
                            <span class="label-text">วันที่สิ้นสุด</span>
                        </label>
                        <input type="date" name="end_date" class="input input-bordered" 
                               value="<?= htmlspecialchars($end_date) ?>">
                    </div>
                    
                    <div class="form-control">
                        <label class="label">
                            <span class="label-text">สถานะ</span>
                        </label>
                        <select name="status" class="select select-bordered">
                            <option value="">ทุกสถานะ</option>
                            <option value="pending" <?= $status_filter === 'pending' ? 'selected' : '' ?>>รออนุมัติ</option>
                            <option value="approved" <?= $status_filter === 'approved' ? 'selected' : '' ?>>อนุมัติ</option>
                            <option value="rejected" <?= $status_filter === 'rejected' ? 'selected' : '' ?>>ไม่อนุมัติ</option>
                            <option value="cancelled" <?= $status_filter === 'cancelled' ? 'selected' : '' ?>>ยกเลิก</option>
                        </select>
                    </div>
                    
                    <div class="form-control">
                        <label class="label">
                            <span class="label-text">&nbsp;</span>
                        </label>
                        <button type="submit" class="btn btn-primary">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            ค้นหา
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- สถิติ -->
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
            <div class="stat bg-base-100 shadow-xl rounded-box">
                <div class="stat-title">รวมทั้งหมด</div>
                <div class="stat-value text-primary"><?= $stats['total'] ?></div>
                <div class="stat-desc">รายการ</div>
            </div>
            <div class="stat bg-base-100 shadow-xl rounded-box">
                <div class="stat-title">อนุมัติแล้ว</div>
                <div class="stat-value text-success"><?= $stats['approved'] ?></div>
                <div class="stat-desc"><?= $stats['total'] > 0 ? round(($stats['approved']/$stats['total'])*100, 1) : 0 ?>%</div>
            </div>
            <div class="stat bg-base-100 shadow-xl rounded-box">
                <div class="stat-title">รออนุมัติ</div>
                <div class="stat-value text-warning"><?= $stats['pending'] ?></div>
                <div class="stat-desc"><?= $stats['total'] > 0 ? round(($stats['pending']/$stats['total'])*100, 1) : 0 ?>%</div>
            </div>
            <div class="stat bg-base-100 shadow-xl rounded-box">
                <div class="stat-title">ไม่อนุมัติ</div>
                <div class="stat-value text-error"><?= $stats['rejected'] ?></div>
                <div class="stat-desc"><?= $stats['total'] > 0 ? round(($stats['rejected']/$stats['total'])*100, 1) : 0 ?>%</div>
            </div>
            <div class="stat bg-base-100 shadow-xl rounded-box">
                <div class="stat-title">ยกเลิก</div>
                <div class="stat-value text-neutral"><?= $stats['cancelled'] ?></div>
                <div class="stat-desc"><?= $stats['total'] > 0 ? round(($stats['cancelled']/$stats['total'])*100, 1) : 0 ?>%</div>
            </div>
        </div>

        <!-- รายการการจอง -->
        <div class="card bg-base-100 shadow-xl">
            <div class="card-body">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="card-title">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        รายการการจอง
                    </h3>
                    <button onclick="window.print()" class="btn btn-outline btn-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                        </svg>
                        พิมพ์
                    </button>
                </div>

                <?php if (isset($error_message)): ?>
                    <div class="alert alert-error mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span><?= htmlspecialchars($error_message) ?></span>
                    </div>
                <?php endif; ?>

                <?php if (empty($bookings)): ?>
                    <div class="text-center py-8">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mx-auto text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <p class="text-gray-500 text-lg">ไม่มีการจองในช่วงเวลาที่เลือก</p>
                        <a href="booking.php?room_id=<?= $room_id ?>" class="btn btn-primary mt-4">จองห้องนี้</a>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>วันที่</th>
                                    <th>เวลา</th>
                                    <th>ผู้จอง</th>
                                    <th>หน่วยงาน</th>
                                    <th>วัตถุประสงค์</th>
                                    <th>สถานะ</th>
                                    <th>ผู้อนุมัติ</th>
                                    <th>จัดการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($bookings as $booking): ?>
                                    <tr>
                                        <td>
                                            <div class="font-bold text-primary">
                                                <?= formatThaiDate($booking['booking_date']) ?>
                                            </div>
                                            <div class="text-sm opacity-50">
                                                <?= date('l', strtotime($booking['booking_date'])) ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="font-mono text-sm">
                                                <?= date('H:i', strtotime($booking['start_time'])) ?> - 
                                                <?= date('H:i', strtotime($booking['end_time'])) ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="font-bold"><?= htmlspecialchars($booking['fullname']) ?></div>
                                            <div class="text-sm opacity-50"><?= htmlspecialchars($booking['position']) ?></div>
                                        </td>
                                        <td>
                                            <div class="text-sm"><?= htmlspecialchars($booking['department']) ?></div>
                                        </td>
                                        <td class="max-w-xs">
                                            <div class="truncate" title="<?= htmlspecialchars($booking['purpose']) ?>">
                                                <?= htmlspecialchars($booking['purpose']) ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="badge <?php 
                                                echo $booking['status'] == 'approved' ? 'badge-success' : 
                                                     ($booking['status'] == 'pending' ? 'badge-warning' : 
                                                     ($booking['status'] == 'rejected' ? 'badge-error' : 'badge-neutral')); 
                                            ?>">
                                                <?php 
                                                    echo $booking['status'] == 'approved' ? 'อนุมัติ' : 
                                                         ($booking['status'] == 'pending' ? 'รออนุมัติ' : 
                                                         ($booking['status'] == 'rejected' ? 'ไม่อนุมัติ' : 'ยกเลิก')); 
                                                ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="text-sm">
                                                <?= $booking['approved_by_name'] ? htmlspecialchars($booking['approved_by_name']) : '-' ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="flex gap-1">
                                                <!-- ปุ่มดูรายละเอียด -->
                                                <button onclick="showBookingDetails(<?= $booking['booking_id'] ?>)" 
                                                        class="btn btn-info btn-xs">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                    </svg>
                                                    ดู
                                                </button>
                                                
                                                <!-- ปุ่มอนุมัติ/ไม่อนุมัติ (สำหรับ admin และ pending status) -->
                                                <?php if ($booking['status'] == 'pending' && checkPermission($pdo, $_SESSION['user_id'], 'approve_bookings')): ?>
                                                    <a href="../approve_booking.php?id=<?= $booking['booking_id'] ?>&action=approve" 
                                                       class="btn btn-success btn-xs"
                                                       onclick="return confirm('อนุมัติการจองนี้?')">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                        </svg>
                                                        อนุมัติ
                                                    </a>
                                                    <a href="../approve_booking.php?id=<?= $booking['booking_id'] ?>&action=reject" 
                                                       class="btn btn-error btn-xs"
                                                       onclick="return confirm('ไม่อนุมัติการจองนี้?')">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                        </svg>
                                                        ไม่อนุมัติ
                                                    </a>
                                                <?php endif; ?>
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

    <!-- Footer -->
    <?php 
    require_once 'version.php'; 
    echo getSystemFooter(); 
    ?>

    <!-- Modal สำหรับดูรายละเอียดการจอง -->
    <dialog id="booking_details_modal" class="modal">
        <div class="modal-box w-11/12 max-w-2xl">
            <h3 class="font-bold text-lg mb-4">รายละเอียดการจอง</h3>
            <div id="booking_details_content">
                <!-- Content will be loaded here -->
            </div>
            <div class="modal-action">
                <button onclick="document.getElementById('booking_details_modal').close()" class="btn">ปิด</button>
            </div>
        </div>
        <form method="dialog" class="modal-backdrop">
            <button>close</button>
        </form>
    </dialog>

    <script>
        // เก็บข้อมูลการจองทั้งหมดไว้ใน JavaScript
        const bookingsData = <?= json_encode($bookings) ?>;
        
        function showBookingDetails(bookingId) {
            console.log('Booking ID:', bookingId);
            
            // หาข้อมูลการจองจาก booking ID
            const booking = bookingsData.find(b => b.booking_id == bookingId);
            
            if (!booking) {
                alert('ไม่พบข้อมูลการจอง');
                return;
            }
            
            const thaiDays = {
                'Sunday': 'อาทิตย์', 'Monday': 'จันทร์', 'Tuesday': 'อังคาร',
                'Wednesday': 'พุธ', 'Thursday': 'พฤหัสบดี', 'Friday': 'ศุกร์', 'Saturday': 'เสาร์'
            };
            
            const statusText = {
                'approved': 'อนุมัติ',
                'pending': 'รออนุมัติ',
                'rejected': 'ไม่อนุมัติ',
                'cancelled': 'ยกเลิกแล้ว'
            };
            
            const statusClass = {
                'approved': 'badge-success',
                'pending': 'badge-warning',
                'rejected': 'badge-error',
                'cancelled': 'badge-neutral'
            };
            
            // Format date safely
            let formattedDate = booking.booking_date || '-';
            let dayName = '';
            try {
                const bookingDate = new Date(booking.booking_date);
                if (!isNaN(bookingDate.getTime())) {
                    dayName = bookingDate.toLocaleDateString('en-US', { weekday: 'long' });
                    const options = { year: 'numeric', month: 'long', day: 'numeric' };
                    formattedDate = bookingDate.toLocaleDateString('th-TH', options);
                }
            } catch (e) {
                console.error('Date parsing error:', e);
            }
            
            const startTime = booking.start_time ? booking.start_time.substring(0, 5) : '-';
            const endTime = booking.end_time ? booking.end_time.substring(0, 5) : '-';
            
            const content = `
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="form-control">
                        <label class="label"><span class="label-text font-semibold">รหัสการจอง</span></label>
                        <div class="text-lg font-mono text-primary">#${booking.booking_id || '-'}</div>
                    </div>
                    
                    <div class="form-control">
                        <label class="label"><span class="label-text font-semibold">สถานะ</span></label>
                        <div class="badge ${statusClass[booking.status] || 'badge-neutral'} badge-lg">${statusText[booking.status] || booking.status}</div>
                    </div>
                    
                    <div class="form-control">
                        <label class="label"><span class="label-text font-semibold">วันที่จอง</span></label>
                        <div class="text-lg">${formattedDate}</div>
                        <div class="text-sm opacity-70">${thaiDays[dayName] || dayName}</div>
                    </div>
                    
                    <div class="form-control">
                        <label class="label"><span class="label-text font-semibold">เวลา</span></label>
                        <div class="text-lg font-mono">${startTime} - ${endTime}</div>
                    </div>
                    
                    <div class="form-control">
                        <label class="label"><span class="label-text font-semibold">ผู้จอง</span></label>
                        <div class="text-lg font-semibold">${booking.fullname || '-'}</div>
                        <div class="text-sm opacity-70">${booking.position || ''}</div>
                    </div>
                    
                    <div class="form-control">
                        <label class="label"><span class="label-text font-semibold">หน่วยงาน</span></label>
                        <div class="text-lg">${booking.department || '-'}</div>
                    </div>
                    
                    <div class="form-control md:col-span-2">
                        <label class="label"><span class="label-text font-semibold">วัตถุประสงค์</span></label>
                        <div class="text-base bg-base-200 p-3 rounded">${booking.purpose || '-'}</div>
                    </div>
                    
                    <div class="form-control">
                        <label class="label"><span class="label-text font-semibold">จำนวนผู้เข้าร่วม</span></label>
                        <div class="text-lg">${booking.attendees || '-'} คน</div>
                    </div>
                    
                    <div class="form-control">
                        <label class="label"><span class="label-text font-semibold">ผู้อนุมัติ</span></label>
                        <div class="text-lg">${booking.approved_by_name || 'ยังไม่ได้อนุมัติ'}</div>
                    </div>
                    
                    <div class="form-control">
                        <label class="label"><span class="label-text font-semibold">วันที่สร้าง</span></label>
                        <div class="text-sm">${booking.created_at || '-'}</div>
                    </div>
                    
                    <div class="form-control">
                        <label class="label"><span class="label-text font-semibold">อัปเดตล่าสุด</span></label>
                        <div class="text-sm">${booking.updated_at || '-'}</div>
                    </div>
                    
                    ${booking.notes ? `
                    <div class="form-control md:col-span-2">
                        <label class="label"><span class="label-text font-semibold">หมายเหตุ</span></label>
                        <div class="text-base bg-base-200 p-3 rounded">${booking.notes}</div>
                    </div>
                    ` : ''}
                </div>
            `;
            
            document.getElementById('booking_details_content').innerHTML = content;
            document.getElementById('booking_details_modal').showModal();
        }
        
        // Auto refresh every 5 minutes
        setTimeout(function() {
            location.reload();
        }, 300000);
    </script>
</body>
</html>