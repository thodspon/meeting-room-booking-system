# 🏢 SMD Meeting Room Booking System v2.6

ระบบจองห้องประชุมออนไลน์ที่ทันสมัย พร้อมระบบแจ้งเตือน Telegram แบบส่วนบุคคล สำหรับองค์กรขนาดเล็กถึงกลาง

[![Version](https://img.shields.io/badge/version-2.6.0-blue.svg)](https://github.com/yourusername/smdmeeting_room/releases)
[![PHP](https://img.shields.io/badge/PHP-7.2+-green.svg)](https://php.net)
[![MySQL](https://img.shields.io/badge/MySQL-5.7+-orange.svg)](https://mysql.com)
[![License](https://img.shields.io/badge/license-MIT-red.svg)](LICENSE)

## 🌟 คุณสมบัติเด่น Version 2.6

### 🚀 ใหม่! Individual User Telegram System
- 📱 **การตั้งค่า Telegram แบบส่วนบุคคล** - แต่ละคนมี Bot Token และ Chat ID ของตัวเอง
- 🔐 **ความปลอดภัยสูง** - ไม่ต้องแชร์ข้อมูล Telegram กับคนอื่น
- 🎯 **แจ้งเตือนตรงเป้า** - รับข้อความเฉพาะที่เกี่ยวข้องกับตัวเอง
- 🔄 **Dual Routing System** - ใช้ Telegram ส่วนตัวก่อน ถ้าไม่มีจึงใช้ระบบสำรอง

### 🏢 ระบบจองห้องประชุม
- 📅 **จองออนไลน์** - จองห้องได้ตลอด 24 ชั่วโมง
- ⚡ **Real-time Calendar** - ดูความว่างแบบเรียลไทม์
- 👥 **ระบบอนุมัติ** - Manager อนุมัติ/ปฏิเสธการจอง
- 📱 **แจ้งเตือนทันที** - Telegram แจ้งทุกขั้นตอน
- 🎨 **Color-coded Rooms** - แยกสีห้องให้ง่ายต่อการมอง

### 👨‍💼 การจัดการขั้นสูง
- 📊 **รายงานการใช้งาน** - สถิติและการวิเคราะห์
- 📱 **Reports via Telegram** - ส่งรายงานผ่าน Telegram
- 🔧 **Debug Tools** - เครื่องมือตรวจสอบระบบ
- 👥 **จัดการผู้ใช้** - เพิ่ม/ลบ/แก้ไขผู้ใช้
- 🏢 **จัดการห้องประชุม** - ตั้งค่าห้องและอุปกรณ์

### 🛡️ ความปลอดภัยและการเข้าถึง
- 🔐 **2FA Authentication** - ยืนยันตัวตนด้วย Telegram
- 🎭 **Role-based Access** - Admin, Manager, User
- 🔒 **Secure Password** - เข้ารหัสรหัสผ่าน
- 📋 **Audit Trail** - บันทึกการทำงานของระบบ

## 📸 ภาพหน้าจอระบบ

### หน้าจอหลัก
![Main Dashboard](docs/images/dashboard.png)

### การจองห้อง
![Booking System](docs/images/booking.png)

### ระบบ Telegram
![Telegram Integration](docs/images/telegram.png)

## 🚀 การติดตั้งด่วน

### 1️⃣ ความต้องการของระบบ
- **PHP:** 7.2 หรือสูงกว่า
- **Database:** MySQL 5.7+ หรือ MariaDB 10.3+
- **Web Server:** Apache 2.4+ หรือ Nginx 1.18+
- **Extensions:** mysqli, json, curl, mbstring

### 2️⃣ ติดตั้งระบบ
```bash
# Clone repository
git clone https://github.com/yourusername/smdmeeting_room.git

# เข้าไปในโฟลเดอร์
cd smdmeeting_room

# คัดลอกไฟล์ตัวอย่าง
cp config/database.php.example config/database.php
cp includes/functions.php.example includes/functions.php

# Import database
mysql -u root -p < database/meeting_room_db.sql
mysql -u root -p < database/password_reset_system.sql
```

### 3️⃣ ตั้งค่าฐานข้อมูล
```php
// config/database.php
$servername = "localhost";
$username = "your_db_user";
$password = "your_db_password";
$dbname = "meeting_room_db";
```

### 4️⃣ เข้าใช้งาน
```
URL: http://yourdomain.com/meeting_room/
Admin: admin / admin123 (เปลี่ยนรหัสผ่านทันทีหลังติดตั้ง)
```

## 📚 คู่มือการใช้งาน

### 👤 สำหรับผู้ใช้ทั่วไป
- 📖 **[คู่มือผู้ใช้](docs/guides/USER_MANUAL.md)** - วิธีจองห้อง, ตั้งค่า Telegram, แก้ไขปัญหา

### 👨‍💼 สำหรับ Manager
- 📊 **[คู่มือ Manager](docs/guides/MANAGER_MANUAL.md)** - การอนุมัติ, รายงาน, จัดการห้อง

### 🔧 สำหรับ Admin
- ⚙️ **[คู่มือ Admin](docs/guides/ADMIN_MANUAL.md)** - การตั้งค่าระบบ, จัดการผู้ใช้, Debug Tools

### 🆘 แก้ไขปัญหา
- 🔍 **[Troubleshooting Guide](docs/guides/TROUBLESHOOTING.md)** - แก้ไขปัญหาด่วน
- 🚀 **[Installation Guide](docs/installation/INSTALLATION_GUIDE.md)** - คู่มือติดตั้งละเอียด

## 🎯 สิทธิ์การใช้งาน

| ฟีเจอร์ | User | Manager | Admin |
|---------|------|---------|-------|
| จองห้องประชุม | ✅ | ✅ | ✅ |
| ดูปฏิทินสาธารณะ | ✅ | ✅ | ✅ |
| อนุมัติการจอง | ❌ | ✅ | ✅ |
| ดูรายงาน | ❌ | ✅ | ✅ |
| ส่ง Reports ผ่าน Telegram | ❌ | ✅ | ✅ |
| จัดการผู้ใช้ | ❌ | ❌ | ✅ |
| จัดการห้องประชุม | ❌ | ✅ | ✅ |
| Debug Tools | ❌ | ❌ | ✅ |
| ตั้งค่าระบบ | ❌ | ❌ | ✅ |

## 📱 การตั้งค่า Telegram Bot

### 1️⃣ สร้าง Bot ใหม่
```
1. หา @BotFather ใน Telegram
2. ส่งคำสั่ง: /newbot
3. ตั้งชื่อ Bot และ Username
4. เก็บ Token ที่ได้รับ
```

### 2️⃣ หา Chat ID
```
1. ส่งข้อความให้ Bot
2. เปิด: https://api.telegram.org/bot[TOKEN]/getUpdates
3. หา "chat":{"id": ในผลลัพธ์
4. เก็บตัวเลข Chat ID
```

### 3️⃣ ตั้งค่าในโปรไฟล์
```
1. เข้า "โปรไฟล์ของฉัน"
2. ใส่ Bot Token และ Chat ID
3. เปิด "เปิดใช้งาน Telegram"
4. บันทึก และทดสอบ
```

## 🔄 การอัปเดต

### Version 2.6.0 (29 กันยายน 2568)
- ✨ **NEW:** Individual User Telegram System
- 🚀 **NEW:** Enhanced Manager Permissions for Reports
- 🔧 **IMPROVED:** Navigation System with Role-based Menus
- 📱 **IMPROVED:** Dual Routing Telegram System
- 🐛 **FIXED:** PHP 7.2 Compatibility Issues
- 📚 **ADDED:** Comprehensive Documentation Suite

### รุ่นก่อนหน้า
- 📋 **[ดู Changelog ทั้งหมด](docs/changelog/CHANGELOG.md)**

## 🔧 การพัฒนาและ Contribution

### ค่าใช้จ่าย
- 💰 **ฟรี 100%** - Open Source Software
- 🔧 **ไม่มีค่าบำรุงรักษา** - รันเองบนเซิร์ฟเวอร์ของตัวเอง
- 📱 **Telegram ฟรี** - ใช้ API ฟรีของ Telegram

### การร่วมพัฒนา
```bash
# Fork repository
git fork https://github.com/yourusername/smdmeeting_room

# Clone fork ของคุณ
git clone https://github.com/yourusername/smdmeeting_room

# สร้าง feature branch
git checkout -b feature/amazing-feature

# Commit changes
git commit -m 'Add amazing feature'

# Push และสร้าง Pull Request
git push origin feature/amazing-feature
```

## 🐛 รายงานปัญหา

หากพบปัญหาการใช้งาน:

1. 🔍 **ค้นหาใน [Issues](https://github.com/yourusername/smdmeeting_room/issues)** ก่อน
2. 📝 **สร้าง Issue ใหม่** พร้อมข้อมูล:
   - เวอร์ชัน PHP และ MySQL
   - ขั้นตอนการทำซ้ำปัญหา
   - ภาพหน้าจอ (ถ้ามี)
   - Error messages

## 📞 การติดต่อ

**ผู้พัฒนา:**
- 👨‍💻 **นายทศพล อุทก**
- 🏥 **โรงพยาบาลร้อยเอ็ด**
- 💼 **นักวิชาการคอมพิวเตอร์ชำนาญการ**

**ช่องทางติดต่อ:**
- 🌐 **GitHub:** [Issues & Discussions](https://github.com/yourusername/smdmeeting_room)
- 📧 **Email:** support@hospital.go.th
- 📱 **Telegram:** @hospital_support

## 📄 License

โปรเจคนี้เผยแพร่ภายใต้ [MIT License](LICENSE) - ดูรายละเอียดในไฟล์ LICENSE

## 🙏 Credits

### เทคโนโลยีที่ใช้
- **PHP** - ภาษาพัฒนาหลัก
- **MySQL** - ระบบฐานข้อมูล
- **Bootstrap** - UI Framework
- **Telegram Bot API** - ระบบแจ้งเตือน
- **jQuery** - JavaScript Library

### แรงบันดาลใจ
- 🏥 **โรงพยาบาลร้อยเอ็ด** - ผู้ให้การสนับสนุน
- 👥 **ทีมงานไอที** - ผู้ทดสอบและให้คำแนะนำ
- 🌐 **Open Source Community** - แรงบันดาลใจในการแชร์

---

### 🌟 ถ้าโปรเจคนี้มีประโยชน์ โปรด Star Repository! ⭐

**📝 อัปเดตล่าสุด:** 29 กันยายน 2568 (Version 2.6.0)  
**🔗 GitHub:** [SMD Meeting Room Booking System](https://github.com/yourusername/smdmeeting_room)