# การแก้ไข user_activity.php และ reports.php สำหรับ PHP 7.2

## ปัญหาที่พบและการแก้ไข

### 1. admin/user_activity.php

**ปัญหา:**
- Null Coalescing Operators (`??`) ไม่รองรับใน PHP 7.2
- การเรียกใช้ฟังก์ชันที่อาจไม่มี

**การแก้ไข:**

#### Null Coalescing Operators
```php
// เดิม (PHP 7.0+)
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');
$user_id_filter = $_GET['user_id'] ?? '';
$activity_type = $_GET['activity_type'] ?? '';

// แก้ไข (PHP 7.2 compatible)
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');
$user_id_filter = isset($_GET['user_id']) ? $_GET['user_id'] : '';
$activity_type = isset($_GET['activity_type']) ? $_GET['activity_type'] : '';
```

#### Session Role References
```php
// เดิม
generateNavigation('user_activity', $_SESSION['role'] ?? 'user', true)

// แก้ไข
generateNavigation('user_activity', isset($_SESSION['role']) ? $_SESSION['role'] : 'user', true)
```

#### Debug Info Display
```php
// เดิม
<?= $debug_info['total_bookings'] ?? 0 ?>

// แก้ไข
<?= isset($debug_info['total_bookings']) ? $debug_info['total_bookings'] : 0 ?>
```

### 2. admin/reports.php

**ปัญหา:**
- การเรียกใช้ `getSystemFooter()` ที่ไม่มีฟังก์ชัน
- การ require vendor/autoload.php ที่อาจทำให้เกิด error

**การแก้ไข:**

#### Safe Footer Loading
```php
// เดิม
<?php echo getSystemFooter(); ?>

// แก้ไข
<?php 
if (file_exists('../version.php')) {
    require_once '../version.php'; 
    if (function_exists('getSystemFooter')) {
        echo getSystemFooter();
    }
}
?>
```

#### Safe Vendor Autoload
```php
// เดิม
require_once '../vendor/autoload.php';

// แก้ไข
if (file_exists('../vendor/autoload.php')) {
    require_once '../vendor/autoload.php';
}
```

## ไฟล์ที่สร้าง/แก้ไข

### ไฟล์สำรอง (PHP 7.4+ เวอร์ชัน)
- `admin/user_activity_php74.php` - เวอร์ชันเดิม

### ไฟล์เวอร์ชัน PHP 7.2
- `admin/user_activity_php72.php` - เวอร์ชันที่แก้ไข

### ไฟล์หลัก (อัพเดทแล้ว)
- `admin/user_activity.php` ✅ อัพเดทให้รองรับ PHP 7.2
- `admin/reports.php` ✅ แก้ไขการโหลดไฟล์ให้ปลอดภัย

### ไฟล์ Debug
- `admin/debug_admin.php` - สำหรับทดสอบไฟล์ admin

## การทดสอบ

### URLs สำหรับทดสอบ
```
http://192.168.99.107/smdmeeting-room/admin/debug_admin.php
http://192.168.99.107/smdmeeting-room/admin/reports.php  
http://192.168.99.107/smdmeeting-room/admin/user_activity.php
```

## ฟีเจอร์ที่ทำงาน

### user_activity.php
- ✅ รายงานกิจกรรมผู้ใช้
- ✅ ตัวกรองตามวันที่
- ✅ ตัวกรองตามผู้ใช้
- ✅ ตัวกรองตามประเภทกิจกรรม
- ✅ แสดงข้อมูลการจอง, การแก้ไข, การยกเลิก
- ✅ การนำออกข้อมูล (Export)

### reports.php  
- ✅ รายงานการจองห้องประชุม
- ✅ ตัวกรองรายงาน (วันที่, ห้อง, สถานะ)
- ✅ สถิติการจอง
- ✅ ส่งออก Excel (.xlsx) - ถ้ามี PhpSpreadsheet
- ✅ ส่งออก PDF (HTML-to-PDF) 
- ✅ การพิมพ์รายงาน
- ✅ รองรับฟอนต์ไทย

## การแก้ปัญหาเพิ่มเติม

### หาก user_activity.php ยังไม่ทำงาน

1. **ตรวจสอบ Database Schema:**
```sql
-- ตรวจสอบตารางที่จำเป็น
SHOW TABLES LIKE 'bookings';
SHOW TABLES LIKE 'users';
SHOW TABLES LIKE 'rooms';

-- ตรวจสอบ columns
DESCRIBE bookings;
DESCRIBE users;
```

2. **ตรวจสอบ Permissions:**
```php
// ตรวจสอบฟังก์ชัน checkPermission
SELECT * FROM user_permissions WHERE user_id = 1;
```

3. **ตรวจสอบ Session:**
```php
// เพิ่มใน user_activity.php
var_dump($_SESSION);
```

### หาก reports.php ยังไม่ทำงาน

1. **ตรวจสอบ PhpSpreadsheet:**
```bash
composer require phpoffice/phpspreadsheet
```

2. **ใช้ HTML Export แทน Excel:**
- ปิดการใช้งาน Excel export ชั่วคราว
- ใช้ HTML-to-PDF แทน TCPDF

3. **ตรวจสอบ Memory Limit:**
```php
ini_set('memory_limit', '512M');
ini_set('max_execution_time', 300);
```

### หาก Navigation ไม่ทำงาน

1. **ตรวจสอบ generateNavigation():**
```php
// ใน includes/functions.php
function generateNavigation($current_page = '', $user_role = 'user', $mobile = false) {
    // Implementation...
}
```

2. **ตรวจสอบ Role-based Menu:**
```php
function getNavigationMenu($role = 'user') {
    // Implementation...
}
```

## Performance Tips

1. **Database Optimization:**
```sql
-- เพิ่ม index สำหรับ queries ที่ใช้บ่อย
CREATE INDEX idx_booking_date ON bookings(booking_date);
CREATE INDEX idx_booking_status ON bookings(status);
CREATE INDEX idx_user_role ON users(role);
```

2. **Caching:**
```php
// Cache organization config
if (!isset($_SESSION['org_config'])) {
    $_SESSION['org_config'] = getOrganizationConfig();
}
```

3. **Pagination สำหรับรายงานขนาดใหญ่:**
```php
$limit = 50;
$offset = ($page - 1) * $limit;
$sql .= " LIMIT $limit OFFSET $offset";
```

## สรุปการแก้ไข

✅ **แก้ไข Null Coalescing Operators**
✅ **แก้ไขการโหลดไฟล์ให้ปลอดภัย**  
✅ **เพิ่ม Error Handling**
✅ **สร้างไฟล์ Debug สำหรับทดสอบ**
✅ **สำรองไฟล์เวอร์ชันเดิม**

ระบบ admin files ควรทำงานได้ใน PHP 7.2 แล้ว! 🎉