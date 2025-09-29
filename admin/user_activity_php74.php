<?php
session_start();
require_once '../config/database.php';
require_once '../config.php';
require_once '../includes/functions.php';

// ตรวจสอบการ login และสิทธิ์
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

// ตรวจสอบสิทธิ์ดูรายงาน
if (!checkPermission($pdo, $_SESSION['user_id'], 'view_reports')) {
    header('Location: ../index.php?error=permission');
    exit();
}

// Get organization config
$org_config = getOrganizationConfig();
$page_title = 'รายงานกิจกรรมผู้ใช้';

// กำหนดช่วงวันที่เริ่มต้น
$start_date = $_GET['start_date'] ?? date('Y-m-01'); // วันแรกของเดือน
$end_date = $_GET['end_date'] ?? date('Y-m-t'); // วันสุดท้ายของเดือน
$user_id_filter = $_GET['user_id'] ?? '';
$activity_type = $_GET['activity_type'] ?? '';

// ดึงข้อมูลผู้ใช้ทั้งหมด
$stmt = $pdo->prepare("SELECT user_id, fullname, department, role FROM users WHERE is_active = 1 ORDER BY fullname");
$stmt->execute();
$users = $stmt->fetchAll();

// สร้าง Query สำหรับรายงานกิจกรรม - แบบง่าย
$base_sql = "
    SELECT 
        'booking_created' as activity_type,
        b.created_at as activity_date,
        u.fullname as user_name,
        u.department,
        u.role,
        CONCAT('สร้างการจอง: ', r.room_name, ' วันที่ ', b.booking_date) as activity_description,
        b.booking_id as reference_id,
        'booking' as reference_type
    FROM bookings b 
    JOIN users u ON b.user_id = u.user_id 
    JOIN rooms r ON b.room_id = r.room_id
    WHERE DATE(b.created_at) BETWEEN ? AND ?
";

$params = [$start_date, $end_date];

// เพิ่มเงื่อนไขกรองผู้ใช้
if ($user_id_filter) {
    $base_sql .= " AND b.user_id = ?";
    $params[] = $user_id_filter;
}

// เพิ่มเงื่อนไขกรองประเภทกิจกรรม - สำหรับ booking_created เท่านั้นในตอนนี้
if ($activity_type && $activity_type !== 'booking_created') {
    // ถ้าไม่ต้องการ booking_created ให้ใช้ query ว่าง
    $base_sql = "SELECT NULL as activity_type, NULL as activity_date, NULL as user_name, 
                 NULL as department, NULL as role, NULL as activity_description, 
                 NULL as reference_id, NULL as reference_type WHERE 1=0";
    $params = [];
}

$base_sql .= " ORDER BY b.created_at DESC LIMIT 200";

try {
    $stmt = $pdo->prepare($base_sql);
    $stmt->execute($params);
    $activities = $stmt->fetchAll();
    
    // Debug: ตรวจสอบจำนวนการจองทั้งหมดในระบบ
    $debug_stmt = $pdo->prepare("SELECT COUNT(*) as total_bookings FROM bookings WHERE DATE(created_at) BETWEEN ? AND ?");
    $debug_stmt->execute([$start_date, $end_date]);
    $debug_info = $debug_stmt->fetch();
    
} catch (PDOException $e) {
    // Debug: แสดงข้อผิดพลาด
    error_log("SQL Error in user_activity.php: " . $e->getMessage());
    $activities = [];
    $debug_info = ['total_bookings' => 0];
}

// สถิติกิจกรรม
$stats = [
    'total' => count($activities),
    'booking_created' => count(array_filter($activities, fn($a) => $a['activity_type'] === 'booking_created')),
    'booking_approved' => count(array_filter($activities, fn($a) => $a['activity_type'] === 'booking_approved')),
    'booking_cancelled' => count(array_filter($activities, fn($a) => $a['activity_type'] === 'booking_cancelled'))
];

// สถิติผู้ใช้ที่ใช้งานมากที่สุด - แบบง่าย
try {
    $user_stats_sql = "
        SELECT 
            u.fullname,
            u.department,
            COUNT(b.booking_id) as total_activities,
            COUNT(b.booking_id) as bookings_created,
            0 as bookings_approved,
            0 as bookings_cancelled
        FROM users u
        LEFT JOIN bookings b ON u.user_id = b.user_id 
        WHERE u.is_active = 1 
        AND (b.created_at IS NULL OR DATE(b.created_at) BETWEEN ? AND ?)
    ";
    
    $stats_params = [$start_date, $end_date];
    
    if ($user_id_filter) {
        $user_stats_sql .= " AND u.user_id = ?";
        $stats_params[] = $user_id_filter;
    }
    
    $user_stats_sql .= "
        GROUP BY u.user_id, u.fullname, u.department
        HAVING total_activities > 0
        ORDER BY total_activities DESC
        LIMIT 10
    ";
    
    $stmt = $pdo->prepare($user_stats_sql);
    $stmt->execute($stats_params);
    $user_stats = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("User Stats SQL Error: " . $e->getMessage());
    $user_stats = [];
}
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
        
        .timeline-item {
            transition: all 0.3s ease;
        }
        
        .timeline-item:hover {
            transform: translateX(5px);
            background-color: rgba(59, 130, 246, 0.05);
        }
        
        .activity-icon {
            width: 2.5rem;
            height: 2.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            font-size: 1rem;
        }
        
        .activity-created { background-color: #10b981; color: white; }
        .activity-approved { background-color: #3b82f6; color: white; }
        .activity-cancelled { background-color: #ef4444; color: white; }
        
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
                    <?= generateNavigation('user_activity', $_SESSION['role'] ?? 'user', true) ?>
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
                <?= generateNavigation('user_activity', $_SESSION['role'] ?? 'user', false) ?>
            </ul>
        </div>
        <div class="navbar-end">
            <div class="dropdown dropdown-end">
                <div tabindex="0" role="button" class="btn btn-ghost">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    สวัสดี, <?php echo htmlspecialchars($_SESSION['username']); ?>
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
                <li><a href="index.php">หน้าหลัก</a></li>
                <li><a href="reports.php">รายงาน</a></li>
                <li>กิจกรรมผู้ใช้</li>
            </ul>
        </div>

        <!-- ฟิลเตอร์ -->
        <div class="card bg-base-100 shadow-xl mb-6">
            <div class="card-body">
                <h3 class="card-title">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.707A1 1 0 013 7V4z" />
                    </svg>
                    ตัวกรองรายงาน
                </h3>
                
                <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-4">
                    <div class="form-control">
                        <label class="label">
                            <span class="label-text">วันที่เริ่มต้น</span>
                        </label>
                        <input type="date" name="start_date" class="input input-bordered" 
                               value="<?php echo htmlspecialchars($start_date); ?>">
                    </div>
                    
                    <div class="form-control">
                        <label class="label">
                            <span class="label-text">วันที่สิ้นสุด</span>
                        </label>
                        <input type="date" name="end_date" class="input input-bordered" 
                               value="<?php echo htmlspecialchars($end_date); ?>">
                    </div>
                    
                    <div class="form-control">
                        <label class="label">
                            <span class="label-text">ผู้ใช้</span>
                        </label>
                        <select name="user_id" class="select select-bordered">
                            <option value="">ทุกคน</option>
                            <?php foreach ($users as $user): ?>
                                <option value="<?php echo $user['user_id']; ?>" 
                                        <?php echo $user_id_filter == $user['user_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($user['fullname']); ?> (<?php echo htmlspecialchars($user['department']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-control">
                        <label class="label">
                            <span class="label-text">ประเภทกิจกรรม</span>
                        </label>
                        <select name="activity_type" class="select select-bordered">
                            <option value="">ทุกประเภท</option>
                            <option value="booking_created" <?php echo $activity_type === 'booking_created' ? 'selected' : ''; ?>>สร้างการจอง</option>
                            <option value="booking_approved" <?php echo $activity_type === 'booking_approved' ? 'selected' : ''; ?>>อนุมัติการจอง</option>
                            <option value="booking_cancelled" <?php echo $activity_type === 'booking_cancelled' ? 'selected' : ''; ?>>ยกเลิกการจอง</option>
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
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="stat bg-base-100 shadow-xl rounded-box">
                <div class="stat-title">รวมทั้งหมด</div>
                <div class="stat-value text-primary"><?php echo $stats['total']; ?></div>
                <div class="stat-desc">กิจกรรม</div>
            </div>
            <div class="stat bg-base-100 shadow-xl rounded-box">
                <div class="stat-title">สร้างการจอง</div>
                <div class="stat-value text-success"><?php echo $stats['booking_created']; ?></div>
                <div class="stat-desc"><?php echo $stats['total'] > 0 ? round(($stats['booking_created']/$stats['total'])*100, 1) : 0; ?>%</div>
            </div>
            <div class="stat bg-base-100 shadow-xl rounded-box">
                <div class="stat-title">อนุมัติการจอง</div>
                <div class="stat-value text-info"><?php echo $stats['booking_approved']; ?></div>
                <div class="stat-desc"><?php echo $stats['total'] > 0 ? round(($stats['booking_approved']/$stats['total'])*100, 1) : 0; ?>%</div>
            </div>
            <div class="stat bg-base-100 shadow-xl rounded-box">
                <div class="stat-title">ยกเลิกการจอง</div>
                <div class="stat-value text-error"><?php echo $stats['booking_cancelled']; ?></div>
                <div class="stat-desc"><?php echo $stats['total'] > 0 ? round(($stats['booking_cancelled']/$stats['total'])*100, 1) : 0; ?>%</div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- รายการกิจกรรม -->
            <div class="lg:col-span-2">
                <div class="card bg-base-100 shadow-xl">
                    <div class="card-body">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="card-title">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                กิจกรรมล่าสุด
                            </h3>
                            <button onclick="window.print()" class="btn btn-outline btn-sm">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                </svg>
                                พิมพ์
                            </button>
                        </div>

                        <!-- Debug Info -->
                        <div class="alert alert-info mb-4">
                            <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <div>
                                <div class="text-sm">
                                    <strong>Debug Info:</strong> ช่วงเวลา: <?= $start_date ?> ถึง <?= $end_date ?> 
                                    | การจองทั้งหมด: <?= $debug_info['total_bookings'] ?? 0 ?> รายการ
                                    | กิจกรรมที่แสดง: <?= count($activities) ?> รายการ
                                    | ผู้ใช้: <?= count($users) ?> คน
                                    <?php if ($user_id_filter): ?>| กรองผู้ใช้: ID <?= $user_id_filter ?><?php endif; ?>
                                    <?php if ($activity_type): ?>| กรองประเภท: <?= $activity_type ?><?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <?php 
                        // เพิ่มข้อมูลตัวอย่างเพื่อทดสอบหากไม่มีข้อมูลจริง
                        if (empty($activities) && isset($_GET['test'])) {
                            $activities = [
                                [
                                    'activity_type' => 'booking_created',
                                    'activity_date' => date('Y-m-d H:i:s'),
                                    'user_name' => 'ทดสอบ ระบบ',
                                    'department' => 'IT',
                                    'role' => 'admin',
                                    'activity_description' => 'สร้างการจอง: ห้องประชุมใหญ่ วันที่ ' . date('d/m/Y'),
                                    'reference_id' => '999',
                                    'reference_type' => 'booking'
                                ]
                            ];
                        }
                        ?>
                        
                        <?php if (empty($activities)): ?>
                            <div class="text-center py-8">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mx-auto text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <p class="text-gray-500 text-lg">ไม่พบกิจกรรมในช่วงเวลาที่เลือก</p>
                                <p class="text-gray-400 text-sm mt-2">
                                    ลองปรับช่วงเวลาให้กว้างขึ้น หรือตรวจสอบว่ามีการจองในระบบหรือไม่
                                </p>
                                <a href="?test=1&start_date=<?= $start_date ?>&end_date=<?= $end_date ?>" 
                                   class="btn btn-outline btn-sm mt-4">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                                    </svg>
                                    ทดสอบแสดงข้อมูลตัวอย่าง
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="space-y-4 max-h-96 overflow-y-auto">
                                <?php foreach ($activities as $activity): ?>
                                    <div class="timeline-item flex items-start gap-4 p-4 rounded-lg border border-base-300">
                                        <div class="activity-icon <?php 
                                            echo $activity['activity_type'] == 'booking_created' ? 'activity-created' : 
                                                 ($activity['activity_type'] == 'booking_approved' ? 'activity-approved' : 'activity-cancelled'); 
                                        ?>">
                                            <?php if ($activity['activity_type'] == 'booking_created'): ?>
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                                </svg>
                                            <?php elseif ($activity['activity_type'] == 'booking_approved'): ?>
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                </svg>
                                            <?php else: ?>
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="flex-1">
                                            <div class="flex justify-between items-start">
                                                <div>
                                                    <h4 class="font-semibold text-base-content">
                                                        <?php echo htmlspecialchars($activity['user_name']); ?>
                                                    </h4>
                                                    <p class="text-sm text-base-content/70">
                                                        <?php echo htmlspecialchars($activity['department']); ?> • 
                                                        <span class="badge badge-sm badge-outline">
                                                            <?php echo htmlspecialchars($activity['role']); ?>
                                                        </span>
                                                    </p>
                                                </div>
                                                <time class="text-sm text-base-content/50">
                                                    <?php echo formatThaiDate(date('Y-m-d', strtotime($activity['activity_date']))); ?>
                                                    <?php echo date('H:i', strtotime($activity['activity_date'])); ?>
                                                </time>
                                            </div>
                                            <p class="text-base-content/80 mt-2">
                                                <?php echo htmlspecialchars($activity['activity_description']); ?>
                                            </p>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <?php if (count($activities) >= 200): ?>
                                <div class="alert alert-info mt-4">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <span>แสดงผล 200 รายการล่าสุด หากต้องการดูข้อมูลเพิ่มเติม กรุณาใช้ตัวกรองให้แคบลง</span>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- สถิติผู้ใช้ -->
            <div class="lg:col-span-1">
                <div class="card bg-base-100 shadow-xl">
                    <div class="card-body">
                        <h3 class="card-title">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            ผู้ใช้งานมากที่สุด
                        </h3>

                        <?php if (empty($user_stats)): ?>
                            <div class="text-center py-8">
                                <p class="text-gray-500">ไม่มีข้อมูล</p>
                            </div>
                        <?php else: ?>
                            <div class="space-y-3">
                                <?php foreach ($user_stats as $index => $user_stat): ?>
                                    <div class="flex items-center gap-3 p-3 rounded-lg bg-base-200">
                                        <div class="badge badge-primary badge-lg">
                                            <?php echo $index + 1; ?>
                                        </div>
                                        <div class="flex-1">
                                            <div class="font-semibold text-sm">
                                                <?php echo htmlspecialchars($user_stat['fullname']); ?>
                                            </div>
                                            <div class="text-xs text-base-content/70">
                                                <?php echo htmlspecialchars($user_stat['department']); ?>
                                            </div>
                                            <div class="flex gap-2 mt-1">
                                                <span class="badge badge-success badge-xs">
                                                    สร้าง: <?php echo $user_stat['bookings_created']; ?>
                                                </span>
                                                <span class="badge badge-info badge-xs">
                                                    อนุมัติ: <?php echo $user_stat['bookings_approved']; ?>
                                                </span>
                                                <span class="badge badge-error badge-xs">
                                                    ยกเลิก: <?php echo $user_stat['bookings_cancelled']; ?>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="text-right">
                                            <div class="text-lg font-bold text-primary">
                                                <?php echo $user_stat['total_activities']; ?>
                                            </div>
                                            <div class="text-xs text-base-content/50">
                                                กิจกรรม
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <?php 
    require_once '../version.php'; 
    echo getSystemFooter(); 
    ?>

    <script>
        // Auto refresh every 2 minutes
        setTimeout(function() {
            location.reload();
        }, 120000);
        
        // Add loading state to filter form
        document.querySelector('form').addEventListener('submit', function() {
            const button = this.querySelector('button[type="submit"]');
            button.innerHTML = '<span class="loading loading-spinner loading-xs"></span> กำลังค้นหา...';
            button.disabled = true;
        });
    </script>
</body>
</html>