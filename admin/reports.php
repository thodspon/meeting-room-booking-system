<?php
// ตั้งค่า UTF-8 encoding
header('Content-Type: text/html; charset=UTF-8');
mb_internal_encoding('UTF-8');
ini_set('default_charset', 'UTF-8');

session_start();
require_once '../config/database.php';
require_once '../config.php';
require_once '../includes/functions.php';
// โหลด vendor autoload อย่างปลอดภัย (เฉพาะ PHP 7.4+)
if (file_exists('../vendor/autoload.php') && version_compare(PHP_VERSION, '7.4.0', '>=')) {
    try {
        require_once '../vendor/autoload.php'; // สำหรับ PhpSpreadsheet และ TCPDF
        define('VENDOR_LOADED', true);
    } catch (Exception $e) {
        define('VENDOR_LOADED', false);
        // ไม่แสดง error เพื่อให้ระบบทำงานต่อไปได้
    }
} else {
    define('VENDOR_LOADED', false);
}

// Get organization config
$org_config = getOrganizationConfig();
$page_title = 'รายงานการจองห้องประชุม';

// ตรวจสอบการ login
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

// ตรวจสอบสิทธิ์
if (!checkPermission($pdo, $_SESSION['user_id'], 'view_reports')) {
    header('Location: ../index.php?error=permission');
    exit();
}

// กำหนดช่วงวันที่เริ่มต้น
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01'); // วันแรกของเดือน
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t'); // วันสุดท้ายของเดือน
$room_id = isset($_GET['room_id']) ? $_GET['room_id'] : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';
$export = isset($_GET['export']) ? $_GET['export'] : '';

// ดึงข้อมูลห้องประชุม
$stmt = $pdo->prepare("SELECT * FROM rooms WHERE is_active = 1 ORDER BY room_name");
$stmt->execute();
$rooms = $stmt->fetchAll();

// สร้าง Query สำหรับรายงาน
$sql = "
    SELECT b.*, r.room_name, r.room_code, u.fullname, u.department, u.position,
           a.fullname as approved_by_name
    FROM bookings b 
    JOIN rooms r ON b.room_id = r.room_id 
    JOIN users u ON b.user_id = u.user_id 
    LEFT JOIN users a ON b.approved_by = a.user_id
    WHERE b.booking_date BETWEEN ? AND ?
";

$params = [$start_date, $end_date];

if ($room_id) {
    $sql .= " AND b.room_id = ?";
    $params[] = $room_id;
}

if ($status) {
    $sql .= " AND b.status = ?";
    $params[] = $status;
}

$sql .= " ORDER BY b.booking_date DESC, b.start_time DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$bookings = $stmt->fetchAll();

// ส่งออกไฟล์
if ($export) {
    if ($export === 'excel') {
        exportToExcel($bookings, $start_date, $end_date);
    } elseif ($export === 'pdf') {
        // ลอง TCPDF ถ้าไม่ได้ให้ใช้ HTML-to-PDF
        try {
            exportToPDF($bookings, $start_date, $end_date);
        } catch (Exception $e) {
            // Fallback to HTML-based PDF
            exportHTMLToPDF($bookings, $start_date, $end_date);
        }
    }
    exit();
}

// ฟังก์ชันส่งออก CSV สำหรับ PHP 7.2 (ทดแทน Excel)
function exportToCSV($bookings, $start_date, $end_date) {
    $org_config = getOrganizationConfig();
    
    // Set headers for CSV download
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment;filename="booking_report_' . date('Y-m-d') . '.csv"');
    header('Cache-Control: max-age=0');
    
    // Output UTF-8 BOM for Excel compatibility
    echo "\xEF\xBB\xBF";
    
    // Create file pointer connected to output stream
    $output = fopen('php://output', 'w');
    
    // Add title rows
    fputcsv($output, array($org_config['name']));
    fputcsv($output, array('รายงานการจองห้องประชุม'));
    fputcsv($output, array('ระหว่างวันที่: ' . formatThaiDate($start_date) . ' ถึง ' . formatThaiDate($end_date)));
    fputcsv($output, array('สร้างเมื่อ: ' . date('d/m/Y H:i:s')));
    fputcsv($output, array('')); // Empty row
    
    // Add column headers
    fputcsv($output, array(
        'วันที่',
        'เวลา',
        'ห้องประชุม',
        'ผู้จอง',
        'หน่วยงาน',
        'วัตถุประสงค์',
        'สถานะ',
        'ผู้อนุมัติ'
    ));
    
    // Add data rows
    foreach ($bookings as $booking) {
        $status_text = $booking['status'] == 'approved' ? 'อนุมัติ' : 
                      ($booking['status'] == 'pending' ? 'รออนุมัติ' : 'ไม่อนุมัติ');
        
        fputcsv($output, array(
            formatThaiDate($booking['booking_date']),
            date('H:i', strtotime($booking['start_time'])) . ' - ' . date('H:i', strtotime($booking['end_time'])),
            $booking['room_name'],
            $booking['fullname'],
            $booking['department'],
            $booking['purpose'],
            $status_text,
            ($booking['approved_by_name'] ? $booking['approved_by_name'] : '-')
        ));
    }
    
    fclose($output);
}

// ฟังก์ชันส่งออก Excel (ใช้ได้เฉพาะ PHP 7.4+)
function exportToExcel($bookings, $start_date, $end_date) {
    if (!defined('VENDOR_LOADED') || !VENDOR_LOADED) {
        exportToCSV($bookings, $start_date, $end_date);
        return;
    }
    
    $org_config = getOrganizationConfig();
    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    
    // ตั้งค่าฟอนต์ที่รองรับภาษาไทย
    $spreadsheet->getDefaultStyle()->getFont()->setName('Angsana New');
    $spreadsheet->getDefaultStyle()->getFont()->setSize(14);
    
    // Style สำหรับ header
    $headerStyle = [
        'font' => [
            'name' => 'Angsana New',
            'size' => 16,
            'bold' => true
        ],
        'alignment' => [
            'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER
        ]
    ];
    
    // Style สำหรับ column headers
    $columnHeaderStyle = [
        'font' => [
            'name' => 'Angsana New',
            'size' => 14,
            'bold' => true
        ],
        'fill' => [
            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
            'color' => ['rgb' => 'E2E8F0']
        ],
        'alignment' => [
            'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER
        ]
    ];
    
    // Header
    $sheet->setCellValue('A1', 'รายงานการจองห้องประชุม - ' . $org_config['name']);
    $sheet->mergeCells('A1:H1');
    $sheet->getStyle('A1')->applyFromArray($headerStyle);
    
    $sheet->setCellValue('A2', 'ช่วงวันที่: ' . formatThaiDate($start_date) . ' ถึง ' . formatThaiDate($end_date));
    $sheet->mergeCells('A2:H2');
    $sheet->getStyle('A2')->applyFromArray($headerStyle);
    
    // Column headers
    $headers = ['วันที่', 'เวลา', 'ห้องประชุม', 'ผู้จอง', 'หน่วยงาน', 'วัตถุประสงค์', 'สถานะ', 'ผู้อนุมัติ'];
    $col = 'A';
    foreach ($headers as $header) {
        $sheet->setCellValue($col . '4', $header);
        $col++;
    }
    $sheet->getStyle('A4:H4')->applyFromArray($columnHeaderStyle);
    
    // Data
    $row = 5;
    foreach ($bookings as $booking) {
        $sheet->setCellValueExplicit('A' . $row, formatThaiDate($booking['booking_date']), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('B' . $row, date('H:i', strtotime($booking['start_time'])) . ' - ' . date('H:i', strtotime($booking['end_time'])), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('C' . $row, $booking['room_name'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('D' . $row, $booking['fullname'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('E' . $row, $booking['department'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('F' . $row, $booking['purpose'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        
        $status_text = $booking['status'] == 'approved' ? 'อนุมัติ' : ($booking['status'] == 'pending' ? 'รออนุมัติ' : 'ไม่อนุมัติ');
        $sheet->setCellValueExplicit('G' . $row, $status_text, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('H' . $row, isset($booking['approved_by_name']) ? $booking['approved_by_name'] : '-', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        $row++;
    }
    
    // Set column widths for better display
    $sheet->getColumnDimension('A')->setWidth(15); // วันที่
    $sheet->getColumnDimension('B')->setWidth(15); // เวลา
    $sheet->getColumnDimension('C')->setWidth(20); // ห้องประชุม
    $sheet->getColumnDimension('D')->setWidth(25); // ผู้จอง
    $sheet->getColumnDimension('E')->setWidth(25); // หน่วยงาน
    $sheet->getColumnDimension('F')->setWidth(35); // วัตถุประสงค์
    $sheet->getColumnDimension('G')->setWidth(15); // สถานะ
    $sheet->getColumnDimension('H')->setWidth(25); // ผู้อนุมัติ
    
    // Set headers for download
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet; charset=UTF-8');
    header('Content-Disposition: attachment;filename="booking_report_' . date('Y-m-d') . '.xlsx"');
    header('Cache-Control: max-age=0');
    
    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    $writer->save('php://output');
}

// ฟังก์ชัน HTML-to-PDF (แทน TCPDF เพื่อหลีกเลี่ยงปัญหาฟอนต์)
function exportHTMLToPDF($bookings, $start_date, $end_date) {
    $org_config = getOrganizationConfig();
    // สร้าง HTML content
    ob_clean();
    ?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>รายงานการจองห้องประชุม</title>
    <style>
        @page { margin: 1cm; }
        body { 
            font-family: "Sarabun", "Arial", sans-serif; 
            font-size: 14px;
            line-height: 1.4;
        }
        .header { 
            text-align: center; 
            margin-bottom: 20px; 
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }
        .header h2 { margin: 5px 0; font-size: 18px; }
        .header h3 { margin: 5px 0; font-size: 16px; color: #666; }
        .header p { margin: 5px 0; }
        
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-top: 10px;
        }
        th, td { 
            border: 1px solid #333; 
            padding: 6px 4px; 
            text-align: left; 
            font-size: 12px;
        }
        th { 
            background-color: #f0f0f0; 
            font-weight: bold;
            text-align: center;
        }
        .center { text-align: center; }
        .status-approved { color: #10b981; font-weight: bold; }
        .status-pending { color: #f59e0b; font-weight: bold; }
        .status-rejected { color: #ef4444; font-weight: bold; }
        
        .footer {
            margin-top: 20px;
            text-align: right;
            font-size: 11px;
            color: #666;
            border-top: 1px solid #ccc;
            padding-top: 10px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>รายงานการจองห้องประชุม</h2>
        <h3><?= $org_config['name'] ?></h3>
        <p>ช่วงวันที่: <?php echo formatThaiDate($start_date); ?> ถึง <?php echo formatThaiDate($end_date); ?></p>
    </div>
    
    <table>
        <thead>
            <tr>
                <th width="10%">วันที่</th>
                <th width="10%">เวลา</th>
                <th width="15%">ห้องประชุม</th>
                <th width="15%">ผู้จอง</th>
                <th width="15%">หน่วยงาน</th>
                <th width="25%">วัตถุประสงค์</th>
                <th width="8%">สถานะ</th>
                <th width="12%">ผู้อนุมัติ</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($bookings as $booking): ?>
                <?php
                $status_text = $booking['status'] == 'approved' ? 'อนุมัติ' : 
                              ($booking['status'] == 'pending' ? 'รออนุมัติ' : 'ไม่อนุมัติ');
                $status_class = 'status-' . $booking['status'];
                ?>
                <tr>
                    <td class="center"><?php echo formatThaiDate($booking['booking_date']); ?></td>
                    <td class="center"><?php echo date('H:i', strtotime($booking['start_time'])) . '-' . date('H:i', strtotime($booking['end_time'])); ?></td>
                    <td><?php echo htmlspecialchars($booking['room_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($booking['fullname'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($booking['department'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars(mb_substr($booking['purpose'], 0, 60, 'UTF-8'), ENT_QUOTES, 'UTF-8'); ?><?php echo mb_strlen($booking['purpose'], 'UTF-8') > 60 ? '...' : ''; ?></td>
                    <td class="center <?php echo $status_class; ?>"><?php echo $status_text; ?></td>
                    <td><?php echo htmlspecialchars(($booking['approved_by_name'] ? $booking['approved_by_name'] : '-'), ENT_QUOTES, 'UTF-8'); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <div class="footer">
        <p>พิมพ์เมื่อ: <?php echo formatThaiDate(date('Y-m-d'), 'full'); ?> เวลา <?php echo date('H:i'); ?> น.</p>
        <p>จำนวนรายการทั้งหมด: <?php echo count($bookings); ?> รายการ</p>
        <p class="font-medium"><?= $org_config['sub_title'] ?> - <?= $org_config['name'] ?></p>
    </div>
    
    <script>
        // Auto print when page loads
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 500);
        }
    </script>
</body>
</html>
    <?php
    exit();
}

// ฟังก์ชันส่งออก PDF
function exportToPDF($bookings, $start_date, $end_date) {
    $org_config = getOrganizationConfig();
    $pdf = new TCPDF('L', PDF_UNIT, 'A4', true, 'UTF-8', false); // แนวนอน
    
    // ตั้งค่าเอกสาร
    $pdf->SetCreator('Meeting Room Booking System');
    $pdf->SetAuthor($org_config['name']);
    $pdf->SetTitle('รายงานการจองห้องประชุม');
    
    // ตั้งค่าฟอนต์ไทย - ใช้ฟอนต์เริ่มต้นที่รองรับ UTF-8
    $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
    $pdf->SetMargins(10, 15, 10);
    $pdf->SetHeaderMargin(5);
    $pdf->SetFooterMargin(10);
    $pdf->SetAutoPageBreak(TRUE, 25);
    
    $pdf->AddPage();
    
    // ลองใช้ฟอนต์ที่ TCPDF รองรับแน่นอน
    try {
        $pdf->SetFont('freeserif', 'B', 16);
    } catch (Exception $e) {
        // หากไม่มี freeserif ให้ใช้ dejavu
        try {
            $pdf->SetFont('dejavusans', 'B', 16);
        } catch (Exception $e2) {
            // หากไม่มีทั้งคู่ ให้ใช้ฟอนต์เริ่มต้น
            $pdf->SetFont('helvetica', 'B', 16);
        }
    }
    
    // Header
    $pdf->Cell(0, 10, 'รายงานการจองห้องประชุม', 0, 1, 'C');
    
    // ตั้งฟอนต์สำหรับเนื้อหา
    try {
        $pdf->SetFont('freeserif', '', 14);
    } catch (Exception $e) {
        try {
            $pdf->SetFont('dejavusans', '', 14);
        } catch (Exception $e2) {
            $pdf->SetFont('helvetica', '', 14);
        }
    }
    
    $pdf->Cell(0, 8, $org_config['name'], 0, 1, 'C');
    $pdf->Cell(0, 8, 'ช่วงวันที่: ' . formatThaiDate($start_date) . ' ถึง ' . formatThaiDate($end_date), 0, 1, 'C');
    $pdf->Ln(5);
    
    // Table header - ตั้งฟอนต์หัวตาราง
    try {
        $pdf->SetFont('freeserif', 'B', 10);
    } catch (Exception $e) {
        try {
            $pdf->SetFont('dejavusans', 'B', 10);
        } catch (Exception $e2) {
            $pdf->SetFont('helvetica', 'B', 10);
        }
    }
    $pdf->SetFillColor(230, 230, 230);
    $pdf->Cell(25, 8, 'วันที่', 1, 0, 'C', 1);
    $pdf->Cell(25, 8, 'เวลา', 1, 0, 'C', 1);
    $pdf->Cell(35, 8, 'ห้องประชุม', 1, 0, 'C', 1);
    $pdf->Cell(45, 8, 'ผู้จอง', 1, 0, 'C', 1);
    $pdf->Cell(40, 8, 'หน่วยงาน', 1, 0, 'C', 1);
    $pdf->Cell(60, 8, 'วัตถุประสงค์', 1, 0, 'C', 1);
    $pdf->Cell(20, 8, 'สถานะ', 1, 0, 'C', 1);
    $pdf->Cell(35, 8, 'ผู้อนุมัติ', 1, 1, 'C', 1);
    
    // Table data - ตั้งฟอนต์เนื้อหาตาราง
    try {
        $pdf->SetFont('freeserif', '', 9);
    } catch (Exception $e) {
        try {
            $pdf->SetFont('dejavusans', '', 9);
        } catch (Exception $e2) {
            $pdf->SetFont('helvetica', '', 9);
        }
    }
    $pdf->SetFillColor(255, 255, 255);
    
    foreach ($bookings as $index => $booking) {
        // สลับสีแถว
        $fill = ($index % 2 == 0) ? 0 : 1;
        if ($fill) {
            $pdf->SetFillColor(248, 249, 250);
        } else {
            $pdf->SetFillColor(255, 255, 255);
        }
        
        $pdf->Cell(25, 8, formatThaiDate($booking['booking_date']), 1, 0, 'C', $fill);
        $pdf->Cell(25, 8, date('H:i', strtotime($booking['start_time'])) . '-' . date('H:i', strtotime($booking['end_time'])), 1, 0, 'C', $fill);
        $pdf->Cell(35, 8, $booking['room_name'], 1, 0, 'L', $fill);
        $pdf->Cell(45, 8, $booking['fullname'], 1, 0, 'L', $fill);
        $pdf->Cell(40, 8, $booking['department'], 1, 0, 'L', $fill);
        
        // ตัดข้อความวัตถุประสงค์ให้สั้นลงถ้ายาวเกินไป
        $purpose = mb_strlen($booking['purpose'], 'UTF-8') > 40 ? mb_substr($booking['purpose'], 0, 37, 'UTF-8') . '...' : $booking['purpose'];
        $pdf->Cell(60, 8, $purpose, 1, 0, 'L', $fill);
        
        $status_text = $booking['status'] == 'approved' ? 'อนุมัติ' : ($booking['status'] == 'pending' ? 'รออนุมัติ' : 'ไม่อนุมัติ');
        $pdf->Cell(20, 8, $status_text, 1, 0, 'C', $fill);
        $pdf->Cell(35, 8, ($booking['approved_by_name'] ? $booking['approved_by_name'] : '-'), 1, 1, 'L', $fill);
    }
    
    // Footer
    $pdf->Ln(5);
    try {
        $pdf->SetFont('freeserif', '', 9);
    } catch (Exception $e) {
        try {
            $pdf->SetFont('dejavusans', '', 9);
        } catch (Exception $e2) {
            $pdf->SetFont('helvetica', '', 9);
        }
    }
    $pdf->Cell(0, 8, 'พิมพ์เมื่อ: ' . formatThaiDate(date('Y-m-d'), 'full') . ' เวลา ' . date('H:i') . ' น.', 0, 1, 'R');
    $pdf->Cell(0, 8, 'จำนวนรายการทั้งหมด: ' . count($bookings) . ' รายการ', 0, 1, 'R');
    
    // Set headers for download
    header('Content-Type: application/pdf; charset=UTF-8');
    header('Content-Disposition: attachment;filename="booking_report_' . date('Y-m-d') . '.pdf"');
    
    $pdf->Output('booking_report_' . date('Y-m-d') . '.pdf', 'D');
}



// สthิติรวม
$stats = [
    'total' => count($bookings),
    'approved' => count(array_filter($bookings, function($b) { return $b['status'] === 'approved'; })),
    'pending' => count(array_filter($bookings, function($b) { return $b['status'] === 'pending'; })),
    'rejected' => count(array_filter($bookings, function($b) { return $b['status'] === 'rejected'; }))
];

// Get organization config for HTML display
$org_config = getOrganizationConfig();
?>

<!DOCTYPE html>
<html lang="th" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายงาน - ระบบจองห้องประชุม</title>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.12.10/dist/full.min.css" rel="stylesheet" type="text/css" />
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Thai Font Support -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body, html {
            font-family: 'Sarabun', 'Prompt', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            -webkit-font-feature-settings: "liga";
            font-feature-settings: "liga";
        }
        
        .thai-text {
            font-family: 'Sarabun', 'Prompt', sans-serif;
            line-height: 1.6;
        }
        
        h1, h2, h3, h4, h5, h6 {
            font-family: 'Prompt', 'Sarabun', sans-serif;
            font-weight: 600;
        }
        
        .card-title, .stat-title {
            font-family: 'Prompt', 'Sarabun', sans-serif;
            font-weight: 600;
        }
        
        .btn {
            font-family: 'Sarabun', 'Prompt', sans-serif;
            font-weight: 500;
        }
        
        .label-text {
            font-family: 'Sarabun', 'Prompt', sans-serif;
            font-weight: 500;
        }
        
        input, textarea, select {
            font-family: 'Sarabun', 'Prompt', sans-serif;
        }
        
        .navbar {
            font-family: 'Prompt', 'Sarabun', sans-serif;
        }
        
        .breadcrumbs {
            font-family: 'Sarabun', 'Prompt', sans-serif;
        }
        
        .table th, .table td {
            font-family: 'Sarabun', 'Prompt', sans-serif;
        }
        
        .badge {
            font-family: 'Sarabun', 'Prompt', sans-serif;
            font-weight: 500;
        }
        
        .table th, .table td {
            vertical-align: middle;
            padding: 12px 8px;
        }
        
        .truncate {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        /* สำหรับการพิมพ์ */
        @media print {
            .navbar, .breadcrumbs, .btn, footer, .btn-group { 
                display: none !important; 
            }
            .card { 
                box-shadow: none !important; 
                border: 1px solid #ccc; 
                break-inside: avoid;
            }
            .table {
                font-size: 12px;
            }
            .table th, .table td {
                padding: 4px 6px;
                border: 1px solid #000;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <div class="navbar bg-primary text-primary-content">
        <div class="navbar-start">
            <div class="dropdown">
                <div tabindex="0" role="button" class="btn btn-ghost lg:hidden">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h8m-8 6h16" />
                    </svg>
                </div>
                <ul tabindex="0" class="menu menu-sm dropdown-content bg-base-100 rounded-box z-[1] mt-3 w-52 p-2 shadow">
                    <?= generateNavigation('reports', isset($_SESSION['role']) ? $_SESSION['role'] : 'user', true) ?>
                </ul>
            </div>
            <a class="btn btn-ghost text-xl flex items-center gap-2" href="../index.php">
                <?php if (file_exists('../' . $org_config['logo_path'])): ?>
                    <img src="../<?= $org_config['logo_path'] ?>" alt="Logo" class="w-8 h-8 object-contain">
                <?php endif; ?>  
                <?= $org_config['sub_title'] ?>
            </a>
        </div>
        <div class="navbar-center hidden lg:flex">
            <ul class="menu menu-horizontal px-1">
                <?= generateNavigation('reports', isset($_SESSION['role']) ? $_SESSION['role'] : 'user', false) ?>
            </ul>
        </div>
        <div class="navbar-end">
            <div class="dropdown dropdown-end">
                <div tabindex="0" role="button" class="btn btn-ghost">
                    สวัสดี, <?php echo htmlspecialchars($_SESSION['username']); ?>
                </div>
                <ul tabindex="0" class="dropdown-content menu bg-base-100 rounded-box z-[1] w-52 p-2 shadow">
                    <li><a href="../profile.php" class="text-base-content">โปรไฟล์</a></li>
                    <li><a href="../version_info.php" class="text-base-content">ข้อมูลระบบ</a></li>
                    <li><a href="../logout.php" class="text-base-content">ออกจากระบบ</a></li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container mx-auto p-4">
        <div class="breadcrumbs text-sm mb-4">
            <ul>
                <li><a href="index.php">หน้าหลัก</a></li>
                <li>รายงาน</li>
            </ul>
        </div>

        <!-- ฟิลเตอร์รายงาน -->
        <div class="card bg-base-100 shadow-xl mb-6">
            <div class="card-body">
                <h3 class="card-title">ตัวกรองรายงาน</h3>
                
                <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-4">
                    <div class="form-control">
                        <label class="label">
                            <span class="label-text">วันที่เริ่มต้น</span>
                        </label>
                        <input type="date" name="start_date" class="input input-bordered" 
                               value="<?php echo htmlspecialchars($start_date); ?>">
                    </div>
                    
                    <div class="form-control">
                        <label class="label">
                            <span class="label-text">วันที่สิ้นสุด</span>
                        </label>
                        <input type="date" name="end_date" class="input input-bordered" 
                               value="<?php echo htmlspecialchars($end_date); ?>">
                    </div>
                    
                    <div class="form-control">
                        <label class="label">
                            <span class="label-text">ห้องประชุม</span>
                        </label>
                        <select name="room_id" class="select select-bordered">
                            <option value="">ทุกห้อง</option>
                            <?php foreach ($rooms as $room): ?>
                                <option value="<?php echo $room['room_id']; ?>" 
                                        <?php echo $room_id == $room['room_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($room['room_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-control">
                        <label class="label">
                            <span class="label-text">สถานะ</span>
                        </label>
                        <select name="status" class="select select-bordered">
                            <option value="">ทุกสถานะ</option>
                            <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>รออนุมัติ</option>
                            <option value="approved" <?php echo $status === 'approved' ? 'selected' : ''; ?>>อนุมัติ</option>
                            <option value="rejected" <?php echo $status === 'rejected' ? 'selected' : ''; ?>>ไม่อนุมัติ</option>
                        </select>
                    </div>
                    
                    <div class="form-control">
                        <label class="label">
                            <span class="label-text">&nbsp;</span>
                        </label>
                        <button type="submit" class="btn btn-primary">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            ค้นหา
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- สถิติ -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="stat bg-base-100 shadow-xl rounded-box">
                <div class="stat-title">รวมทั้งหมด</div>
                <div class="stat-value text-primary"><?php echo $stats['total']; ?></div>
                <div class="stat-desc">รายการ</div>
            </div>
            <div class="stat bg-base-100 shadow-xl rounded-box">
                <div class="stat-title">อนุมัติแล้ว</div>
                <div class="stat-value text-success"><?php echo $stats['approved']; ?></div>
                <div class="stat-desc"><?php echo $stats['total'] > 0 ? round(($stats['approved']/$stats['total'])*100, 1) : 0; ?>%</div>
            </div>
            <div class="stat bg-base-100 shadow-xl rounded-box">
                <div class="stat-title">รออนุมัติ</div>
                <div class="stat-value text-warning"><?php echo $stats['pending']; ?></div>
                <div class="stat-desc"><?php echo $stats['total'] > 0 ? round(($stats['pending']/$stats['total'])*100, 1) : 0; ?>%</div>
            </div>
            <div class="stat bg-base-100 shadow-xl rounded-box">
                <div class="stat-title">ไม่อนุมัติ</div>
                <div class="stat-value text-error"><?php echo $stats['rejected']; ?></div>
                <div class="stat-desc"><?php echo $stats['total'] > 0 ? round(($stats['rejected']/$stats['total'])*100, 1) : 0; ?>%</div>
            </div>
        </div>

        <!-- Admin/Manager Dashboard สำหรับส่งสรุปการจอง -->
        <?php if (isset($_SESSION['role']) && ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'manager')): ?>
        <div class="card bg-gradient-to-r from-purple-500 to-pink-500 text-white shadow-xl mt-6">
            <div class="card-body">
                <h3 class="card-title flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.367 2.684 3 3 0 00-5.367-2.684z" />
                    </svg>
                    Dashboard - ส่งสรุปการจองผ่าน Telegram
                </h3>
                
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-4">
                    <!-- ส่วนเลือกวันที่และ User -->
                    <div class="bg-white/20 rounded-lg p-4 backdrop-blur-sm">
                        <h4 class="font-semibold mb-3 flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            ส่งสรุปการจองแบบกำหนดเอง
                        </h4>
                        
                        <form id="telegramSummaryForm" class="space-y-3">
                            <div class="grid grid-cols-2 gap-2">
                                <div class="form-control">
                                    <label class="label">
                                        <span class="label-text text-white">วันที่เริ่มต้น</span>
                                    </label>
                                    <input type="date" id="startDateTelegram" name="start_date" class="input input-bordered text-black" 
                                           value="<?php echo htmlspecialchars($start_date); ?>" max="<?php echo date('Y-m-d', strtotime('+30 days')); ?>">
                                </div>
                                
                                <div class="form-control">
                                    <label class="label">
                                        <span class="label-text text-white">วันที่สิ้นสุด</span>
                                    </label>
                                    <input type="date" id="endDateTelegram" name="end_date" class="input input-bordered text-black" 
                                           value="<?php echo htmlspecialchars($end_date); ?>" max="<?php echo date('Y-m-d', strtotime('+30 days')); ?>">
                                </div>
                            </div>
                            
                            <div class="form-control">
                                <label class="label">
                                    <span class="label-text text-white">ช่วงเวลาที่กำหนดไว้</span>
                                </label>
                                <div class="btn-group w-full">
                                    <button type="button" onclick="setDateRange('today')" class="btn btn-sm btn-outline text-white border-white/30 hover:bg-white/20">วันนี้</button>
                                    <button type="button" onclick="setDateRange('week')" class="btn btn-sm btn-outline text-white border-white/30 hover:bg-white/20">สัปดาห์นี้</button>
                                    <button type="button" onclick="setDateRange('month')" class="btn btn-sm btn-outline text-white border-white/30 hover:bg-white/20">เดือนนี้</button>
                                </div>
                            </div>
                            
                            <div class="form-control">
                                <label class="label">
                                    <span class="label-text text-white">เลือกผู้รับ</span>
                                </label>
                                <select id="recipient" name="recipient" class="select select-bordered text-black">
                                    <option value="all">ส่งให้ทุกคน</option>
                                    <option value="admins">ส่งให้ Admin เท่านั้น</option>
                                    <option value="managers">ส่งให้ Manager และ Admin</option>
                                    <option value="custom">เลือกผู้ใช้เฉพาะ</option>
                                </select>
                            </div>
                            
                            <div id="userSelector" class="form-control hidden">
                                <label class="label">
                                    <span class="label-text text-white">เลือกผู้ใช้</span>
                                </label>
                                <select multiple id="selectedUsers" name="selected_users[]" class="select select-bordered text-black h-32">
                                    <?php
                                    // ดึงรายชื่อผู้ใช้ทั้งหมด
                                    $stmt_users = $pdo->prepare("SELECT user_id, fullname, username, role FROM users WHERE is_active = 1 ORDER BY role DESC, fullname");
                                    $stmt_users->execute();
                                    $all_users = $stmt_users->fetchAll();
                                    
                                    foreach ($all_users as $user):
                                        $role_badge = $user['role'] === 'admin' ? '[Admin]' : 
                                                     ($user['role'] === 'manager' ? '[Manager]' : '[User]');
                                    ?>
                                        <option value="<?php echo $user['user_id']; ?>">
                                            <?php echo htmlspecialchars($user['fullname'] . ' ' . $role_badge); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-control">
                                <label class="label">
                                    <span class="label-text text-white">ประเภทรายงาน</span>
                                </label>
                                <select id="reportType" name="report_type" class="select select-bordered text-black">
                                    <option value="summary">สรุปทั่วไป</option>
                                    <option value="detailed">รายละเอียดครบถ้วน</option>
                                    <option value="pending_only">เฉพาะรออนุมัติ</option>
                                    <option value="approved_only">เฉพาะอนุมัติแล้ว</option>
                                </select>
                            </div>
                            
                            <button type="submit" class="btn btn-warning w-full" id="sendTelegramBtn">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                                </svg>
                                ส่งผ่าน Telegram
                            </button>
                        </form>
                    </div>
                    
                    <!-- ส่วนตัวอย่างข้อความ -->
                    <div class="bg-white/20 rounded-lg p-4 backdrop-blur-sm">
                        <h4 class="font-semibold mb-3 flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                            ตัวอย่างข้อความ
                        </h4>
                        
                        <div id="messagePreview" class="bg-black/30 rounded p-3 text-sm font-mono">
                            <div class="text-yellow-300">📊 สรุปการจองห้องประชุม</div>
                            <div class="text-blue-300">🏢 <?php echo htmlspecialchars($org_config['name']); ?></div>
                            <div class="text-green-300">📅 วันที่: <?php echo formatThaiDate(date('Y-m-d'), 'full'); ?></div>
                            <div class="mt-2">
                                <div class="text-white">📈 สถิติ:</div>
                                <div class="ml-2">
                                    <div>✅ อนุมัติแล้ว: <?php echo $stats['approved']; ?> รายการ</div>
                                    <div>⏳ รออนุมัติ: <?php echo $stats['pending']; ?> รายการ</div>
                                    <div>🔢 รวมทั้งหมด: <?php echo $stats['total']; ?> รายการ</div>
                                </div>
                            </div>
                            <div class="mt-2 text-gray-300">⏰ ส่งเมื่อ: [เวลาปัจจุบัน]</div>
                        </div>
                        
                        <!-- Quick Actions -->
                        <div class="mt-4 space-y-2">
                            <button onclick="sendQuickSummary('today')" class="btn btn-sm btn-info w-full">
                                📊 ส่งสรุปวันนี้ (ด่วน)
                            </button>
                            <button onclick="sendQuickSummary('pending')" class="btn btn-sm btn-warning w-full">
                                ⏳ ส่งรายการรออนุมัติ (ด่วน)
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- สถิติการส่ง Telegram -->
                <div class="mt-6 grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="stat bg-white/20 rounded-lg text-center">
                        <div class="stat-title text-white/80">ส่งวันนี้</div>
                        <div class="stat-value text-2xl">0</div>
                        <div class="stat-desc text-white/60">ข้อความ</div>
                    </div>
                    <div class="stat bg-white/20 rounded-lg text-center">
                        <div class="stat-title text-white/80">ส่งสัปดาห์นี้</div>
                        <div class="stat-value text-2xl">0</div>
                        <div class="stat-desc text-white/60">ข้อความ</div>
                    </div>
                    <div class="stat bg-white/20 rounded-lg text-center">
                        <div class="stat-title text-white/80">ผู้รับทั้งหมด</div>
                        <div class="stat-value text-2xl"><?php echo count($all_users); ?></div>
                        <div class="stat-desc text-white/60">คน</div>
                    </div>
                    <div class="stat bg-white/20 rounded-lg text-center">
                        <div class="stat-title text-white/80">สถานะ Telegram</div>
                        <div class="stat-value text-lg text-success">✅</div>
                        <div class="stat-desc text-white/60">เชื่อมต่อแล้ว</div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- รายงาน -->
        <div class="card bg-base-100 shadow-xl">
            <div class="card-body">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="card-title">รายงานการจองห้องประชุม</h3>
                    <div class="btn-group">
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['export' => 'excel'])); ?>" 
                           class="btn btn-success btn-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <?php echo (defined('VENDOR_LOADED') && VENDOR_LOADED) ? 'Excel' : 'CSV'; ?>
                        </a>
                        <div class="dropdown dropdown-end">
                            <label tabindex="0" class="btn btn-error btn-sm">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                PDF
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </label>
                            <ul tabindex="0" class="dropdown-content menu p-2 shadow bg-base-100 rounded-box w-40">
                                <li><a href="?<?php echo http_build_query(array_merge($_GET, ['export' => 'pdf'])); ?>">PDF แบบตาราง</a></li>
                                <li><a href="javascript:printReport()">พิมพ์หน้าเว็บ</a></li>
                            </ul>
                        </div>
                        <button onclick="window.print()" class="btn btn-info btn-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                            </svg>
                            พิมพ์
                        </button>
                    </div>
                </div>

                <?php if (empty($bookings)): ?>
                    <div class="text-center py-8">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mx-auto text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <p class="text-gray-500 text-lg">ไม่พบข้อมูลในช่วงเวลาที่เลือก</p>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="table" id="reportTable">
                            <thead>
                                <tr>
                                    <th>วันที่</th>
                                    <th>เวลา</th>
                                    <th>ห้องประชุม</th>
                                    <th>ผู้จอง</th>
                                    <th>หน่วยงาน</th>
                                    <th>วัตถุประสงค์</th>
                                    <th>สถานะ</th>
                                    <th>ผู้อนุมัติ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($bookings as $booking): ?>
                                    <tr>
                                        <td>
                                            <div class="font-bold text-primary">
                                                <?php echo formatThaiDate($booking['booking_date']); ?>
                                            </div>
                                            <div class="text-sm opacity-50">
                                                <?php echo date('l', strtotime($booking['booking_date'])); ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="font-mono text-sm">
                                                <?php echo date('H:i', strtotime($booking['start_time'])); ?> - 
                                                <?php echo date('H:i', strtotime($booking['end_time'])); ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="font-bold"><?php echo htmlspecialchars($booking['room_name'], ENT_QUOTES, 'UTF-8'); ?></div>
                                            <div class="text-sm opacity-50"><?php echo htmlspecialchars($booking['room_code'], ENT_QUOTES, 'UTF-8'); ?></div>
                                        </td>
                                        <td>
                                            <div class="font-bold"><?php echo htmlspecialchars($booking['fullname'], ENT_QUOTES, 'UTF-8'); ?></div>
                                            <div class="text-sm opacity-50"><?php echo htmlspecialchars($booking['position'], ENT_QUOTES, 'UTF-8'); ?></div>
                                        </td>
                                        <td>
                                            <div class="text-sm"><?php echo htmlspecialchars($booking['department'], ENT_QUOTES, 'UTF-8'); ?></div>
                                        </td>
                                        <td class="max-w-xs">
                                            <div class="truncate" title="<?php echo htmlspecialchars($booking['purpose'], ENT_QUOTES, 'UTF-8'); ?>">
                                                <?php echo htmlspecialchars($booking['purpose'], ENT_QUOTES, 'UTF-8'); ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="badge <?php 
                                                echo $booking['status'] == 'approved' ? 'badge-success' : 
                                                     ($booking['status'] == 'pending' ? 'badge-warning' : 'badge-error'); 
                                            ?>">
                                                <?php 
                                                    echo $booking['status'] == 'approved' ? 'อนุมัติ' : 
                                                         ($booking['status'] == 'pending' ? 'รออนุมัติ' : 'ไม่อนุมัติ'); 
                                                ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="text-sm">
                                                <?php echo $booking['approved_by_name'] ? htmlspecialchars($booking['approved_by_name'], ENT_QUOTES, 'UTF-8') : '-'; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <?php 
    if (file_exists('../version.php')) {
        require_once '../version.php'; 
        if (function_exists('getSystemFooter')) {
            echo getSystemFooter();
        }
    }
    ?>

    <style>
        @media print {
            .navbar, .breadcrumbs, .btn, footer { display: none !important; }
            .card { box-shadow: none !important; border: 1px solid #ccc; }
        }
    </style>

    <!-- JavaScript for filtering, export functionality and Telegram Dashboard -->
    <script>
    // Telegram Dashboard Functions
    function setDateRange(period) {
        var today = new Date();
        var startInput = document.getElementById('startDateTelegram');
        var endInput = document.getElementById('endDateTelegram');
        
        switch (period) {
            case 'today':
                var todayStr = today.toISOString().split('T')[0];
                startInput.value = todayStr;
                endInput.value = todayStr;
                break;
            case 'week':
                var startOfWeek = new Date(today);
                startOfWeek.setDate(today.getDate() - today.getDay());
                var endOfWeek = new Date(startOfWeek);
                endOfWeek.setDate(startOfWeek.getDate() + 6);
                startInput.value = startOfWeek.toISOString().split('T')[0];
                endInput.value = endOfWeek.toISOString().split('T')[0];
                break;
            case 'month':
                var startOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);
                var endOfMonth = new Date(today.getFullYear(), today.getMonth() + 1, 0);
                startInput.value = startOfMonth.toISOString().split('T')[0];
                endInput.value = endOfMonth.toISOString().split('T')[0];
                break;
        }
        updateMessagePreview();
    }
    
    function updateMessagePreview() {
        var startDate = document.getElementById('startDateTelegram').value;
        var endDate = document.getElementById('endDateTelegram').value;
        var reportType = document.getElementById('reportType').value;
        var recipient = document.getElementById('recipient').value;
        
        var dateText = startDate;
        if (startDate !== endDate) {
            dateText = startDate + ' ถึง ' + endDate;
        }
        
        var typeText = 'สรุปทั่วไป';
        switch (reportType) {
            case 'detailed': typeText = 'รายละเอียดครบถ้วน'; break;
            case 'pending_only': typeText = 'เฉพาะรออนุมัติ'; break;
            case 'approved_only': typeText = 'เฉพาะอนุมัติแล้ว'; break;
        }
        
        var recipientText = 'ทุกคน';
        switch (recipient) {
            case 'admins': recipientText = 'Admin เท่านั้น'; break;
            case 'managers': recipientText = 'Manager และ Admin'; break;
            case 'custom': recipientText = 'ผู้ใช้ที่เลือก'; break;
        }
        
        var preview = '📊 สรุปการจองห้องประชุม<br>' +
                     '🏢 <?php echo htmlspecialchars($org_config["name"]); ?><br>' +
                     '📅 วันที่: ' + dateText + '<br>' +
                     '📋 ประเภท: ' + typeText + '<br>' +
                     '👥 ส่งให้: ' + recipientText + '<br>' +
                     '⏰ ส่งเมื่อ: ' + new Date().toLocaleString('th-TH');
        
        document.getElementById('messagePreview').innerHTML = preview;
    }
    
    // Handle recipient selection
    document.getElementById('recipient').addEventListener('change', function() {
        var userSelector = document.getElementById('userSelector');
        if (this.value === 'custom') {
            userSelector.classList.remove('hidden');
        } else {
            userSelector.classList.add('hidden');
        }
        updateMessagePreview();
    });
    
    // Handle Telegram summary form submission
    document.getElementById('telegramSummaryForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        var formData = new FormData(this);
        var submitBtn = document.getElementById('sendTelegramBtn');
        var originalText = submitBtn.innerHTML;
        
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="loading loading-spinner loading-sm"></span> กำลังส่ง...';
        
        fetch('send_telegram_summary.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Swal.fire({
                    title: 'สำเร็จ!',
                    text: data.message || 'ส่งสรุปการจองผ่าน Telegram เรียบร้อยแล้ว',
                    icon: 'success',
                    confirmButtonText: 'ตกลง'
                });
            } else {
                // ตรวจสอบว่าต้อง redirect หรือไม่
                if (data.redirect) {
                    Swal.fire({
                        title: 'กรุณาเข้าสู่ระบบใหม่!',
                        text: data.message || 'Session หมดอายุ',
                        icon: 'warning',
                        confirmButtonText: 'เข้าสู่ระบบ'
                    }).then(() => {
                        window.location.href = data.redirect;
                    });
                } else {
                    Swal.fire({
                        title: 'เกิดข้อผิดพลาด!',
                        text: data.message || 'ไม่สามารถส่งข้อความได้',
                        icon: 'error',
                        confirmButtonText: 'ตกลง'
                    });
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                title: 'เกิดข้อผิดพลาด!',
                text: 'เกิดข้อผิดพลาดในการเชื่อมต่อ',
                icon: 'error',
                confirmButtonText: 'ตกลง'
            });
        })
        .finally(() => {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        });
    });
    
    // Quick send functions
    function sendQuickSummary(type) {
        var today = new Date().toISOString().split('T')[0];
        var params = new URLSearchParams();
        
        if (type === 'today') {
            params.append('start_date', today);
            params.append('end_date', today);
            params.append('report_type', 'summary');
            params.append('recipient', 'all');
        } else if (type === 'pending') {
            params.append('start_date', today);
            params.append('end_date', today);
            params.append('report_type', 'pending_only');
            params.append('recipient', 'managers');
        }
        
        var btn = event.target;
        var originalText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="loading loading-spinner loading-sm"></span> กำลังส่ง...';
        
        fetch('send_telegram_summary.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: params
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Swal.fire({
                    title: 'สำเร็จ!',
                    text: data.message || 'ส่งสรุปการจองผ่าน Telegram เรียบร้อยแล้ว',
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false
                });
            } else {
                // ตรวจสอบว่าต้อง redirect หรือไม่
                if (data.redirect) {
                    Swal.fire({
                        title: 'กรุณาเข้าสู่ระบบใหม่!',
                        text: data.message || 'Session หมดอายุ',
                        icon: 'warning',
                        confirmButtonText: 'เข้าสู่ระบบ'
                    }).then(() => {
                        window.location.href = data.redirect;
                    });
                } else {
                    Swal.fire({
                        title: 'เกิดข้อผิดพลาด!',
                        text: data.message || 'ไม่สามารถส่งข้อความได้',
                        icon: 'error',
                        confirmButtonText: 'ตกลง'
                    });
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                title: 'เกิดข้อผิดพลาด!',
                text: 'เกิดข้อผิดพลาดในการเชื่อมต่อ',
                icon: 'error',
                confirmButtonText: 'ตกลง'
            });
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = originalText;
        });
    }
    
    // Initialize date inputs and preview
    document.addEventListener('DOMContentLoaded', function() {
        updateMessagePreview();
        
        // Add event listeners for date changes
        document.getElementById('startDateTelegram').addEventListener('change', updateMessagePreview);
        document.getElementById('endDateTelegram').addEventListener('change', updateMessagePreview);
        document.getElementById('reportType').addEventListener('change', updateMessagePreview);
    });
    </script>
</body>
</html>