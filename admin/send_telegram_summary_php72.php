<?php
session_start();
require_once '../config/database.php';
require_once '../config.php';
require_once '../includes/functions.php';

// ตรวจสอบสิทธิ์ admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'ไม่มีสิทธิ์เข้าถึง']);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

try {
    $org_config = getOrganizationConfig();
    
    // ตรวจสอบ POST data
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Method not allowed');
    }
    
    // รับข้อมูลจากฟอร์ม
    $start_date = $_POST['start_date'] ?? date('Y-m-d');
    $end_date = $_POST['end_date'] ?? date('Y-m-d');
    $report_type = $_POST['report_type'] ?? 'summary';
    $recipient = $_POST['recipient'] ?? 'all';
    $selected_users = $_POST['selected_users'] ?? [];
    $quick_type = $_POST['quick_type'] ?? null;
    
    // สำหรับ quick actions
    if ($quick_type) {
        switch ($quick_type) {
            case 'today':
                $start_date = $end_date = date('Y-m-d');
                $report_type = 'summary';
                $recipient = 'all';
                break;
            case 'pending':
                $start_date = $end_date = date('Y-m-d');
                $report_type = 'pending_only';
                $recipient = 'managers';
                break;
        }
    }
    
    // ตรวจสอบวันที่
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $start_date) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $end_date)) {
        throw new Exception('รูปแบบวันที่ไม่ถูกต้อง');
    }
    
    // ตรวจสอบว่า start_date <= end_date
    if ($start_date > $end_date) {
        throw new Exception('วันที่เริ่มต้นต้องไม่เกินวันที่สิ้นสุด');
    }
    
    // ตรวจสอบช่วงเวลาไม่เกิน 30 วัน
    $date_diff = (strtotime($end_date) - strtotime($start_date)) / (60 * 60 * 24);
    if ($date_diff > 30) {
        throw new Exception('ช่วงเวลาไม่ควรเกิน 30 วัน');
    }
    
    // ดึงข้อมูลการจองในช่วงวันที่ที่เลือก
    $stmt = $pdo->prepare("
        SELECT b.*, r.room_name, r.room_code, u.fullname, u.department, u.position,
               a.fullname as approved_by_name
        FROM bookings b 
        JOIN rooms r ON b.room_id = r.room_id 
        JOIN users u ON b.user_id = u.user_id 
        LEFT JOIN users a ON b.approved_by = a.user_id
        WHERE b.booking_date BETWEEN ? AND ? 
        ORDER BY b.booking_date, b.start_time, r.room_name
    ");
    $stmt->execute([$start_date, $end_date]);
    $bookings = $stmt->fetchAll();
    
    // แยกข้อมูลตามสถานะ
    $approved_bookings = array_filter($bookings, function($b) { return $b['status'] === 'approved'; });
    $pending_bookings = array_filter($bookings, function($b) { return $b['status'] === 'pending'; });
    $rejected_bookings = array_filter($bookings, function($b) { return $b['status'] === 'rejected'; });
    $cancelled_bookings = array_filter($bookings, function($b) { return $b['status'] === 'cancelled'; });
    
    // สร้างข้อความ
    $message = generateTelegramMessage($org_config, $start_date, $end_date, $bookings, $approved_bookings, $pending_bookings, $rejected_bookings, $cancelled_bookings, $report_type);
    
    // ดึงรายชื่อผู้รับ
    $recipients_list = getRecipientsList($pdo, $recipient, $selected_users);
    
    if (empty($recipients_list)) {
        throw new Exception('ไม่พบผู้รับข้อความ');
    }
    
    // ส่งข้อความไปยังผู้รับแต่ละคน
    $success_count = 0;
    $total_count = count($recipients_list);
    $errors = [];
    
    foreach ($recipients_list as $user) {
        try {
            // ส่งข้อความ (ใช้การตั้งค่า Telegram เริ่มต้น)
            $result = sendTelegramMessage($message);
            
            if ($result['ok'] ?? false) {
                $success_count++;
                
                // บันทึก log การส่ง
                $date_range = $start_date === $end_date ? $start_date : "{$start_date} ถึง {$end_date}";
                logActivity($pdo, $_SESSION['user_id'], 'telegram_sent', 
                    "ส่งสรุปการจอง {$date_range} ถึง {$user['fullname']} ({$user['username']})");
            } else {
                $errors[] = "ส่งไม่สำเร็จ: {$user['fullname']} - " . ($result['description'] ?? 'Unknown error');
            }
        } catch (Exception $e) {
            $errors[] = "ข้อผิดพลาด: {$user['fullname']} - " . $e->getMessage();
        }
        
        // หน่วงเวลาเล็กน้อยระหว่างการส่ง
        usleep(500000); // 0.5 วินาที
    }
    
    // ส่งผลลัพธ์
    if ($success_count > 0) {
        echo json_encode([
            'success' => true,
            'message' => "ส่งข้อความสำเร็จ {$success_count} จาก {$total_count} คน",
            'recipients' => $success_count,
            'total' => $total_count,
            'errors' => $errors
        ]);
    } else {
        throw new Exception('ไม่สามารถส่งข้อความได้เลย: ' . implode(', ', $errors));
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

/**
 * สร้างข้อความ Telegram
 */
function generateTelegramMessage($org_config, $start_date, $end_date, $all_bookings, $approved, $pending, $rejected, $cancelled, $type) {
    $message = "📊 สรุปการจองห้องประชุม\n";
    $message .= "🏢 {$org_config['name']}\n";
    
    if ($start_date === $end_date) {
        $thai_date = formatThaiDate($start_date, 'full');
        $message .= "� {$thai_date}\n\n";
    } else {
        $thai_start = formatThaiDate($start_date, 'full');
        $thai_end = formatThaiDate($end_date, 'full');
        $message .= "📅 ช่วงวันที่: {$thai_start} ถึง {$thai_end}\n\n";
    }
    
    // สถิติรวม
    $message .= "📈 สถิติ:\n";
    $message .= "✅ อนุมัติแล้ว: " . count($approved) . " รายการ\n";
    $message .= "⏳ รออนุมัติ: " . count($pending) . " รายการ\n";
    $message .= "❌ ไม่อนุมัติ: " . count($rejected) . " รายการ\n";
    $message .= "🚫 ยกเลิก: " . count($cancelled) . " รายการ\n";
    $message .= "🔢 รวมทั้งหมด: " . count($all_bookings) . " รายการ\n\n";
    
    switch ($type) {
        case 'detailed':
            $message .= generateDetailedReport($all_bookings);
            break;
        case 'pending_only':
            $message .= generatePendingReport($pending);
            break;
        case 'approved_only':
            $message .= generateApprovedReport($approved);
            break;
        default: // summary
            $message .= generateSummaryReport($approved, $pending);
            break;
    }
    
    $message .= "\n⏰ ส่งเมื่อ: " . date('d/m/Y H:i:s') . " น.\n";
    $message .= "👤 ส่งโดย: " . ($_SESSION['username'] ?? 'System');
    
    return $message;
}

/**
 * สร้างรายงานแบบรายละเอียด
 */
function generateDetailedReport($bookings) {
    if (empty($bookings)) {
        return "📋 ไม่มีการจองในช่วงเวลาที่เลือก\n";
    }
    
    $message = "📋 รายละเอียดการจอง:\n\n";
    
    // จัดกลุ่มตามวันที่
    $bookings_by_date = [];
    foreach ($bookings as $booking) {
        $date = $booking['booking_date'];
        if (!isset($bookings_by_date[$date])) {
            $bookings_by_date[$date] = [];
        }
        $bookings_by_date[$date][] = $booking;
    }
    
    $total_count = 0;
    foreach ($bookings_by_date as $date => $day_bookings) {
        $thai_date = formatThaiDate($date, 'full');
        $message .= "📅 {$thai_date} (" . count($day_bookings) . " รายการ):\n";
        
        foreach ($day_bookings as $i => $booking) {
            $total_count++;
            $status_icon = [
                'approved' => '✅',
                'pending' => '⏳',
                'rejected' => '❌',
                'cancelled' => '🚫'
            ][$booking['status']] ?? '❓';
            
            $message .= "   {$status_icon} {$booking['room_name']}\n";
            $message .= "      🕐 " . date('H:i', strtotime($booking['start_time'])) . 
                       " - " . date('H:i', strtotime($booking['end_time'])) . "\n";
            $message .= "      👤 {$booking['fullname']}\n";
            $message .= "      🏛️ {$booking['department']}\n";
            
            if (!empty($booking['purpose'])) {
                $purpose = mb_substr($booking['purpose'], 0, 50, 'UTF-8');
                if (mb_strlen($booking['purpose'], 'UTF-8') > 50) $purpose .= '...';
                $message .= "      📝 {$purpose}\n";
            }
            
            $message .= "\n";
        }
        $message .= "\n";
    }

    if ($total_count > 0) {
        $message .= "📈 รวมทั้งหมด: {$total_count} รายการ\n";
    }
    
    return $message;
}

/**
 * สร้างรายงานเฉพาะรออนุมัติ
 */
function generatePendingReport($pending) {
    if (empty($pending)) {
        return "🎉 ไม่มีการจองที่รออนุมัติ\n";
    }
    
    $message = "⏳ รายการรออนุมัติ (" . count($pending) . " รายการ):\n\n";
    
    foreach ($pending as $i => $booking) {
        $message .= ($i + 1) . ". 🏠 {$booking['room_name']}\n";
        $message .= "   🕐 " . date('H:i', strtotime($booking['start_time'])) . 
                   " - " . date('H:i', strtotime($booking['end_time'])) . "\n";
        $message .= "   👤 {$booking['fullname']} ({$booking['department']})\n";
        
        if (!empty($booking['purpose'])) {
            $purpose = mb_substr($booking['purpose'], 0, 40, 'UTF-8');
            if (mb_strlen($booking['purpose'], 'UTF-8') > 40) $purpose .= '...';
            $message .= "   📝 {$purpose}\n";
        }
        
        $message .= "\n";
    }
    
    $message .= "⚠️ กรุณาเข้าระบบเพื่ออนุมัติการจอง\n";
    
    return $message;
}

/**
 * สร้างรายงานเฉพาะอนุมัติแล้ว
 */
function generateApprovedReport($approved) {
    if (empty($approved)) {
        return "📋 ไม่มีการจองที่อนุมัติแล้ว\n";
    }
    
    $message = "✅ รายการอนุมัติแล้ว (" . count($approved) . " รายการ):\n\n";
    
    // จัดกลุ่มตามห้อง
    $rooms = [];
    foreach ($approved as $booking) {
        $rooms[$booking['room_name']][] = $booking;
    }
    
    foreach ($rooms as $room_name => $room_bookings) {
        $message .= "🏠 {$room_name}:\n";
        
        foreach ($room_bookings as $booking) {
            $message .= "   • " . date('H:i', strtotime($booking['start_time'])) . 
                       "-" . date('H:i', strtotime($booking['end_time'])) . 
                       " | {$booking['fullname']}\n";
        }
        $message .= "\n";
    }
    
    return $message;
}

/**
 * สร้างรายงานแบบสรุป
 */
function generateSummaryReport($approved, $pending) {
    $message = "";
    
    if (!empty($approved)) {
        $message .= "✅ การจองที่อนุมัติแล้ว:\n";
        foreach ($approved as $booking) {
            $message .= "• {$booking['room_name']} | " . 
                       date('H:i', strtotime($booking['start_time'])) . 
                       "-" . date('H:i', strtotime($booking['end_time'])) . 
                       " | {$booking['fullname']}\n";
        }
        $message .= "\n";
    }
    
    if (!empty($pending)) {
        $message .= "⏳ รออนุมัติ:\n";
        foreach ($pending as $booking) {
            $message .= "• {$booking['room_name']} | " . 
                       date('H:i', strtotime($booking['start_time'])) . 
                       "-" . date('H:i', strtotime($booking['end_time'])) . 
                       " | {$booking['fullname']}\n";
        }
        $message .= "\n";
    }
    
    if (empty($approved) && empty($pending)) {
        $message .= "📋 ไม่มีการจองในวันที่เลือก\n";
    }
    
    return $message;
}

/**
 * ดึงรายชื่อผู้รับ
 */
function getRecipientsList($pdo, $recipient, $selected_users = []) {
    switch ($recipient) {
        case 'all':
            $stmt = $pdo->prepare("SELECT user_id, username, fullname, role FROM users WHERE is_active = 1");
            $stmt->execute();
            break;
        case 'admins':
            $stmt = $pdo->prepare("SELECT user_id, username, fullname, role FROM users WHERE is_active = 1 AND role = 'admin'");
            $stmt->execute();
            break;
        case 'managers':
            $stmt = $pdo->prepare("SELECT user_id, username, fullname, role FROM users WHERE is_active = 1 AND role IN ('admin', 'manager')");
            $stmt->execute();
            break;
        case 'custom':
            if (empty($selected_users)) {
                return [];
            }
            $placeholders = str_repeat('?,', count($selected_users) - 1) . '?';
            $stmt = $pdo->prepare("SELECT user_id, username, fullname, role FROM users WHERE is_active = 1 AND user_id IN ({$placeholders})");
            $stmt->execute($selected_users);
            break;
        default:
            return [];
    }
    
    return $stmt->fetchAll();
}
?>