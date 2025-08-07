// public/js/session-manager.js

(function() {
    const TIMEOUT_SECONDS = 600; // 10 นาที (ต้องตรงกับค่าใน PHP)
    const PING_INTERVAL_SECONDS = 300; // 5 นาที: ส่ง ping ไปหา server ทุกๆ 5 นาทีถ้ามีการใช้งาน

    let inactivityTimer;
    let lastPingTime = Date.now();

    // ฟังก์ชันสำหรับบังคับ Logout
    const forceLogout = () => {
        console.log("Session timed out due to inactivity. Logging out.");
        // Redirect ไปยัง URL ที่เรากำหนดไว้ใน PHP พร้อมเหตุผล
        window.location.href = '/mcvpro/public/logout?reason=timeout';
    };

    // ฟังก์ชันสำหรับ "ping" ไปยังเซิร์ฟเวอร์
    const pingServer = () => {
        // Pi2ng ต่อเมื่อผ่านไปแล้วระยะหนึ่ง เพื่อไม่ให้ยิง request ถี่เกินไป
        const now = Date.now();
        if (now - lastPingTime > (PING_INTERVAL_SECONDS * 1000)) {
            fetch('/mcvpro/public/session/keep-alive', { 
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest' // บอก Server ว่าเป็น AJAX
                }
            }).then(response => {
                if (!response.ok) {
                    // ถ้า server ตอบกลับมาว่า session หมดอายุแล้ว (เช่น status 401)
                    forceLogout();
                } else {
                    console.log('Session extended on server.');
                    lastPingTime = Date.now(); // อัปเดตเวลา ping ล่าสุด
                }
            }).catch(error => {
                console.error('Ping failed:', error);
            });
        }
    };

    // ฟังก์ชันสำหรับรีเซ็ตตัวนับเวลา
    const resetTimer = () => {
        // เคลียร์ timer เก่า
        clearTimeout(inactivityTimer);
        
        // เริ่มนับเวลาใหม่
        inactivityTimer = setTimeout(forceLogout, TIMEOUT_SECONDS * 1000);
        
        // ส่งสัญญาณไปอัปเดตเวลาที่เซิร์ฟเวอร์
        pingServer();
    };

    // เริ่มต้นทำงานเมื่อหน้าเว็บโหลด
    window.onload = resetTimer;

    // เพิ่ม Event Listeners เพื่อตรวจจับการใช้งาน
    document.addEventListener('mousemove', resetTimer, { passive: true });
    document.addEventListener('keypress', resetTimer, { passive: true });
    document.addEventListener('click', resetTimer, { passive: true });
    document.addEventListener('scroll', resetTimer, { passive: true });
    
    console.log("Inactivity tracker is active.");
})();