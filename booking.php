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
$error = '';
$success = '';

// ประมวลผลการจอง
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $room_id = intval($_POST['room_id']);
        $booking_date = $_POST['booking_date'];
        $start_time = $_POST['start_time'];
        $end_time = $_POST['end_time'];
        $purpose = trim($_POST['purpose']);
        $attendees = intval($_POST['attendees']);

        // Validation
        if (empty($room_id) || empty($booking_date) || empty($start_time) || empty($end_time)) {
            throw new Exception('กรุณากรอกข้อมูลให้ครบถ้วน');
        }

        if ($booking_date < date('Y-m-d')) {
            throw new Exception('ไม่สามารถจองย้อนหลังได้');
        }

        if ($start_time >= $end_time) {
            throw new Exception('เวลาเริ่มต้นต้องน้อยกว่าเวลาสิ้นสุด');
        }

        // ตรวจสอบการซ้ำซ้อน
        if (checkBookingConflict($pdo, $room_id, $booking_date, $start_time, $end_time)) {
            throw new Exception('มีการจองในช่วงเวลานี้แล้ว');
        }

        // เพิ่มการจอง
        $stmt = $pdo->prepare("
            INSERT INTO bookings (room_id, user_id, booking_date, start_time, end_time, purpose, attendees, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')
        ");
        $stmt->execute([$room_id, $user_id, $booking_date, $start_time, $end_time, $purpose, $attendees]);
        $booking_id = $pdo->lastInsertId();

        // ดึงข้อมูลสำหรับส่งการแจ้งเตือน
        $stmt = $pdo->prepare("
            SELECT b.*, r.room_name, u.fullname, u.department 
            FROM bookings b 
            JOIN rooms r ON b.room_id = r.room_id 
            JOIN users u ON b.user_id = u.user_id 
            WHERE b.booking_id = ?
        ");
        $stmt->execute([$booking_id]);
        $booking_data = $stmt->fetch();

        // ส่งการแจ้งเตือน
        sendBookingNotification($booking_data, 'new');

        // Log activity
        logActivity($pdo, $user_id, 'create_booking', "Created booking ID: {$booking_id} for room: {$booking_data['room_name']}");

        $success = 'จองห้องประชุมเรียบร้อยแล้ว รอการอนุมัติ';
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// ดึงข้อมูลห้องประชุม
$stmt = $pdo->prepare("SELECT * FROM rooms WHERE is_active = 1 ORDER BY room_name");
$stmt->execute();
$rooms = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="th" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จองห้องประชุม - ระบบจองห้องประชุม</title>
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
                    <?= generateNavigation('booking', $_SESSION['role'] ?? 'user', true) ?>
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
                <?= generateNavigation('booking', $_SESSION['role'] ?? 'user', false) ?>
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
                <li>จองห้องประชุม</li>
            </ul>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- ฟอร์มจองห้องประชุม -->
            <div class="card bg-base-100 shadow-xl">
                <div class="card-body">
                    <h2 class="card-title">จองห้องประชุม</h2>
                    
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

                    <form method="POST" id="bookingForm">
                        <div class="form-control">
                            <label class="label">
                                <span class="label-text">ห้องประชุม</span>
                            </label>
                            <select name="room_id" class="select select-bordered" required id="roomSelect">
                                <option value="">เลือกห้องประชุม</option>
                                <?php foreach ($rooms as $room): ?>
                                    <option value="<?php echo $room['room_id']; ?>" 
                                            data-capacity="<?php echo $room['capacity']; ?>"
                                            data-location="<?php echo htmlspecialchars($room['location']); ?>">
                                        <?php echo htmlspecialchars($room['room_name']); ?> (<?php echo $room['capacity']; ?> ที่นั่ง)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div id="roomInfo" class="alert alert-info mt-2 hidden">
                            <div>
                                <div class="font-bold">ข้อมูลห้องประชุม</div>
                                <div id="roomDetails"></div>
                            </div>
                        </div>

                        <div class="form-control">
                            <label class="label">
                                <span class="label-text">วันที่</span>
                            </label>
                            <input type="date" name="booking_date" class="input input-bordered" required 
                                   min="<?php echo date('Y-m-d'); ?>" 
                                   max="<?php echo date('Y-m-d', strtotime('+30 days')); ?>"
                                   id="bookingDate">
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div class="form-control">
                                <label class="label">
                                    <span class="label-text">เวลาเริ่ม</span>
                                </label>
                                <input type="time" name="start_time" class="input input-bordered" required 
                                       min="08:00" max="17:00" id="startTime">
                            </div>
                            <div class="form-control">
                                <label class="label">
                                    <span class="label-text">เวลาสิ้นสุด</span>
                                </label>
                                <input type="time" name="end_time" class="input input-bordered" required 
                                       min="08:00" max="17:00" id="endTime">
                            </div>
                        </div>

                        <div class="form-control">
                            <label class="label">
                                <span class="label-text">จำนวนผู้เข้าร่วม</span>
                            </label>
                            <input type="number" name="attendees" class="input input-bordered" required 
                                   min="1" max="100" value="1" id="attendees">
                        </div>

                        <div class="form-control">
                            <label class="label">
                                <span class="label-text">วัตถุประสงค์</span>
                            </label>
                            <textarea name="purpose" class="textarea textarea-bordered" rows="3" 
                                      placeholder="กรอกวัตถุประสงค์การจอง" required></textarea>
                        </div>

                        <div class="form-control mt-6">
                            <button type="submit" class="btn btn-primary">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                จองห้องประชุม
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- ตารางการจองวันนี้ -->
            <div class="card bg-base-100 shadow-xl">
                <div class="card-body">
                    <h3 class="card-title">การจองในวันที่เลือก</h3>
                    <div id="todayBookings">
                        <div class="text-center py-8">
                            <p class="text-gray-500">เลือกวันที่เพื่อดูการจอง</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ข้อมูลห้องประชุม -->
        <div class="card bg-base-100 shadow-xl mt-6">
            <div class="card-body">
                <h3 class="card-title">ข้อมูลห้องประชุม</h3>
                <div class="overflow-x-auto">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ชื่อห้อง</th>
                                <th>รหัสห้อง</th>
                                <th>ความจุ</th>
                                <th>สถานที่</th>
                                <th>อุปกรณ์</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rooms as $room): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($room['room_name']); ?></td>
                                    <td><?php echo htmlspecialchars($room['room_code']); ?></td>
                                    <td><?php echo $room['capacity']; ?> ที่นั่ง</td>
                                    <td><?php echo htmlspecialchars($room['location']); ?></td>
                                    <td><?php echo htmlspecialchars($room['equipment']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Show room information
        document.getElementById('roomSelect').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const roomInfo = document.getElementById('roomInfo');
            const roomDetails = document.getElementById('roomDetails');
            
            if (selectedOption.value) {
                const capacity = selectedOption.getAttribute('data-capacity');
                const location = selectedOption.getAttribute('data-location');
                
                roomDetails.innerHTML = `
                    <div>จำนวนที่นั่ง: ${capacity} ที่นั่ง</div>
                    <div>สถานที่: ${location}</div>
                `;
                roomInfo.classList.remove('hidden');
                
                // Set max attendees
                document.getElementById('attendees').max = capacity;
            } else {
                roomInfo.classList.add('hidden');
            }
        });

        // Load bookings for selected date
        document.getElementById('bookingDate').addEventListener('change', function() {
            const selectedDate = this.value;
            const roomId = document.getElementById('roomSelect').value;
            
            if (selectedDate) {
                fetch(`check_availability.php?date=${selectedDate}&room_id=${roomId}`)
                    .then(response => response.json())
                    .then(data => {
                        const container = document.getElementById('todayBookings');
                        
                        if (data.bookings && data.bookings.length > 0) {
                            let html = '<div class="overflow-x-auto"><table class="table table-sm"><thead><tr><th>เวลา</th><th>ห้อง</th><th>ผู้จอง</th><th>สถานะ</th></tr></thead><tbody>';
                            
                            data.bookings.forEach(booking => {
                                const statusClass = booking.status === 'approved' ? 'badge-success' : 
                                                  (booking.status === 'pending' ? 'badge-warning' : 'badge-error');
                                const statusText = booking.status === 'approved' ? 'อนุมัติ' : 
                                                  (booking.status === 'pending' ? 'รออนุมัติ' : 'ไม่อนุมัติ');
                                
                                html += `<tr>
                                    <td>${booking.start_time.substring(0,5)}-${booking.end_time.substring(0,5)}</td>
                                    <td>${booking.room_name}</td>
                                    <td>${booking.fullname}</td>
                                    <td><span class="badge ${statusClass}">${statusText}</span></td>
                                </tr>`;
                            });
                            
                            html += '</tbody></table></div>';
                            container.innerHTML = html;
                        } else {
                            container.innerHTML = '<div class="text-center py-8"><p class="text-gray-500">ไม่มีการจองในวันนี้</p></div>';
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                    });
            }
        });

        // Validate time
        document.getElementById('startTime').addEventListener('change', function() {
            const startTime = this.value;
            const endTimeInput = document.getElementById('endTime');
            
            if (startTime) {
                // Set minimum end time to 1 hour after start time
                const start = new Date(`2000-01-01 ${startTime}`);
                start.setHours(start.getHours() + 1);
                const minEndTime = start.toTimeString().substring(0, 5);
                endTimeInput.min = minEndTime;
                
                if (endTimeInput.value && endTimeInput.value <= startTime) {
                    endTimeInput.value = minEndTime;
                }
            }
        });

        // Auto-set default date to tomorrow
        document.addEventListener('DOMContentLoaded', function() {
            const tomorrow = new Date();
            tomorrow.setDate(tomorrow.getDate() + 1);
            document.getElementById('bookingDate').value = tomorrow.toISOString().split('T')[0];
        });
    </script>

    <!-- Footer -->
    <?php require_once 'version.php'; echo getSystemFooter(); ?>
</body>
</html>