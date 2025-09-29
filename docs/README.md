# 📚 Documentation Directory

โฟลเดอร์นี้ประกอบด้วยเอกสารต่างๆ ของระบบ Meeting Room Booking System

## 📁 โครงสร้างโฟลเดอร์

### 📋 **changelog/**
- `CHANGELOG.md` - บันทึกการเปลี่ยนแปลงทั้งหมด
- `CHANGELOG_v2.5.md` - บันทึกการเปลี่ยนแปลงเวอร์ชัน 2.5
- `UPDATE_v2.5.1.md` - รายละเอียดการอัปเดตเวอร์ชัน 2.5.1
- `VERSION_2.6_UPDATE.md` - รายละเอียดการอัปเดตเวอร์ชัน 2.6

### 🛠️ **installation/**
- `INSTALL.md` - คู่มือการติดตั้งระบบ
- `ALMA9_PERMISSIONS.md` - การตั้งค่าสิทธิ์บน AlmaLinux 9
- `ORGANIZATION_SETUP.md` - การตั้งค่าองค์กรเริ่มต้น

### 📖 **guides/**
- `GITHUB_UPLOAD_GUIDE.md` - คู่มือการอัปโหลดไปยัง GitHub
- `GIT_UPLOAD_GUIDE.md` - คู่มือการใช้งาน Git
- `PASSWORD_RESET_GUIDE.md` - คู่มือระบบรีเซ็ตรหัสผ่าน
- `TELEGRAM_GUIDE.md` - คู่มือการตั้งค่า Telegram Bot

### 🔧 **fixes/** (ไฟล์ที่ย้ายมาใหม่)
- `admin_navigation_fix.md` - การแก้ไขระบบ Navigation
- `ADMIN_PHP72_FIX.md` - การแก้ไข Admin สำหรับ PHP 7.2
- `COMPLETE_ADMIN_PHP72_FIX.md` - การแก้ไข Admin PHP 7.2 แบบสมบูรณ์
- `NAVIGATION_UPDATE.md` - การอัปเดตระบบ Navigation
- `USER_ACTIVITY_REPORTS_FIX.md` - การแก้ไข User Activity Reports
- `PHP72_FIX_GUIDE.md` - คู่มือแก้ไข PHP 7.2
- `PATH_MANAGEMENT.md` - การจัดการ Path
- `LOGIN_REDIRECT_SETUP.md` - การตั้งค่า Login Redirect
- `PROJECT_ORGANIZATION.md` - การจัดระเบียบโปรเจค
- `README.md` - คำอธิบายไฟล์ในโฟลเดอร์ fixes

### 📝 **logs/**
- `CLEANUP_LOG.md` - บันทึกการทำความสะอาดระบบ
- `FIX_LOG.md` - บันทึกการแก้ไขปัญหาต่างๆ

## 🎯 การใช้งานเอกสาร

### 👥 **สำหรับผู้ใช้งานใหม่**
1. อ่าน `installation/INSTALL.md` ก่อน
2. ตั้งค่าองค์กรตาม `installation/ORGANIZATION_SETUP.md`
3. ตั้งค่าสิทธิ์ตาม `installation/ALMA9_PERMISSIONS.md` (Linux)

### 🔧 **สำหรับผู้พัฒนา**
1. ศึกษา `guides/GIT_UPLOAD_GUIDE.md`
2. ดู `changelog/` สำหรับประวัติการพัฒนา
3. ใช้ `guides/GITHUB_UPLOAD_GUIDE.md` สำหรับการอัปโหลด

### 📱 **การตั้งค่า Telegram**
1. อ่าน `guides/TELEGRAM_GUIDE.md`
2. ทำตามขั้นตอนในเอกสาร
3. ทดสอบการทำงานก่อนใช้งานจริง

### 🔐 **การจัดการรหัสผ่าน**
1. ศึกษา `guides/PASSWORD_RESET_GUIDE.md`
2. ตั้งค่า Telegram Bot สำหรับรีเซ็ตรหัสผ่าน

## 📋 **หมายเหตุสำคัญ**

- เอกสารทั้งหมดเขียนเป็นภาษาไทยเพื่อความเข้าใจง่าย
- ควรอัปเดตเอกสารเมื่อมีการเปลี่ยนแปลงระบบ
- ใช้ Markdown format สำหรับความสวยงาม
- เก็บเอกสารเวอร์ชันเก่าไว้สำหรับอ้างอิง

## 🔄 **การอัปเดตเอกสาร**

เมื่อมีการเปลี่ยนแปลงระบบ:
1. อัปเดต changelog ที่เกี่ยวข้อง
2. แก้ไขคู่มือการติดตั้งหากจำเป็น
3. เพิ่มหรือแก้ไข guides ตามความเหมาะสม
4. สร้าง backup ของเอกสารเวอร์ชันเก่า