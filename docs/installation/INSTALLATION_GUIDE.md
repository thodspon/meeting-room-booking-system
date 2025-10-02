# 🚀 System Installation & Setup Guide - คู่มือติดตั้งและตั้งค่าระบบ

## 📋 ข้อมูลทั่วไป

**ชื่อระบบ:** SMD Meeting Room Booking System  
**เวอร์ชัน:** 2.6.0  
**ผู้พัฒนา:** นายทศพล อุทก  
**องค์กร:** โรงพยาบาลร้อยเอ็ด  
**ภาษา:** PHP 7.2+  
**ฐานข้อมูล:** MySQL/MariaDB  

---

## 🎯 ความต้องการของระบบ

### 📋 System Requirements

**เซิร์ฟเวอร์:**
- 🖥️ **Operating System:** Linux/Windows/macOS
- 🌐 **Web Server:** Apache 2.4+ หรือ Nginx 1.18+
- 💾 **PHP:** Version 7.2 หรือสูงกว่า
- 🗄️ **Database:** MySQL 5.7+ หรือ MariaDB 10.3+
- 💽 **Storage:** อย่างน้อย 500MB ว่าง
- 🧠 **RAM:** อย่างน้อย 512MB
- 🌐 **Internet:** สำหรับ Telegram Bot API

**PHP Extensions ที่จำเป็น:**
```
- mysqli หรือ pdo_mysql
- json
- curl
- mbstring
- openssl
- session
- filter
- hash
```

**เบราว์เซอร์ที่รองรับ:**
- 🌐 **Chrome:** 70+
- 🦊 **Firefox:** 65+
- 🌍 **Safari:** 12+
- 🗿 **Edge:** 18+
- 📱 **Mobile Browsers:** iOS Safari, Chrome Mobile

---

## 📦 การติดตั้งระบบ

### 1️⃣ เตรียมข้อมูล

**Download Source Code:**
```bash
# Git Clone
git clone https://github.com/yourusername/smdmeeting_room.git

# หรือ Download ZIP จาก GitHub
wget https://github.com/yourusername/smdmeeting_room/archive/v2.6.0.zip
unzip v2.6.0.zip
```

**ย้ายไฟล์:**
```bash
# ย้ายไฟล์ไปยัง Web Root
cp -r smdmeeting_room/* /var/www/html/
# หรือสำหรับ XAMPP
cp -r smdmeeting_room/* C:/xampp/htdocs/meeting_room/
```

### 2️⃣ ตั้งค่าฐานข้อมูล

**สร้าง Database:**
```sql
CREATE DATABASE meeting_room_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'meeting_user'@'localhost' IDENTIFIED BY 'secure_password_123';
GRANT ALL PRIVILEGES ON meeting_room_db.* TO 'meeting_user'@'localhost';
FLUSH PRIVILEGES;
```

**Import Database Schema:**
```bash
# เข้าไปในโฟลเดอร์ database
cd database/

# Import ฐานข้อมูลหลัก
mysql -u meeting_user -p meeting_room_db < meeting_room_db.sql

# Import ระบบ Password Reset
mysql -u meeting_user -p meeting_room_db < password_reset_system.sql

# อัปเดตเป็น Color Edition (ถ้าต้องการ)
mysql -u meeting_user -p meeting_room_db < update_to_color_edition.sql
```

### 3️⃣ ตั้งค่าไฟล์ Config

**คัดลอกไฟล์ตัวอย่าง:**
```bash
# Database Config
cp config/database.php.example config/database.php

# Functions Config  
cp includes/functions.php.example includes/functions.php

# Telegram Users (ถ้าต้องการ)
cp config/telegram_users.json.example config/telegram_users.json
```

**แก้ไข Database Config:**
```php
// config/database.php
<?php
$servername = "localhost";
$username = "meeting_user";
$password = "secure_password_123";
$dbname = "meeting_room_db";

// สร้างการเชื่อมต่อ
$conn = new mysqli($servername, $username, $password, $dbname);
$conn->set_charset("utf8mb4");

// ตรวจสอบการเชื่อมต่อ
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
```

**แก้ไข Functions Config:**
```php
// includes/functions.php
<?php
// Database Connection
include_once 'config/database.php';

// Telegram Bot Configuration (ถ้าต้องการระบบสำรอง)
$telegram_bot_token = "YOUR_BOT_TOKEN";
$telegram_chat_id = "YOUR_CHAT_ID";

// Organization Settings
$organization_name = "โรงพยาบาลร้อยเอ็ด";
$organization_short = "SMD";
$admin_email = "admin@hospital.go.th";
?>
```

### 4️⃣ ตั้งค่า File Permissions

**Linux/macOS:**
```bash
# ตั้งค่าสิทธิ์ไฟล์
chmod -R 755 /var/www/html/meeting_room/
chmod -R 777 /var/www/html/meeting_room/config/
chmod -R 777 /var/www/html/meeting_room/logs/ (ถ้ามี)

# ตั้งค่า Owner
chown -R www-data:www-data /var/www/html/meeting_room/
```

**Windows (XAMPP):**
```cmd
# ไม่จำเป็นต้องตั้งค่าพิเศษ
# แต่ควรตรวจสอบว่า PHP สามารถเขียนไฟล์ใน config/ ได้
```

---

## 🔧 การตั้งค่าเบื้องต้น

### 1️⃣ สร้างผู้ใช้ Admin คนแรก

**เข้าระบบครั้งแรก:**
```
URL: http://yourdomain.com/meeting_room/
หรือ: http://localhost/meeting_room/
```

**วิธีสร้าง Admin:**
1. **เข้า Database โดยตรง:**
```sql
-- ใส่ข้อมูล Admin คนแรก
INSERT INTO users (username, password, email, role, telegram_enabled, created_at) 
VALUES (
    'admin', 
    MD5('admin123'), 
    'admin@hospital.go.th', 
    'admin', 
    0, 
    NOW()
);
```

2. **หรือแก้ไขผู้ใช้ที่มีอยู่:**
```sql
-- เปลี่ยนผู้ใช้ทั่วไปเป็น Admin
UPDATE users SET role = 'admin' WHERE username = 'existing_user';
```

### 2️⃣ สร้างห้องประชุม

**เข้าเมนู Admin:**
```
1. Login ด้วยสิทธิ์ Admin
2. เลือกเมนู "จัดการห้องประชุม"
3. คลิก "เพิ่มห้องใหม่"
```

**ข้อมูลห้องประชุมตัวอย่าง:**
```
ชื่อห้อง: ห้องประชุมใหญ่
ความจุ: 50 คน
อุปกรณ์: โปรเจคเตอร์, ไมโครโฟน, แอร์
สี: #3498db (สีน้ำเงิน)
สถานะ: เปิดใช้งาน
```

### 3️⃣ สร้างผู้ใช้

**เพิ่มผู้ใช้หลายคน:**
```sql
-- User ทั่วไป
INSERT INTO users (username, password, email, role, telegram_enabled) VALUES
('user001', MD5('password123'), 'user1@hospital.go.th', 'user', 0),
('user002', MD5('password123'), 'user2@hospital.go.th', 'user', 0);

-- Manager
INSERT INTO users (username, password, email, role, telegram_enabled) VALUES
('manager01', MD5('password123'), 'manager1@hospital.go.th', 'manager', 0);
```

**หรือใช้หน้า Admin:**
```
1. เข้าเมนู "จัดการผู้ใช้"
2. คลิก "เพิ่มผู้ใช้ใหม่"
3. กรอกข้อมูลและบันทึก
```

---

## 📱 ตั้งค่า Telegram Bot

### 1️⃣ สร้าง Telegram Bot

**ขั้นตอนการสร้าง Bot:**
```
1. เปิด Telegram แล้วหา @BotFather
2. ส่งคำสั่ง: /newbot
3. ตั้งชื่อ Bot: SMD Meeting Room Bot
4. ตั้ง Username: smd_meeting_bot
5. เก็บ Token ที่ได้รับ
```

**Token ตัวอย่าง:**
```
123456789:ABCdefGHIjklMNOpqrsTUVwxyz
```

### 2️⃣ หา Chat ID

**วิธีหา Chat ID:**
```
1. ส่งข้อความให้ Bot ที่สร้าง
2. เปิด URL: https://api.telegram.org/bot[TOKEN]/getUpdates
3. หา "chat":{"id": ในผลลัพธ์
4. เก็บตัวเลข Chat ID
```

**Chat ID ตัวอย่าง:**
```
987654321
```

### 3️⃣ ตั้งค่าในระบบ

**สำหรับผู้ใช้แต่ละคน:**
```
1. Login เข้าระบบ
2. เข้า "โปรไฟล์ของฉัน"
3. เลื่อนลงไปหา "การตั้งค่า Telegram"
4. ใส่ Bot Token และ Chat ID
5. เปิด "เปิดใช้งาน Telegram"
6. บันทึก
```

**ทดสอบการทำงาน:**
```
1. ลองจองห้องประชุม
2. ตรวจสอบว่าได้รับข้อความ Telegram
3. ถ้าไม่ได้รับ ให้ใช้ Debug Tools
```

---

## 🔍 การทดสอบระบบ

### 1️⃣ Basic Function Test

**ทดสอบการเข้าสู่ระบบ:**
```
✅ Login ด้วยผู้ใช้ทั่วไป
✅ Login ด้วย Manager  
✅ Login ด้วย Admin
✅ ทดสอบ Logout
```

**ทดสอบการจองห้อง:**
```
✅ จองห้องใหม่
✅ แก้ไขการจอง
✅ ยกเลิกการจอง
✅ ดูปฏิทิน
```

**ทดสอบการอนุมัติ:**
```
✅ Manager อนุมัติการจอง
✅ Manager ปฏิเสธการจอง
✅ ดูรายการรออนุมัติ
```

### 2️⃣ Telegram Function Test

**ทดสอบการส่งข้อความ:**
```
✅ ข้อความการจองใหม่
✅ ข้อความการอนุมัติ
✅ ข้อความการยกเลิก
✅ รายงาน Telegram
```

**ทดสอบ Debug Tools:**
```
✅ Debug System
✅ Debug Telegram
✅ Debug Reports
✅ Debug Session
```

### 3️⃣ Performance Test

**ทดสอบความเร็ว:**
```
✅ โหลดหน้าแรก < 3 วินาที
✅ บันทึกข้อมูล < 2 วินาที
✅ ส่ง Telegram < 5 วินาที
✅ Export รายงาน < 10 วินาที
```

**ทดสอบความเสถียร:**
```
✅ ใช้งานต่อเนื่อง 1 ชั่วโมง
✅ จองพร้อมกัน 10 คน
✅ Export รายงานขนาดใหญ่
✅ ส่ง Telegram หลายข้อความ
```

---

## 🛡️ การตั้งค่าความปลอดภัย

### 1️⃣ Database Security

**สร้าง User ที่มีสิทธิ์จำกัด:**
```sql
-- สำหรับ Production
CREATE USER 'meeting_app'@'localhost' IDENTIFIED BY 'complex_password_456';
GRANT SELECT, INSERT, UPDATE, DELETE ON meeting_room_db.* TO 'meeting_app'@'localhost';
FLUSH PRIVILEGES;
```

**Backup Database:**
```bash
# สร้าง Backup ประจำวัน
mysqldump -u meeting_user -p meeting_room_db > backup_$(date +%Y%m%d).sql

# สร้าง Cron Job สำหรับ Backup อัตโนมัติ
0 2 * * * /path/to/backup_script.sh
```

### 2️⃣ File Security

**ป้องกันไฟล์สำคัญ:**
```apache
# สร้าง .htaccess ในโฟลเดอร์ config/
<Files "*.php">
    Order allow,deny
    Deny from all
</Files>

# อนุญาตเฉพาะ index.php
<Files "index.php">
    Order allow,deny
    Allow from all
</Files>
```

**ซ่อนไฟล์ระบบ:**
```apache
# .htaccess ในรูท
<Files "composer.json">
    Order allow,deny  
    Deny from all
</Files>

<Files "*.md">
    Order allow,deny
    Deny from all
</Files>
```

### 3️⃣ Password Security

**นโยบายรหัสผ่าน:**
```php
// เพิ่มในฟังก์ชันตรวจสอบรหัสผ่าน
function validatePassword($password) {
    if (strlen($password) < 8) return false;
    if (!preg_match('/[A-Z]/', $password)) return false;
    if (!preg_match('/[a-z]/', $password)) return false;
    if (!preg_match('/[0-9]/', $password)) return false;
    return true;
}
```

**เข้ารหัสรหัสผ่าน:**
```php
// ใช้ password_hash() แทน MD5
$hashed = password_hash($password, PASSWORD_DEFAULT);

// ตรวจสอบรหัสผ่าน
if (password_verify($password, $hashed_from_db)) {
    // Login สำเร็จ
}
```

---

## 🔧 การบำรุงรักษา

### 1️⃣ การ Backup ประจำ

**Database Backup:**
```bash
#!/bin/bash
# backup_script.sh

DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/backup/meeting_room"
DB_NAME="meeting_room_db"

# สร้างโฟลเดอร์ backup
mkdir -p $BACKUP_DIR

# Backup database
mysqldump -u meeting_user -p$DB_PASSWORD $DB_NAME > $BACKUP_DIR/db_$DATE.sql

# บีบอัดไฟล์
gzip $BACKUP_DIR/db_$DATE.sql

# ลบไฟล์เก่าที่เก็บไว้เกิน 30 วัน
find $BACKUP_DIR -name "*.gz" -mtime +30 -delete
```

**File Backup:**
```bash
#!/bin/bash
# backup_files.sh

DATE=$(date +%Y%m%d_%H%M%S)
SOURCE_DIR="/var/www/html/meeting_room"
BACKUP_DIR="/backup/meeting_room_files"

# สร้าง tar archive
tar -czf $BACKUP_DIR/files_$DATE.tar.gz -C $SOURCE_DIR .

# ลบไฟล์เก่า
find $BACKUP_DIR -name "*.tar.gz" -mtime +7 -delete
```

### 2️⃣ การอัปเดตระบบ

**ขั้นตอนการอัปเดต:**
```bash
# 1. Backup ข้อมูลเดิม
./backup_script.sh

# 2. Download version ใหม่
wget https://github.com/user/repo/archive/v2.7.0.zip

# 3. สำรองไฟล์ config
cp config/database.php config/database.php.backup
cp includes/functions.php includes/functions.php.backup

# 4. แตกไฟล์ใหม่
unzip v2.7.0.zip
cp -r smdmeeting_room-2.7.0/* ./

# 5. คืนค่าไฟล์ config
cp config/database.php.backup config/database.php
cp includes/functions.php.backup includes/functions.php

# 6. อัปเดต database (ถ้ามี)
mysql -u meeting_user -p meeting_room_db < database/update_to_v2.7.sql

# 7. ทดสอบระบบ
```

### 3️⃣ การ Monitor ระบบ

**Log Files:**
```bash
# สร้างโฟลเดอร์ logs
mkdir logs
chmod 777 logs

# เพิ่มการเขียน log ในระบบ
error_log("User login: " . $username, 3, "logs/access.log");
```

**Health Check Script:**
```php
// health_check.php
<?php
$health = [
    'database' => false,
    'telegram' => false,
    'storage' => false
];

// ตรวจสอบ Database
try {
    include 'config/database.php';
    $result = $conn->query("SELECT 1");
    $health['database'] = true;
} catch (Exception $e) {
    $health['database'] = false;
}

// ตรวจสอบ Telegram API
$test_url = "https://api.telegram.org/bot" . $telegram_bot_token . "/getMe";
$response = @file_get_contents($test_url);
$health['telegram'] = ($response !== false);

// ตรวจสอบ Storage
$health['storage'] = is_writable('./config/');

echo json_encode($health);
?>
```

---

## 📊 การ Monitor และ Analytics

### 1️⃣ ตั้งค่า Google Analytics (ถ้าต้องการ)

**เพิ่มโค้ด GA:**
```html
<!-- เพิ่มใน head ของทุกหน้า -->
<script async src="https://www.googletagmanager.com/gtag/js?id=GA_MEASUREMENT_ID"></script>
<script>
window.dataLayer = window.dataLayer || [];
function gtag(){dataLayer.push(arguments);}
gtag('js', new Date());
gtag('config', 'GA_MEASUREMENT_ID');
</script>
```

### 2️⃣ สร้าง Usage Report

**สร้างรายงานการใช้งาน:**
```php
// usage_report.php
<?php
// สถิติการจองรายเดือน
$monthly_bookings = $conn->query("
    SELECT DATE_FORMAT(booking_date, '%Y-%m') as month, 
           COUNT(*) as total_bookings
    FROM bookings 
    WHERE booking_date >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    GROUP BY month
    ORDER BY month
");

// ห้องที่ได้รับความนิยม
$popular_rooms = $conn->query("
    SELECT r.room_name, COUNT(b.id) as booking_count
    FROM rooms r
    LEFT JOIN bookings b ON r.id = b.room_id
    WHERE b.booking_date >= DATE_SUB(NOW(), INTERVAL 3 MONTH)
    GROUP BY r.id
    ORDER BY booking_count DESC
    LIMIT 10
");
?>
```

---

## ❓ FAQ การติดตั้ง

### Q: ติดตั้งบนเซิร์ฟเวอร์ Linux อย่างไร?
**A:** ใช้ขั้นตอนมาตรฐาน LAMP Stack:
```bash
# Ubuntu/Debian
sudo apt update
sudo apt install apache2 mysql-server php php-mysql php-curl

# CentOS/RHEL  
sudo yum install httpd mariadb-server php php-mysql php-curl
```

### Q: ใช้กับ Nginx ได้ไหม?
**A:** ได้ ต้องตั้งค่า PHP-FPM:
```nginx
server {
    listen 80;
    server_name yourdomain.com;
    root /var/www/html/meeting_room;
    index index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
    }
}
```

### Q: รองรับ SSL/HTTPS ไหม?
**A:** รองรับ แนะนำให้ใช้:
```bash
# Let's Encrypt SSL
sudo certbot --apache -d yourdomain.com
```

### Q: ย้ายระบบไปเซิร์ฟเวอร์ใหม่ทำไง?
**A:** ทำตามขั้นตอน:
```bash
# 1. Export database
mysqldump -u user -p meeting_room_db > backup.sql

# 2. Copy files
tar -czf meeting_room.tar.gz /path/to/meeting_room/

# 3. Import ในเซิร์ฟเวอร์ใหม่
mysql -u newuser -p new_meeting_room_db < backup.sql

# 4. แก้ไข config files
```

### Q: อัปเดต PHP version ใหม่ทำไง?
**A:** ตรวจสอบ compatibility ก่อน:
```bash
# ทดสอบ PHP version ใหม่
php -v
php -m | grep mysqli

# ทดสอบระบบ
php -l index.php
```

---

## 📞 การติดต่อและการสนับสนุน

**ผู้พัฒนา:**
- 👨‍💻 **ชื่อ:** นายทศพล อุทก
- 🏥 **หน่วยงาน:** โรงพยาบาลร้อยเอ็ด
- 💼 **ตำแหน่ง:** นักวิชาการคอมพิวเตอร์ชำนาญการ

**GitHub Repository:**
- 🌐 **URL:** https://github.com/yourusername/smdmeeting_room
- 📋 **Issues:** สำหรับรายงานปัญหา
- 💡 **Wiki:** เอกสารเพิ่มเติม

**เอกสารอ้างอิง:**
- 📚 **User Manual:** [USER_MANUAL.md](USER_MANUAL.md)
- 👨‍💼 **Manager Manual:** [MANAGER_MANUAL.md](MANAGER_MANUAL.md)  
- 🔧 **Admin Manual:** [ADMIN_MANUAL.md](ADMIN_MANUAL.md)
- 🆘 **Troubleshooting:** [TROUBLESHOOTING.md](TROUBLESHOOTING.md)

---

**🎯 สำคัญ: ทำการ Backup ข้อมูลก่อนติดตั้งหรืออัปเดตเสมอ!**

---

*📝 เอกสารนี้อัปเดตล่าสุด: 29 กันยายน 2568 (Version 2.6)*  
*🔄 สำหรับข้อมูลล่าสุด โปรดตรวจสอบ GitHub Repository*