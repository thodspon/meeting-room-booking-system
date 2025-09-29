<?php
// ไฟล์ทดสอบสำหรับ debug ปัญหา index.php ใน PHP 7.2
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', 'php_errors.log');

echo "<h1>Debug Index.php สำหรับ PHP 7.2</h1>";

try {
    echo "<h2>1. ทดสอบการโหลดไฟล์</h2>";
    
    // ทดสอบโหลด database config
    echo "โหลด config/database.php...<br>";
    if (file_exists('config/database.php')) {
        require_once 'config/database.php';
        echo "✅ โหลด database config สำเร็จ<br>";
        
        // ทดสอบการเชื่อมต่อฐานข้อมูล
        if (isset($pdo)) {
            echo "✅ เชื่อมต่อฐานข้อมูลสำเร็จ<br>";
        } else {
            echo "❌ ไม่สามารถเชื่อมต่อฐานข้อมูลได้<br>";
        }
    } else {
        echo "❌ ไม่พบไฟล์ config/database.php<br>";
    }
    
    // ทดสอบโหลด config.php
    echo "โหลด config.php...<br>";
    if (file_exists('config.php')) {
        require_once 'config.php';
        echo "✅ โหลด config.php สำเร็จ<br>";
    } else {
        echo "❌ ไม่พบไฟล์ config.php<br>";
    }
    
    // ทดสอบโหลด functions.php
    echo "โหลด includes/functions.php...<br>";
    if (file_exists('includes/functions.php')) {
        require_once 'includes/functions.php';
        echo "✅ โหลด functions.php สำเร็จ<br>";
    } else {
        echo "❌ ไม่พบไฟล์ includes/functions.php<br>";
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
    $_SESSION['username'] = 'test_user';
    $_SESSION['role'] = 'user';
    
    echo "✅ Session เริ่มต้นสำเร็จ<br>";
    echo "User ID: " . $_SESSION['user_id'] . "<br>";
    echo "Username: " . $_SESSION['username'] . "<br>";
    echo "Role: " . $_SESSION['role'] . "<br>";
    
    echo "<h2>4. ทดสอบการเรียกข้อมูลจากฐานข้อมูล</h2>";
    
    if (isset($pdo)) {
        try {
            // ทดสอบดึงข้อมูลการจองวันนี้
            $today = date('Y-m-d');
            $stmt = $pdo->prepare("
                SELECT b.*, r.room_name, u.fullname, u.department 
                FROM bookings b 
                JOIN rooms r ON b.room_id = r.room_id 
                JOIN users u ON b.user_id = u.user_id 
                WHERE DATE(b.booking_date) = ? 
                ORDER BY b.start_time
                LIMIT 5
            ");
            $stmt->execute([$today]);
            $today_bookings = $stmt->fetchAll();
            
            echo "✅ ดึงข้อมูลการจองวันนี้สำเร็จ: " . count($today_bookings) . " รายการ<br>";
            
            // ทดสอบ array_filter แบบ PHP 7.2
            $approved_bookings = array_filter($today_bookings, function($b) { 
                return $b['status'] === 'approved'; 
            });
            echo "✅ ใช้ array_filter สำเร็จ: " . count($approved_bookings) . " รายการอนุมัติแล้ว<br>";
            
        } catch (Exception $e) {
            echo "❌ ข้อผิดพลาดในการดึงข้อมูล: " . $e->getMessage() . "<br>";
        }
    }
    
    echo "<h2>5. ทดสอบ PHP Version และ Features</h2>";
    echo "PHP Version: " . phpversion() . "<br>";
    
    // ตรวจสอบ features ที่ใช้
    if (version_compare(phpversion(), '7.4.0', '>=')) {
        echo "✅ รองรับ Arrow Functions และ Null Coalescing Assignment<br>";
    } else {
        echo "⚠️ ไม่รองรับ Arrow Functions และ Null Coalescing Assignment (ต้องใช้ anonymous functions)<br>";
    }
    
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
echo "<h2>6. ข้อแนะนำการแก้ไข</h2>";
echo "<ul>";
echo "<li>ตรวจสอบให้แน่ใจว่าไฟล์ config ทั้งหมดมีอยู่และสามารถเข้าถึงได้</li>";
echo "<li>ตรวจสอบสิทธิ์ในการอ่านไฟล์ (chmod 644 สำหรับไฟล์ PHP)</li>";
echo "<li>ตรวจสอบการตั้งค่าฐานข้อมูลใน config/database.php</li>";
echo "<li>แก้ไข Arrow Functions ให้เป็น Anonymous Functions สำหรับ PHP 7.2</li>";
echo "<li>ตรวจสอบ error log ของ Apache/Nginx</li>";
echo "</ul>";
?>