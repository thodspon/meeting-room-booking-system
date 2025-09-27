<?php
require_once 'config/database.php';
require_once 'config.php';

// ไม่ต้องเช็คการล็อกอิน เพราะเป็นหน้า public

// รับเดือนและปีจาก URL หรือใช้เดือนปัจจุบัน
$current_month = isset($_GET['month']) ? intval($_GET['month']) : date('n');
$current_year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');

// ตรวจสอบความถูกต้องของเดือนและปี
if ($current_month < 1 || $current_month > 12) {
    $current_month = date('n');
}
if ($current_year < 2020 || $current_year > 2030) {
    $current_year = date('Y');
}

// กำหนดช่วงวันที่ของเดือน
$first_day = mktime(0, 0, 0, $current_month, 1, $current_year);
$last_day = mktime(0, 0, 0, $current_month + 1, 0, $current_year);
$days_in_month = date('t', $first_day);
$start_day_of_week = date('w', $first_day); // 0 = วันอาทิตย์

// ดึงข้อมูลห้องประชุม
try {
    $stmt = $pdo->prepare("SELECT room_id, room_name, room_code, room_color FROM rooms WHERE is_active = 1 ORDER BY room_code");
    $stmt->execute();
    $rooms = $stmt->fetchAll();
} catch (PDOException $e) {
    $rooms = [];
}

// ดึงข้อมูลการจองในเดือนนี้
try {
    $start_date = date('Y-m-01', $first_day);
    $end_date = date('Y-m-t', $first_day);
    
    $stmt = $pdo->prepare("
        SELECT b.*, r.room_name, r.room_code, r.room_color, u.fullname, u.department 
        FROM bookings b 
        JOIN rooms r ON b.room_id = r.room_id 
        JOIN users u ON b.user_id = u.user_id
        WHERE b.booking_date BETWEEN ? AND ? 
        AND b.status IN ('approved', 'pending')
        ORDER BY b.booking_date, b.start_time
    ");
    $stmt->execute([$start_date, $end_date]);
    $bookings = $stmt->fetchAll();
} catch (PDOException $e) {
    $bookings = [];
}

// จัดกลุ่มการจองตามวันที่
$bookings_by_date = [];
foreach ($bookings as $booking) {
    $date = $booking['booking_date'];
    if (!isset($bookings_by_date[$date])) {
        $bookings_by_date[$date] = [];
    }
    $bookings_by_date[$date][] = $booking;
}

// ฟังก์ชันสำหรับสร้าง URL เดือนก่อนหน้าและถัดไป
function get_nav_url($month, $year) {
    return "?month={$month}&year={$year}";
}

$prev_month = $current_month - 1;
$prev_year = $current_year;
if ($prev_month < 1) {
    $prev_month = 12;
    $prev_year--;
}

$next_month = $current_month + 1;
$next_year = $current_year;
if ($next_month > 12) {
    $next_month = 1;
    $next_year++;
}

$month_names = [
    1 => 'มกราคม', 2 => 'กุมภาพันธ์', 3 => 'มีนาคม', 4 => 'เมษายน',
    5 => 'พฤษภาคม', 6 => 'มิถุนายน', 7 => 'กรกฎาคม', 8 => 'สิงหาคม',
    9 => 'กันยายน', 10 => 'ตุลาคม', 11 => 'พฤศจิกายน', 12 => 'ธันวาคม'
];

$day_names = ['อา', 'จ', 'อ', 'พ', 'พฤ', 'ศ', 'ส'];

$org_config = getOrganizationConfig();
?>

<!DOCTYPE html>
<html lang="th" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ปฏิทินการจองห้องประชุม - <?= $org_config['name'] ?></title>
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
            min-height: 100px;
            border: 1px solid #e5e7eb;
        }
        .booking-item {
            font-size: 0.7rem;
            padding: 3px 4px;
            border-radius: 6px;
            margin: 1px 0;
            overflow: hidden;
            min-height: 18px;
            display: flex;
            align-items: center;
            box-shadow: 0 1px 3px rgba(0,0,0,0.2);
            transition: all 0.2s ease;
        }
        
        .booking-item:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 6px rgba(0,0,0,0.3);
        }
        .legend-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            border-radius: 8px;
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.2);
            backdrop-filter: blur(10px);
        }
        
        .status-example {
            min-width: 60px;
            height: 20px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
            font-weight: 500;
            color: white;
            position: relative;
        }
    </style>
</head>
<body class="bg-base-200 min-h-screen">
    <!-- Header -->
    <header class="bg-primary text-primary-content shadow-lg">
        <div class="container mx-auto px-4 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <?php if (file_exists($org_config['logo_path'])): ?>
                        <img src="<?= $org_config['logo_path'] ?>" alt="Logo" class="w-12 h-12 object-contain">
                    <?php endif; ?>
                    <div>
                        <h1 class="text-2xl font-bold"><?= $org_config['name'] ?></h1>
                        <p class="text-sm opacity-90">ปฏิทินการจองห้องประชุม</p>
                    </div>
                </div>
                <div class="text-right">
                    <div id="current-time" class="text-sm font-medium text-primary mb-1">กำลังโหลดเวลา...</div>
                    <div class="text-sm opacity-90">สำหรับดูข้อมูลเท่านั้น</div>
                    <a href="login.php" class="btn btn-secondary btn-sm mt-1">เข้าสู่ระบบเพื่อจอง</a>
                </div>
            </div>
        </div>
    </header>

    <div class="container mx-auto px-4 py-6">
        <!-- Navigation -->
        <div class="card bg-base-100 shadow-xl mb-6">
            <div class="card-body">
                <div class="flex items-center justify-between">
                    <a href="<?= get_nav_url($prev_month, $prev_year) ?>" class="btn btn-outline btn-sm">
                        ← <?= $month_names[$prev_month] ?> <?= $prev_year ?>
                    </a>
                    
                    <h2 class="text-2xl font-bold text-center">
                        <?= $month_names[$current_month] ?> <?= $current_year ?>
                    </h2>
                    
                    <a href="<?= get_nav_url($next_month, $next_year) ?>" class="btn btn-outline btn-sm">
                        <?= $month_names[$next_month] ?> <?= $next_year ?> →
                    </a>
                </div>
            </div>
        </div>

        <!-- Legend -->
        <div class="card bg-base-100 shadow-xl mb-6">
            <div class="card-body">
                <h3 class="card-title text-lg mb-4">🏢 ห้องประชุม</h3>
                <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-2">
                    <?php foreach ($rooms as $room): ?>
                        <div class="legend-item">
                            <div class="w-4 h-4 rounded" style="background-color: <?= htmlspecialchars($room['room_color']) ?>"></div>
                            <span class="text-sm font-medium"><?= htmlspecialchars($room['room_code']) ?></span>
                            <span class="text-xs opacity-70"><?= htmlspecialchars($room['room_name']) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="divider"></div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3 text-sm">
                    <div class="flex items-center gap-2">
                        <div class="status-example bg-blue-500 relative">
                            <span class="text-xs">R001 09:00</span>
                            <div class="absolute -top-1 -right-1 w-3 h-3 bg-green-400 rounded-full border-2 border-white"></div>
                        </div>
                        <div>
                            <div class="font-medium">อนุมัติแล้ว</div>
                            <div class="text-xs opacity-70">วงกลมเขียว</div>
                        </div>
                    </div>
                    
                    <div class="flex items-center gap-2">
                        <div class="status-example bg-purple-500 border-2 border-dashed border-yellow-400 relative">
                            <span class="text-xs">R002 14:00</span>
                            <div class="absolute -top-1 -right-1 w-3 h-3 bg-yellow-400 rounded-full border-2 border-white animate-pulse"></div>
                        </div>
                        <div>
                            <div class="font-medium">รออนุมัติ</div>
                            <div class="text-xs opacity-70">เส้นประ + กระพริบ</div>
                        </div>
                    </div>
                    
                    <div class="flex items-center gap-2">
                        <div class="status-example bg-green-500 relative">
                            <span class="text-xs">R003 15:30</span>
                            <div class="absolute -top-1 -right-1 w-3 h-3 bg-red-500 rounded-full border-2 border-white animate-pulse"></div>
                        </div>
                        <div>
                            <div class="font-medium">กำลังใช้งาน</div>
                            <div class="text-xs opacity-70">วงกลมแดงกระพริบ</div>
                        </div>
                    </div>
                    
                    <div class="flex items-center gap-2">
                        <div class="status-example bg-gray-500 opacity-50 relative">
                            <span class="text-xs">R004 10:00</span>
                            <div class="absolute -top-1 -right-1 w-3 h-3 bg-gray-400 rounded-full border-2 border-white"></div>
                        </div>
                        <div>
                            <div class="font-medium">เสร็จสิ้น/ไม่อนุมัติ</div>
                            <div class="text-xs opacity-70">จางลง + วงกลมเทา</div>
                        </div>
                    </div>
                </div>
                
                <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div class="p-3 bg-info/20 rounded-lg border border-info/30">
                        <div class="flex items-center gap-2 text-sm">
                            <span class="text-lg">💡</span>
                            <div>
                                <div class="font-medium text-info-content">วิธีดูรายละเอียด</div>
                                <div class="text-xs opacity-80">🖱️ เว็บ: เอาเมาส์ชี้ที่การจอง</div>
                                <div class="text-xs opacity-80">📱 มือถือ: แตะที่การจอง</div>
                            </div>
                        </div>
                    </div>
                    <div class="p-3 bg-success/20 rounded-lg border border-success/30">
                        <div class="flex items-center gap-2 text-sm">
                            <span class="text-lg">📋</span>
                            <div>
                                <div class="font-medium text-success-content">ข้อมูลที่แสดง</div>
                                <div class="text-xs opacity-80">ห้อง, เวลา, ผู้จอง, แผนก, วัตถุประสงค์</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Calendar -->
        <div class="card bg-base-100 shadow-xl">
            <div class="card-body p-0">
                <!-- Calendar Header -->
                <div class="grid grid-cols-7 bg-primary text-primary-content">
                    <?php foreach ($day_names as $day): ?>
                        <div class="p-3 text-center font-medium border-r border-primary-content/20 last:border-r-0">
                            <?= $day ?>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Calendar Body -->
                <div class="grid grid-cols-7">
                    <?php
                    // เติมวันว่างก่อนวันที่ 1
                    for ($i = 0; $i < $start_day_of_week; $i++) {
                        echo '<div class="calendar-day bg-base-200"></div>';
                    }

                    // วันที่ในเดือน
                    for ($day = 1; $day <= $days_in_month; $day++) {
                        $current_date = sprintf('%04d-%02d-%02d', $current_year, $current_month, $day);
                        $is_today = ($current_date === date('Y-m-d'));
                        $is_weekend = (($start_day_of_week + $day - 1) % 7 == 0 || ($start_day_of_week + $day - 1) % 7 == 6);
                        
                        $day_class = 'calendar-day relative p-2 ';
                        $day_class .= $is_today ? 'bg-blue-50 ' : '';
                        $day_class .= $is_weekend ? 'bg-red-50 ' : 'bg-white ';
                        
                        echo "<div class=\"{$day_class}\">";
                        echo "<div class=\"text-sm font-medium mb-1 " . ($is_today ? 'text-blue-600' : '') . "\">{$day}</div>";
                        
                        // แสดงการจองในวันนี้
                        if (isset($bookings_by_date[$current_date])) {
                            foreach ($bookings_by_date[$current_date] as $booking) {
                                $color = $booking['room_color'];
                                $text_color = 'white';
                                
                                // กำหนด style ตามสถานะ
                                $booking_style = "background-color: {$color}; color: {$text_color}; position: relative;";
                                $border_style = "";
                                $opacity = "1";
                                
                                if ($booking['status'] === 'approved') {
                                    // อนุมัติแล้ว: สีห้อง + วงกลมเขียว
                                    $booking_style .= " border: none;";
                                } elseif ($booking['status'] === 'pending') {
                                    // รออนุมัติ: เส้นประรอบๆ
                                    $booking_style .= " border: 2px dashed #fbbf24; background-color: {$color}; opacity: 0.8;";
                                } else {
                                    // ไม่อนุมัติ: จางลง
                                    $booking_style .= " opacity: 0.4;";
                                }
                                
                                // สร้าง tooltip ภาษาไทย
                                $status_thai = $booking['status'] === 'approved' ? 'อนุมัติแล้ว' : 
                                              ($booking['status'] === 'pending' ? 'รออนุมัติ' : 'ไม่อนุมัติ');
                                $start_time_thai = date('H:i', strtotime($booking['start_time'])) . ' น.';
                                $end_time_thai = date('H:i', strtotime($booking['end_time'])) . ' น.';
                                
                                // เพิ่มเวลาปัจจุบันในกรณีที่เป็นวันนี้
                                $current_time_info = "";
                                if ($current_date === date('Y-m-d')) {
                                    $current_time = date('H:i');
                                    $booking_start = date('H:i', strtotime($booking['start_time']));
                                    $booking_end = date('H:i', strtotime($booking['end_time']));
                                    
                                    if ($current_time < $booking_start) {
                                        $time_diff = strtotime($booking['start_time']) - strtotime(date('H:i:s'));
                                        $hours = floor($time_diff / 3600);
                                        $minutes = floor(($time_diff % 3600) / 60);
                                        $current_time_info = "\n🕐 เริ่มใน: " . ($hours > 0 ? $hours . " ชั่วโมง " : "") . $minutes . " นาที";
                                    } elseif ($current_time >= $booking_start && $current_time <= $booking_end) {
                                        $current_time_info = "\n🔴 กำลังใช้งานอยู่";
                                    } elseif ($current_time > $booking_end) {
                                        $current_time_info = "\n✅ เสร็จสิ้นแล้ว";
                                    }
                                    $current_time_info .= "\n🕐 เวลาปัจจุบัน: " . date('H:i:s') . " น.";
                                }
                                
                                $tooltip = "🏢 ห้อง: " . $booking['room_name'] . "\n" .
                                          "⏰ เวลา: " . $start_time_thai . " - " . $end_time_thai . "\n" .
                                          "👤 ผู้จอง: " . $booking['fullname'] . "\n" .
                                          "🏛️ หน่วยงาน: " . $booking['department'] . "\n" .
                                          "📝 วัตถุประสงค์: " . mb_substr($booking['purpose'], 0, 50, 'UTF-8') . 
                                          (mb_strlen($booking['purpose'], 'UTF-8') > 50 ? '...' : '') . "\n" .
                                          "👥 จำนวนผู้เข้าร่วม: " . $booking['attendees'] . " คน\n" .
                                          "✅ สถานะ: " . $status_thai . $current_time_info;
                                
                                echo "<div class=\"booking-item cursor-pointer hover:opacity-80 transition-all\" style=\"{$booking_style}\" title=\"" . htmlspecialchars($tooltip, ENT_QUOTES, 'UTF-8') . "\">";
                                echo "<div class=\"flex items-center justify-between\">";
                                echo "<span class=\"text-xs font-medium\">" . htmlspecialchars($booking['room_code']) . " " . substr($booking['start_time'], 0, 5) . "</span>";
                                
                                // แสดงสัญลักษณ์สถานะ (รวมสถานะเวลาปัจจุบัน)
                                $status_icon_class = "w-2 h-2 rounded-full border border-white ml-1 flex-shrink-0";
                                $is_today = ($current_date === date('Y-m-d'));
                                $current_time = date('H:i');
                                $booking_start = date('H:i', strtotime($booking['start_time']));
                                $booking_end = date('H:i', strtotime($booking['end_time']));
                                
                                if ($booking['status'] === 'approved') {
                                    if ($is_today && $current_time >= $booking_start && $current_time <= $booking_end) {
                                        // กำลังใช้งานอยู่
                                        echo "<div class=\"{$status_icon_class} bg-red-500 animate-pulse\" title=\"กำลังใช้งานอยู่\"></div>";
                                    } elseif ($is_today && $current_time > $booking_end) {
                                        // เสร็จสิ้นแล้ว
                                        echo "<div class=\"{$status_icon_class} bg-gray-400\" title=\"เสร็จสิ้นแล้ว\"></div>";
                                    } else {
                                        // อนุมัติแล้ว (ยังไม่ถึงเวลา หรือไม่ใช่วันนี้)
                                        echo "<div class=\"{$status_icon_class} bg-green-400\" title=\"อนุมัติแล้ว\"></div>";
                                    }
                                } elseif ($booking['status'] === 'pending') {
                                    echo "<div class=\"{$status_icon_class} bg-yellow-400 animate-pulse\" title=\"รออนุมัติ\"></div>";
                                } else {
                                    echo "<div class=\"{$status_icon_class} bg-red-400\" title=\"ไม่อนุมัติ\"></div>";
                                }
                                
                                echo "</div>";
                                echo "</div>";
                            }
                        }
                        
                        echo "</div>";
                    }

                    // เติมวันว่างหลังวันสุดท้าย
                    $remaining_days = 42 - ($days_in_month + $start_day_of_week);
                    for ($i = 0; $i < $remaining_days && $i < 7; $i++) {
                        echo '<div class="calendar-day bg-base-200"></div>';
                    }
                    ?>
                </div>
            </div>
        </div>

        <!-- Statistics -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-6">
            <div class="stat bg-base-100 rounded-box shadow">
                <div class="stat-title">จำนวนห้องประชุม</div>
                <div class="stat-value text-primary"><?= count($rooms) ?></div>
                <div class="stat-desc">ห้องที่เปิดใช้งาน</div>
            </div>
            
            <div class="stat bg-base-100 rounded-box shadow">
                <div class="stat-title">การจองในเดือนนี้</div>
                <div class="stat-value text-secondary"><?= count($bookings) ?></div>
                <div class="stat-desc">ทั้งหมด</div>
            </div>
            
            <div class="stat bg-base-100 rounded-box shadow">
                <div class="stat-title">การจองที่อนุมัติแล้ว</div>
                <div class="stat-value text-accent"><?= count(array_filter($bookings, function($b) { return $b['status'] === 'approved'; })) ?></div>
                <div class="stat-desc">จาก <?= count($bookings) ?> การจอง</div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer footer-center p-10 bg-primary text-primary-content mt-10">
        <aside>
            <p class="font-bold text-lg"><?= $org_config['name'] ?></p>
            <p><?= $org_config['address'] ?></p>
            <p>โทร: <?= $org_config['phone'] ?> | อีเมล: <?= $org_config['email'] ?></p>
            <?php 
            $version_info = include 'version.php';
            echo "<p class='text-sm opacity-75'>เวอร์ชัน " . $version_info['version'] . " " . $version_info['edition'] . " | " . $version_info['team'] . "</p>";
            echo "<p class='text-sm opacity-75'>พัฒนาโดย " . $version_info['developer_name'] . " " . $version_info['developer_position'] . "</p>";
            ?>
        </aside>
    </footer>

    <!-- Custom Tooltip -->
    <div id="custom-tooltip" class="fixed hidden z-50 p-3 bg-base-100 rounded-lg shadow-xl border max-w-sm">
        <div id="tooltip-content" class="text-sm"></div>
    </div>

    <script>
        // Refresh every 5 minutes to show updated bookings
        setTimeout(function() {
            location.reload();
        }, 300000);
        
        // Custom tooltip functionality
        document.addEventListener('DOMContentLoaded', function() {
            const tooltip = document.getElementById('custom-tooltip');
            const tooltipContent = document.getElementById('tooltip-content');
            const bookingItems = document.querySelectorAll('.booking-item');
            
            // Function to update tooltip with current time
            function updateTooltipContent(content) {
                let formattedContent = content
                    .replace(/🏢 ห้อง: (.+)/g, '<div class="font-bold text-primary mb-1">🏢 $1</div>')
                    .replace(/⏰ เวลา: (.+)/g, '<div class="text-accent mb-1">⏰ $1</div>')
                    .replace(/👤 ผู้จอง: (.+)/g, '<div class="mb-1">👤 $1</div>')
                    .replace(/🏛️ หน่วยงาน: (.+)/g, '<div class="mb-1 text-secondary">🏛️ $1</div>')
                    .replace(/📝 วัตถุประสงค์: (.+)/g, '<div class="mb-1">📝 $1</div>')
                    .replace(/👥 จำนวนผู้เข้าร่วม: (.+)/g, '<div class="mb-1">👥 $1</div>')
                    .replace(/✅ สถานะ: (.+)/g, '<div class="font-semibold text-success mb-1">✅ $1</div>')
                    .replace(/🕐 เริ่มใน: (.+)/g, '<div class="text-warning mb-1">🕐 เริ่มใน: $1</div>')
                    .replace(/� กำลังใช้งานอยู่/g, '<div class="font-bold text-error mb-1">� กำลังใช้งานอยู่</div>')
                    .replace(/✅ เสร็จสิ้นแล้ว/g, '<div class="text-success mb-1">✅ เสร็จสิ้นแล้ว</div>')
                    .replace(/� เวลาปัจจุบัน: (.+)/g, '<div class="text-xs text-info border-t pt-1 mt-1">� เวลาปัจจุบัน: $1</div>')
                    .replace(/\n/g, '');
                
                // Update current time in real-time
                const now = new Date();
                const currentTime = now.toLocaleTimeString('th-TH', {
                    hour: '2-digit',
                    minute: '2-digit',
                    second: '2-digit',
                    timeZone: 'Asia/Bangkok'
                });
                
                formattedContent = formattedContent.replace(
                    /🕐 เวลาปัจจุบัน: \d{2}:\d{2}:\d{2} น\./g,
                    `🕐 เวลาปัจจุบัน: ${currentTime} น.`
                );
                
                return formattedContent;
            }
            
            bookingItems.forEach(item => {
                // Remove default title to prevent native tooltip
                const titleContent = item.getAttribute('title');
                item.removeAttribute('title');
                item.setAttribute('data-tooltip', titleContent);
                
                let tooltipInterval;
                
                item.addEventListener('mouseenter', function(e) {
                    const content = this.getAttribute('data-tooltip');
                    if (content) {
                        // Initial tooltip display
                        tooltipContent.innerHTML = updateTooltipContent(content);
                        tooltip.classList.remove('hidden');
                        
                        // Update tooltip every second if it contains current time
                        if (content.includes('เวลาปัจจุบัน')) {
                            tooltipInterval = setInterval(() => {
                                if (!tooltip.classList.contains('hidden')) {
                                    tooltipContent.innerHTML = updateTooltipContent(content);
                                }
                            }, 1000);
                        }
                        
                        // Scale effect
                        this.style.transform = 'scale(1.05)';
                        this.style.zIndex = '20';
                        this.style.boxShadow = '0 4px 12px rgba(0,0,0,0.3)';
                    }
                });
                
                item.addEventListener('mousemove', function(e) {
                    if (!tooltip.classList.contains('hidden')) {
                        const x = e.clientX + 10;
                        const y = e.clientY + 10;
                        
                        // Keep tooltip within viewport
                        const rect = tooltip.getBoundingClientRect();
                        const maxX = window.innerWidth - rect.width - 20;
                        const maxY = window.innerHeight - rect.height - 20;
                        
                        tooltip.style.left = Math.min(x, maxX) + 'px';
                        tooltip.style.top = Math.min(y, maxY) + 'px';
                    }
                });
                
                item.addEventListener('mouseleave', function() {
                    tooltip.classList.add('hidden');
                    this.style.transform = '';
                    this.style.zIndex = '';
                    this.style.boxShadow = '';
                    
                    // Clear interval when mouse leaves
                    if (tooltipInterval) {
                        clearInterval(tooltipInterval);
                        tooltipInterval = null;
                    }
                });
            });
            
            // Click to show booking details in mobile
            bookingItems.forEach(item => {
                item.addEventListener('click', function() {
                    const content = this.getAttribute('data-tooltip');
                    if (content && window.innerWidth <= 768) {
                        alert(content.replace(/\n/g, '\n'));
                    }
                });
            });
        });
        
        // Add current time display
        function updateCurrentTime() {
            const now = new Date();
            const options = {
                timeZone: 'Asia/Bangkok',
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                locale: 'th-TH'
            };
            
            const timeString = now.toLocaleString('th-TH', {
                timeZone: 'Asia/Bangkok',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            });
            
            const dateString = now.toLocaleDateString('th-TH', {
                timeZone: 'Asia/Bangkok',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
            
            // Update time if element exists
            const timeElement = document.getElementById('current-time');
            if (timeElement) {
                timeElement.innerHTML = `${dateString} เวลา ${timeString}`;
            }
        }
        
        // Update time every second
        setInterval(updateCurrentTime, 1000);
        updateCurrentTime();
    </script>
</body>
</html>