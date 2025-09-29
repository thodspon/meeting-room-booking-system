# คำสั่งปรับสิทธิ์ไฟล์สำหรับ AlmaLinux 9

## 🔐 คำสั่งปรับสิทธิ์พื้นฐาน

### 1. เปลี่ยนเจ้าของไฟล์ให้เป็น Apache
```bash
# เปลี่ยนเจ้าของทั้งหมดให้เป็น apache:apache
sudo chown -R apache:apache /var/www/html/meeting-room-booking-system/

# หรือหากใช้ nginx
sudo chown -R nginx:nginx /var/www/html/meeting-room-booking-system/
```

### 2. ตั้งสิทธิ์โฟลเดอร์และไฟล์
```bash
# ไปยังโฟลเดอร์โปรเจค
cd /var/www/html/meeting-room-booking-system/

# ตั้งสิทธิ์โฟลเดอร์ = 755 (อ่าน/เขียน/เข้าถึงสำหรับเจ้าของ, อ่าน/เข้าถึงสำหรับกลุ่มและอื่นๆ)
sudo find . -type d -exec chmod 755 {} \;

# ตั้งสิทธิ์ไฟล์ = 644 (อ่าน/เขียนสำหรับเจ้าของ, อ่านอย่างเดียวสำหรับกลุ่มและอื่นๆ)
sudo find . -type f -exec chmod 644 {} \;
```

### 3. ตั้งสิทธิ์พิเศษสำหรับโฟลเดอร์ที่ต้องเขียนได้
```bash
# โฟลเดอร์ config ต้องเขียนได้
sudo chmod -R 755 config/
sudo chown -R apache:apache config/

# โฟลเดอร์ assets สำหรับอัพโหลดไฟล์
sudo chmod -R 755 assets/
sudo chown -R apache:apache assets/

# โฟลเดอร์ logs (ถ้ามี)
sudo mkdir -p logs/
sudo chmod -R 755 logs/
sudo chown -R apache:apache logs/

# โฟลเดอร์ cache (ถ้ามี)
sudo mkdir -p cache/
sudo chmod -R 755 cache/
sudo chown -R apache:apache cache/
```

### 4. ตั้งสิทธิ์ไฟล์ที่สำคัญ
```bash
# ไฟล์การตั้งค่าฐานข้อมูล
sudo chmod 640 config/database.php
sudo chown apache:apache config/database.php

# ไฟล์ JSON สำหรับ Telegram
sudo chmod 644 config/telegram_users.json
sudo chown apache:apache config/telegram_users.json

# ไฟล์ composer
sudo chmod 644 composer.json composer.lock
```

## 🛡️ SELinux Configuration (สำคัญสำหรับ AlmaLinux)

### 1. ตรวจสอบสถานะ SELinux
```bash
# ตรวจสอบสถานะ SELinux
sestatus

# ตรวจสอบ context ของไฟล์
ls -Z /var/www/html/meeting-room-booking-system/
```

### 2. ตั้งค่า SELinux Context
```bash
# ตั้งค่า SELinux context สำหรับไฟล์เว็บ
sudo setsebool -P httpd_can_network_connect 1
sudo setsebool -P httpd_can_network_connect_db 1
sudo setsebool -P httpd_execmem 1

# ตั้งค่า context สำหรับโฟลเดอร์เว็บ
sudo semanage fcontext -a -t httpd_exec_t "/var/www/html/meeting-room-booking-system(/.*)?"
sudo restorecon -Rv /var/www/html/meeting-room-booking-system/

# หากต้องการให้ Apache เขียนไฟล์ได้
sudo setsebool -P httpd_unified 1
sudo chcon -R -t httpd_exec_t /var/www/html/meeting-room-booking-system/
```

### 3. ตั้งค่าพิเศษสำหรับการเขียนไฟล์
```bash
# อนุญาตให้ Apache เขียนไฟล์ใน config/
sudo semanage fcontext -a -t httpd_config_t "/var/www/html/meeting-room-booking-system/config(/.*)?"
sudo restorecon -Rv /var/www/html/meeting-room-booking-system/config/

# อนุญาตให้ Apache เขียนไฟล์ใน assets/
sudo semanage fcontext -a -t httpd_sys_rw_content_t "/var/www/html/meeting-room-booking-system/assets(/.*)?"
sudo restorecon -Rv /var/www/html/meeting-room-booking-system/assets/
```

## 🔥 Firewall Configuration

### เปิดพอร์ตสำหรับเว็บเซิร์ฟเวอร์
```bash
# เปิดพอร์ต HTTP (80)
sudo firewall-cmd --permanent --add-service=http

# เปิดพอร์ต HTTPS (443)
sudo firewall-cmd --permanent --add-service=https

# รีโหลดการตั้งค่า firewall
sudo firewall-cmd --reload

# ตรวจสอบสถานะ
sudo firewall-cmd --list-all
```

## 📂 โครงสร้างสิทธิ์ที่แนะนำ

```
meeting-room-booking-system/           (755, apache:apache)
├── index.php                          (644, apache:apache)
├── config/                            (755, apache:apache)
│   ├── database.php                   (640, apache:apache)
│   └── telegram_users.json            (644, apache:apache)
├── assets/                            (755, apache:apache)
│   └── images/                        (755, apache:apache)
├── includes/                          (755, apache:apache)
├── vendor/                            (755, apache:apache)
├── logs/                              (755, apache:apache)
└── *.php                              (644, apache:apache)
```

## 🔧 คำสั่งรวม (One-liner)

```bash
#!/bin/bash
# สคริปต์ปรับสิทธิ์แบบครบชุด

PROJECT_PATH="/var/www/html/meeting-room-booking-system"

# เปลี่ยนเจ้าของ
sudo chown -R apache:apache $PROJECT_PATH

# ตั้งสิทธิ์พื้นฐาน
sudo find $PROJECT_PATH -type d -exec chmod 755 {} \;
sudo find $PROJECT_PATH -type f -exec chmod 644 {} \;

# ตั้งสิทธิ์พิเศษ
sudo chmod 640 $PROJECT_PATH/config/database.php
sudo chmod -R 755 $PROJECT_PATH/config/
sudo chmod -R 755 $PROJECT_PATH/assets/

# SELinux
sudo setsebool -P httpd_can_network_connect 1
sudo setsebool -P httpd_can_network_connect_db 1
sudo semanage fcontext -a -t httpd_exec_t "$PROJECT_PATH(/.*)?"
sudo restorecon -Rv $PROJECT_PATH

echo "✅ การตั้งค่าสิทธิ์เสร็จสิ้น"
```

## 🚨 การแก้ไขปัญหาทั่วไป

### หาก PHP ไม่สามารถเขียนไฟล์ได้
```bash
# ตรวจสอบ SELinux บล็อกหรือไม่
sudo tail -f /var/log/audit/audit.log | grep denied

# อนุญาตให้ Apache เขียนไฟล์
sudo setsebool -P httpd_unified 1
```

### หาก Database connection ไม่ได้
```bash
# อนุญาตให้ Apache เชื่อมต่อฐานข้อมูล
sudo setsebool -P httpd_can_network_connect_db 1
```

### หากมีปัญหา Permission Denied
```bash
# รีเซ็ตสิทธิ์ทั้งหมด
sudo chown -R apache:apache /var/www/html/meeting-room-booking-system/
sudo chmod -R 755 /var/www/html/meeting-room-booking-system/
sudo restorecon -Rv /var/www/html/meeting-room-booking-system/
```

---
**หมายเหตุ**: ปรับเปลี่ยน path `/var/www/html/meeting-room-booking-system/` ให้ตรงกับตำแหน่งที่ติดตั้งจริง