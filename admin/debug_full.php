<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ทดสอบ Reports Telegram - Debug</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <div style="padding: 20px; font-family: Arial, sans-serif;">
        <h1>🔍 ทดสอบ Reports Telegram - Debug</h1>
        
        <!-- Mock Login -->
        <div style="background: #e8f5e8; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
            <h3>🔐 Mock Login</h3>
            <button onclick="mockLogin()" style="background: #4CAF50; color: white; padding: 10px 15px; border: none; border-radius: 5px;">เข้าสู่ระบบแบบ Mock</button>
            <div id="loginStatus" style="margin-top: 10px;"></div>
        </div>

        <!-- Test Form -->
        <div style="background: #f0f8ff; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
            <h3>📋 ทดสอบส่ง Telegram</h3>
            
            <!-- Custom Form -->
            <div>
                <h4>📊 แบบกำหนดเอง</h4>
                <form id="customForm">
                    <label>วันที่เริ่ม: <input type="date" name="start_date" value="<?php echo date('Y-m-d'); ?>"></label><br><br>
                    <label>วันที่สิ้นสุด: <input type="date" name="end_date" value="<?php echo date('Y-m-d'); ?>"></label><br><br>
                    <label>ประเภท: 
                        <select name="report_type">
                            <option value="summary">สรุป</option>
                            <option value="detailed">รายละเอียด</option>
                            <option value="pending_only">รออนุมัติ</option>
                        </select>
                    </label><br><br>
                    <label>ผู้รับ: 
                        <select name="recipient">
                            <option value="all">ทุกคน</option>
                            <option value="admins">Admin</option>
                            <option value="managers">Manager และ Admin</option>
                        </select>
                    </label><br><br>
                    <button type="button" onclick="sendCustomTelegram()" style="background: #2196F3; color: white; padding: 10px 20px; border: none; border-radius: 5px;">ส่งแบบกำหนดเอง</button>
                </form>
            </div>

            <hr style="margin: 20px 0;">

            <!-- Quick Actions -->
            <div>
                <h4>⚡ Quick Actions</h4>
                <button onclick="sendQuickTelegram('today')" style="background: #FF9800; color: white; padding: 10px 15px; border: none; border-radius: 5px; margin-right: 10px;">ส่งสรุปวันนี้</button>
                <button onclick="sendQuickTelegram('pending')" style="background: #f44336; color: white; padding: 10px 15px; border: none; border-radius: 5px;">ส่งรายการรออนุมัติ</button>
            </div>
        </div>

        <!-- Debug Panel -->
        <div style="background: #fff3cd; padding: 15px; border-radius: 8px; border-left: 4px solid #ffc107;">
            <h3>🐛 Debug Information</h3>
            <div id="debugInfo" style="font-family: monospace; background: white; padding: 10px; border-radius: 4px; white-space: pre-wrap;"></div>
        </div>
    </div>

    <script>
        const debugDiv = document.getElementById('debugInfo');
        const loginDiv = document.getElementById('loginStatus');
        
        function log(message) {
            const timestamp = new Date().toLocaleTimeString();
            debugDiv.innerHTML += `[${timestamp}] ${message}\n`;
            console.log(`[${timestamp}] ${message}`);
        }

        async function mockLogin() {
            log('🔐 กำลัง Mock Login...');
            try {
                const response = await fetch('mock_login.php');
                const text = await response.text();
                log('✅ Mock Login สำเร็จ');
                loginDiv.innerHTML = '<span style="color: green;">✅ Login แบบ Mock สำเร็จ</span>';
            } catch (error) {
                log('❌ Mock Login ล้มเหลว: ' + error.message);
                loginDiv.innerHTML = '<span style="color: red;">❌ Mock Login ล้มเหลว</span>';
            }
        }

        async function sendCustomTelegram() {
            log('📊 เริ่มส่ง Telegram แบบกำหนดเอง...');
            
            const form = document.getElementById('customForm');
            const formData = new FormData(form);
            
            log('📝 ข้อมูลฟอร์ม: ' + JSON.stringify(Object.fromEntries(formData)));
            
            await sendTelegramRequest(formData, 'แบบกำหนดเอง');
        }

        async function sendQuickTelegram(type) {
            log(`⚡ เริ่มส่ง Telegram Quick: ${type}...`);
            
            const formData = new FormData();
            formData.append('quick_type', type);
            
            if (type === 'today') {
                formData.append('start_date', '<?php echo date('Y-m-d'); ?>');
                formData.append('end_date', '<?php echo date('Y-m-d'); ?>');
                formData.append('report_type', 'summary');
                formData.append('recipient', 'all');
            } else if (type === 'pending') {
                formData.append('start_date', '<?php echo date('Y-m-d'); ?>');
                formData.append('end_date', '<?php echo date('Y-m-d'); ?>');
                formData.append('report_type', 'pending_only');
                formData.append('recipient', 'managers');
            }
            
            log('📝 ข้อมูล Quick: ' + JSON.stringify(Object.fromEntries(formData)));
            
            await sendTelegramRequest(formData, `Quick: ${type}`);
        }

        async function sendTelegramRequest(formData, description) {
            try {
                log(`📡 ส่ง Request ${description}...`);
                
                const startTime = Date.now();
                
                const response = await fetch('send_telegram_summary.php', {
                    method: 'POST',
                    body: formData
                });
                
                const endTime = Date.now();
                log(`⏱️ Response Time: ${endTime - startTime}ms`);
                
                log(`📨 Response Status: ${response.status} ${response.statusText}`);
                log(`📋 Response Headers: ${JSON.stringify(Object.fromEntries(response.headers))}`);
                
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                
                const rawText = await response.text();
                log(`📄 Raw Response (${rawText.length} chars): ${rawText.substring(0, 500)}${rawText.length > 500 ? '...' : ''}`);
                
                try {
                    const data = JSON.parse(rawText);
                    log(`✅ JSON Parsed: ${JSON.stringify(data, null, 2)}`);
                    
                    if (data.success) {
                        log('🎉 ส่งสำเร็จ!');
                        Swal.fire({
                            title: 'สำเร็จ!',
                            text: data.message || 'ส่งสรุปการจองผ่าน Telegram เรียบร้อยแล้ว',
                            icon: 'success',
                            confirmButtonText: 'ตกลง'
                        });
                    } else {
                        log(`❌ ส่งล้มเหลว: ${data.message}`);
                        
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
                } catch (parseError) {
                    log(`❌ JSON Parse Error: ${parseError.message}`);
                    log(`📄 Raw response ที่ไม่สามารถ parse ได้: "${rawText}"`);
                    
                    Swal.fire({
                        title: 'เกิดข้อผิดพลาด!',
                        text: 'ไม่สามารถแปลงข้อมูลได้: ' + parseError.message,
                        icon: 'error',
                        confirmButtonText: 'ตกลง'
                    });
                }
                
            } catch (fetchError) {
                log(`❌ Fetch Error: ${fetchError.message}`);
                
                Swal.fire({
                    title: 'เกิดข้อผิดพลาด!',
                    text: 'เกิดข้อผิดพลาดในการเชื่อมต่อ: ' + fetchError.message,
                    icon: 'error',
                    confirmButtonText: 'ตกลง'
                });
            }
        }

        // Auto mock login on load
        window.addEventListener('load', function() {
            log('🚀 หน้าเว็บโหลดเสร็จแล้ว');
            mockLogin();
        });
    </script>
</body>
</html>