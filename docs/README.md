# 📚 SMD Meeting Room Booking System v2.6 - เอกสารทั้งหมด

ยินดีต้อนรับสู่ระบบจองห้องประชุม SMD เวอร์ชัน 2.6 พร้อมระบบ Individual User Telegram System ที่ทันสมัย

## 🎯 เริ่มต้นด่วน - Quick Start

### � สำหรับผู้ใช้งานใหม่
1. 📖 **[คู่มือผู้ใช้](guides/USER_MANUAL.md)** - เริ่มต้นการใช้งานระบบ
2. 🆘 **[แก้ไขปัญหาด่วน](guides/TROUBLESHOOTING.md)** - แก้ปัญหาเบื้องต้น
3. 📱 **[การตั้งค่า Telegram](guides/TELEGRAM_GUIDE.md)** - ตั้งค่าแจ้งเตือน

### 👨‍💼 สำหรับ Manager
1. 📊 **[คู่มือ Manager](guides/MANAGER_MANUAL.md)** - การอนุมัติและรายงาน
2. 📱 **[Telegram Reports](guides/TELEGRAM_GUIDE.md#manager-features)** - ส่งรายงานผ่าน Telegram

### � สำหรับ Admin
1. ⚙️ **[คู่มือ Admin](guides/ADMIN_MANUAL.md)** - การจัดการระบบเต็มรูปแบบ
2. 🚀 **[คู่มือติดตั้ง](installation/INSTALLATION_GUIDE.md)** - ติดตั้งระบบใหม่
3. 🔍 **[Debug Tools](guides/ADMIN_MANUAL.md#debug-tools)** - เครื่องมือตรวจสอบระบบ

---

## 📋 เอกสารครบชุด

### 📚 คู่มือการใช้งาน (User Guides)
- 👤 **[USER_MANUAL.md](guides/USER_MANUAL.md)**
  - การเข้าสู่ระบบและ 2FA
  - การจองห้องประชุม
  - การตั้งค่า Telegram ส่วนบุคคล
  - การจัดการการจองของตนเอง
  - การแก้ไขปัญหาเบื้องต้น

- 👨‍� **[MANAGER_MANUAL.md](guides/MANAGER_MANUAL.md)**
  - การอนุมัติ/ปฏิเสธการจอง
  - การดูและส่งรายงาน
  - การใช้ Reports ผ่าน Telegram
  - การจัดการห้องประชุม (บางส่วน)
  - Dashboard สำหรับ Manager

- 🔧 **[ADMIN_MANUAL.md](guides/ADMIN_MANUAL.md)**
  - การจัดการผู้ใช้ทั้งหมด
  - การจัดการห้องประชุม
  - การตั้งค่าระบบ Telegram
  - Debug Tools และ Monitoring
  - การบำรุงรักษาระบบ
  - Security และ Backup

### 🆘 การแก้ไขปัญหา (Troubleshooting)
- 🔍 **[TROUBLESHOOTING.md](guides/TROUBLESHOOTING.md)**
  - ปัญหาการเข้าสู่ระบบ
  - ปัญหา Telegram ไม่ทำงาน
  - ปัญหาการจองห้อง
  - ปัญหาการแสดงผล
  - การติดต่อขอความช่วยเหลือ

### 🚀 การติดตั้งและตั้งค่า (Installation & Setup)
- 📥 **[INSTALLATION_GUIDE.md](installation/INSTALLATION_GUIDE.md)**
  - ความต้องการของระบบ
  - ขั้นตอนการติดตั้งเต็มรูปแบบ
  - การตั้งค่าฐานข้อมูล
  - การตั้งค่า Web Server
  - การ Config ระบบความปลอดภัย
  - การ Backup และ Restore

### 📱 การตั้งค่า Telegram (เก่า - อ้างอิง)
- 🤖 **[TELEGRAM_GUIDE.md](guides/TELEGRAM_GUIDE.md)**
  - ระบบ Telegram เดิม (แบบส่วนกลาง)
  - การสร้าง Bot
  - การหา Chat ID
  - การตั้งค่าในระบบ

### � การอัปเดตและเปลี่ยนแปลง (Updates & Changes)
- 📋 **[CHANGELOG.md](changelog/CHANGELOG.md)**
  - ประวัติการอัปเดตทั้งหมด
  - รายการฟีเจอร์ใหม่
  - การแก้ไขข้อผิดพลาด
  - Breaking Changes

- 🆕 **[RELEASE_NOTES_v2.6.md](../RELEASE_NOTES_v2.6.md)**
  - สรุปคุณสมบัติใหม่ใน v2.6
  - Individual User Telegram System
  - Enhanced Manager Permissions
  - การปรับปรุงทั้งหมด

### 🔧 การบำรุงรักษา (Maintenance)
- 🧹 **[CLEANUP_LOG.md](CLEANUP_LOG.md)**
  - การจัดระเบียบไฟล์
  - การย้ายเอกสาร
  - การลบไฟล์ที่ไม่ใช้

- 🔨 **[FIX_LOG.md](fixes/FIX_LOG.md)**
  - บันทึกการแก้ไขปัญหา
  - การอัปเดต Compatibility
  - Performance Improvements

---

## 🌟 ฟีเจอร์เด่นของ Version 2.6

### 🚀 Individual User Telegram System
ระบบแจ้งเตือน Telegram แบบส่วนบุคคล - นวัตกรรมใหม่ที่ให้ผู้ใช้แต่ละคนตั้งค่า Telegram Bot ของตัวเองได้

**ประโยชน์:**
- 🔐 **ปลอดภัยกว่า** - ไม่ต้องแชร์ข้อมูล Telegram
- 🎯 **แจ้งเตือนตรงเป้า** - รับข้อความเฉพาะที่เกี่ยวข้อง
- ⚡ **ตั้งค่าง่าย** - ตั้งค่าใน Profile ของตนเอง
- 🔄 **Dual Routing** - มีระบบสำรองเมื่อไม่ได้ตั้งค่า

### 🎯 Enhanced Manager Permissions
Manager สามารถใช้งาน Reports Telegram ได้แล้ว - ไม่ใช่เฉพาะ Admin อีกต่อไป

**ฟีเจอร์ใหม่:**
- 📊 **Manager Reports Access** - ดูรายงานทั้งหมด
- 📱 **Telegram Reports** - ส่งรายงานผ่าน Telegram
- 🏢 **Room Management** - จัดการห้องประชุมบางส่วน
- 📈 **Analytics Dashboard** - ดูสถิติการใช้งาน

### � การปรับปรุงระบบ
- 🌐 **Navigation ใหม่** - เมนูตามสิทธิ์การใช้งาน
- 🔄 **Dual Routing** - ระบบเส้นทางคู่สำหรับ Telegram
- 📱 **PHP 7.2 Compatible** - รองรับ PHP เวอร์ชันเก่า
- 📚 **Documentation** - เอกสารครบถ้วนสมบูรณ์

---

## 📊 การใช้งานตามสิทธิ์

| ฟีเจอร์ | User | Manager | Admin |
|---------|:----:|:-------:|:-----:|
| **การจองพื้นฐาน** ||||
| จองห้องประชุม | ✅ | ✅ | ✅ |
| ดูปฏิทินสาธารณะ | ✅ | ✅ | ✅ |
| จัดการการจองตนเอง | ✅ | ✅ | ✅ |
| ตั้งค่า Telegram ส่วนตัว | ✅ | ✅ | ✅ |
| **การอนุมัติและรายงาน** ||||
| อนุมัติ/ปฏิเสธการจอง | ❌ | ✅ | ✅ |
| ดูรายงานการใช้งาน | ❌ | ✅ | ✅ |
| ส่ง Reports ผ่าน Telegram | ❌ | ✅ | ✅ |
| Export ข้อมูล Excel/PDF | ❌ | ✅ | ✅ |
| **การจัดการระบบ** ||||
| จัดการผู้ใช้ | ❌ | ❌ | ✅ |
| จัดการห้องประชุม | ❌ | ✅¹ | ✅ |
| ตั้งค่าระบบ | ❌ | ❌ | ✅ |
| Debug Tools | ❌ | ❌ | ✅ |

¹ Manager สามารถจัดการห้องประชุมได้บางส่วน (แก้ไขข้อมูล, ปิด-เปิดห้อง)

---

## 🎯 การเลือกคู่มือที่เหมาะสม

### 🏃‍♂️ ต้องการเริ่มใช้งานเร็วๆ
1. **[คู่มือผู้ใช้](guides/USER_MANUAL.md#quick-start)** - เรียนรู้การจองห้องใน 5 นาที
2. **[การตั้งค่า Telegram](guides/USER_MANUAL.md#telegram-setup)** - ตั้งค่าแจ้งเตือนส่วนตัว

### 🔧 ต้องการติดตั้งระบบใหม่
1. **[คู่มือติดตั้ง](installation/INSTALLATION_GUIDE.md)** - ติดตั้งเต็มรูปแบบ
2. **[คู่มือ Admin](guides/ADMIN_MANUAL.md#initial-setup)** - ตั้งค่าเริ่มต้น

### 🆘 พบปัญหาการใช้งาน
1. **[แก้ไขปัญหาด่วน](guides/TROUBLESHOOTING.md)** - แก้ปัญหาทันที
2. **[การติดต่อ Support](guides/TROUBLESHOOTING.md#contact-support)** - ขอความช่วยเหลือ

### � ต้องการใช้งาน Manager
1. **[คู่มือ Manager](guides/MANAGER_MANUAL.md#approval-workflow)** - การอนุมัติการจอง
2. **[Reports System](guides/MANAGER_MANUAL.md#reports-system)** - ดูและส่งรายงาน

### 🔐 ต้องการจัดการระบบ
1. **[คู่มือ Admin](guides/ADMIN_MANUAL.md#user-management)** - จัดการผู้ใช้
2. **[Debug Tools](guides/ADMIN_MANUAL.md#debug-tools)** - เครื่องมือวิเคราะห์

---

## � การติดต่อและการสนับสนุน

### 👨‍💻 ผู้พัฒนา
- **ชื่อ:** นายทศพล อุทก
- **หน่วยงาน:** โรงพยาบาลร้อยเอ็ด
- **ตำแหน่ง:** นักวิชาการคอมพิวเตอร์ชำนาญการ

### 🌐 ช่องทางติดต่อ
- **GitHub Issues:** [รายงานปัญหา](https://github.com/yourusername/smdmeeting_room/issues)
- **GitHub Discussions:** [สอบถามและแลกเปลี่ยน](https://github.com/yourusername/smdmeeting_room/discussions)
- **Email:** support@hospital.go.th

### 🆘 การขอความช่วยเหลือ
1. **ตรวจสอบ [Troubleshooting](guides/TROUBLESHOOTING.md) ก่อน**
2. **ค้นหาใน [GitHub Issues](https://github.com/yourusername/smdmeeting_room/issues)**
3. **สร้าง Issue ใหม่พร้อมข้อมูล:**
   - เวอร์ชัน PHP และ MySQL
   - ขั้นตอนการทำซ้ำปัญหา
   - Screenshots (ถ้ามี)
   - Error messages

---

## � การอัปเดตเอกสาร

เอกสารชุดนี้จะได้รับการอัปเดตให้ทันสมัยเสมอ:

- **📅 อัปเดตล่าสุด:** 29 กันยายน 2568
- **📋 เวอร์ชัน:** 2.6.0
- **🔄 Revision:** Build 20250929

### การแจ้งการอัปเดต
- 📢 **GitHub Releases** - การอัปเดตใหญ่
- 📝 **Commit Messages** - การอัปเดตเล็กน้อย
- 📧 **Email Notifications** - สำหรับผู้ที่ติดตาม

---

**🌟 ขอบคุณที่ใช้งาน SMD Meeting Room Booking System v2.6**

*📚 หากคุณต้องการเอกสารเพิ่มเติมหรือมีคำแนะนำ โปรดติดต่อทีมพัฒนา*