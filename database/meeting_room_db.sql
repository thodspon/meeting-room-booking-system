-- สร้างฐานข้อมูลระบบจองห้องประชุม
-- ศูนย์ส่งเสริมสุขภาพสวนพยอม
-- พัฒนาโดย นายทศพล อุทก นักวิชาการคอมพิวเตอร์ชำนาญการ โร-- การตั้งค่าระบบเริ่มต้น
INSERT INTO system_settings (setting_key, setting_value, description) VALUES
('site_name', 'ระบบจองห้องประชุม ศูนย์ส่งเสริมสุขภาพสวนพยอม - Color Edition v2.2', 'ชื่อเว็บไซต์'),
('booking_advance_days', '30', 'จำนวนวันที่สามารถจองล่วงหน้าได้'),
('min_booking_duration', '60', 'ระยะเวลาการจองขั้นต่ำ (นาที)'),
('max_booking_duration', '480', 'ระยะเวลาการจองสูงสุด (นาที)'),
('booking_start_time', '08:00', 'เวลาเริ่มต้นที่สามารถจองได้'),
('booking_end_time', '17:00', 'เวลาสิ้นสุดที่สามารถจองได้'),
('auto_approve', '0', 'อนุมัติการจองอัตโนมัติ (0=ไม่, 1=ใช่)'),
('telegram_notifications', '1', 'ส่งการแจ้งเตือนผ่าน Telegram (0=ไม่, 1=ใช่)'),
('room_color_enabled', '1', 'เปิดใช้งานระบบสีห้องประชุม (0=ปิด, 1=เปิด)'),
('default_room_color', '#3b82f6', 'สีเริ่มต้นสำหรับห้องประชุมใหม่'),
('public_calendar_enabled', '1', 'เปิดใช้งานปฏิทินสาธารณะ (0=ปิด, 1=เปิด)');ยเอ็ด
-- ทีมพัฒนา: Roi-et Digital Health Team
-- เวอร์ชั่น 2.2 Color Edition วันที่ 26 มกราคม 2568
-- อัพเดต: เพิ่มระบบสีห้องประชุม (Room Color System), ปฏิทินสาธารณะ (Public Calendar)

CREATE DATABASE IF NOT EXISTS meeting_room_db CHARACTER SET tis620 COLLATE tis620_thai_ci;
USE meeting_room_db;

-- ตารางผู้ใช้งาน
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    fullname VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    phone VARCHAR(20),
    department VARCHAR(100),
    position VARCHAR(100),
    role ENUM('admin', 'manager', 'user') DEFAULT 'user',
    telegram_chat_id VARCHAR(50),
    telegram_token VARCHAR(255),
    telegram_enabled BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    last_login DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) CHARACTER SET tis620 COLLATE tis620_thai_ci;

-- ตารางห้องประชุม
CREATE TABLE rooms (
    room_id INT AUTO_INCREMENT PRIMARY KEY,
    room_name VARCHAR(100) NOT NULL,
    room_code VARCHAR(20) UNIQUE,
    capacity INT NOT NULL,
    location VARCHAR(200),
    description TEXT,
    equipment TEXT,
    room_color VARCHAR(7) DEFAULT '#3b82f6',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) CHARACTER SET tis620 COLLATE tis620_thai_ci;

-- ตารางการจองห้องประชุม
CREATE TABLE bookings (
    booking_id INT AUTO_INCREMENT PRIMARY KEY,
    room_id INT NOT NULL,
    user_id INT NOT NULL,
    booking_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    purpose TEXT,
    attendees INT DEFAULT 1,
    status ENUM('pending', 'approved', 'rejected', 'cancelled') DEFAULT 'pending',
    approved_by INT,
    approved_at DATETIME,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (room_id) REFERENCES rooms(room_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (approved_by) REFERENCES users(user_id)
) CHARACTER SET tis620 COLLATE tis620_thai_ci;

-- ตารางบันทึกกิจกรรม (Activity Logs)
CREATE TABLE activity_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    details TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id)
) CHARACTER SET tis620 COLLATE tis620_thai_ci;

-- ตารางรหัส 2FA
CREATE TABLE two_factor_codes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    code VARCHAR(6) NOT NULL,
    expires_at DATETIME NOT NULL,
    used BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id)
) CHARACTER SET tis620 COLLATE tis620_thai_ci;

-- ตารางการตั้งค่าระบบ
CREATE TABLE system_settings (
    setting_id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) CHARACTER SET tis620 COLLATE tis620_thai_ci;

-- สร้าง Index เพื่อเพิ่มประสิทธิภาพ
CREATE INDEX idx_bookings_date ON bookings(booking_date);
CREATE INDEX idx_bookings_room_date ON bookings(room_id, booking_date);
CREATE INDEX idx_bookings_user ON bookings(user_id);
CREATE INDEX idx_bookings_status ON bookings(status);
CREATE INDEX idx_activity_logs_user ON activity_logs(user_id);
CREATE INDEX idx_activity_logs_date ON activity_logs(created_at);
CREATE INDEX idx_two_factor_codes_user ON two_factor_codes(user_id);
CREATE INDEX idx_two_factor_codes_expires ON two_factor_codes(expires_at);

-- เพิ่มข้อมูลเริ่มต้น

-- ผู้ใช้งานเริ่มต้น
INSERT INTO users (username, password, fullname, email, department, position, role) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ผู้ดูแลระบบ', 'admin@hospital.go.th', 'งานคอมพิวเตอร์', 'ผู้ดูแลระบบ', 'admin'),
('manager', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ผู้จัดการ', 'manager@hospital.go.th', 'งานบริหาร', 'หัวหน้างาน', 'manager'),
('user1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'นายทดสอบ ระบบ', 'user1@hospital.go.th', 'แผนกพยาบาล', 'พยาบาลวิชาชีพ', 'user');

-- ห้องประชุมเริ่มต้น
INSERT INTO rooms (room_name, room_code, capacity, location, description, equipment, room_color) VALUES
('ห้องประชุมใหญ่', 'R001', 50, 'ชั้น 2 อาคารบริหาร', 'ห้องประชุมใหญ่สำหรับการประชุมบุคลากร', 'โปรเจคเตอร์, ระบบเสียง, เครื่องปรับอากาศ, Wi-Fi', '#ef4444'),
('ห้องประชุมเล็ก', 'R002', 20, 'ชั้น 1 อาคารบริหาร', 'ห้องประชุมสำหรับการประชุมแผนก', 'โปรเจคเตอร์, เครื่องปรับอากาศ, Wi-Fi', '#10b981'),
('ห้องฝึกอบรม', 'R003', 30, 'ชั้น 3 อาคารบริหาร', 'ห้องสำหรับการฝึกอบรมและสัมมนา', 'โปรเจคเตอร์, ระบบเสียง, เครื่องปรับอากาศ, Wi-Fi, คอมพิวเตอร์', '#f59e0b'),
('ห้องประชุมกลาง', 'R004', 35, 'ชั้น 2 อาคารผู้ป่วยนอก', 'ห้องประชุมสำหรับการประชุมหลายแผนก', 'โปรเจคเตอร์, ระบบเสียง, เครื่องปรับอากาศ, Wi-Fi', '#8b5cf6');

-- การตั้งค่าระบบเริ่มต้น
INSERT INTO system_settings (setting_key, setting_value, description) VALUES
('site_name', 'ระบบจองห้องประชุม ศูนย์ส่งเสริมสุขภาพสวนพยอม', 'ชื่อเว็บไซต์'),
('booking_advance_days', '30', 'จำนวนวันที่สามารถจองล่วงหน้าได้'),
('min_booking_duration', '60', 'ระยะเวลาการจองขั้นต่ำ (นาที)'),
('max_booking_duration', '480', 'ระยะเวลาการจองสูงสุด (นาที)'),
('booking_start_time', '08:00', 'เวลาเริ่มต้นที่สามารถจองได้'),
('booking_end_time', '17:00', 'เวลาสิ้นสุดที่สามารถจองได้'),
('auto_approve', '0', 'อนุมัติการจองอัตโนมัติ (0=ไม่, 1=ใช่)'),
('telegram_notifications', '1', 'ส่งการแจ้งเตือนผ่าน Telegram (0=ไม่, 1=ใช่)');

-- สร้าง View สำหรับรายงาน
CREATE VIEW booking_summary AS
SELECT 
    b.booking_id,
    b.booking_date,
    b.start_time,
    b.end_time,
    b.purpose,
    b.status,
    b.created_at,
    r.room_name,
    r.room_code,
    u.fullname as user_fullname,
    u.department as user_department,
    a.fullname as approved_by_name
FROM bookings b
JOIN rooms r ON b.room_id = r.room_id
JOIN users u ON b.user_id = u.user_id
LEFT JOIN users a ON b.approved_by = a.user_id;

-- สร้าง Stored Procedure สำหรับล้างข้อมูล 2FA ที่หมดอายุ
DELIMITER //
CREATE PROCEDURE CleanExpired2FA()
BEGIN
    DELETE FROM two_factor_codes WHERE expires_at < NOW();
END //
DELIMITER ;

-- สร้าง Event เพื่อล้างข้อมูล 2FA ที่หมดอายุทุก 1 ชั่วโมง
SET GLOBAL event_scheduler = ON;
CREATE EVENT IF NOT EXISTS clean_expired_2fa
ON SCHEDULE EVERY 1 HOUR
DO
    CALL CleanExpired2FA();

COMMIT;