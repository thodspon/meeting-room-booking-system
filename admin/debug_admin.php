<?php
// ไฟล์ทดสอบสำหรับ admin files ใน PHP 7.2
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', 'php_errors.log');

echo "<h1>Debug Admin Files สำหรับ PHP 7.2</h1>";

try {
    echo "<h2>1. ทดสอบการโหลดไฟล์</h2>";
    
    // เปลี่ยน working directory ไปที่ admin folder
    if (!chdir('../admin')) {
        die('ไม่สามารถเปลี่ยน directory ไปที่ admin ได้');
    }
    
    // ทดสอบโหลด config files
    echo "โหลด ../config/database.php...<br>";
    if (file_exists('../config/database.php')) {
        require_once '../config/database.php';
        echo "✅ โหลด database config สำเร็จ<br>";
    } else {
        echo "❌ ไม่พบไฟล์ ../config/database.php<br>";
    }
    
    echo "โหลด ../config.php...<br>";
    if (file_exists('../config.php')) {
        require_once '../config.php';
        echo "✅ โหลด config.php สำเร็จ<br>";
    } else {
        echo "❌ ไม่พบไฟล์ ../config.php<br>";
    }
    
    echo "โหลด ../includes/functions.php...<br>";
    if (file_exists('../includes/functions.php')) {
        require_once '../includes/functions.php';
        echo "✅ โหลด functions.php สำเร็จ<br>";
    } else {
        echo "❌ ไม่พบไฟล์ ../includes/functions.php<br>";
    }
    
    echo "<h2>2. ทดสอบฟังก์ชัน</h2>";
    
    // ทดสอบ getOrganizationConfig
    if (function_exists('getOrganizationConfig')) {
        echo "✅ ฟังก์ชัน getOrganizationConfig มีอยู่<br>";
        $org_config = getOrganizationConfig();
        echo "Organization Name: " . ($org_config['name'] ?? 'ไม่พบ') . "<br>";
    } else {
        echo "❌ ฟังก์ชัน getOrganizationConfig ไม่พบ<br>";
    }
    
    // ทดสอบ checkPermission
    if (function_exists('checkPermission')) {
        echo "✅ ฟังก์ชัน checkPermission มีอยู่<br>";
    } else {
        echo "❌ ฟังก์ชัน checkPermission ไม่พบ<br>";
    }
    
    // ทดสอบ generateNavigation
    if (function_exists('generateNavigation')) {
        echo "✅ ฟังก์ชัน generateNavigation มีอยู่<br>";
    } else {
        echo "❌ ฟังก์ชัน generateNavigation ไม่พบ<br>";
    }
    
    echo "<h2>3. ทดสอบ Session</h2>";
    session_start();
    
    // สร้าง mock session สำหรับทดสอบ
    $_SESSION['user_id'] = 1;
    $_SESSION['username'] = 'admin_test';
    $_SESSION['role'] = 'admin';
    
    echo "✅ Session เริ่มต้นสำเร็จ<br>";
    echo "User ID: " . $_SESSION['user_id'] . "<br>";
    echo "Username: " . $_SESSION['username'] . "<br>";
    echo "Role: " . $_SESSION['role'] . "<br>";
    
    echo "<h2>4. ทดสอบการเชื่อมต่อฐานข้อมูล</h2>";
    
    if (isset($pdo)) {
        try {
            // ทดสอบการ query ฐานข้อมูล
            $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM users");
            $stmt->execute();
            $result = $stmt->fetch();
            
            echo "✅ เชื่อมต่อฐานข้อมูลสำเร็จ<br>";
            echo "จำนวนผู้ใช้ทั้งหมด: " . $result['total'] . " คน<br>";
            
            // ทดสอบการ query รายงาน
            $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM bookings WHERE booking_date >= CURDATE()");
            $stmt->execute();
            $booking_result = $stmt->fetch();
            
            echo "การจองจากวันนี้เป็นต้นไป: " . $booking_result['total'] . " รายการ<br>";
            
        } catch (Exception $e) {
            echo "❌ ข้อผิดพลาดในการ query: " . $e->getMessage() . "<br>";
        }
    } else {
        echo "❌ ไม่สามารถเชื่อมต่อฐานข้อมูลได้<br>";
    }
    
    echo "<h2>5. ทดสอบไฟล์ Admin</h2>";
    
    // ตรวจสอบไฟล์ที่จำเป็น
    $admin_files = [
        'reports.php' => 'รายงานการจอง',
        'users.php' => 'จัดการผู้ใช้',  
        'user_activity.php' => 'รายงานกิจกรรม',
        'rooms.php' => 'จัดการห้องประชุม',
        'room_bookings.php' => 'จัดการการจอง',
        'telegram_settings.php' => 'ตั้งค่า Telegram',
        'send_telegram_summary.php' => 'ส่งสรุป Telegram'
    ];
    
    foreach ($admin_files as $file => $description) {
        if (file_exists($file)) {
            echo "✅ {$file} ({$description}) - พบไฟล์<br>";
            
            // ตรวจสอบ syntax โดยใช้ php -l
            $syntax_check = shell_exec("php -l {$file} 2>&1");
            if (strpos($syntax_check, 'No syntax errors') !== false) {
                echo "&nbsp;&nbsp;&nbsp;✅ Syntax ถูกต้อง<br>";
            } else {
                echo "&nbsp;&nbsp;&nbsp;❌ Syntax Error: " . htmlspecialchars($syntax_check) . "<br>";
            }
        } else {
            echo "❌ {$file} ({$description}) - ไม่พบไฟล์<br>";
        }
    }
    
    echo "<h2>6. ทดสอบ PHP 7.2 Compatibility</h2>";
    echo "PHP Version: " . phpversion() . "<br>";
    
    // ทดสอบ array_filter กับ anonymous functions
    $test_array = [
        ['status' => 'approved'],
        ['status' => 'pending'],  
        ['status' => 'approved']
    ];
    
    $approved = array_filter($test_array, function($item) {
        return $item['status'] === 'approved';
    });
    
    echo "✅ Anonymous Functions ทำงานได้: " . count($approved) . " รายการ approved<br>";
    
    // ทดสอบ ternary operator แทน null coalescing
    $test_var = isset($_GET['test']) ? $_GET['test'] : 'default_value';
    echo "✅ Ternary Operator ทำงานได้: " . $test_var . "<br>";
    
} catch (Exception $e) {
    echo "<h2>❌ เกิดข้อผิดพลาด</h2>";
    echo "Error: " . $e->getMessage() . "<br>";
    echo "File: " . $e->getFile() . "<br>";
    echo "Line: " . $e->getLine() . "<br>";
} catch (Error $e) {
    echo "<h2>❌ เกิด Fatal Error</h2>";
    echo "Error: " . $e->getMessage() . "<br>";
    echo "File: " . $e->getFile() . "<br>";
    echo "Line: " . $e->getLine() . "<br>";
}

echo "<hr>";
echo "<h2>7. การทดสอบแต่ละไฟล์</h2>";
echo "<ul>";
echo "<li><a href='reports.php' target='_blank'>ทดสอบ Reports</a></li>";
echo "<li><a href='user_activity.php' target='_blank'>ทดสอบ User Activity</a></li>";
echo "<li><a href='users.php' target='_blank'>ทดสอบ Users Management</a></li>";
echo "<li><a href='rooms.php' target='_blank'>ทดสอบ Rooms Management</a></li>";
echo "</ul>";

echo "<h2>8. ข้อแนะนำการแก้ไข</h2>";
echo "<ul>";
echo "<li>หาก reports.php ไม่ทำงาน: ตรวจสอบฟังก์ชัน getSystemFooter() และ vendor/autoload.php</li>";
echo "<li>หาก user_activity.php ไม่ทำงาน: ตรวจสอบการ query ฐานข้อมูลและ permissions</li>";
echo "<li>หาก Excel export ไม่ทำงาน: ตรวจสอบ PhpSpreadsheet library</li>";
echo "<li>หาก PDF export ไม่ทำงาน: ใช้ HTML-to-PDF แทน TCPDF</li>";
echo "<li>ตรวจสอบ error log: tail -f /var/log/php_errors.log</li>";
echo "</ul>";
?>