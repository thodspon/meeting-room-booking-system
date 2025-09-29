# คู่มือระบบรีเซ็ตรหัสผ่านผ่าน Telegram

## 📋 ภาพรวมระบบ

ระบบรีเซ็ตรหัสผ่านผ่าน Telegram เป็นฟีเจอร์ความปลอดภัยใหม่ในเวอร์ชัน 2.4 Security Edition Pro ที่ช่วยให้ผู้ใช้สามารถรีเซ็ตรหัสผ่านได้ด้วยตนเองผ่าน Telegram Bot โดยไม่ต้องติดต่อผู้ดูแลระบบ

## 🚀 คุณสมบัติหลัก

### ✨ ความปลอดภัย
- **รหัสรีเซ็ต 6 หลัก** - สุ่มใหม่ทุกครั้ง
- **หมดอายุ 15 นาที** - ป้องกันการใช้งานนาน
- **ใช้ได้ครั้งเดียว** - ป้องกันการใช้ซ้ำ
- **Database Transaction** - ป้องกันข้อมูลเสียหาย

### 📱 Telegram Integration
- ส่งรหัสรีเซ็ตอัตโนมัติ
- แจ้งเตือนเมื่อเปลี่ยนรหัสผ่านสำเร็จ
- ข้อความภาษาไทยที่สวยงาม
- รองรับ HTML formatting

### 🎨 User Experience
- **3-Step Process** - ขั้นตอนที่ชัดเจน
- **Progress Indicator** - แสดงความคืบหน้า
- **Responsive Design** - ใช้งานได้ทุกอุปกรณ์
- **Real-time Validation** - ตรวจสอบทันที

## 📁 ไฟล์ที่เกี่ยวข้อง

```
├── forgot_password.php          # หน้าหลักระบบรีเซ็ตรหัสผ่าน
├── reset_password.php           # ประมวลผล form submission
├── cleanup_password_resets.php  # ลบข้อมูลเก่า (Cron Job)
├── database/
│   └── password_reset_system.sql # สร้างตาราง password_resets
└── includes/
    └── functions.php            # ฟังก์ชัน Telegram และ config
```

## 🗄️ โครงสร้างฐานข้อมูล

### ตาราง `password_resets`
```sql
CREATE TABLE `password_resets` (
  `reset_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `reset_code` varchar(6) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  PRIMARY KEY (`reset_id`),
  UNIQUE KEY `unique_user_reset` (`user_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`)
);
```

## 🔧 การติดตั้ง

### 1. อัพเดตฐานข้อมูล
```bash
mysql -u [username] -p [database_name] < database/password_reset_system.sql
```

### 2. ตั้งค่า Telegram Bot
ตรวจสอบให้แน่ใจว่ามีการตั้งค่า Telegram Bot ใน `includes/functions.php`:
```php
define('TELEGRAM_TOKEN', 'YOUR_BOT_TOKEN');
define('TELEGRAM_CHAT_ID', 'YOUR_CHAT_ID');
```

### 3. ตั้งค่า Cron Job (Optional)
```bash
# ลบข้อมูลเก่าทุก 30 นาที
*/30 * * * * php /path/to/cleanup_password_resets.php
```

## 📖 วิธีการใช้งาน

### สำหรับผู้ใช้ทั่วไป

#### ขั้นตอนที่ 1: ขอรหัสรีเซ็ต
1. เข้าไปที่หน้า Login
2. คลิก "ลืมรหัสผ่าน?"
3. กรอกชื่อผู้ใช้
4. คลิก "ส่งรหัสรีเซ็ต"

#### ขั้นตอนที่ 2: ตรวจสอบ Telegram
1. เปิด Telegram
2. ตรวจสอบข้อความจาก Bot
3. คัดลอกรหัส 6 หลัก

#### ขั้นตอนที่ 3: รีเซ็ตรหัสผ่าน
1. กรอกรหัส 6 หลักที่ได้รับ
2. กรอกรหัสผ่านใหม่
3. ยืนยันรหัสผ่านใหม่
4. คลิก "รีเซ็ตรหัสผ่าน"

### สำหรับผู้ดูแลระบบ

#### การจัดการผู้ใช้
- ตรวจสอบ Log ไฟล์เพื่อดูกิจกรรมการรีเซ็ต
- ติดตาม expired codes ผ่าน database
- รัน cleanup script เป็นระยะ

#### การแก้ไขปัญหา
- ตรวจสอบการตั้งค่า Telegram Bot
- ตรวจสอบ PHP Error Log
- ทดสอบการส่งข้อความ Telegram

## 🔍 การแก้ไขปัญหา

### ปัญหาที่พบบ่อย

#### 1. ไม่ได้รับข้อความ Telegram
**สาเหตุ:**
- Bot Token ไม่ถูกต้อง
- Chat ID ไม่ถูกต้อง
- Bot ถูก Block

**วิธีแก้:**
```php
// ทดสอบการส่งข้อความ
$result = testTelegramMessage($token, $chat_id);
if (!$result['success']) {
    echo $result['error'];
}
```

#### 2. รหัสรีเซ็ตหมดอายุ
**สาเหตุ:**
- ใช้เวลานานเกิน 15 นาที

**วิธีแก้:**
- ขอรหัสใหม่
- ตรวจสอบเวลาเซิร์ฟเวอร์

#### 3. รหัสไม่ถูกต้อง
**สาเหตุ:**
- พิมพ์ผิด
- รหัสถูกใช้ไปแล้ว

**วิธีแก้:**
- ตรวจสอบอีกครั้ง
- ขอรหัสใหม่

## 🎯 API Reference

### ฟังก์ชันหลัก

#### `getUserTelegramConfig($user_id)`
```php
// ดึงการตั้งค่า Telegram ของผู้ใช้
$config = getUserTelegramConfig(123);
// Returns: ['enabled' => true, 'token' => '...', 'chat_id' => '...']
```

#### `getTelegramConfig()`
```php
// ดึงการตั้งค่า Telegram ของระบบ
$config = getTelegramConfig();
// Returns: ['enabled' => true, 'default_token' => '...', 'default_chat_id' => '...']
```

#### `testTelegramMessage($token, $chat_id)`
```php
// ทดสอบการส่งข้อความ
$result = testTelegramMessage('BOT_TOKEN', 'CHAT_ID');
// Returns: ['success' => true/false, 'message' => '...', 'error' => '...']
```

## 🛡️ ความปลอดภัย

### มาตรการป้องกัน
- **Rate Limiting**: ป้องกันการขอรหัสบ่อยเกินไป
- **Input Validation**: ตรวจสอบข้อมูลทุกรูปแบบ
- **SQL Injection Protection**: ใช้ Prepared Statements
- **XSS Protection**: Escape output ทุกจุด

### การ Audit
- บันทึก Log ทุกการรีเซ็ต
- เก็บประวัติการใช้งาน
- ติดตาม Failed attempts

## 📊 สถิติและ Monitoring

### การติดตามใช้งาน
```sql
-- จำนวนการรีเซ็ตรหัสผ่านต่อวัน
SELECT DATE(created_at) as date, COUNT(*) as reset_count
FROM password_resets 
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
GROUP BY DATE(created_at);

-- ผู้ใช้ที่รีเซ็ตบ่อย
SELECT u.username, u.fullname, COUNT(*) as reset_count
FROM password_resets pr
JOIN users u ON pr.user_id = u.user_id
WHERE pr.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
GROUP BY pr.user_id
ORDER BY reset_count DESC;
```

## 🔮 การพัฒนาต่อ

### ฟีเจอร์ที่อาจเพิ่มในอนาคต
- **SMS Integration** - รีเซ็ตผ่าน SMS
- **Email Fallback** - ส่งผ่าน Email สำรอง
- **2FA Integration** - เชื่อมต่อกับระบบ 2FA
- **Admin Dashboard** - จัดการรีเซ็ตผ่านหน้าเว็บ

### การขยายฟังก์ชัน
- รองรับ Multiple Telegram Accounts
- Webhook Integration
- Advanced Analytics
- Mobile App Support

---

## 📞 การสนับสนุน

หากมีคำถามหรือปัญหา กรุณาติดต่อ:
- **ผู้พัฒนา:** นายทศพล อุทก
- **ตำแหน่ง:** นักวิชาการคอมพิวเตอร์ชำนาญการ
- **หน่วยงาน:** โรงพยาบาลร้อยเอ็ด

## 📝 บันทึกการเปลี่ยนแปลง

### v2.4.0 (2025-09-26)
- เพิ่มระบบรีเซ็ตรหัสผ่านผ่าน Telegram
- สร้างตาราง password_resets
- เพิ่มฟังก์ชัน Telegram management
- สร้าง UI/UX แบบ 3 ขั้นตอน
- เพิ่ม Cleanup script

---
*เอกสารนี้อัพเดตล่าสุด: 26 กันยายน 2025*