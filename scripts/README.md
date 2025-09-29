# 📁 Scripts Directory

โฟลเดอร์นี้ประกอบด้วย Scripts สำหรับการบำรุงรักษาและจัดการระบบ

## 🔧 รายการ Scripts

### 🛡️ **การจัดการสิทธิ์**
- `setup_permissions.sh` - Script สำหรับตั้งค่าสิทธิ์ไฟล์บน Linux/Unix

### 🧹 **การทำความสะอาด**
- `cleanup_password_resets.php` - ทำความสะอาดข้อมูลรีเซ็ตรหัสผ่านที่หมดอายุ

## 🚀 การใช้งาน

### 🐧 **Linux/Unix Scripts**
```bash
# ตั้งค่าสิทธิ์ไฟล์
chmod +x scripts/setup_permissions.sh
sudo ./scripts/setup_permissions.sh
```

### 🖥️ **PHP Scripts**
```bash
# ทำความสะอาดข้อมูลรีเซ็ตรหัสผ่าน
php scripts/cleanup_password_resets.php

# หรือตั้งเป็น Cron Job
# เรียกใช้ทุกวันเที่ยงคืน
0 0 * * * php /path/to/scripts/cleanup_password_resets.php
```

## ⏰ **Cron Jobs แนะนำ**

```bash
# แก้ไขไฟล์ crontab
crontab -e

# เพิ่มงานต่อไปนี้:

# ทำความสะอาดข้อมูลรีเซ็ตรหัสผ่านทุกวันเที่ยงคืน
0 0 * * * php /var/www/html/meeting-room-booking-system/scripts/cleanup_password_resets.php

# สำรองฐานข้อมูลทุกวันเวลา 02:00
0 2 * * * mysqldump -u username -p password database_name > /backup/meeting_room_$(date +\%Y\%m\%d).sql
```

## ⚠️ **คำแนะนำความปลอดภัย**

1. **ตรวจสอบสิทธิ์**: Scripts ควรมีสิทธิ์ที่เหมาะสม
2. **ทดสอบก่อน**: ทดสอบใน development environment ก่อน
3. **สำรองข้อมูล**: สำรองข้อมูลก่อนรัน scripts
4. **ตรวจสอบ Log**: ตรวจสอบ log หลังรัน scripts

## 📝 การสร้าง Script ใหม่

เมื่อสร้าง script ใหม่ ควรมี:
- คำอธิบายการใช้งานในหัวไฟล์
- การตรวจสอบ error
- การ log ผลลัพธ์
- การจัดการกับ edge cases