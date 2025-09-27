-- สร้างตาราง password_resets สำหรับระบบรีเซ็ตรหัสผ่าน
-- Meeting Room Booking System v2.3
-- Created: 2025-09-26

CREATE TABLE IF NOT EXISTS `password_resets` (
  `reset_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `reset_code` varchar(6) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  PRIMARY KEY (`reset_id`),
  UNIQUE KEY `unique_user_reset` (`user_id`),
  KEY `idx_reset_code` (`reset_code`),
  KEY `idx_expires_at` (`expires_at`),
  CONSTRAINT `fk_password_resets_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='ตารางเก็บรหัสรีเซ็ตรหัสผ่าน';

-- เพิ่ม index สำหรับประสิทธิภาพ
CREATE INDEX `idx_user_expires` ON `password_resets` (`user_id`, `expires_at`);
CREATE INDEX `idx_code_expires` ON `password_resets` (`reset_code`, `expires_at`);

-- Cleanup job: ลบรหัสที่หมดอายุแล้ว (สามารถรันเป็น scheduled job)
-- DELETE FROM password_resets WHERE expires_at < NOW() OR used_at IS NOT NULL;