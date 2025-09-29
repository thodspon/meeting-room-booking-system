#!/bin/bash
# สคริปต์ปรับสิทธิ์สำหรับ Meeting Room Booking System บน AlmaLinux 9
# Usage: sudo bash setup_permissions.sh

set -e

# ตัวแปรการตั้งค่า
PROJECT_PATH="/var/www/html/meeting-room-booking-system"
WEB_USER="apache"
WEB_GROUP="apache"

# สี
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${GREEN}🚀 เริ่มต้นการตั้งค่าสิทธิ์สำหรับ Meeting Room Booking System${NC}"

# ตรวจสอบว่าเป็น root หรือไม่
if [[ $EUID -ne 0 ]]; then
   echo -e "${RED}❌ กรุณารันสคริปต์นี้ด้วย sudo${NC}"
   exit 1
fi

# ตรวจสอบว่าโฟลเดอร์โปรเจคมีอยู่หรือไม่
if [ ! -d "$PROJECT_PATH" ]; then
   echo -e "${RED}❌ ไม่พบโฟลเดอร์โปรเจค: $PROJECT_PATH${NC}"
   echo -e "${YELLOW}💡 กรุณาปรับแก้ PROJECT_PATH ในสคริปต์ให้ถูกต้อง${NC}"
   exit 1
fi

echo -e "${YELLOW}📁 ทำงานกับโฟลเดอร์: $PROJECT_PATH${NC}"

# 1. เปลี่ยนเจ้าของไฟล์
echo -e "${GREEN}1️⃣ กำลังเปลี่ยนเจ้าของไฟล์เป็น $WEB_USER:$WEB_GROUP${NC}"
chown -R $WEB_USER:$WEB_GROUP $PROJECT_PATH
echo "✅ เปลี่ยนเจ้าของไฟล์เสร็จสิ้น"

# 2. ตั้งสิทธิ์โฟลเดอร์และไฟล์
echo -e "${GREEN}2️⃣ กำลังตั้งสิทธิ์โฟลเดอร์และไฟล์${NC}"
find $PROJECT_PATH -type d -exec chmod 755 {} \;
find $PROJECT_PATH -type f -exec chmod 644 {} \;
echo "✅ ตั้งสิทธิ์พื้นฐานเสร็จสิ้น"

# 3. ตั้งสิทธิ์พิเศษสำหรับโฟลเดอร์สำคัญ
echo -e "${GREEN}3️⃣ กำลังตั้งสิทธิ์พิเศษสำหรับโฟลเดอร์สำคัญ${NC}"

# โฟลเดอร์ config
if [ -d "$PROJECT_PATH/config" ]; then
    chmod -R 755 $PROJECT_PATH/config/
    chown -R $WEB_USER:$WEB_GROUP $PROJECT_PATH/config/
    echo "✅ ตั้งสิทธิ์โฟลเดอร์ config"
fi

# ไฟล์ database.php
if [ -f "$PROJECT_PATH/config/database.php" ]; then
    chmod 640 $PROJECT_PATH/config/database.php
    echo "✅ ตั้งสิทธิ์ไฟล์ database.php"
fi

# โฟลเดอร์ assets
if [ -d "$PROJECT_PATH/assets" ]; then
    chmod -R 755 $PROJECT_PATH/assets/
    chown -R $WEB_USER:$WEB_GROUP $PROJECT_PATH/assets/
    echo "✅ ตั้งสิทธิ์โฟลเดอร์ assets"
fi

# สร้างและตั้งสิทธิ์โฟลเดอร์ logs
mkdir -p $PROJECT_PATH/logs/
chmod -R 755 $PROJECT_PATH/logs/
chown -R $WEB_USER:$WEB_GROUP $PROJECT_PATH/logs/
echo "✅ สร้างและตั้งสิทธิ์โฟลเดอร์ logs"

# สร้างและตั้งสิทธิ์โฟลเดอร์ cache
mkdir -p $PROJECT_PATH/cache/
chmod -R 755 $PROJECT_PATH/cache/
chown -R $WEB_USER:$WEB_GROUP $PROJECT_PATH/cache/
echo "✅ สร้างและตั้งสิทธิ์โฟลเดอร์ cache"

# 4. ตั้งค่า SELinux
echo -e "${GREEN}4️⃣ กำลังตั้งค่า SELinux${NC}"

# ตรวจสอบว่า SELinux เปิดใช้งานหรือไม่
if command -v getenforce > /dev/null 2>&1; then
    SELINUX_STATUS=$(getenforce)
    if [ "$SELINUX_STATUS" != "Disabled" ]; then
        echo "🔐 SELinux Status: $SELINUX_STATUS"
        
        # ตั้งค่า SELinux booleans
        setsebool -P httpd_can_network_connect 1
        setsebool -P httpd_can_network_connect_db 1
        setsebool -P httpd_execmem 1
        setsebool -P httpd_unified 1
        echo "✅ ตั้งค่า SELinux booleans"
        
        # ตั้งค่า file contexts
        semanage fcontext -a -t httpd_exec_t "$PROJECT_PATH(/.*)?" 2>/dev/null || true
        semanage fcontext -a -t httpd_sys_rw_content_t "$PROJECT_PATH/config(/.*)?" 2>/dev/null || true
        semanage fcontext -a -t httpd_sys_rw_content_t "$PROJECT_PATH/assets(/.*)?" 2>/dev/null || true
        semanage fcontext -a -t httpd_sys_rw_content_t "$PROJECT_PATH/logs(/.*)?" 2>/dev/null || true
        semanage fcontext -a -t httpd_sys_rw_content_t "$PROJECT_PATH/cache(/.*)?" 2>/dev/null || true
        echo "✅ ตั้งค่า SELinux file contexts"
        
        # Restore contexts
        restorecon -Rv $PROJECT_PATH
        echo "✅ Restore SELinux contexts"
    else
        echo "ℹ️  SELinux ปิดใช้งาน - ข้ามการตั้งค่า SELinux"
    fi
else
    echo "ℹ️  ไม่พบ SELinux - ข้ามการตั้งค่า SELinux"
fi

# 5. ตั้งค่า Firewall
echo -e "${GREEN}5️⃣ กำลังตั้งค่า Firewall${NC}"
if command -v firewall-cmd > /dev/null 2>&1; then
    firewall-cmd --permanent --add-service=http 2>/dev/null || true
    firewall-cmd --permanent --add-service=https 2>/dev/null || true
    firewall-cmd --reload 2>/dev/null || true
    echo "✅ เปิดพอร์ต HTTP และ HTTPS"
else
    echo "ℹ️  ไม่พบ firewall-cmd - ข้ามการตั้งค่า Firewall"
fi

# 6. ตรวจสอบการตั้งค่า
echo -e "${GREEN}6️⃣ กำลังตรวจสอบการตั้งค่า${NC}"
echo "📋 ข้อมูลโฟลเดอร์หลัก:"
ls -la $PROJECT_PATH | head -10

echo -e "\n📋 ข้อมูลโฟลเดอร์ config:"
if [ -d "$PROJECT_PATH/config" ]; then
    ls -la $PROJECT_PATH/config/
else
    echo "❌ ไม่พบโฟลเดอร์ config"
fi

# 7. แสดงสรุป
echo -e "\n${GREEN}🎉 การตั้งค่าสิทธิ์เสร็จสิ้น!${NC}"
echo -e "${YELLOW}📝 สรุปการตั้งค่า:${NC}"
echo "   - เจ้าของไฟล์: $WEB_USER:$WEB_GROUP"
echo "   - สิทธิ์โฟลเดอร์: 755"
echo "   - สิทธิ์ไฟล์: 644"
echo "   - database.php: 640"
echo "   - SELinux: ตั้งค่าแล้ว (หากเปิดใช้งาน)"
echo "   - Firewall: เปิดพอร์ต HTTP/HTTPS แล้ว"

echo -e "\n${GREEN}🚀 ระบบพร้อมใช้งาน!${NC}"
echo -e "${YELLOW}💡 หากยังมีปัญหา ให้ตรวจสอบ:${NC}"
echo "   - Apache/Nginx service ว่าทำงานหรือไม่"
echo "   - PHP และ extensions ที่จำเป็น"
echo "   - การตั้งค่าฐานข้อมูล"
echo "   - Log files: /var/log/httpd/ หรือ /var/log/nginx/"