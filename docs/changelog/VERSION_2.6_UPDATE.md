# Version 2.6 Update Log

## 📝 สรุปการอัปเดต Version 2.6
**Telegram Integration Pro Edition**
- **วันที่:** 29 กันยายน 2568 (2025-09-29)
- **Build:** 20250929

## 🎯 เป้าหมายหลัก
ปรับปรุงระบบ Telegram Integration ให้ใช้ข้อมูล Telegram ส่วนบุคคลของแต่ละผู้ใช้แทนระบบรวมศูนย์

## ✨ ฟีเจอร์ใหม่

### 🔐 Individual User Telegram System
- **ข้อมูล Telegram ส่วนบุคคล:** แต่ละผู้ใช้มี `telegram_chat_id`, `telegram_token`, `telegram_enabled` ของตัวเอง
- **Dual Routing System:** ถ้าผู้ใช้ตั้งค่า Telegram ใช้ของตัวเอง ถ้าไม่มีใช้ระบบเดิม
- **Enhanced Security:** ข้อมูล Telegram ไม่ถูกแชร์ระหว่างผู้ใช้

### 📱 2FA System Enhancement
- **Personal 2FA:** ส่งรหัส 2FA ไปยัง Telegram ส่วนตัวของผู้ใช้
- **Fallback Support:** ถ้าไม่ได้ตั้งค่า Telegram ส่วนตัว ใช้ระบบเดิม
- **Better Error Handling:** แสดงสถานะการส่งที่ชัดเจน

### 🔔 Login Notification System
- **Personal Notifications:** แจ้งเตือนการ Login ไปยัง Telegram ของผู้ใช้ที่เข้าระบบ
- **Login Details:** แสดงข้อมูล IP, Browser, เวลาเข้าระบบ

### 👥 Manager Permissions
- **Reports Access:** Manager สามารถใช้ Reports Telegram Dashboard ได้
- **Send Permissions:** Manager ส่ง Reports ผ่าน Telegram ได้
- **Role-based UI:** แสดงส่วน Telegram Dashboard สำหรับ Manager

## 🛠️ Technical Improvements

### 🔧 System Enhancements
- **PHP 7.2 Compatibility:** แปลง arrow functions เป็น anonymous functions
- **Output Buffer Management:** จัดการ `ob_start()`, `ob_clean()` ให้ถูกต้อง
- **JSON Response Handling:** ป้องกัน output ที่ไม่ใช่ JSON
- **Function Organization:** จัดระเบียบ functions ใน `includes/functions.php`

### 📊 Database Schema Updates
```sql
-- เพิ่มคอลัมน์ใหม่ในตาราง users
ALTER TABLE users ADD COLUMN telegram_chat_id VARCHAR(50) NULL;
ALTER TABLE users ADD COLUMN telegram_token VARCHAR(100) NULL;
ALTER TABLE users ADD COLUMN telegram_enabled TINYINT(1) DEFAULT 0;
```

### 🎨 Navigation System
- **Dynamic Menu:** แสดงเมนูตามสิทธิ์ผู้ใช้
- **Role Badges:** แสดง badge สิทธิ์ในแถบเมนู
- **Permission Checks:** ตรวจสอบสิทธิ์แบบ role-based

## 🔧 Core Functions

### `sendTelegramMessageToUser($token, $chat_id, $message)`
```php
/**
 * ส่งข้อความ Telegram ไปยัง user เฉพาะโดยใช้ token และ chat_id ของ user นั้นๆ
 */
function sendTelegramMessageToUser($token, $chat_id, $message) {
    if (empty($token) || empty($chat_id)) {
        return ['ok' => false, 'description' => 'Token หรือ Chat ID ไม่ถูกต้อง'];
    }
    
    $url = "https://api.telegram.org/bot{$token}/sendMessage";
    $data = [
        'chat_id' => $chat_id,
        'text' => $message,
        'parse_mode' => 'HTML'
    ];
    
    // Send message via cURL or file_get_contents
    // Return Telegram API response
}
```

## 🐛 Bug Fixes
- **✅ Reports Telegram ส่งไม่ได้:** แก้ไขปัญหาการส่ง Reports ผ่าน Telegram
- **✅ Function Duplication:** แก้ไขปัญหา function ถูก declare ซ้ำ
- **✅ Session Handling:** จัดการ session หมดอายุแบบ graceful
- **✅ JSON Response:** แก้ไขปัญหา JSON response corruption
- **✅ Permission Logic:** ปรับปรุงการตรวจสอบสิทธิ์

## 📁 ไฟล์ที่อัปเดต

### Core Files
- `version.php` - อัปเดตเป็น 2.6
- `admin/version.php` - อัปเดตเป็น 2.6
- `includes/functions.php` - เพิ่ม `sendTelegramMessageToUser()`

### Admin Features
- `admin/reports.php` - เพิ่มสิทธิ์ Manager
- `admin/send_telegram_summary.php` - ใช้ individual user Telegram
- `auth.php` - Login notifications + 2FA routing

### Debug & Testing
- `admin/debug_full.php` - Debug tool สำหรับทดสอบ
- `admin/mock_login.php` - Mock login สำหรับทดสอบ
- `admin/test_telegram_permissions.php` - ทดสอบสิทธิ์

## 🚀 Migration Guide

### สำหรับผู้ใช้ปัจจุบัน
1. **อัปเดตฐานข้อมูล:** เพิ่ม 3 คอลัมน์ใหม่ในตาราง `users`
2. **ตั้งค่า Telegram:** ผู้ใช้สามารถตั้งค่า Telegram ส่วนตัวได้
3. **ทดสอบระบบ:** ใช้ไฟล์ debug เพื่อทดสอบการทำงาน

### สำหรับ Admin
1. **Permission Update:** Manager ได้สิทธิ์ใช้ Reports Telegram
2. **New UI Elements:** Telegram Dashboard แสดงให้ Manager ด้วย
3. **Individual Settings:** ผู้ใช้ตั้งค่า Telegram เองได้

## 📈 Performance Improvements
- **Reduced API Calls:** ใช้ Telegram API แบบประหยัด
- **Better Error Handling:** จัดการข้อผิดพลาดได้ดีขึ้น
- **Optimized Database:** Query ที่มีประสิทธิภาพขึ้น
- **Memory Management:** จัดการหน่วยความจำดีขึ้น

## 🔮 Future Roadmap
- **Multi-Bot Support:** รองรับหลาย Telegram Bot
- **Webhook Integration:** ใช้ Telegram Webhook แทน Polling
- **Rich Messages:** ส่งข้อความแบบ Interactive
- **File Attachments:** ส่งไฟล์แนบผ่าน Telegram

---

**พัฒนาโดย:** นายทศพล อุทก  
**ตำแหน่ง:** นักวิชาการคอมพิวเตอร์ชำนาญการ  
**หน่วยงาน:** โรงพยาบาลร้อยเอ็ด  
**ทีมพัฒนา:** Roi-et Digital Health Team

🎉 **Version 2.6 พร้อมใช้งานแล้ว!**