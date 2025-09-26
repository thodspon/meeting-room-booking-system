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
define('SYSTEM_VERSION', '2.2');
define('SYSTEM_BUILD', '20250926');
define('SYSTEM_CODENAME', 'Color Edition Pro');

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