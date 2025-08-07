<?php
// core/Controller.php

class Controller
{
    protected $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;

        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        // เรียกใช้ฟังก์ชันตรวจสอบ session timeout ทุกครั้งที่สร้าง Controller
        $this->checkSessionTimeout();
    }


    
    protected function checkSessionTimeout()
    {
        // ตรวจสอบเฉพาะผู้ใช้ที่ล็อกอินแล้วเท่านั้น
        if (isset($_SESSION['user_id'])) {
            
            // กำหนดเวลา Timeout (10 นาที * 60 วินาที = 600 วินาที)
            $timeout_duration = 600; 

            // ตรวจสอบว่าเวลาที่ใช้งานล่าสุด (last_activity) ผ่านมานานเกินกำหนดหรือยัง
            if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout_duration) {
                
                // ถ้าหมดเวลา ให้ส่งไปหน้า logout
                header('Location: /mcvpro/public/logout?reason=timeout');
                exit();
            }

            // ถ้ายังไม่หมดเวลา ให้อัปเดตเวลาล่าสุดเป็นเวลาปัจจุบัน
            $_SESSION['last_activity'] = time();

            // --- จุดแก้ไข: เพิ่มส่วนนี้เข้าไป ---
            // คำนวณเวลาที่จะหมดอายุจริงๆ แล้วเก็บไว้ใน Session เพื่อส่งไปให้ JavaScript
            $_SESSION['session_expires_at'] = time() + $timeout_duration;
        }
    }


    protected function auth()
    {
        if (!isset($_SESSION['user_id'])) {
            $_SESSION['error'] = 'กรุณาเข้าสู่ระบบก่อนใช้งาน';
            header('Location: /mcvpro/public/auth/login');
            exit();
        }
    }

 protected function hasRole(array $allowedRoles)
{
    if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], $allowedRoles)) {
        http_response_code(403);
        die("<h1>403 Forbidden</h1><p>You do not have permission to access this page.</p>");
    }
}

protected function isAllowed(array $allowedRoles): bool
    {
        if (!isset($_SESSION['user_role'])) {
            return false;
        }
        
        // in_array(..., ..., true) จะเปรียบเทียบชนิดข้อมูล (type) ด้วย
        // ทำให้แน่ใจว่า int(1) จะไม่สับสนกับ string("1")
        return in_array($_SESSION['user_role'], $allowedRoles, true);
    }


}