<?php
session_start();
require_once 'config/database.php';
require_once 'config.php';
require_once 'includes/functions.php';

// Get organization config
$org_config = getOrganizationConfig();

// ตรวจสอบการ login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// ดึงการจองของผู้ใช้
$stmt = $pdo->prepare("
    SELECT b.*, r.room_name, r.location, r.capacity, a.fullname as approved_by_name
    FROM bookings b 
    JOIN rooms r ON b.room_id = r.room_id 
    LEFT JOIN users a ON b.approved_by = a.user_id
    WHERE b.user_id = ? 
    ORDER BY b.booking_date DESC, b.start_time DESC
    LIMIT 50
");
$stmt->execute([$user_id]);
$my_bookings = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="th" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>การจองของฉัน - ระบบจองห้องประชุม</title>
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
        
        .modal-box {
            font-family: 'Sarabun', 'Prompt', sans-serif;
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
                    <?= generateNavigation('my_bookings', $_SESSION['role'] ?? 'user', true) ?>
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
                <?= generateNavigation('my_bookings', $_SESSION['role'] ?? 'user', false) ?>
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
        <div class="breadcrumbs text-sm mb-4">
            <ul>
                <li><a href="index.php">หน้าหลัก</a></li>
                <li>การจองของฉัน</li>
            </ul>
        </div>

        <!-- Alert Messages -->
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>
                    <?php 
                        echo $_GET['success'] === 'cancelled' ? 'ยกเลิกการจองเรียบร้อยแล้ว' : 'ดำเนินการเรียบร้อยแล้ว';
                    ?>
                </span>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-error mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>
                    <?php 
                        $error_messages = [
                            'invalid_id' => 'รหัสการจองไม่ถูกต้อง',
                            'not_found' => 'ไม่พบการจองหรือไม่สามารถยกเลิกได้',
                            'past_booking' => 'ไม่สามารถยกเลิกการจองที่ผ่านมาแล้ว',
                            'system_error' => 'เกิดข้อผิดพลาดในระบบ กรุณาลองใหม่อีกครั้ง'
                        ];
                        echo $error_messages[$_GET['error']] ?? 'เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง';
                    ?>
                </span>
            </div>
        <?php endif; ?>

        <div class="card bg-base-100 shadow-xl">
            <div class="card-body">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="card-title">การจองของฉัน</h2>
                    <a href="booking.php" class="btn btn-primary">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                        จองห้องใหม่
                    </a>
                </div>

                <?php if (empty($my_bookings)): ?>
                    <div class="text-center py-8">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mx-auto text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <p class="text-gray-500 text-lg">ยังไม่มีการจองห้องประชุม</p>
                        <a href="booking.php" class="btn btn-primary mt-4">จองห้องประชุม</a>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>วันที่</th>
                                    <th>เวลา</th>
                                    <th>ห้องประชุม</th>
                                    <th>วัตถุประสงค์</th>
                                    <th>สถานะ</th>
                                    <th>ผู้อนุมัติ</th>
                                    <th>จัดการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($my_bookings as $booking): ?>
                                    <tr>
                                        <td>
                                            <div class="font-bold"><?php echo formatThaiDate($booking['booking_date']); ?></div>
                                            <div class="text-sm opacity-50"><?php echo formatThaiDate($booking['booking_date'], 'full'); ?></div>
                                        </td>
                                        <td>
                                            <?php echo date('H:i', strtotime($booking['start_time'])); ?> - 
                                            <?php echo date('H:i', strtotime($booking['end_time'])); ?>
                                        </td>
                                        <td>
                                            <div class="font-bold"><?php echo htmlspecialchars($booking['room_name']); ?></div>
                                            <div class="text-sm opacity-50"><?php echo htmlspecialchars($booking['location']); ?></div>
                                        </td>
                                        <td class="max-w-xs">
                                            <div class="truncate" title="<?php echo htmlspecialchars($booking['purpose']); ?>">
                                                <?php echo htmlspecialchars($booking['purpose']); ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="badge <?php 
                                                echo $booking['status'] == 'approved' ? 'badge-success' : 
                                                     ($booking['status'] == 'pending' ? 'badge-warning' : 
                                                     ($booking['status'] == 'rejected' ? 'badge-error' : 'badge-ghost')); 
                                            ?>">
                                                <?php 
                                                    echo $booking['status'] == 'approved' ? 'อนุมัติ' : 
                                                         ($booking['status'] == 'pending' ? 'รออนุมัติ' : 
                                                         ($booking['status'] == 'rejected' ? 'ไม่อนุมัติ' : 'ยกเลิก')); 
                                                ?>
                                            </div>
                                        </td>
                                        <td>
                                            <?php echo $booking['approved_by_name'] ? htmlspecialchars($booking['approved_by_name']) : '-'; ?>
                                        </td>
                                        <td>
                                            <div class="flex gap-1">
                                                <!-- ปุ่มดูรายละเอียด -->
                                                <button onclick="showBookingDetails(<?php echo $booking['booking_id']; ?>)" 
                                                        class="btn btn-info btn-xs">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                    </svg>
                                                    ดู
                                                </button>
                                                
                                                <!-- ปุ่มยกเลิก (สำหรับ pending หรือ approved ที่ยังไม่ถึงเวลา) -->
                                                <?php if ($booking['status'] == 'pending' || ($booking['status'] == 'approved' && strtotime($booking['booking_date'] . ' ' . $booking['start_time']) > time())): ?>
                                                    <button onclick="confirmCancel(<?php echo $booking['booking_id']; ?>)" 
                                                            class="btn btn-error btn-xs">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                        </svg>
                                                        ยกเลิก
                                                    </button>
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

        <!-- สถิติการจอง -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mt-6">
            <?php
            $stats = [];
            foreach ($my_bookings as $booking) {
                $stats[$booking['status']] = ($stats[$booking['status']] ?? 0) + 1;
            }
            ?>
            <div class="stat bg-base-100 shadow-xl rounded-box">
                <div class="stat-title">รวมทั้งหมด</div>
                <div class="stat-value text-primary"><?php echo count($my_bookings); ?></div>
                <div class="stat-desc">รายการ</div>
            </div>
            <div class="stat bg-base-100 shadow-xl rounded-box">
                <div class="stat-title">อนุมัติแล้ว</div>
                <div class="stat-value text-success"><?php echo $stats['approved'] ?? 0; ?></div>
                <div class="stat-desc">รายการ</div>
            </div>
            <div class="stat bg-base-100 shadow-xl rounded-box">
                <div class="stat-title">รออนุมัติ</div>
                <div class="stat-value text-warning"><?php echo $stats['pending'] ?? 0; ?></div>
                <div class="stat-desc">รายการ</div>
            </div>
            <div class="stat bg-base-100 shadow-xl rounded-box">
                <div class="stat-title">ไม่อนุมัติ</div>
                <div class="stat-value text-error"><?php echo $stats['rejected'] ?? 0; ?></div>
                <div class="stat-desc">รายการ</div>
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

    <!-- Modal สำหรับยืนยันการยกเลิก -->
    <dialog id="cancel_modal" class="modal">
        <div class="modal-box">
            <h3 class="font-bold text-lg">ยืนยันการยกเลิก</h3>
            <p class="py-4">คุณต้องการยกเลิกการจองนี้หรือไม่?</p>
            <div class="modal-action">
                <button onclick="document.getElementById('cancel_modal').close()" class="btn">ยกเลิก</button>
                <a id="confirm_cancel_link" href="#" class="btn btn-error">ยืนยันยกเลิก</a>
            </div>
        </div>
        <form method="dialog" class="modal-backdrop">
            <button>close</button>
        </form>
    </dialog>

    <script>
        // เก็บข้อมูลการจองทั้งหมดไว้ใน JavaScript
        const bookingsData = <?php echo json_encode($my_bookings); ?>;
        
        function showBookingDetails(bookingId) {
            console.log('Booking ID:', bookingId); // Debug log
            
            // หาข้อมูลการจองจาก booking ID
            const booking = bookingsData.find(b => b.booking_id == bookingId);
            
            if (!booking) {
                alert('ไม่พบข้อมูลการจอง');
                return;
            }
            
            console.log('Booking data:', booking); // Debug log
            
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
                    // Format Thai date
                    const options = { year: 'numeric', month: 'long', day: 'numeric' };
                    formattedDate = bookingDate.toLocaleDateString('th-TH', options);
                }
            } catch (e) {
                console.error('Date parsing error:', e);
            }
            
            // Format time safely
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
                        <label class="label"><span class="label-text font-semibold">ห้องประชุม</span></label>
                        <div class="text-lg font-semibold">${booking.room_name || '-'}</div>
                        <div class="text-sm opacity-70">${booking.location || ''}</div>
                        ${booking.capacity ? `<div class="text-sm opacity-70">จุได้ ${booking.capacity} คน</div>` : ''}
                    </div>
                    
                    <div class="form-control">
                        <label class="label"><span class="label-text font-semibold">ผู้อนุมัติ</span></label>
                        <div class="text-lg">${booking.approved_by_name || 'ยังไม่ได้อนุมัติ'}</div>
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
                        <label class="label"><span class="label-text font-semibold">อุปกรณ์เสริม</span></label>
                        <div class="text-lg">${booking.equipment || 'ไม่ระบุ'}</div>
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
        
        function confirmCancel(bookingId) {
            document.getElementById('confirm_cancel_link').href = 'cancel_booking.php?id=' + bookingId;
            document.getElementById('cancel_modal').showModal();
        }
    </script>
</body>
</html>