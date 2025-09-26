-- คำสั่ง SQL สำหรับเพิ่มคอลัมน์ room_color ในตารางที่มีอยู่แล้ว
-- ระบบจองห้องประชุม - อัพเดทเวอร์ชัน 2.2 Color Edition
-- พัฒนาโดย นายทศพล อุทก นักวิชาการคอมพิวเตอร์ชำนาญการ โรงพยาบาลร้อยเอ็ด
-- ทีมพัฒนา: Roi-et Digital Health Team
-- วันที่: 26 มกราคม 2568

USE meeting_room_db;

-- เพิ่มคอลัมน์ room_color ในตาราง rooms (ถ้ายังไม่มี)
ALTER TABLE rooms ADD COLUMN IF NOT EXISTS room_color VARCHAR(7) DEFAULT '#3b82f6' AFTER equipment;

-- อัพเดทสีเริ่มต้นสำหรับห้องที่มีอยู่แล้ว (ถ้ายังไม่มีสี)
UPDATE rooms SET room_color = CASE 
    WHEN room_id = 1 THEN '#ef4444'  -- แดง (ห้องประชุมใหญ่)
    WHEN room_id = 2 THEN '#10b981'  -- เขียว (ห้องประชุมเล็ก)
    WHEN room_id = 3 THEN '#f59e0b'  -- เหลือง (ห้องฝึกอบรม)
    WHEN room_id = 4 THEN '#8b5cf6'  -- ม่วง (ห้องประชุมกลาง)
    ELSE '#3b82f6'                   -- น้ำเงิน (เริ่มต้น)
END
WHERE room_color IS NULL OR room_color = '';

-- ตรวจสอบการเปลี่ยนแปลง
SELECT room_id, room_name, room_color FROM rooms ORDER BY room_id;

COMMIT;