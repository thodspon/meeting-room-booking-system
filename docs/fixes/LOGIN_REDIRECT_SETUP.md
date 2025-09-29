# การตั้งค่าหน้าแรกของระบบ

## สิ่งที่เปลี่ยนแปลง

### 1. ไฟล์ .htaccess
- เพิ่ม `DirectoryIndex default.php login.php index.php`
- เพิ่ม RewriteRule เพื่อ redirect หน้าแรกไป `default.php`

### 2. ไฟล์ default.php (ใหม่)
- ตรวจสอบสถานะการ login ของผู้ใช้
- ถ้า login แล้ว → redirect ไป `index.php` (หน้าหลักระบบ)  
- ถ้ายัง login → redirect ไป `login.php` (หน้า login)

### 3. ไฟล์ redirect_to_login.html (ทางเลือก)
- หน้า HTML ที่จะ redirect ไป login.php โดยตรง
- มี loading animation และ auto-redirect

## การทำงานของระบบ

### เมื่อเข้า http://localhost/vs_github/smdmeeting_room/
1. Apache จะเรียก `default.php` ก่อน (ตาม DirectoryIndex)
2. `default.php` จะตรวจสอบ `$_SESSION['user_id']`
3. **ถ้ายัง login** → redirect ไป `login.php`
4. **ถ้า login แล้ว** → redirect ไป `index.php` (dashboard หลัก)

### เมื่อเข้า http://localhost/vs_github/smdmeeting_room/login.php
- ไปหน้า login โดยตรง (ไม่ผ่าน default.php)

### เมื่อเข้า http://localhost/vs_github/smdmeeting_room/index.php
- ไปหน้าหลักระบบ (จะมีการตรวจสอบ login ใน index.php เอง)

## ข้อดี
- ✅ เมื่อเข้าระบบครั้งแรกจะไปหน้า login.php อัตโนมัติ
- ✅ เมื่อ login แล้วจะไปหน้าหลักระบบ (index.php)
- ✅ ไม่กระทบกับ URL อื่นๆ ในระบบ
- ✅ ทำงานได้กับทั้ง session และ cookie

## การทดสอบ
1. เปิดเบราว์เซอร์ใหม่ (หรือ incognito)
2. ไปที่ `http://localhost/vs_github/smdmeeting_room/`
3. ควรจะ redirect ไป login.php อัตโนมัติ
4. หลัง login สำเร็จแล้ว ให้ไปที่ root อีกครั้ง
5. ควรจะไปหน้า index.php (หน้าหลัก)

---
สร้างเมื่อ: <?= date('Y-m-d H:i:s') ?>
ระบบ: Meeting Room Booking System v2.5.1