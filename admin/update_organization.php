<?php
/**
 * สคริปต์อัพเดตชื่อองค์กรในฐานข้อมูล
 * ใช้สำหรับอัพเดตชื่อองค์กรจากค่าคงที่เป็นแบบไดนามิก
 */

require_once '../config.php';
require_once '../config/database.php';

// ดึงข้อมูลองค์กรจากการตั้งค่า
$org_config = getOrganizationConfig();

try {
    // ตรวจสอบว่ามีตาราง settings หรือไม่
    $tables = $pdo->query("SHOW TABLES LIKE 'settings'")->fetchAll();
    
    if (count($tables) > 0) {
        // อัพเดต site_name ในตาราง settings (ถ้ามี)
        $stmt = $pdo->prepare("UPDATE settings SET value = ? WHERE `key` = 'site_name'");
        $site_name = $org_config['sub_title'] . ' ' . $org_config['name'];
        $stmt->execute([$site_name]);
        
        if ($stmt->rowCount() > 0) {
            echo "✅ อัพเดตชื่อเว็บไซต์สำเร็จ: " . $site_name . "\n";
        } else {
            echo "ℹ️  ไม่พบการตั้งค่า site_name ในฐานข้อมูล\n";
        }
    } else {
        echo "ℹ️  ไม่พบตาราง settings ในฐานข้อมูล\n";
    }
    
    // แสดงข้อมูลองค์กรปัจจุบัน
    echo "\n📋 ข้อมูลองค์กรปัจจุบัน:\n";
    echo "   ชื่อ: " . $org_config['name'] . "\n";
    echo "   ชื่อ EN: " . $org_config['name_english'] . "\n";
    echo "   ที่อยู่: " . $org_config['address'] . "\n";
    echo "   โทร: " . $org_config['phone'] . "\n";
    echo "   อีเมล: " . $org_config['email'] . "\n";
    echo "   เว็บไซต์: " . $org_config['website'] . "\n";
    echo "   หัวข้อ: " . $org_config['header_title'] . "\n";
    echo "   คำบรรยาย: " . $org_config['sub_title'] . "\n";
    
    echo "\n🎉 การอัพเดตเสร็จสมบูรณ์!\n";
    echo "💡 ตอนนี้ระบบจะใช้ชื่อองค์กรจากการตั้งค่าใน config.php แทนค่าคงที่\n";
    
} catch (Exception $e) {
    echo "❌ เกิดข้อผิดพลาด: " . $e->getMessage() . "\n";
}
?>