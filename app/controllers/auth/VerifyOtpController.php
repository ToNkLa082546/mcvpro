<?php
require_once __DIR__ . '/../../models/User.php';

class VerifyOtpController {
    public function index() {
        include '../../app/views/verify_otp.php';
    }

    public function verify() {
        session_start();
        require_once ROOT_PATH . 'core/connect.php';
        if (!isset($_POST['otp']) || !isset($_SESSION['pending_email'])) {
            $_SESSION['error_message'] = "ข้อมูลไม่ครบถ้วน";
            header("Location: /mcvpro/public/verify_otp");
            exit();
        }

        $email = $_SESSION['pending_email'];
        $otp = $_POST['otp'];

        $userModel = new User($pdo);
        $user = $userModel->getUserByEmail($email);

        if (!$user) {
            $_SESSION['error_message'] = "ไม่พบผู้ใช้";
            header("Location: /mcvpro/public/verify_otp");
            exit();
        }

        if ($user['verify_token'] === $otp) {
            // อัปเดตสถานะยืนยัน
            $userModel->verifyUser($email);
            unset($_SESSION['pending_email']);
            header("Location: /mcvpro/public/login");
            exit();
        } else {
            $_SESSION['error_message'] = "❌ OTP ไม่ถูกต้อง";
            header("Location: /mcvpro/public/verify_otp");
            exit();
        }
    }
}
