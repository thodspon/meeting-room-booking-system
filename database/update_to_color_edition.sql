-- สคริปต์อัพเดทระบบสำหรับเวอร์ชัน 2.2 Color Edition
-- ระบบจองห้องประชุม - ศูนย์ส่งเสริมสุขภาพสวนพยอม
-- พัฒนาโดย นายทศพล อุทก นักวิชาการคอมพิวเตอร์ชำนาญการ โรงพยาบาลร้อยเอ็ด
-- ทีมพัฒนา: Roi-et Digital Health Team

USE meeting_room_db;

-- =====================================================
-- การอัพเดทโครงสร้างฐานข้อมูลสำหรับระบบสีห้อง
-- =====================================================

-- 1. เพิ่มคอลัมน์ room_color (ถ้ายังไม่มี)
SET @sql = (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE rooms ADD COLUMN room_color VARCHAR(7) DEFAULT ''#3b82f6'' AFTER equipment;',
        'SELECT ''Column room_color already exists'';'
    )
    FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'rooms' 
    AND COLUMN_NAME = 'room_color'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 2. อัพเดทสีเริ่มต้นสำหรับห้องที่มีอยู่แล้ว
UPDATE rooms SET 
    room_color = CASE 
        WHEN room_name LIKE '%ใหญ่%' THEN '#ef4444'      -- แดง สำหรับห้องใหญ่
        WHEN room_name LIKE '%เล็ก%' THEN '#10b981'       -- เขียว สำหรับห้องเล็ก
        WHEN room_name LIKE '%ฝึกอบรม%' THEN '#f59e0b'   -- เหลือง สำหรับห้องฝึกอบรม
        WHEN room_name LIKE '%กลาง%' THEN '#8b5cf6'      -- ม่วง สำหรับห้องกลาง
        WHEN room_name LIKE '%VIP%' THEN '#ec4899'       -- ชมพู สำหรับห้อง VIP
        WHEN room_name LIKE '%บอร์ด%' THEN '#06b6d4'     -- ฟ้า สำหรับห้องบอร์ด
        ELSE '#3b82f6'                                   -- น้ำเงิน เริ่มต้น
    END
WHERE room_color IS NULL OR room_color = '' OR room_color = '#3b82f6';

-- 3. เพิ่มข้อมูลการตั้งค่าระบบใหม่
INSERT IGNORE INTO system_settings (setting_key, setting_value, description) VALUES
('room_color_enabled', '1', 'เปิดใช้งานระบบสีห้องประชุม (0=ปิด, 1=เปิด)'),
('default_room_color', '#3b82f6', 'สีเริ่มต้นสำหรับห้องประชุมใหม่'),
('public_calendar_enabled', '1', 'เปิดใช้งานปฏิทินสาธารณะ (0=ปิด, 1=เปิด)');

-- 4. อัพเดทข้อมูลเวอร์ชัน
UPDATE system_settings SET 
    setting_value = 'ระบบจองห้องประชุม ศูนย์ส่งเสริมสุขภาพสวนพยอม - Color Edition v2.2'
WHERE setting_key = 'site_name';

-- =====================================================
-- สร้าง View ใหม่สำหรับการแสดงข้อมูลแบบมีสี
-- =====================================================

-- View สำหรับปฏิทินพร้อมสีห้อง
CREATE OR REPLACE VIEW calendar_view AS
SELECT 
    b.booking_id,
    b.booking_date,
    b.start_time,
    b.end_time,
    b.purpose,
    b.status,
    b.attendees,
    b.created_at,
    r.room_id,
    r.room_name,
    r.room_code,
    r.room_color,
    r.capacity,
    r.location,
    u.user_id,
    u.fullname as user_fullname,
    u.department as user_department,
    u.email as user_email,
    a.fullname as approved_by_name
FROM bookings b
JOIN rooms r ON b.room_id = r.room_id
JOIN users u ON b.user_id = u.user_id
LEFT JOIN users a ON b.approved_by = a.user_id;

-- View สำหรับสถิติการใช้งานแยกตามสีห้อง
CREATE OR REPLACE VIEW room_usage_stats AS
SELECT 
    r.room_id,
    r.room_name,
    r.room_code,
    r.room_color,
    r.capacity,
    COUNT(b.booking_id) as total_bookings,
    COUNT(CASE WHEN b.status = 'approved' THEN 1 END) as approved_bookings,
    COUNT(CASE WHEN b.status = 'pending' THEN 1 END) as pending_bookings,
    COUNT(CASE WHEN b.status = 'rejected' THEN 1 END) as rejected_bookings,
    COUNT(CASE WHEN b.booking_date >= CURDATE() THEN 1 END) as upcoming_bookings,
    COUNT(CASE WHEN b.booking_date = CURDATE() THEN 1 END) as today_bookings
FROM rooms r
LEFT JOIN bookings b ON r.room_id = b.room_id
WHERE r.is_active = 1
GROUP BY r.room_id, r.room_name, r.room_code, r.room_color
ORDER BY total_bookings DESC;

-- =====================================================
-- ตรวจสอบผลลัพธ์
-- =====================================================

-- แสดงข้อมูลห้องพร้อมสี
SELECT 
    room_id,
    room_name,
    room_code,
    room_color,
    capacity,
    CASE 
        WHEN room_color = '#ef4444' THEN 'แดง'
        WHEN room_color = '#10b981' THEN 'เขียว'
        WHEN room_color = '#f59e0b' THEN 'เหลือง'
        WHEN room_color = '#8b5cf6' THEN 'ม่วง'
        WHEN room_color = '#3b82f6' THEN 'น้ำเงิน'
        WHEN room_color = '#ec4899' THEN 'ชมพู'
        WHEN room_color = '#06b6d4' THEN 'ฟ้า'
        ELSE 'อื่นๆ'
    END as color_name
FROM rooms 
WHERE is_active = 1
ORDER BY room_id;

-- แสดงการตั้งค่าใหม่
SELECT setting_key, setting_value, description 
FROM system_settings 
WHERE setting_key IN ('room_color_enabled', 'default_room_color', 'public_calendar_enabled')
ORDER BY setting_key;

COMMIT;

-- แสดงข้อความยืนยัน
SELECT 
    'อัพเดทระบบสีห้องประชุมเรียบร้อยแล้ว!' as message,
    '🎨 ระบบพร้อมใช้งานเวอร์ชัน 2.2 Color Edition' as status,
    NOW() as updated_at;