# Git Commands for Upload - Meeting Room Booking System v2.2

## ขั้นตอนการอัพโหลดไป GitHub

### 1. เตรียมไฟล์
```bash
# ตรวจสอบสถานะไฟล์
git status

# เพิ่มไฟล์ทั้งหมด (ยกเว้นไฟล์ใน .gitignore)
git add .

# ตรวจสอบไฟล์ที่จะ commit
git status
```

### 2. Commit การเปลี่ยนแปลง
```bash
# Commit ด้วยข้อความที่ชัดเจน
git commit -m "🎨 Release v2.2 Color Edition Pro

✨ New Features:
- Room Color System with Color Picker
- Public Calendar (non-authenticated access)  
- Enhanced Tooltips with real-time data
- Visual Status Indicators (4 states)
- Auto-refresh calendar every 5 minutes

🔧 Improvements:
- Better UI/UX with animations
- Mobile-friendly tooltips
- Real-time status updates
- Memory leak prevention
- Enhanced database structure

🗄️ Database Updates:
- Added room_color column
- New system settings
- Updated sample data with colors

📁 File Updates:
- Updated .gitignore for sensitive files
- Created .example files for configuration
- Updated documentation (README, CHANGELOG, INSTALL)
- Version bumped to 2.2 Color Edition Pro"
```

### 3. อัพโหลดไป GitHub
```bash
# Push ไป main branch
git push origin main

# ตรวจสอบการอัพโหลด
git log --oneline -5
```

## ไฟล์ที่จะถูกอัพโหลด

### ✅ ไฟล์หลักของระบบ
- index.php
- login.php, logout.php, auth.php
- booking.php, my_bookings.php, cancel_booking.php
- calendar.php, **public_calendar.php (ใหม่)**
- rooms.php, users.php, reports.php
- profile.php, version.php, version_info.php
- organization_config.php, update_organization.php
- telegram_settings.php, test_telegram.php

### ✅ ไฟล์ฐานข้อมูล
- database/meeting_room_db.sql
- **database/add_room_color.sql (ใหม่)**
- **database/update_to_color_edition.sql (ใหม่)**
- **database/README_UPDATE.md (ใหม่)**

### ✅ ไฟล์การตั้งค่าตัวอย่าง
- config/database.php.example
- **includes/functions.php.example (ใหม่)**
- config/telegram_users.json.example

### ✅ เอกสารประกอบ
- README.md (อัพเดทใหม่)
- **CHANGELOG.md (ใหม่)**
- INSTALL.md
- LICENSE
- ALMA9_PERMISSIONS.md
- CLEANUP_LOG.md
- GITHUB_UPLOAD_GUIDE.md
- ORGANIZATION_SETUP.md
- TELEGRAM_GUIDE.md

### ✅ ไฟล์อื่นๆ
- composer.json, composer.lock
- assets/images/logo.png
- vendor/ (dependencies)
- setup_permissions.sh

## ❌ ไฟล์ที่จะถูกเก็บเป็นความลับ (.gitignore)

- **config/database.php** - ข้อมูลฐานข้อมูลจริง
- **includes/functions.php** - ข้อมูลองค์กรและ Telegram
- config/telegram_users.json - ข้อมูล Telegram ผู้ใช้
- *.log - ไฟล์ log
- uploads/ - ไฟล์ที่อัพโหลด
- .env - ไฟล์ environment

## 📋 Checklist ก่อนอัพโหลด

- [ ] ตรวจสอบว่าไฟล์ sensitive ถูก ignore แล้ว
- [ ] ตรวจสอบไฟล์ .example ทุกไฟล์
- [ ] อัพเดท README.md และ CHANGELOG.md
- [ ] ตรวจสอบเวอร์ชันใน version.php
- [ ] ทดสอบระบบในโหมด production
- [ ] ลบข้อมูลทดสอบออกจาก database

## 🏷️ Git Tags (สำหรับ Release)

```bash
# สร้าง tag สำหรับเวอร์ชันใหม่
git tag -a v2.2.0 -m "Release v2.2.0 Color Edition Pro"

# Push tag ไป GitHub
git push origin v2.2.0

# ดู tags ทั้งหมด
git tag -l
```

## 🌐 GitHub Repository Settings

### Branch Protection (แนะนำ)
- Protect main branch
- Require pull request reviews
- Require status checks to pass

### Release Notes Template
```markdown
## 🎨 Color Edition Pro v2.2.0

### ✨ New Features
- Room Color System
- Public Calendar  
- Enhanced Tooltips
- Visual Status Indicators

### 🔧 Improvements
- Better UI/UX
- Mobile Support
- Real-time Updates

### 📥 Installation
See [INSTALL.md](INSTALL.md) for detailed instructions.

### 🆙 Upgrade from v2.1
```sql
ALTER TABLE rooms ADD COLUMN room_color VARCHAR(7) DEFAULT '#3b82f6';
```
```

---

**พัฒนาโดย:** Roi-et Digital Health Team  
**เวอร์ชัน:** 2.2 Color Edition Pro  
**วันที่:** 26 กันยายน 2568