<?php
// app/services/MailService.php

require_once ROOT_PATH . 'public/PHPMailer/src/PHPMailer.php';
require_once ROOT_PATH . 'public/PHPMailer/src/SMTP.php';
require_once ROOT_PATH . 'public/PHPMailer/src/Exception.php';


use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class MailService
{
    private $mailer;

    public function __construct()
    {
        // ตรวจสอบให้แน่ใจว่าไฟล์ connect.php ถูก include หรือ require
        // ก่อนที่จะสร้างอ็อบเจ็กต์จากคลาสนี้
        // เช่น require_once 'path/to/your/connect.php';

        $this->mailer = new PHPMailer(true);
        try {
            //Server settings
            $this->mailer->isSMTP();
            $this->mailer->Host       = SMTP_HOST; 
            $this->mailer->SMTPAuth   = true;
            $this->mailer->Username   = SMTP_USER;   
            $this->mailer->Password   = SMTP_PASS;     
            $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $this->mailer->Port       = SMTP_PORT;     
            $this->mailer->CharSet    = 'UTF-8';
            $this->mailer->setFrom(SMTP_USER, 'MCVPro'); 

        } catch (Exception $e) {
            error_log("PHPMailer could not be configured. Mailer Error: {$this->mailer->ErrorInfo}");
        }
    }

    public function sendOtpEmail(string $recipientEmail, string $otp): bool
    {
        try {
            $this->mailer->addAddress($recipientEmail);
            $this->mailer->isHTML(true);
            $this->mailer->Subject = "รหัส OTP สำหรับการยืนยันตัวตน";
            $this->mailer->Body = "
                <div style='font-family: Arial, sans-serif; text-align: center; color: #333;'>
                    <h2>ยืนยันการสมัครสมาชิก</h2>
                    <p>ขอบคุณที่สมัครสมาชิกกับ ProjectMe</p>
                    <p>รหัส OTP ของคุณคือ:</p>
                    <h3 style='color: #0d6efd; letter-spacing: 2px;'><strong>$otp</strong></h3>
                    <p>รหัสนี้มีอายุการใช้งาน 10 นาที</p>
                </div>";

            return $this->mailer->send();
        } catch (Exception $e) {
            error_log("MailService Error: " . $this->mailer->ErrorInfo);
            return false;
        }
    }


public function sendPasswordResetOtpEmail(string $recipientEmail, string $otp): bool
{
    try {
        $this->mailer->addAddress($recipientEmail);
        $this->mailer->isHTML(true);
        $this->mailer->Subject = "รหัส OTP สำหรับการตั้งค่ารหัสผ่านใหม่"; // เปลี่ยนหัวข้ออีเมล
        $this->mailer->Body = "
            <div style='font-family: Arial, sans-serif; text-align: center; color: #333;'>
                <h2>คำขอตั้งรหัสผ่านใหม่</h2>
                <p>เราได้รับคำขอให้รีเซ็ตรหัสผ่านสำหรับบัญชีของคุณ</p>
                <p>รหัส OTP ของคุณคือ:</p>
                <h3 style='color: #dc3545; letter-spacing: 2px;'><strong>$otp</strong></h3>
                <p>หากคุณไม่ได้เป็นผู้ร้องขอ โปรดเพิกเฉยต่ออีเมลนี้</p>
            </div>";

        return $this->mailer->send();
    } catch (Exception $e) {
        error_log("MailService Error (Password Reset): " . $this->mailer->ErrorInfo);
        return false;
    }
}

}