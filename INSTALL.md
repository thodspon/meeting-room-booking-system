# การติดตั้งระบบจองห้องประชุม

> ระบบที่สามารถปรับเปลี่ยนชื่อองค์กรได้ตามการตั้งค่าใน `config.php`

## ขั้นตอนการติดตั้ง

### 1. ติดตั้ง Dependencies

เปิด Command Prompt หรือ PowerShell ในโฟลเดอร์โปรเจ็กต์และรันคำสั่ง:

```bash
composer install
```

หากยังไม่มี Composer ให้ดาวน์โหลดจาก https://getcomposer.org/

### 2. สร้างฐานข้อมูล

1. เปิด phpMyAdmin
2. สร้างฐานข้อมูลใหม่ชื่อ `meeting_room_db`
3. เลือก Collation เป็น `tis620_thai_ci`
4. Import ไฟล์ `database/meeting_room_db.sql`

### 3. ตั้งค่าฐานข้อมูล

แก้ไขไฟล์ `config/database.php`:

```php
$host = 'localhost';           // ที่อยู่เซิร์ฟเวอร์ฐานข้อมูล
$dbname = 'meeting_room_db';   // ชื่อฐานข้อมูล
$username = 'root';            // ชื่อผู้ใช้ฐานข้อมูล
$password = '';                // รหัสผ่านฐานข้อมูล
```

### 4. ตั้งค่า Telegram Bot

1. สร้าง Telegram Bot ใหม่โดยคุยกับ @BotFather
2. ได้รับ Token จาก BotFather
3. หา Chat ID ของคุณ
4. แก้ไขไฟล์ `includes/functions.php`:

```php
define('TELEGRAM_TOKEN', 'YOUR_BOT_TOKEN');
define('TELEGRAM_CHAT_ID', 'YOUR_CHAT_ID');
```

### 5. ตั้งค่า Web Server

#### สำหรับ XAMPP:
1. คัดลอกโฟลเดอร์โปรเจ็กต์ไปที่ `C:\xampp\htdocs\`
2. เปิด XAMPP Control Panel
3. Start Apache และ MySQL
4. เข้าถึงเว็บไซต์ที่ `http://localhost/smdmeeting_room/`

#### สำหรับ Server อื่นๆ:
1. อัพโหลดไฟล์ทั้งหมดไปยัง Document Root
2. ตั้งค่า Virtual Host (ถ้าจำเป็น)
3. ตรวจสอบสิทธิ์ไฟล์ให้เหมาะสม

### 6. ทดสอบระบบ

เข้าสู่ระบบด้วยบัญชีเริ่มต้น:

| Username | Password | บทบาท |
|----------|----------|---------|
| admin | password | ผู้ดูแลระบบ |
| manager | password | ผู้จัดการ |
| user1 | password | ผู้ใช้ทั่วไป |

**หมายเหตุ:** เปลี่ยนรหัสผ่านเริ่มต้นทันทีหลังติดตั้ง

## การแก้ไขปัญหาที่พบบ่อย

### 1. ข้อผิดพลาด "Class not found"
```bash
composer install
```

### 2. ข้อผิดพลาดการเชื่อมต่อฐานข้อมูล
- ตรวจสอบการตั้งค่าใน `config/database.php`
- ตรวจสอบว่า MySQL Server ทำงานอยู่
- ตรวจสอบ username และ password

### 3. ไม่ได้รับแจ้งเตือน Telegram
- ตรวจสอบ TELEGRAM_TOKEN และ CHAT_ID
- ตรวจสอบการเชื่อมต่ออินเทอร์เน็ต
- ลองส่งข้อความทดสอบด้วยตนเอง

### 4. ข้อผิดพลาดการส่งออก PDF/Excel
- ตรวจสอบว่าติดตั้ง Composer dependencies แล้ว
- ตรวจสอบสิทธิ์การเขียนไฟล์

### 5. หน้าเว็บแสดงผิดพลาด
- เปิด Error Reporting ใน PHP
- ตรวจสอบ Error Log
- ตรวจสอบสิทธิ์ไฟล์และโฟลเดอร์

## ข้อกำหนดระบบ

- PHP 7.4 หรือสูงกว่า
- MySQL 5.7 หรือสูงกว่า
- Web Server (Apache/Nginx)
- Composer
- PHP Extensions: PDO, PDO_MySQL, cURL, OpenSSL
- อินเทอร์เน็ตสำหรับการแจ้งเตือน Telegram

## การอัพเดท

1. สำรองข้อมูลฐานข้อมูล
2. สำรองไฟล์ config
3. แทนที่ไฟล์เก่าด้วยไฟล์ใหม่
4. รัน `composer update`
5. อัพเดทฐานข้อมูล (ถ้ามี)

## การปรับแต่ง

### เปลี่ยนธีม
แก้ไข `data-theme` ในไฟล์ HTML:
```html
<html lang="th" data-theme="dark">
```

### เปลี่ยนโลโก้
แทนที่ไฟล์ในโฟลเดอร์ `assets/images/`

### เปลี่ยนสี
แก้ไข CSS variables หรือใช้ daisyUI theme ที่มีอยู่

## การสำรองข้อมูล

### สำรองฐานข้อมูล
```bash
mysqldump -u username -p meeting_room_db > backup.sql
```

### สำรองไฟล์
สำรองโฟลเดอร์ทั้งหมดและไฟล์ config

## ความปลอดภัย

1. เปลี่ยนรหัสผ่านเริ่มต้น
2. ใช้ HTTPS ในการใช้งานจริง
3. ตั้งค่าไฟล์วอลล์
4. อัพเดท PHP และ MySQL เป็นประจำ
5. สำรองข้อมูลเป็นประจำ

## การสนับสนุน

หากมีปัญหาการติดตั้งหรือใช้งาน กรุณาติดต่อ:

**นายทศพล อุทก**  
นักวิชาการคอมพิวเตอร์ชำนาญการ  
โรงพยาบาลร้อยเอ็ด  
Email: developer@hospital.go.th