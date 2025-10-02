# ระบบจองห้องประชุมออนไลน์ (Meeting Room Booking System)

> ระบบที่สามารถปรับเปลี่ยนชื่อองค์กรได้ง่ายตามการตั้งค่า  
> พร้อมใช้งานสำหรับหน่วยงานต่างๆ

พัฒนาโดย นายทศพล อุทก นักวิชาการคอมพิวเตอร์ชำนาญการ โรงพยาบาลร้อยเอ็ด  
**ทีมพัฒนา: Roi-et Digital Health Team**  
**เวอร์ชั่น 2.2 (Color Edition Pro) Build 20250926** วันที่ 26 กันยายน 2568

[![Version](https://img.shields.io/badge/Version-2.2-blue.svg)](https://github.com/thodspon/meeting-room-booking-system)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)
[![PHP](https://img.shields.io/badge/PHP-7.4+-purple.svg)](https://php.net)
[![MySQL](https://img.shields.io/badge/MySQL-5.7+-orange.svg)](https://mysql.com)

## 🆕 ข้อมูลเวอร์ชัน 2.2 - Color Edition Pro

### ✨ ฟีเจอร์ใหม่ในเวอร์ชัน 2.2 Pro
- ✅ **ระบบสีห้องประชุม** (Room Color System) - เลือกสีประจำแต่ละห้อง
- ✅ **ปฏิทินสาธารณะ** (Public Calendar) - ดูการจองแบบไม่ต้อง Login
- ✅ **การแสดงสีห้องในปฏิทิน** แบบ Color-coded พร้อมสถานะ
- ✅ **Color Picker Interface** - เลือกสีจากชุดสีเริ่มต้นหรือกำหนดเอง
- ✅ **Tooltip แบบ Custom** - แสดงรายละเอียดครบถ้วนเป็นภาษาไทย
- ✅ **เวลาเรียลไทม์** - แสดงเวลาปัจจุบันและสถานะการใช้งาน
- ✅ **สถานะการใช้งานแบบไดนามิก** - กำลังใช้งาน, เสร็จสิ้นแล้ว
- ✅ **Visual Status Indicators** - วงกลมสีและเอฟเฟกต์แสดงสถานะ
- ✅ **Mobile-Friendly Tooltips** - รองรับการแตะบนมือถือ
- ✅ **Auto-Refresh Calendar** - อัพเดทอัตโนมัติทุก 5 นาที

### 🎨 ฟีเจอร์เด่นของ Color Edition Pro

#### 🏢 ระบบสีห้องประชุม
- **Color Management:** จัดการสีห้องผ่านหน้า Admin
- **Color Picker:** เลือกจากสีเริ่มต้น หรือกำหนดสี Hex Code
- **Visual Coding:** แต่ละห้องมีสีประจำตัวที่แสดงในปฏิทิน

#### 📅 ปฏิทินสาธารณะ
- **Public Access:** ดูการจองโดยไม่ต้อง Login
- **Real-time Updates:** ข้อมูลอัพเดทแบบเรียลไทม์
- **Current Time Display:** แสดงเวลาปัจจุบัน (GMT+7)

#### 💡 Tooltip System
- **Rich Information:** แสดงข้อมูล 7 หมวด (ห้อง, เวลา, ผู้จอง, แผนก, วัตถุประสงค์, จำนวนคน, สถานะ)
- **Time-aware:** แสดงสถานะเวลา (เริ่มใน, กำลังใช้, เสร็จสิ้น)
- **Live Clock:** เวลาปัจจุบันอัพเดททุกวินาที

#### 🎯 Visual Status System
- 🟢 **อนุมัติแล้ว:** สีห้อง + วงกลมเขียว
- 🟡 **รออนุมัติ:** เส้นประ + วงกลมเหลืองกระพริบ  
- 🔴 **กำลังใช้งาน:** วงกลมแดงกระพริบ
- ⚪ **เสร็จสิ้น/ไม่อนุมัติ:** วงกลมเทา + จางลง

## 🔄 ข้อมูลเวอร์ชัน 2.1 - Team Edition

### ✨ ฟีเจอร์ใหม่ในเวอร์ชัน 2.1
- ✅ เพิ่มข้อมูลทีมพัฒนา Roi-et Digital Health Team
- ✅ ปรับปรุงระบบจัดการเวอร์ชันให้รองรับ sub-version (x.1, x.2, x.3)
- ✅ เพิ่มฟังก์ชันแสดงข้อมูลทีมในระบบ
- ✅ ปรับปรุงการแสดงผลข้อมูลผู้พัฒนาให้ครบถ้วน

## 📋 ข้อมูลเวอร์ชัน 2.0 - Enhanced Edition

### ✨ ฟีเจอร์ใหม่และการปรับปรุงสำคัญ
- ✅ แก้ไขปัญหาการแสดงผลภาษาไทยในไฟล์ PDF
- ✅ ปรับปรุงระบบ 2FA ให้แสดงชื่อระบบและ URL  
- ✅ แก้ไขการแสดงผลตัวอักษรไทยในปฏิทิน
- ✅ ปรับปรุงปุ่มจัดการในหน้าการจองของฉัน
- ✅ เพิ่มระบบสำรองสำหรับการส่งออก PDF
- ✅ ปรับปรุงการจัดการฟอนต์ TCPDF
- ✅ เพิ่มระบบ HTML-to-PDF เป็นทางเลือก
- ✅ ปรับปรุงการเข้ารหัส UTF-8 ทั้งระบบ
- ✅ เพิ่มระบบจัดการเวอร์ชันแบบครบวงจร
- ✅ หน้าแสดงข้อมูลระบบและประวัติการพัฒนา

### 🐛 การแก้ไขข้อผิดพลาด
- 🔧 แก้ไขข้อผิดพลาด Syntax Error ในไฟล์ reports.php
- 🔧 แก้ไขปัญหาฟอนต์ TCPDF ที่ไม่พบไฟล์นิยาม
- 🔧 แก้ไขการแสดงผลตัวอักษรไทยที่เป็น "�������к�"
- 🔧 แก้ไขปุ่มจัดการที่ไม่ทำงานในหน้าการจองของฉัน
- 🔧 แก้ไขการส่งข้อความ 2FA ที่ขาดข้อมูลระบบ

## คุณสมบัติระบบ

### 1. ระบบยืนยันตัวตน (2FA)
- เข้าสู่ระบบผ่าน Username/Password
- ยืนยันตัวตนผ่าน Telegram Bot
- ระบบป้องกันการเข้าสู่ระบบที่ไม่ได้รับอนุญาต

### 2. ระบบจองห้องประชุม
- จองห้องประชุมล่วงหน้า
- ตรวจสอบเวลาซ้ำซ้อน
- ระบบอนุมัติการจอง
- แจ้งเตือนผ่าน Telegram

### 3. การจัดการข้อมูล
- จัดการห้องประชุม
- จัดการผู้ใช้งาน
- ระบบสิทธิ์การใช้งาน (Admin, Manager, User)

### 4. ระบบรายงาน
- รายงานการจองห้องประชุม
- ส่งออกไฟล์ Excel และ PDF
- พิมพ์รายงาน

### 5. ระบบ Log
- บันทึกกิจกรรมผู้ใช้
- ติดตาม Login/Logout
- บันทึกการเปลี่ยนแปลงข้อมูล

## การติดตั้ง

### ความต้องการระบบ
- PHP 7.4 หรือสูงกว่า
- MySQL 5.7 หรือสูงกว่า
- Web Server (Apache/Nginx)
- Composer

### ขั้นตอนการติดตั้ง

ดูรายละเอียดการติดตั้งแบบละเอียดใน [INSTALL.md](INSTALL.md)

1. **คัดลอกไฟล์โปรเจ็กต์**
   ```bash
   git clone [repository-url]
   cd smdmeeting_room
   ```

### การเปลี่ยนชื่อองค์กร

ระบบสามารถเปลี่ยนชื่อองค์กรได้ง่าย สำหรับใช้กับหน่วยงานต่างๆ  
ดูรายละเอียดการตั้งค่าใน [ORGANIZATION_SETUP.md](ORGANIZATION_SETUP.md)

### การตั้งค่า Telegram ส่วนบุคคล

ผู้ใช้สามารถตั้งค่า Telegram Bot ของตัวเองได้ รับข้อความ 2FA และแจ้งเตือนแบบส่วนตัว  
ดูรายละเอียดการใช้งานใน [TELEGRAM_GUIDE.md](TELEGRAM_GUIDE.md)

2. **ติดตั้ง Dependencies**
   ```bash
   composer install
   ```

3. **สร้างฐานข้อมูล**
   - เปิด phpMyAdmin หรือ MySQL Client
   - Import ไฟล์ `database/meeting_room_db.sql`

4. **ตั้งค่าฐานข้อมูล**
   - แก้ไขไฟล์ `config/database.php`
   - ระบุ host, database name, username, password

5. **ตั้งค่า Telegram Bot**
   - แก้ไขไฟล์ `includes/functions.php`
   - ระบุ TELEGRAM_TOKEN และ CHAT_ID

### บัญชีผู้ใช้เริ่มต้น

| Username | Password | Role | รายละเอียด |
|----------|----------|------|------------|
| admin | password | admin | ผู้ดูแลระบบ |
| manager | password | manager | ผู้จัดการ |
| user1 | password | user | ผู้ใช้ทั่วไป |

## การใช้งาน

### สำหรับผู้ดูแลระบบ (Admin)
- จัดการผู้ใช้งาน
- จัดการห้องประชุม
- อนุมัติการจอง
- ดูรายงานทั้งหมด

### สำหรับผู้จัดการ (Manager)
- อนุมัติการจอง
- ดูรายงาน
- จัดการห้องประชุม

### สำหรับผู้ใช้ (User)
- จองห้องประชุม
- ดูการจองของตนเอง
- แก้ไข/ยกเลิกการจอง

## 📁 โครงสร้างไฟล์ (v2.5.1 - Organized Structure)

```
meeting-room-booking-system/
├── 📁 admin/                    # ไฟล์สำหรับผู้ดูแลระบบ
│   ├── users.php               # จัดการผู้ใช้
│   ├── rooms.php               # จัดการห้องประชุม
│   ├── reports.php             # รายงาน
│   ├── room_bookings.php       # จัดการการจอง
│   ├── user_activity.php       # ติดตามกิจกรรมผู้ใช้
│   ├── organization_config.php  # ตั้งค่าองค์กร
│   ├── telegram_settings.php   # ตั้งค่า Telegram
│   ├── send_telegram_summary.php # ส่งสรุปผ่าน Telegram
│   ├── debug_system.php        # เครื่องมือ debug
│   └── README.md               # คู่มือการใช้งาน Admin
├── 📁 docs/                     # เอกสารทั้งหมด
│   ├── 📁 changelog/           # บันทึกการเปลี่ยนแปลง
│   │   ├── CHANGELOG.md
│   │   ├── CHANGELOG_v2.5.md
│   │   └── UPDATE_v2.5.1.md
│   ├── 📁 installation/        # คู่มือติดตั้ง
│   │   ├── INSTALL.md
│   │   ├── ALMA9_PERMISSIONS.md
│   │   └── ORGANIZATION_SETUP.md
│   ├── 📁 guides/              # คู่มือการใช้งาน
│   │   ├── GITHUB_UPLOAD_GUIDE.md
│   │   ├── GIT_UPLOAD_GUIDE.md
│   │   ├── PASSWORD_RESET_GUIDE.md
│   │   └── TELEGRAM_GUIDE.md
│   ├── CLEANUP_LOG.md          # บันทึกการทำความสะอาด
│   ├── FIX_LOG.md              # บันทึกการแก้ไข
│   └── README.md               # ภาพรวมเอกสาร
├── 📁 scripts/                  # Scripts สำหรับบำรุงรักษา
│   ├── setup_permissions.sh    # ตั้งค่าสิทธิ์ (Linux)
│   ├── cleanup_password_resets.php # ทำความสะอาดรหัสผ่าน
│   └── README.md               # คู่มือ Scripts
├── 📁 tests/                    # ไฟล์ทดสอบ
│   ├── test_forgot_password.php
│   ├── test_permissions.php
│   ├── test_system.php
│   ├── test_telegram.php
│   ├── test_telegram_form.php
│   └── README.md               # คู่มือการทดสอบ
├── 📁 config/                   # การตั้งค่า
│   ├── database.php            # ตั้งค่าฐานข้อมูล
│   ├── database.php.example
│   ├── telegram_users.json     # ข้อมูล Telegram Users
│   └── telegram_users.json.example
├── 📁 includes/                 # ไฟล์ระบบ
│   ├── functions.php           # ฟังก์ชันหลัก
│   └── functions.php.example
├── 📁 database/                 # ไฟล์ฐานข้อมูล
│   ├── meeting_room_db.sql     # ฐานข้อมูลหลัก
│   ├── add_room_color.sql      # อัพเดทสีห้อง
│   ├── password_reset_system.sql # ระบบรีเซ็ตรหัสผ่าน
│   └── update_to_color_edition.sql
├── 📁 assets/                   # ไฟล์ Static
│   └── images/
│       └── logo.png
├── 📁 vendor/                   # Dependencies
├── 🌐 หน้าเว็บหลัก
├── index.php                   # หน้าแรก + Admin Dashboard
├── login.php                   # เข้าสู่ระบบ
├── logout.php                  # ออกจากระบบ
├── booking.php                 # จองห้องประชุม
├── calendar.php                # ปฏิทินการจอง
├── public_calendar.php         # ปฏิทินสาธารณะ
├── my_bookings.php             # การจองของฉัน
├── profile.php                 # ข้อมูลส่วนตัว
├── 🔐 ระบบรักษาความปลอดภัย
├── auth.php                    # ยืนยันตัวตน
├── create_session.php          # สร้าง Session
├── forgot_password.php         # ลืมรหัสผ่าน
├── reset_password.php          # รีเซ็ตรหัสผ่าน
├── simple_forgot_password.php  # รีเซ็ตรหัสผ่านแบบง่าย
├── 🔧 API และ Utilities
├── approve_booking.php         # อนุมัติการจอง
├── cancel_booking.php          # ยกเลิกการจอง
├── check_availability.php      # ตรวจสอบความว่าง
├── version.php                 # ข้อมูลเวอร์ชัน
├── version_info.php            # แสดงข้อมูลเวอร์ชัน
├── config.php                  # การตั้งค่าหลัก
├── 📄 Files อื่นๆ
├── composer.json               # PHP Dependencies
├── composer.lock
├── LICENSE                     # สัญญาอนุญาต
└── README.md                   # คู่มือหลัก
```

### 🎯 **ประโยชน์ของโครงสร้างใหม่**
- ✅ **แยกหมวดหมู่ชัดเจน**: ไฟล์จัดกลุ่มตามการใช้งาน
- ✅ **ค้นหาง่าย**: รู้ว่าไฟล์อยู่ที่ไหน
- ✅ **บำรุงรักษาง่าย**: แก้ไขไฟล์ในหมวดที่เกี่ยวข้อง
- ✅ **ความปลอดภัย**: แยกไฟล์ admin และ public
- ✅ **เอกสารครบถ้วน**: มี README ในทุกโฟลเดอร์

## การแจ้งเตือนผ่าน Telegram

ระบบจะส่งการแจ้งเตือนไปยัง Telegram ในกรณีต่อไปนี้:
- การส่งรหัส 2FA
- การเข้า/ออกจากระบบ
- การจองห้องประชุมใหม่
- การอนุมัติ/ไม่อนุมัติการจอง

## การปรับแต่ง

### เปลี่ยนธีม
แก้ไขไฟล์ HTML โดยเปลี่ยน `data-theme` attribute:
```html
<html lang="th" data-theme="dark">
```

### เพิ่มห้องประชุม
เข้าสู่เมนู "จัดการห้องประชุม" และกรอกข้อมูลห้องใหม่

### ตั้งค่าเวลาทำการ
แก้ไขในตาราง `system_settings`:
- `booking_start_time`: เวลาเริ่มต้น
- `booking_end_time`: เวลาสิ้นสุด

## การแก้ไขปัญหา

### 1. ไม่สามารถเข้าสู่ระบบได้
- ตรวจสอบการเชื่อมต่อฐานข้อมูล
- ตรวจสอบ Telegram Bot Token

### 2. ไม่ได้รับแจ้งเตือน Telegram
- ตรวจสอบ TELEGRAM_TOKEN และ CHAT_ID
- ตรวจสอบการเชื่อมต่ออินเทอร์เน็ต

### 3. ข้อผิดพลาดในการส่งออกไฟล์
- ตรวจสอบว่าได้ติดตั้ง Composer dependencies แล้ว
- ตรวจสอบสิทธิ์การเขียนไฟล์

## การพัฒนาต่อ

### เพิ่มโมดูลใหม่
1. สร้างไฟล์ PHP ใหม่
2. เพิ่มเมนูใน Navigation
3. ตั้งค่าสิทธิ์การเข้าถึง

### การปรับปรุงฐานข้อมูล
1. สร้างไฟล์ migration
2. อัพเดทโครงสร้างตาราง
3. ทดสอบการทำงาน

## 🚀 การติดตั้งและอัพเดท

### 📋 ข้อกำหนดระบบ Color Edition Pro
- PHP 7.4+ 
- MySQL/MariaDB
- Web Server (Apache/Nginx)
- Extension: PDO, GD, cURL

### 2. การติดตั้ง
1. Clone หรือ Download โค้ด
2. สร้างฐานข้อมูล `meeting_room_db`
3. Import ไฟล์ `database/meeting_room_db.sql`
4. แก้ไขไฟล์ `config/database.php` ตามการตั้งค่าของคุณ
5. แก้ไขไฟล์ `config.php` ใส่ข้อมูลองค์กร

### 3. บัญชีผู้ใช้เริ่มต้น
- **Username**: `admin`
- **Password**: `admin123`
- ⚠️ **กรุณาเปลี่ยนรหัสผ่านทันทีหลังเข้าสู่ระบบ**

### 4. 🎨 การอัพเดทไปเวอร์ชัน 2.2 Color Edition Pro

#### สำหรับผู้ใช้ที่มีระบบเดิมอยู่แล้ว:
```sql
-- เพิ่มคอลัมน์สีห้อง
ALTER TABLE rooms ADD COLUMN room_color VARCHAR(7) DEFAULT '#3b82f6';

-- อัพเดทสีเริ่มต้น
UPDATE rooms SET room_color = '#ef4444' WHERE room_id = 1; -- แดง
UPDATE rooms SET room_color = '#10b981' WHERE room_id = 2; -- เขียว
UPDATE rooms SET room_color = '#f59e0b' WHERE room_id = 3; -- เหลือง
UPDATE rooms SET room_color = '#8b5cf6' WHERE room_id = 4; -- ม่วง

-- เพิ่มการตั้งค่าใหม่
INSERT INTO system_settings (setting_key, setting_value, description) VALUES
('room_color_enabled', '1', 'เปิดใช้งานระบบสีห้องประชุม'),
('public_calendar_enabled', '1', 'เปิดใช้งานปฏิทินสาธารณะ');
```

#### การใช้งานฟีเจอร์ใหม่:
1. **จัดการสีห้อง:** Admin → จัดการห้องประชุม → เลือกสี
2. **ปฏิทินสาธารณะ:** เข้าผ่าน `public_calendar.php`
3. **ดู Tooltip:** เอาเมาส์ชี้ที่การจองในปฏิทิน

### 5. การตั้งค่า Telegram (ไม่บังคับ)
1. สร้าง Bot ผ่าน @BotFather
2. หา Chat ID 
3. แก้ไขไฟล์ `includes/functions.php` และ `config.php`
4. ทดสอบการส่งข้อความ

### 5. ขั้นตอนหลังติดตั้ง
1. เข้าสู่ระบบด้วยบัญชี admin
2. เปลี่ยนรหัสผ่าน
3. เพิ่มห้องประชุม
4. สร้างบัญชีผู้ใช้
5. ตั้งค่า Telegram (ถ้าต้องการ)

## สนับสนุนและติดต่อ

พัฒนาโดย: นายทศพล อุทก  
ตำแหน่ง: นักวิชาการคอมพิวเตอร์ชำนาญการ  
หน่วยงาน: โรงพยาบาลร้อยเอ็ด  
Email: thodspon.u@kkumail.com

## License

MIT License - ใช้งานได้อย่างอิสระ