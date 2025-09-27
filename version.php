<?php
/**
 * ไฟล์จัดการเวอร์ชันระบบจองห้องประชุม
 * ระบบจองห้องประชุม - ศูนย์ส่งเสริมสุขภาพสวนพยอม
 * 
 * @author นายทศพล อุทก
 * @position นักวิชาการคอมพิวเตอร์ชำนาญการ
 * @organization โรงพยาบาลร้อยเอ็ด
 * @since 2025-09-26
 */

// ข้อมูลเวอร์ชันปัจจุบัน
define('SYSTEM_VERSION', '2.5.1');
define('SYSTEM_BUILD', '20250927');
define('SYSTEM_CODENAME', 'Admin Pro Edition Plus');

// ข้อมูลผู้พัฒนา
define('DEVELOPER_NAME', 'นายทศพล อุทก');
define('DEVELOPER_POSITION', 'นักวิชาการคอมพิวเตอร์ชำนาญการ');
define('DEVELOPER_ORGANIZATION', 'โรงพยาบาลร้อยเอ็ด');
define('DEVELOPER_TEAM', 'Roi-et Digital Health Team');

// ข้อมูลระบบ
define('SYSTEM_NAME', 'ระบบจองห้องประชุม');

// โหลดการตั้งค่าองค์กรจากไฟล์ config.php
require_once __DIR__ . '/config.php';
define('SYSTEM_ORGANIZATION', getOrganizationName());

/**
 * ประวัติการพัฒนาระบบ
 */
$version_history = [
    '2.5.1' => [
        'date' => '2025-09-27',
        'title' => 'Admin Pro Edition Plus - Date Range Enhancement',
        'features' => [
            'ปรับปรุงระบบส่ง Telegram ให้รองรับช่วงวันที่ (Date Range)',
            'เลือกวันที่เริ่มต้น - วันที่สิ้นสุด แทนการเลือกวันเดียว',
            'ปุ่มเลือกช่วงเวลาแบบด่วน - วันนี้, สัปดาห์นี้, เดือนนี้',
            'การจัดกลุ่มรายงานตามวันที่ในรายงานรายละเอียด',
            'ตรวจสอบความถูกต้องของช่วงวันที่ (ไม่เกิน 30 วัน)',
            'แสดงช่วงวันที่ในข้อความ Telegram แบบภาษาไทย',
            'ปรับปรุง UI/UX การเลือกช่วงวันที่ให้ใช้งานง่าย',
            'Activity Logging รองรับการบันทึกช่วงวันที่'
        ],
        'enhancements' => [
            'ฟอร์มเลือกวันที่: แบ่งเป็น 2 คอลัมน์ (เริ่มต้น - สิ้นสุด)',
            'Quick Selection: ปุ่มเลือกด่วน 3 แบบ',
            'Date Validation: ตรวจสอบวันที่เริ่มต้น <= วันที่สิ้นสุด',
            'Range Limit: จำกัดช่วงเวลาไม่เกิน 30 วัน',
            'Detailed Report: จัดกลุ่มตามวันที่พร้อมสถิติ',
            'Thai Date Format: แสดงวันที่แบบไทยในข้อความ',
            'JavaScript Enhancement: ฟังก์ชันเลือกช่วงวันแบบอัตโนมัติ'
        ],
        'technical_improvements' => [
            'SQL Query: BETWEEN clause สำหรับช่วงวันที่',
            'Parameter Validation: ตรวจสอบ start_date และ end_date',
            'Error Handling: จัดการข้อผิดพลาดช่วงวันที่',
            'Performance: จัดกลุ่มข้อมูลแบบมีประสิทธิภาพ',
            'Memory Management: ปรับปรุงการใช้หน่วยความจำ'
        ]
    ],
    '2.5' => [
        'date' => '2025-09-27',
        'title' => 'Admin Pro Edition - Enhanced Dashboard & Telegram Broadcasting',
        'features' => [
            'ปรับปรุงหน้าแรก (index.php) - แสดงรายละเอียดการจองวันนี้แบบครบถ้วน',
            'ระบบสิทธิ์ 3 ระดับ - Admin, Manager, User พร้อมเมนูที่ปรับเปลี่ยนตามสิทธิ์',
            'Admin Dashboard - ส่วนจัดการสำหรับ Admin เท่านั้น',
            'ระบบส่งสรุปการจองผ่าน Telegram - เลือกวันที่, ประเภทรายงาน, ผู้รับ',
            'การแสดงสถานะเวลาจริง - กำลังใช้งาน, เริ่มใน X นาที, เหลือ X นาที',
            'ระบบเมนูไดนามิก - แสดงเฉพาะเมนูที่มีสิทธิ์เข้าถึง',
            'Badge แสดงระดับสิทธิ์ - Admin (แดง), Manager (เหลือง), User (น้ำเงิน)',
            'หน้าทดสอบสิทธิ์ (test_permissions.php) - ทดสอบระบบสิทธิ์แบบครบถ้วน',
            'อัปเดต public_calendar.php - แสดงหน่วยงานแทน "แผนก"',
            'ไฟล์ room_bookings.php - แสดงการจองของห้องเฉพาะ',
            'ระบบส่ง Telegram แบบ Broadcast - ส่งให้หลายคนพร้อมกัน'
        ],
        'admin_features' => [
            'Admin Dashboard บนหน้าแรก - เฉพาะ Admin เท่านั้น',
            'ส่งสรุปการจองแบบกำหนดเอง - เลือกวันที่, ประเภท, ผู้รับ',
            'ตัวอย่างข้อความ - แสดงก่อนส่งจริง',
            'ส่งด่วน 2 แบบ: สรุปวันนี้ (ทุกคน), รายการรออนุมัติ (Manager+Admin)',
            'เลือกผู้รับ 4 แบบ: ทุกคน, Admin เท่านั้น, Manager+Admin, เลือกเอง',
            'รายงาน 4 ประเภท: สรุป, รายละเอียด, เฉพาะรออนุมัติ, เฉพาะอนุมัติแล้ว',
            'สถิติการส่ง Telegram - แสดงจำนวนข้อความที่ส่งและผู้รับ'
        ],
        'enhanced_booking_display' => [
            'การจองวันนี้แสดงแบบ Card Layout - สวยงามและครบถ้วน',
            'สถานะเวลาแบบเรียลไทม์ - แสดงเวลาที่เหลือ/เริ่มใน',
            'สีสันตามสถานะ - กำลังใช้งาน (แดงกระพริบ), เริ่มใน (น้ำเงิน), เสร็จแล้ว (เทา)',
            'แสดงข้อมูลครบถ้วน - ผู้จอง, หน่วยงาน, วัตถุประสงค์, จำนวนคน',
            'ปุ่มอนุมัติ/ไม่อนุมัติ - สำหรับ Admin/Manager ในการจองที่รออนุมัติ',
            'สถิติแยกตามสถานะ - อนุมัติแล้ว, รออนุมัติ, อื่นๆ',
            'การนับถอยหลัง - แสดงเวลาที่เหลือแบบเรียลไทม์'
        ],
        'permission_system' => [
            'ระบบเมนูไดนามิก - generateNavigation() function',
            'ตรวจสอบสิทธิ์ - checkPermission() แบบละเอียด',
            'User: หน้าหลัก, จองห้อง, ปฏิทิน, การจองของฉัน',
            'Manager: สิทธิ์ User + จัดการห้อง, รายงาน, กิจกรรมผู้ใช้, อนุมัติ',
            'Admin: สิทธิ์ Manager + จัดการผู้ใช้, ตั้งค่า Telegram, Admin Dashboard',
            'Badge สิทธิ์ - แสดงใน Navigation Bar',
            'ไอคอน SVG - สำหรับแต่ละเมนู'
        ],
        'telegram_broadcasting' => [
            'ส่งสรุปการจองแบบ Broadcast - ส่งให้หลายคนพร้อมกัน',
            'รายงาน 4 แบบ: สรุป, รายละเอียด, เฉพาะรออนุมัติ, เฉพาะอนุมัติแล้ว',
            'การจัดรูปแบบข้อความ - Emoji และข้อความภาษาไทยสวยงาม',
            'ระบบ Rate Limiting - หน่วงเวลา 0.5 วินาทีระหว่างการส่ง',
            'Error Handling - จัดการข้อผิดพลาดและรายงานผล',
            'Activity Logging - บันทึกการส่งข้อความ',
            'JSON Response - ส่งผลลัพธ์แบบ JSON'
        ],
        'ui_improvements' => [
            'Gradient Background - Admin Dashboard สีม่วง-ชมพู',
            'Glass Effect - Backdrop blur ใน Admin sections',
            'Loading States - แสดงสถานะการส่งข้อความ',
            'Form Validation - ตรวจสอบข้อมูลก่อนส่ง',
            'Preview Message - แสดงตัวอย่างข้อความก่อนส่ง',
            'Responsive Design - ใช้งานได้ทุกอุปกรณ์',
            'Animation Effects - Pulse, Transform, Hover effects'
        ],
        'testing_tools' => [
            'test_permissions.php - หน้าทดสอบระบบสิทธิ์',
            'เปลี่ยนสิทธิ์แบบทดสอบ - User, Manager, Admin',
            'แสดงเมนูที่เข้าถึงได้ - ตามสิทธิ์ที่เลือก',
            'ทดสอบการอนุญาต - แสดงสิทธิ์แต่ละประเภท',
            'ลิงก์ทดสอบ - เปิดหน้าต่างๆ ตามสิทธิ์',
            'การวิเคราะห์สิทธิ์ - แสดงตารางสิทธิ์แบบละเอียด'
        ]
    ],
    '2.4' => [
        'date' => '2025-09-26',
        'title' => 'Security Edition Pro - Password Reset System via Telegram',
        'features' => [
            'ระบบรีเซ็ตรหัสผ่านผ่าน Telegram - ส่งรหัส 6 หลักแบบปลอดภัย',
            'หน้า Forgot Password (forgot_password.php) - UI/UX สวยงามแบบ 3 ขั้นตอน',
            'ระบบ Password Reset Handler (reset_password.php) - จัดการข้อมูลอย่างปลอดภัย',
            'ตาราง password_resets - เก็บรหัสรีเซ็ตพร้อม expire time 15 นาที',
            'ระบบ Cleanup Script - ลบข้อมูลรีเซ็ตที่หมดอายุอัตโนมัติ',
            'การแจ้งเตือนผ่าน Telegram - ทั้งขอรหัสและยืนยันการเปลี่ยน',
            'ฟังก์ชัน Telegram Config - จัดการการตั้งค่าผู้ใช้และระบบ'
        ],
        'security_features' => [
            'รหัสรีเซ็ต 6 หลัก - สุ่มใหม่ทุกครั้ง ป้องกันการเดา',
            'หมดอายุอัตโนมัติ - รหัสใช้ได้เฉพาะ 15 นาที',
            'One-time Use - ใช้ได้ครั้งเดียว ป้องกันการใช้ซ้ำ',
            'Database Transaction - ป้องกันข้อมูลเสียหาย',
            'Error Logging - บันทึกข้อผิดพลาดเพื่อตรวจสอบ',
            'Input Validation - ตรวจสอบข้อมูลอย่างเข้มงวด'
        ],
        'telegram_integration' => [
            'ส่งรหัสรีเซ็ตผ่าน Telegram Bot อัตโนมัติ',
            'แจ้งเตือนเมื่อเปลี่ยนรหัสผ่านสำเร็จ',
            'รองรับการตั้งค่า Telegram ส่วนตัวและระบบ',
            'ข้อความภาษาไทยที่สวยงามพร้อม Emoji',
            'การจัดรูปแบบข้อความแบบ HTML',
            'ระบบ Fallback - ลองส่งหลายช่องทางถ้าไม่สำเร็จ'
        ],
        'ui_improvements' => [
            '3-Step Progress Indicator - แสดงขั้นตอนอย่างชัดเจน',
            'Responsive Design - ใช้งานได้ทุกอุปกรณ์',
            'Loading States - แสดงสถานะการประมวลผล',
            'Form Validation - ตรวจสอบข้อมูลแบบเรียลไทม์',
            'Success/Error Messages - ข้อความแจ้งเตือนที่ชัดเจน',
            'Auto-focus และ UX Enhancements'
        ],
        'maintenance_features' => [
            'Cleanup Script - ลบข้อมูลเก่าอัตโนมัติ',
            'Database Optimization - Index และ Foreign Key',
            'Error Handling - จัดการข้อผิดพลาดอย่างมีประสิทธิภาพ',
            'Logging System - บันทึกกิจกรรมสำคัญ',
            'AJAX Support - รองรับการส่งข้อมูลแบบไม่รีเฟรช'
        ]
    ],
    '2.3' => [
        'date' => '2025-09-26',
        'title' => 'Enhanced Edition Pro - Font & UI Improvements + Public Access',
        'features' => [
            'ปรับปรุงฟอนต์ไทยทั้งระบบ - ใช้ Sarabun + Prompt สำหรับการแสดงผลที่สวยงาม',
            'แก้ไขปัญหาการแสดงรายละเอียดการจองใน my_bookings.php',
            'ปรับปรุง Modal การแสดงข้อมูลการจองให้ครบถ้วนและสวยงาม',
            'เพิ่มปุ่ม "ดูปฏิทินการจองสาธารณะ" ในหน้า Login',
            'เพิ่มหน้า User Activity (user_activity.php) - รายงานกิจกรรมผู้ใช้งานแบบครบถ้วน',
            'ระบบติดตามกิจกรรม: สร้างการจอง, อนุมัติ, ยกเลิก พร้อมสถิติแบบเรียลไทม์',
            'ปรับปรุงการจัดการข้อมูลใน JavaScript ให้เสถียรมากขึ้น',
            'เพิ่มระบบ Debug logging สำหรับตรวจสอบปัญหา',
            'ปรับปรุงการแสดงผลในหน้า Reports และ Telegram Settings'
        ],
        'font_improvements' => [
            'ใช้ Google Fonts: Sarabun (300,400,500,600,700) และ Prompt (300,400,500,600,700)',
            'ตั้งค่า font-family hierarchy ที่เหมาะสมสำหรับภาษาไทย',
            'ปรับ line-height เป็น 1.6 สำหรับการอ่านที่สบายตา',
            'แยกฟอนต์สำหรับส่วนต่างๆ: Sarabun สำหรับเนื้อหา, Prompt สำหรับหัวข้อ',
            'เพิ่ม font-feature-settings สำหรับ ligature ที่สวยงาม',
            'ปรับปรุง Typography ให้มีความสอดคล้องทั้งระบบ'
        ],
        'bug_fixes' => [
            'แก้ไขปัญหา undefined variable $org_config ใน my_bookings.php',
            'แก้ไขปัญหาการส่งข้อมูล JSON ใน onclick attributes',
            'ปรับปรุงการจัดการ Modal ให้แสดงข้อมูลได้ถูกต้อง',
            'แก้ไขปัญหาการโหลดไฟล์ config.php และ version.php',
            'ปรับปรุงการ handle errors ใน JavaScript functions',
            'แก้ไขปัญหาการแสดงผลวันที่และเวลาในภาษาไทย'
        ],
        'ui_enhancements' => [
            'ปรับปรุงหน้า Login ให้มีปุ่มเข้าถึงปฏิทินสาธารณะ',
            'เพิ่ม hover effects และ transition animations',
            'ปรับปรุง Modal design ให้สวยงามและใช้งานง่าย',
            'เพิ่ม backdrop blur effect ให้ Login card',
            'ปรับปรุงการจัดวาง Layout ให้เป็นระเบียบมากขึ้น',
            'เพิ่มไอคอนและคำอธิบายที่ชัดเจน'
        ],
        'accessibility_improvements' => [
            'เพิ่มปุ่มเข้าถึงปฏิทินสาธารณะโดยไม่ต้อง Login',
            'ปรับปรุงการแสดงข้อมูลให้ชัดเจนและครบถ้วน',
            'เพิ่มคำอธิบายและ tooltips ที่เป็นประโยชน์',
            'ปรับปรุงการนำทาง (Navigation) ให้ใช้งานง่าย',
            'เพิ่มการตรวจสอบข้อมูลก่อนแสดงผล'
        ]
    ],
    '2.2' => [
        'date' => '2025-09-26',
        'title' => 'Color Edition Pro - Visual Enhancement & Real-time Features',
        'features' => [
            'ระบบสีห้องประชุม (Room Color System) - เลือกสีประจำแต่ละห้อง',
            'ปฏิทินสาธารณะ (Public Calendar) - ดูการจองแบบไม่ต้อง Login',
            'การแสดงสีห้องในปฏิทินแบบ Color-coded',
            'ระบบจัดการสีห้องในหน้าจัดการห้องประชุม',
            'Color Picker พร้อมชุดสีเริ่มต้น (แดง, เขียว, เหลือง, ม่วง, น้ำเงิน)',
            'Tooltip แบบ Custom พร้อมข้อมูลครบถ้วนเป็นภาษาไทย',
            'การแสดงเวลาปัจจุบันแบบเรียลไทม์ใน Tooltip',
            'สถานะการใช้งานแบบเรียลไทม์ (กำลังใช้งาน, เสร็จสิ้นแล้ว)',
            'ปุ่มเข้าถึงปฏิทินสาธารณะจากหน้าหลักและปฏิทินส่วนตัว',
            'Legend แสดงสถานะการจองและสีห้องอย่างครบถ้วน',
            'การแสดงสีห้องในรายการการจองทั้งหมด',
            'Auto-refresh ปฏิทินสาธารณะทุก 5 นาที'
        ],
        'improvements' => [
            'ปรับปรุงการแสดงผลการจองด้วยสีสันและสัญลักษณ์',
            'เพิ่มความแม่นยำของการแสดงเวลาปัจจุบัน',
            'ปรับปรุง UI/UX ให้สวยงามและใช้งานง่ายขึ้น',
            'เพิ่มการตอบสนองบนอุปกรณ์มือถือ',
            'ปรับปรุงประสิทธิภาพการแสดงผลปฏิทิน',
            'เพิ่มความชัดเจนของสถานะการจอง',
            'ปรับปรุงการจัดการ Memory Leaks ใน JavaScript',
            'เพิ่มการแสดงผลแบบ Responsive Design'
        ],
        'database_updates' => [
            'เพิ่มคอลัมน์ room_color (VARCHAR(7)) ในตาราง rooms',
            'อัพเดทข้อมูลสีเริ่มต้นสำหรับห้องที่มีอยู่',
            'เพิ่มการตั้งค่าระบบใหม่: room_color_enabled, public_calendar_enabled',
            'สร้าง View ใหม่: calendar_view, room_usage_stats'
        ],
        'visual_enhancements' => [
            'สถานะการอนุมัติ: สีห้อง + วงกลมเขียว',
            'สถานะรออนุมัติ: เส้นประรอบๆ + วงกลมเหลืองกระพริบ',
            'สถานะกำลังใช้งาน: วงกลมแดงกระพริบ',
            'สถานะเสร็จสิ้น: วงกลมเทา',
            'Hover Effects และ Animation ที่นุ่มนวล',
            'Shadow Effects และ Transform Animation'
        ]
    ],
    '2.1' => [
        'date' => '2025-09-26',
        'title' => 'Team Edition - Configuration & Enhancement',
        'features' => [
            'เพิ่มระบบ Dynamic Organization Configuration',
            'เพิ่มการตั้งค่า Telegram แบบส่วนบุคคลและระบบ',
            'ปรับปรุงระบบ 2FA ให้ใช้ MySQL Timestamp แทน PHP Date',
            'เพิ่มฟังก์ชันจัดการการตั้งค่าองค์กรแบบไดนามิก',
            'เพิ่มหน้าตั้งค่า Telegram สำหรับผู้ใช้และ Admin',
            'ปรับปรุงการแสดงชื่อองค์กรให้เป็นแบบไดนามิก',
            'เพิ่มข้อมูลทีมพัฒนา Roi-et Digital Health Team',
            'ทำความสะอาดไฟล์ที่ไม่ได้ใช้งานออกจากระบบ'
        ],
        'improvements' => [
            'แก้ไขปัญหา Timezone Mismatch ระหว่าง MySQL และ PHP',
            'ปรับปรุงการจัดการ Session และ Authentication',
            'เพิ่มฟังก์ชันการตรวจสอบและทดสอบ Telegram',
            'ปรับปรุงโครงสร้างฐานข้อมูลให้รองรับการตั้งค่าใหม่',
            'เพิ่มระบบ Debug และ Error Handling ที่ดีขึ้น'
        ],
        'bugfixes' => [
            'แก้ไขปัญหารหัส 2FA หมดอายุก่อนเวลา',
            'แก้ไขข้อผิดพลาด getTelegramConfig() ไม่พบฟังก์ชัน',
            'แก้ไขปัญหาการแสดงชื่อองค์กรที่ไม่แสดงผล',
            'แก้ไขปัญหาการ include ไฟล์ config ในฟังก์ชัน',
            'แก้ไขโครงสร้างฐานข้อมูลให้สมบูรณ์'
        ]
    ],
    '2.0' => [
        'date' => '2025-09-26',
        'title' => 'Enhanced Edition - การปรับปรุงใหญ่',
        'features' => [
            'แก้ไขปัญหาการแสดงผลภาษาไทยในไฟล์ PDF',
            'ปรับปรุงระบบ 2FA ให้แสดงชื่อระบบและ URL',
            'แก้ไขการแสดงผลตัวอักษรไทยในปฏิทิน',
            'ปรับปรุงปุ่มจัดการในหน้าการจองของฉัน',
            'เพิ่มระบบสำรองสำหรับการส่งออก PDF',
            'ปรับปรุงการจัดการฟอนต์ TCPDF',
            'เพิ่มระบบ HTML-to-PDF เป็นทางเลือก',
            'ปรับปรุงการเข้ารหัส UTF-8 ทั้งระบบ',
            'เพิ่มระบบตรวจสอบข้อผิดพลาดที่แข็งแกร่ง',
            'ปรับปรุงส่วนติดต่อผู้ใช้ให้ใช้งานง่ายขึ้น'
        ],
        'bugfixes' => [
            'แก้ไขข้อผิดพลาด Syntax Error ในไฟล์ reports.php',
            'แก้ไขปัญหาฟอนต์ TCPDF ที่ไม่พบไฟล์นิยาม',
            'แก้ไขการแสดงผลตัวอักษรไทยที่เป็น "�������к�"',
            'แก้ไขปุ่มจัดการที่ไม่ทำงานในหน้าการจองของฉัน',
            'แก้ไขการส่งข้อความ 2FA ที่ขาดข้อมูลระบบ'
        ],
        'improvements' => [
            'ปรับปรุงประสิทธิภาพการส่งออกไฟล์',
            'เพิ่มความเสถียรของระบบ',
            'ปรับปรุงการจัดการข้อผิดพลาด',
            'เพิ่มตัวเลือกการส่งออกหลากหลาย',
            'ปรับปรุงการตอบสนองของระบบ'
        ]
    ],
    '1.0' => [
        'date' => '2025-09-26',
        'title' => 'Initial Release - เวอร์ชันเริ่มต้น',
        'features' => [
            'ระบบจองห้องประชุมพื้นฐาน',
            'ระบบการเข้าสู่ระบบ',
            'ระบบอนุมัติการจอง',
            'ปฏิทินการจอง',
            'รายงานการใช้งาน',
            'ระบบ 2FA ผ่าน Telegram',
            'การส่งออกไฟล์ Excel และ PDF',
            'จัดการห้องประชุมและผู้ใช้'
        ]
    ]
];

/**
 * ฟังก์ชันแสดงข้อมูลเวอร์ชัน
 */
function getSystemVersion() {
    return SYSTEM_VERSION;
}

function getSystemBuild() {
    return SYSTEM_BUILD;
}

function getSystemCodename() {
    return SYSTEM_CODENAME;
}

function getFullVersion() {
    return SYSTEM_VERSION . ' (' . SYSTEM_CODENAME . ') Build ' . SYSTEM_BUILD;
}

/**
 * ฟังก์ชันแสดงข้อมูลผู้พัฒนา
 */
function getDeveloperInfo() {
    return [
        'name' => DEVELOPER_NAME,
        'position' => DEVELOPER_POSITION,
        'organization' => DEVELOPER_ORGANIZATION,
        'team' => DEVELOPER_TEAM
    ];
}

function getDeveloperString() {
    return 'พัฒนาโดย ' . DEVELOPER_NAME . ' ' . DEVELOPER_POSITION . ' ' . DEVELOPER_ORGANIZATION;
}

function getDeveloperTeam() {
    return DEVELOPER_TEAM;
}

function getFullDeveloperString() {
    return 'พัฒนาโดย ' . DEVELOPER_NAME . ' ' . DEVELOPER_POSITION . ' ' . DEVELOPER_ORGANIZATION . ' (' . DEVELOPER_TEAM . ')';
}

/**
 * ฟังก์ชันแสดงข้อมูลระบบ
 */
function getSystemInfo() {
    return [
        'name' => SYSTEM_NAME,
        'organization' => SYSTEM_ORGANIZATION,
        'version' => getFullVersion(),
        'developer' => getDeveloperString()
    ];
}

/**
 * ฟังก์ชันแสดงประวัติเวอร์ชัน
 */
function getVersionHistory() {
    global $version_history;
    return $version_history;
}

/**
 * ฟังก์ชันแสดงข้อมูลเวอร์ชันปัจจุบัน
 */
function getCurrentVersionInfo() {
    global $version_history;
    return $version_history[SYSTEM_VERSION] ?? null;
}

/**
 * ฟังก์ชันสร้าง Footer HTML
 */
function getSystemFooter() {
    $info = getSystemInfo();
    $team = getDeveloperTeam();
    return "
    <footer class=\"footer footer-center p-10 bg-base-200 text-base-content rounded mt-10\">
        <aside>
            <p class=\"font-bold text-lg\">{$info['name']}</p>
            <p>{$info['organization']}</p>
            <p>{$info['developer']}</p>
            <p class=\"text-sm text-primary font-semibold\">ทีมพัฒนา: {$team}</p>
            <p>เวอร์ชั่น {$info['version']} วันที่ " . date('d') . " " . getThaiMonth(date('n')) . " " . (date('Y') + 543) . "</p>
        </aside>
    </footer>";
}

/**
 * ฟังก์ชันแปลงเดือนเป็นภาษาไทย
 */
function getThaiMonth($month) {
    $thai_months = [
        1 => 'มกราคม', 2 => 'กุมภาพันธ์', 3 => 'มีนาคม', 4 => 'เมษายน',
        5 => 'พฤษภาคม', 6 => 'มิถุนายน', 7 => 'กรกฎาคม', 8 => 'สิงหาคม',
        9 => 'กันยายน', 10 => 'ตุลาคม', 11 => 'พฤศจิกายน', 12 => 'ธันวาคม'
    ];
    return $thai_months[$month] ?? '';
}
?>