<?php
// app/controllers/auth/RegisterController.php

class RegisterController extends Controller
{
    public function index()
    {
        include ROOT_PATH . 'app/views/auth/register.php';
    }

    public function submit()
    {
        $requestData = $_POST;

        $validator = new ValidationService($requestData);
        $errors = $validator->validateRegistration()->getErrors();

        if (!empty($errors)) {
            $_SESSION['error'] = implode('<br>', $errors);
            header('Location: /mcvpro/public/register');
            exit();
        }

        $userModel = new User($this->pdo);
        $registrationService = new RegistrationService($userModel, $this->pdo);
        $result = $registrationService->registerNewUser($requestData);

        if (is_string($result)) {
            $_SESSION['error'] = $result;
            header('Location: /mcvpro/public/register');
            exit();
        }

        $otp = $result['otp'];

        $mailService = new MailService();
        if ($mailService->sendOtpEmail($requestData['email'], $otp)) {
            $_SESSION['verify_email'] = $requestData['email'];
            header('Location: /mcvpro/public/register/verify');
            exit();
        } else {
            $_SESSION['error'] = "สร้างบัญชีสำเร็จ แต่ไม่สามารถส่งอีเมลยืนยันได้ กรุณาติดต่อผู้ดูแล";
            header('Location: /mcvpro/public/register');
            exit();
        }
    }

    public function verify()
    {
        if (empty($_SESSION['verify_email'])) {
             header('Location: /mcvpro/public/register');
             exit();
        }
        include ROOT_PATH . 'app/views/auth/verify_otp.php';
    }

    // ในไฟล์ RegisterController.php

public function checkotp()
{

    
    // --- STEP 1: รับข้อมูลและตรวจสอบค่าว่าง ---
    $submittedOtp = $_POST['otp'] ?? '';
    $email = $_SESSION['verify_email'] ?? '';
    if (empty($submittedOtp) || empty($email)) {
        // ถ้าไม่มีข้อมูลสำคัญ ให้กลับไปหน้าแรกของการสมัคร
        header('Location: /mcvpro/public/register');
        exit();
        
    }
    // --- STEP 2: ดึงข้อมูล OTP จากฐานข้อมูล ---
    $userModel = new User($this->pdo);
    $otpData = $userModel->getOtpDataByEmail($email);

    // --- STEP 3: ตรวจสอบ OTP ทีละขั้นตอน ---

    // 3.1) ตรวจสอบว่ามี OTP ในระบบหรือไม่
    if (!$otpData || empty($otpData['otp_code'])) {
        $_SESSION['error'] = '❌ ไม่มีข้อมูล OTP สำหรับอีเมลนี้ หรืออาจยืนยันไปแล้ว';
        header('Location: /mcvpro/public/register/verify');
        exit();
    }

    // 3.2) ตรวจสอบว่า OTP ที่กรอกมา ถูกต้องหรือไม่
    if ($submittedOtp !== $otpData['otp_code']) {
        $_SESSION['error'] = '❌ รหัส OTP ไม่ถูกต้อง';
        header('Location: /mcvpro/public/register/verify');
        exit();
    }

    // 3.3) ตรวจสอบว่า OTP หมดอายุหรือยัง
    $expireTime = strtotime($otpData['otp_expire']);
    $currentTime = time();

    if ($currentTime > $expireTime) {
        $_SESSION['error'] = '❌ รหัส OTP หมดอายุแล้ว กรุณาดำเนินการสมัครใหม่อีกครั้ง';
        // อาจจะต้องลบ user ที่สมัครค้างไว้ออก หรือมีระบบขอ OTP ใหม่
        header('Location: /mcvpro/public/register');
        exit();
    }

    // --- STEP 4: สำเร็จทุกขั้นตอน ---

    // 4.1) อัปเดตสถานะผู้ใช้ในฐานข้อมูล
    $userModel->verifyUser($email);

    // 4.2) เคลียร์ session ที่ไม่จำเป็น
    unset($_SESSION['verify_email']);

    // 4.3) ตั้งค่า session สำหรับการ Login อัตโนมัติ (แนะนำ)
    // ดึงข้อมูลผู้ใช้ทั้งหมดหลังยืนยันสำเร็จ
    $userData = $userModel->getUserByEmail($email);
    $_SESSION['user_id'] = $userData['id_user'];
    $_SESSION['user_fname'] = $userData['fname_personal'];
    $_SESSION['user_role'] = $userData['role_id'];

    // 4.4) ✅ เปลี่ยนเส้นทางไปหน้า Home ตามที่คุณต้องการ
    header('Location: /mcvpro/public/home');
    exit();
}


// เพิ่มฟังก์ชันนี้เข้าไปในคลาส RegisterController

public function resendOtp()
{
    // ตรวจสอบว่ามีอีเมลใน session หรือไม่ (ป้องกันการเข้าถึงหน้านี้โดยตรง)
    if (empty($_SESSION['verify_email'])) {
        header('Location: /mcvpro/public/register');
        exit();
    }

    $email = $_SESSION['verify_email'];

    // 1. สร้าง OTP ใหม่
    $newOtp = strval(random_int(100000, 999999));

    // 2. อัปเดต OTP และเวลาหมดอายุในฐานข้อมูล
    $userModel = new User($this->pdo);
    // เราต้องสร้าง method ใหม่ใน User model เพื่ออัปเดต OTP
    $userModel->updateOtp($email, $newOtp);

    // 3. ส่งอีเมล OTP ใหม่
    $mailService = new MailService();
    if ($mailService->sendOtpEmail($email, $newOtp)) {
        // ส่งสำเร็จ กลับไปหน้า verify พร้อมข้อความแจ้งเตือน
        $_SESSION['success'] = '✅ ส่งรหัส OTP ใหม่ไปยังอีเมลของคุณแล้ว';
        header('Location: /mcvpro/public/register/verify');
        exit();
    } else {
        // ส่งล้มเหลว
        $_SESSION['error'] = '❌ เกิดข้อผิดพลาด ไม่สามารถส่งรหัส OTP ใหม่ได้';
        header('Location: /mcvpro/public/register/verify');
        exit();
    }
}



}
