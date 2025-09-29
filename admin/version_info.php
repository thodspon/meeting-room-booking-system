<?php
/**
 * หน้าแสดงข้อมูลเวอร์ชันและประวัติการพัฒนา
 * ระบบจองห้องประชุม - ศูนย์ส่งเสริมสุขภาพสวนพยอม
 */

session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'version.php';

// ตรวจสอบการ login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$system_info = getSystemInfo();
$version_history = getVersionHistory();
$current_version = getCurrentVersionInfo();
?>

<!DOCTYPE html>
<html lang="th" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ข้อมูลระบบและเวอร์ชัน - <?php echo $system_info['name']; ?></title>
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
                    <?php if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'manager'): ?>
                        <li><a href="rooms.php" class="text-base-content">จัดการห้องประชุม</a></li>
                        <li><a href="reports.php" class="text-base-content">รายงาน</a></li>
                        <li><a href="users.php" class="text-base-content">จัดการผู้ใช้</a></li>
                    <?php endif; ?>
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
                <li><a href="calendar.php">ปฏิทินการจอง</a></li>
                <li><a href="my_bookings.php">การจองของฉัน</a></li>
                <?php if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'manager'): ?>
                    <li><a href="rooms.php">จัดการห้องประชุม</a></li>
                    <li><a href="reports.php">รายงาน</a></li>
                    <li><a href="users.php">จัดการผู้ใช้</a></li>
                <?php endif; ?>
                <li><a href="version_info.php" class="active">ข้อมูลระบบ</a></li>
            </ul>
        </div>
        <div class="navbar-end">
            <div class="dropdown dropdown-end">
                <div tabindex="0" role="button" class="btn btn-ghost">
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
                <li>ข้อมูลระบบและเวอร์ชัน</li>
            </ul>
        </div>

        <!-- ข้อมูลระบบ -->
        <div class="card bg-base-100 shadow-xl mb-6">
            <div class="card-body">
                <h2 class="card-title text-2xl mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    ข้อมูลระบบ
                </h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-4">
                        <div class="flex items-center space-x-3">
                            <div class="flex-shrink-0">
                                <div class="w-2 h-2 bg-primary rounded-full"></div>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">ชื่อระบบ</p>
                                <p class="font-semibold"><?php echo $system_info['name']; ?></p>
                            </div>
                        </div>
                        
                        <div class="flex items-center space-x-3">
                            <div class="flex-shrink-0">
                                <div class="w-2 h-2 bg-secondary rounded-full"></div>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">องค์กร</p>
                                <p class="font-semibold"><?php echo $system_info['organization']; ?></p>
                            </div>
                        </div>
                        
                        <div class="flex items-center space-x-3">
                            <div class="flex-shrink-0">
                                <div class="w-2 h-2 bg-accent rounded-full"></div>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">เวอร์ชัน</p>
                                <p class="font-semibold text-primary"><?php echo $system_info['version']; ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="space-y-4">
                        <div class="flex items-center space-x-3">
                            <div class="flex-shrink-0">
                                <div class="w-2 h-2 bg-success rounded-full"></div>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">ผู้พัฒนา</p>
                                <p class="font-semibold"><?php echo DEVELOPER_NAME; ?></p>
                            </div>
                        </div>
                        
                        <div class="flex items-center space-x-3">
                            <div class="flex-shrink-0">
                                <div class="w-2 h-2 bg-warning rounded-full"></div>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">ตำแหน่ง</p>
                                <p class="font-semibold"><?php echo DEVELOPER_POSITION; ?></p>
                            </div>
                        </div>
                        
                        <div class="flex items-center space-x-3">
                            <div class="flex-shrink-0">
                                <div class="w-2 h-2 bg-error rounded-full"></div>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">หน่วยงาน</p>
                                <p class="font-semibold"><?php echo DEVELOPER_ORGANIZATION; ?></p>
                            </div>
                        </div>
                        
                        <div class="flex items-center space-x-3">
                            <div class="flex-shrink-0">
                                <div class="w-2 h-2 bg-info rounded-full"></div>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">ทีมพัฒนา</p>
                                <p class="font-semibold text-primary"><?php echo DEVELOPER_TEAM; ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ข้อมูลเวอร์ชันปัจจุบัน -->
        <?php if ($current_version): ?>
        <div class="card bg-gradient-to-r from-primary to-secondary text-primary-content shadow-xl mb-6">
            <div class="card-body">
                <h2 class="card-title text-2xl mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                    </svg>
                    <?php echo $current_version['title']; ?>
                </h2>
                
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div>
                        <h3 class="text-lg font-semibold mb-3">✨ ฟีเจอร์ใหม่</h3>
                        <ul class="space-y-2">
                            <?php foreach ($current_version['features'] as $feature): ?>
                                <li class="flex items-start space-x-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mt-0.5 text-green-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                    <span class="text-sm"><?php echo $feature; ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    
                    <div>
                        <h3 class="text-lg font-semibold mb-3">🐛 การแก้ไขข้อผิดพลาด</h3>
                        <ul class="space-y-2">
                            <?php foreach ($current_version['bugfixes'] as $bugfix): ?>
                                <li class="flex items-start space-x-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mt-0.5 text-yellow-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.464 0L4.268 16.5c-.77.833.192 2.5 1.732 2.5z" />
                                    </svg>
                                    <span class="text-sm"><?php echo $bugfix; ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        
                        <h3 class="text-lg font-semibold mb-3 mt-6">⚡ การปรับปรุง</h3>
                        <ul class="space-y-2">
                            <?php foreach ($current_version['improvements'] as $improvement): ?>
                                <li class="flex items-start space-x-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mt-0.5 text-blue-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                    </svg>
                                    <span class="text-sm"><?php echo $improvement; ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- ประวัติเวอร์ชัน -->
        <div class="card bg-base-100 shadow-xl">
            <div class="card-body">
                <h2 class="card-title text-2xl mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    ประวัติการพัฒนา
                </h2>
                
                <div class="timeline">
                    <?php foreach ($version_history as $version => $info): ?>
                        <div class="timeline-item">
                            <div class="timeline-middle">
                                <div class="w-4 h-4 <?php echo $version === SYSTEM_VERSION ? 'bg-primary' : 'bg-gray-300'; ?> rounded-full"></div>
                            </div>
                            <div class="timeline-end mb-10">
                                <time class="font-mono italic"><?php echo $info['date']; ?></time>
                                <div class="text-lg font-black <?php echo $version === SYSTEM_VERSION ? 'text-primary' : ''; ?>">
                                    <?php if ($version === SYSTEM_VERSION): ?>
                                        <span class="badge badge-primary badge-sm mr-2">ปัจจุบัน</span>
                                    <?php endif; ?>
                                    เวอร์ชัน <?php echo $version; ?> - <?php echo $info['title']; ?>
                                </div>
                                
                                <?php if (!empty($info['features'])): ?>
                                    <div class="mt-3">
                                        <h4 class="font-semibold text-success">ฟีเจอร์ใหม่:</h4>
                                        <ul class="mt-2 space-y-1">
                                            <?php foreach ($info['features'] as $feature): ?>
                                                <li class="text-sm flex items-start space-x-2">
                                                    <span class="text-success">•</span>
                                                    <span><?php echo $feature; ?></span>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($info['bugfixes'])): ?>
                                    <div class="mt-3">
                                        <h4 class="font-semibold text-warning">การแก้ไข:</h4>
                                        <ul class="mt-2 space-y-1">
                                            <?php foreach ($info['bugfixes'] as $bugfix): ?>
                                                <li class="text-sm flex items-start space-x-2">
                                                    <span class="text-warning">•</span>
                                                    <span><?php echo $bugfix; ?></span>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($info['improvements'])): ?>
                                    <div class="mt-3">
                                        <h4 class="font-semibold text-info">การปรับปรุง:</h4>
                                        <ul class="mt-2 space-y-1">
                                            <?php foreach ($info['improvements'] as $improvement): ?>
                                                <li class="text-sm flex items-start space-x-2">
                                                    <span class="text-info">•</span>
                                                    <span><?php echo $improvement; ?></span>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <hr />
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <?php echo getSystemFooter(); ?>
</body>
</html>