# 📝 Changelog - ประวัติการเปลี่ยนแปลง

## [2.2.0] Color Edition Pro - 2025-09-26

### 🎨 New Features
- **Room Color System** - ระบบสีห้องประชุม
  - เลือกสีประจำแต่ละห้องผ่าน Color Picker
  - สีเริ่มต้น: แดง, เขียว, เหลือง, ม่วง, น้ำเงิน
  - จัดเก็บในฐานข้อมูลเป็น Hex Code (#RRGGBB)

- **Public Calendar** - ปฏิทินสาธารณะ
  - ดูการจองได้โดยไม่ต้อง Login
  - Auto-refresh ทุก 5 นาที
  - แสดงเวลาปัจจุบันแบบเรียลไทม์

- **Enhanced Tooltips** - Tooltip แบบ Custom
  - แสดงข้อมูล 7 หมวด: ห้อง, เวลา, ผู้จอง, แผนก, วัตถุประสงค์, จำนวนคน, สถานะ
  - เวลาปัจจุบันอัพเดททุกวินาที
  - สถานะการใช้งาน: เริ่มใน, กำลังใช้งาน, เสร็จสิ้นแล้ว
  - รองรับมือถือ (แตะเพื่อดู)

- **Visual Status Indicators** - สัญลักษณ์สถานะ
  - 🟢 อนุมัติแล้ว: สีห้อง + วงกลมเขียว
  - 🟡 รออนุมัติ: เส้นประ + วงกลมเหลืองกระพริบ
  - 🔴 กำลังใช้งาน: วงกลมแดงกระพริบ
  - ⚪ เสร็จสิ้น/ไม่อนุมัติ: วงกลมเทา

### 🔧 Improvements
- ปรับปรุง UI/UX ให้สวยงามและใช้งานง่าย
- เพิ่ม Hover Effects และ Animation
- ปรับปรุงการแสดงผลบน Mobile
- เพิ่มความชัดเจนของสถานะการจอง
- ปรับปรุงประสิทธิภาพการแสดงผลปฏิทิน

### 🗄️ Database Changes
- เพิ่มคอลัมน์ `room_color VARCHAR(7) DEFAULT '#3b82f6'` ในตาราง rooms
- เพิ่มการตั้งค่า: `room_color_enabled`, `public_calendar_enabled`
- สร้าง View: `calendar_view`, `room_usage_stats`

### 📁 New Files
- `public_calendar.php` - หน้าปฏิทินสาธารณะ
- `database/add_room_color.sql` - สคริปต์เพิ่มสีห้อง
- `database/update_to_color_edition.sql` - สคริปต์อัพเดทครบวงจร

---

## [2.1.0] Team Edition - 2025-09-26

### ✨ New Features
- Dynamic Organization Configuration System
- Individual Telegram Settings for Users
- Enhanced 2FA with MySQL Timestamp
- Organization Configuration Management
- Team Information Display

### 🔧 Improvements
- Fixed Timezone Mismatch between MySQL and PHP
- Enhanced Session and Authentication Management
- Database Structure Improvements
- Better Error Handling and Debug System

### 🐛 Bug Fixes
- Fixed 2FA codes expiring prematurely
- Fixed missing getTelegramConfig() function
- Fixed organization name display issues
- Fixed config file inclusion problems

---

## [2.0.0] Enhanced Edition - 2025-09-26

### ✨ New Features
- Thai Language Support in PDF Export
- Enhanced 2FA with System Information
- Backup PDF Export System
- TCPDF Font Management
- HTML-to-PDF Alternative
- System-wide UTF-8 Encoding

### 🔧 Improvements
- PDF Export Performance
- System Stability
- Error Handling
- Response Time
- User Interface

### 🐛 Bug Fixes
- Fixed Syntax Errors in reports.php
- Fixed TCPDF Font Definition Issues
- Fixed Thai Character Display (�������к�)
- Fixed Management Buttons in My Bookings
- Fixed 2FA Message Missing System Info

---

## [1.0.0] Initial Release - 2025-09-26

### ✨ Initial Features
- Basic Meeting Room Booking System
- User Authentication System
- Booking Approval System
- Calendar View
- Usage Reports
- User Management
- Room Management
- Basic Telegram Notifications

---

## 🏗️ Development Team

**พัฒนาโดย:** นายทศพล อุทก  
**ตำแหน่ง:** นักวิชาการคอมพิวเตอร์ชำนาญการ  
**หน่วยงาน:** โรงพยาบาลร้อยเอ็ด  
**ทีม:** Roi-et Digital Health Team  

---

## 📊 Version Naming Convention

- **x.0** = Major Release (ฟีเจอร์ใหม่หลัก)
- **x.1** = Minor Release (ปรับปรุง/เพิ่มฟีเจอร์)
- **x.2** = Enhancement Release (ปรับปรุงใหญ่)
- **x.x.x** = Patch Release (แก้ไขบัค)

## 🎯 Roadmap

### Version 2.3 (Planning)
- [ ] Advanced Analytics Dashboard
- [ ] Email Notification System
- [ ] Resource Management
- [ ] Multi-language Support
- [ ] API Integration

### Version 3.0 (Future)
- [ ] Progressive Web App (PWA)
- [ ] Advanced Reporting
- [ ] Integration with External Calendar
- [ ] Advanced User Permissions