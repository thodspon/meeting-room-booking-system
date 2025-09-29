<?php
/**
 * Password Reset Cleanup Script
 * Meeting Room Booking System v2.3
 * 
 * ลบข้อมูลรีเซ็ตรหัสผ่านที่หมดอายุหรือใช้แล้ว
 * รันสคริปต์นี้เป็น Cron Job ทุก 30 นาที หรือทุกชั่วโมง
 * 
 * @author นายทศพล อุทก
 * @organization โรงพยาบาลร้อยเอ็ด
 * @since 2025-09-26
 */

require_once '../config/database.php';
require_once '../includes/functions.php';

// ป้องกันการเข้าถึงผ่าน web browser (ควรรันผ่าน CLI เท่านั้น)
if (isset($_SERVER['HTTP_HOST'])) {
    die('This script should be run from command line only.');
}

try {
    echo "[" . date('Y-m-d H:i:s') . "] Starting password reset cleanup...\n";
    
    // นับจำนวนรายการที่จะลบ
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM password_resets 
        WHERE expires_at < NOW() OR used_at IS NOT NULL
    ");
    $stmt->execute();
    $count_result = $stmt->fetch();
    $records_to_delete = $count_result['count'];
    
    if ($records_to_delete > 0) {
        // ลบรายการที่หมดอายุหรือใช้แล้ว
        $stmt = $pdo->prepare("
            DELETE FROM password_resets 
            WHERE expires_at < NOW() OR used_at IS NOT NULL
        ");
        $stmt->execute();
        $deleted_rows = $stmt->rowCount();
        
        echo "[" . date('Y-m-d H:i:s') . "] Cleaned up {$deleted_rows} expired/used password reset records.\n";
        
        // Log การทำความสะอาด
        error_log("Password reset cleanup: Deleted {$deleted_rows} records");
    } else {
        echo "[" . date('Y-m-d H:i:s') . "] No records to clean up.\n";
    }
    
    // สถิติปัจจุบัน
    $stmt = $pdo->prepare("SELECT COUNT(*) as active_count FROM password_resets WHERE expires_at > NOW() AND used_at IS NULL");
    $stmt->execute();
    $active_result = $stmt->fetch();
    $active_records = $active_result['active_count'];
    
    echo "[" . date('Y-m-d H:i:s') . "] Active reset codes: {$active_records}\n";
    echo "[" . date('Y-m-d H:i:s') . "] Cleanup completed successfully.\n";
    
} catch (Exception $e) {
    echo "[" . date('Y-m-d H:i:s') . "] ERROR: " . $e->getMessage() . "\n";
    error_log("Password reset cleanup error: " . $e->getMessage());
    exit(1);
}
?>