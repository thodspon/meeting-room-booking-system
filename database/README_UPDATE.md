# คำแนะนำการอัพเดทระบบสีห้องประชุม
**ระบบจองห้องประชุม เวอร์ชัน 2.2 Color Edition**

## 📋 ไฟล์ SQL ที่สร้างขึ้น

### 1. `add_room_color.sql`
**วัตถุประสงค์:** เพิ่มคอลัมน์ `room_color` ในตารางที่มีอยู่แล้ว
```sql
-- เพิ่มคอลัมน์ room_color
ALTER TABLE rooms ADD COLUMN IF NOT EXISTS room_color VARCHAR(7) DEFAULT '#3b82f6' AFTER equipment;

-- อัพเดทสีเริ่มต้น
UPDATE rooms SET room_color = CASE 
    WHEN room_id = 1 THEN '#ef4444'  -- แดง
    WHEN room_id = 2 THEN '#10b981'  -- เขียว
    WHEN room_id = 3 THEN '#f59e0b'  -- เหลือง
    WHEN room_id = 4 THEN '#8b5cf6'  -- ม่วง
    ELSE '#3b82f6'                   -- น้ำเงิน
END;
```

### 2. `update_to_color_edition.sql`
**วัตถุประสงค์:** อัพเดทระบบครบวงจรสำหรับเวอร์ชัน 2.2
- เพิ่มคอลัมน์ `room_color` (ถ้ายังไม่มี)
- อัพเดทสีตามชื่อห้อง
- เพิ่มการตั้งค่าระบบใหม่
- สร้าง View ใหม่พร้อมสีห้อง

### 3. `meeting_room_db.sql` (อัพเดท)
**วัตถุประสงค์:** ฐานข้อมูลหลักที่รองรับสีห้อง
- มีคอลัมน์ `room_color` ในโครงสร้างตาราง `rooms`
- ข้อมูลห้องเริ่มต้นมีสีกำหนดไว้แล้ว
- การตั้งค่าระบบรองรับฟีเจอร์สีห้อง

## 🚀 วิธีการอัพเดท

### สำหรับระบบที่มีอยู่แล้ว:
```bash
# 1. เข้าสู่ MySQL
mysql -u root -p

# 2. รันสคริปต์อัพเดท
mysql> source /path/to/add_room_color.sql;
# หรือ
mysql> source /path/to/update_to_color_edition.sql;
```

### สำหรับติดตั้งใหม่:
```bash
# ใช้ไฟล์หลัก
mysql> source /path/to/meeting_room_db.sql;
```

## 🎨 สีห้องเริ่มต้น

| สี | Hex Code | ใช้สำหรับ |
|---|---|---|
| 🔴 แดง | #ef4444 | ห้องประชุมใหญ่ |
| 🟢 เขียว | #10b981 | ห้องประชุมเล็ก |
| 🟡 เหลือง | #f59e0b | ห้องฝึกอบรม |
| 🟣 ม่วง | #8b5cf6 | ห้องประชุมกลาง |
| 🔵 น้ำเงิน | #3b82f6 | เริ่มต้น |
| 🩷 ชมพู | #ec4899 | ห้อง VIP |
| 🩵 ฟ้า | #06b6d4 | ห้องบอร์ด |

## ✅ การตรวจสอบ

หลังจากรันสคริปต์แล้ว ให้ตรวจสอบ:

```sql
-- ตรวจสอบโครงสร้างตาราง
DESCRIBE rooms;

-- ตรวจสอบข้อมูลสีห้อง
SELECT room_id, room_name, room_color FROM rooms;

-- ตรวจสอบการตั้งค่าใหม่
SELECT * FROM system_settings WHERE setting_key LIKE '%color%' OR setting_key LIKE '%public%';
```

## 🔧 การใช้งาน

1. **จัดการสีห้อง:** เข้าสู่ระบบ → จัดการห้องประชุม → เลือกสี
2. **ปฏิทินสาธารณะ:** เข้าผ่าน `public_calendar.php` โดยไม่ต้อง Login
3. **ปฏิทินส่วนตัว:** แสดงสีห้องในการจองทั้งหมด

## 📱 ฟีเจอร์ใหม่

- ✅ **Color Picker:** เลือกสีห้องได้ตามต้องการ
- ✅ **Public Calendar:** ดูการจองแบบสาธารณะ
- ✅ **Color Legend:** คำอธิบายสีแต่ละห้อง
- ✅ **Status Opacity:** ความเข้มสีแสดงสถานะการอนุมัติ
- ✅ **Auto Refresh:** ปฏิทินสาธารณะอัพเดทอัตโนมัติ

พัฒนาโดย: **ทีม Roi-et Digital Health Team** 🏥✨