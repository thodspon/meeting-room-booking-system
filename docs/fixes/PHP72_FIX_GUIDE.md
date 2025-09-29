# การแก้ไข index.php สำหรับ PHP 7.2

## ปัญหาที่พบ

1. **Arrow Functions** - ไม่รองรับใน PHP 7.2 (รองรับเริ่ม PHP 7.4)
2. **Null Coalescing Assignment Operator (??=)** - ไม่รองรับใน PHP 7.2 (รองรับเริ่ม PHP 7.4)
3. **การใช้งาน $_SESSION ที่ไม่ปลอดภัย**

## การแก้ไขที่ทำ

### 1. แทนที่ Arrow Functions ด้วย Anonymous Functions

**เดิม (PHP 7.4+):**
```php
$approved_bookings = array_filter($today_bookings, fn($b) => $b['status'] === 'approved');
```

**แก้ไข (PHP 7.2):**
```php
$approved_bookings = array_filter($today_bookings, function($b) { return $b['status'] === 'approved'; });
```

### 2. ปรับปรุงการใช้งาน $_SESSION

**เดิม:**
```php
<?= generateNavigation('index', $_SESSION['role'] ?? 'user', true) ?>
```

**แก้ไข:**
```php
<?php $user_role = isset($_SESSION['role']) ? $_SESSION['role'] : 'user'; ?>
<?= generateNavigation('index', $user_role, true) ?>
```

### 3. เพิ่ม htmlspecialchars เพื่อความปลอดภัย

**เดิม:**
```php
<title><?= $org_config['sub_title'] ?> - <?= $org_config['name'] ?></title>
```

**แก้ไข:**
```php
<title><?= htmlspecialchars($org_config['sub_title']) ?> - <?= htmlspecialchars($org_config['name']) ?></title>
```

### 4. ปรับปรุงการตรวจสอบสิทธิ์

เพิ่มการตรวจสอบสิทธิ์ admin/manager ในส่วนปุ่มอนุมัติ:

```php
<?php if ($booking['status'] == 'pending' && ($user_role === 'admin' || $user_role === 'manager')): ?>
```

### 5. ลบฟีเจอร์ที่ไม่จำเป็นสำหรับ PHP 7.2

- ลบส่วน Admin Dashboard ที่มี JavaScript ซับซ้อน
- ลบฟีเจอร์ Telegram Integration ที่อาจทำให้เกิดปัญหาใน PHP 7.2

## การทดสอบ

1. **เรียกไฟล์ debug_index.php** เพื่อตรวจสอบการทำงาน:
   ```
   http://192.168.99.107/smdmeeting-room/debug_index.php
   ```

2. **ตรวจสอบ PHP Error Log** ใน server:
   ```bash
   tail -f /var/log/php_errors.log
   ```

3. **ตรวจสอบ Apache/Nginx Error Log**:
   ```bash
   tail -f /var/log/apache2/error.log
   # หรือ
   tail -f /var/log/nginx/error.log
   ```

## ไฟล์ที่สร้าง/แก้ไข

1. `debug_index.php` - ไฟล์ทดสอบ
2. `index_php72.php` - เวอร์ชันที่แก้ไขสำหรับ PHP 7.2
3. `index_backup.php` - สำรองไฟล์เดิม
4. `index.php` - ไฟล์หลักที่อัพเดทแล้ว

## ข้อแนะนำเพิ่มเติม

### การตรวจสอบ Database Connection

ตรวจสอบการตั้งค่าใน `config/database.php`:

1. **Host**: `192.168.99.25`
2. **Database**: `meeting_room_db`
3. **Username**: `smd101`
4. **Password**: `Smd#101`
5. **Charset**: `tis620`

### การตั้งค่า PHP.ini สำหรับ PHP 7.2

เพิ่มในไฟล์ php.ini:

```ini
date.timezone = "Asia/Bangkok"
default_charset = "UTF-8"
error_reporting = E_ALL
display_errors = On
log_errors = On
error_log = /var/log/php_errors.log

; MySQL extensions
extension=pdo_mysql
extension=mysqli

; Character encoding
mbstring.internal_encoding = UTF-8
mbstring.http_output = UTF-8
```

### การตั้งค่า Apache/Nginx

**Apache (.htaccess):**
```apache
RewriteEngine On
DirectoryIndex default.php login.php index.php

# เปิดใช้งาน PHP
AddHandler application/x-httpd-php .php
```

**Nginx:**
```nginx
location ~ \.php$ {
    fastcgi_pass unix:/var/run/php/php7.2-fpm.sock;
    fastcgi_index index.php;
    include fastcgi_params;
}
```

## ขั้นตอนการแก้ปัญหาเพิ่มเติม

หากยังมีปัญหา ให้ตรวจสอบ:

1. **File Permissions**: `chmod 644 *.php`
2. **Directory Permissions**: `chmod 755 .`
3. **SELinux**: `setsebool -P httpd_can_network_connect on`
4. **Firewall**: เปิด port 80, 443
5. **PHP Extensions**: ตรวจสอบว่ามี pdo_mysql และ mysqli

## การอัพเกรด

สำหรับอนาคต ควรพิจารณาอัพเกรดเป็น PHP 8.x เพื่อใช้ฟีเจอร์ใหม่ๆ:

- Arrow Functions
- Null Coalescing Assignment
- Match Expressions
- Named Arguments
- Attributes

แต่ต้องทดสอบให้แน่ใจว่าทุกฟีเจอร์ทำงานได้ดี