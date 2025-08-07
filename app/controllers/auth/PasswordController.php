<?php

class PasswordController extends Controller
{
    /**
     * เมธอดสำหรับแสดงหน้าฟอร์ม "ลืมรหัสผ่าน"
     */
    public function forgot()
    {
        // ถ้า Login อยู่แล้ว ให้กลับไปหน้า Home
        if (isset($_SESSION['user_id'])) {
            header('Location: /mcvpro/public/home');
            exit();
        }
        
        $page_title = "Forgot Password";
        // ไม่ต้อง include layout เพราะเป็นหน้าฟอร์มเดี่ยวๆ
        include ROOT_PATH . 'app/views/auth/forgot_password.php';
    }

    /**
     * เมธอดสำหรับรับอีเมลที่ผู้ใช้กรอก และเริ่มกระบวนการส่งลิงก์
     */
    public function request()
    {
        // โค้ดส่วนนี้เหมือนเดิม
        $email = $_POST['email'] ?? '';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = '❌ รูปแบบอีเมลไม่ถูกต้อง';
            header('Location: /mcvpro/public/password/forgot');
            exit();
        }

        $userModel = new User($this->pdo);
        if (!$userModel->checkEmailExists($email)) {
            $_SESSION['success'] = 'หากอีเมลนี้มีอยู่ในระบบ เราได้ส่งรหัส OTP ไปให้แล้ว';
            header('Location: /mcvpro/public/password/forgot');
            exit();
        }

        $otp = strval(random_int(100000, 999999));
        $userModel->updateOtp($email, $otp);

        $mailService = new MailService();
        if ($mailService->sendPasswordResetOtpEmail($email, $otp)) {
            $_SESSION['password_reset_email'] = $email;
            header('Location: /mcvpro/public/password/reset');
            exit();
        } else {
            $_SESSION['error'] = '❌ เกิดข้อผิดพลาด ไม่สามารถส่งอีเมลได้';
            header('Location: /mcvpro/public/password/forgot');
            exit();
        }
    }
    
    public function reset()
    {
        // ตรวจสอบว่าผู้ใช้ผ่านขั้นตอนการขอ OTP มาก่อนหรือไม่
        if (empty($_SESSION['password_reset_email'])) {
            // ถ้ายังไม่ได้ขอ OTP ให้กลับไปหน้าแรก
            header('Location: /mcvpro/public/password/forgot');
            exit();
        }

        // แสดงฟอร์มสำหรับตั้งรหัสผ่านใหม่
        include ROOT_PATH . 'app/views/auth/reset_password.php';
    }


public function update()
{
    // --- STEP 1: รับข้อมูลจากฟอร์มและ Session ---
    $email = $_POST['email'] ?? '';
    $submittedOtp = $_POST['otp'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Security Check: ตรวจสอบว่า email ในฟอร์มตรงกับใน session
    if (empty($email) || $email !== ($_SESSION['password_reset_email'] ?? '')) {
        // ถ้าไม่ตรงกันหรือมีการเข้าถึงโดยตรง ให้กลับไปหน้าแรก
        header('Location: /mcvpro/public/login');
        exit();
    }

    // --- STEP 2: ตรวจสอบความถูกต้องของข้อมูล (Validation) ---
    if (empty($submittedOtp) || empty($password) || empty($confirm_password)) {
        $_SESSION['error'] = '❌ กรุณากรอกข้อมูลให้ครบทุกช่อง';
        header('Location: /mcvpro/public/password/reset');
        exit();
    }

    if ($password !== $confirm_password) {
        $_SESSION['error'] = '❌ รหัสผ่านใหม่และการยืนยันไม่ตรงกัน';
        header('Location: /mcvpro/public/password/reset');
        exit();
    }

    // (แนะนำ) ตรวจสอบความซับซ้อนของรหัสผ่านใหม่
    if (strlen($password) < 8 || !preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/[0-9]/', $password)) {
        $_SESSION['error'] = '❌ รหัสผ่านใหม่ต้องมี 8-20 ตัวอักษร, พิมพ์ใหญ่, พิมพ์เล็ก, และตัวเลขอย่างน้อย 1 ตัว';
        header('Location: /mcvpro/public/password/reset');
        exit();
    }

    $userModel = new User($this->pdo);
    $otpData = $userModel->getOtpDataByEmail($email);

    if (!$otpData || $submittedOtp !== $otpData['otp_code']) {
        $_SESSION['error'] = '❌ รหัส OTP ไม่ถูกต้อง';
        header('Location: /mcvpro/public/password/reset');
        exit();
    }

    if (time() > strtotime($otpData['otp_expire'])) {
        $_SESSION['error'] = '❌ รหัส OTP หมดอายุแล้ว กรุณาเริ่มทำรายการใหม่อีกครั้ง';
        unset($_SESSION['password_reset_email']); // ล้าง session เมื่อ OTP หมดอายุ
        header('Location: /mcvpro/public/password/forgot');
        exit();
    }

    // --- STEP 4: อัปเดตรหัสผ่านใหม่ ---
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    if ($userModel->updatePasswordByEmail($email, $hashedPassword)) {
        // สำเร็จ!
        unset($_SESSION['password_reset_email']);
        $_SESSION['success'] = '✅ รหัสผ่านของคุณถูกเปลี่ยนเรียบร้อยแล้ว กรุณาเข้าสู่ระบบด้วยรหัสผ่านใหม่';
        header('Location: /mcvpro/public/login');
        exit();
    } else {
        // ล้มเหลว (อาจเกิดจากปัญหา DB)
        $_SESSION['error'] = '❌ เกิดข้อผิดพลาดร้ายแรง ไม่สามารถอัปเดตรหัสผ่านได้';
        header('Location: /mcvpro/public/password/reset');
        exit();
    }
}
}