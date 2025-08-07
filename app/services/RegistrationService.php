<?php
// app/services/RegistrationService.php

class RegistrationService
{
    
    private $userModel;
    private $pdo;

    public function __construct(User $userModel, PDO $pdo)
    {
        $this->userModel = $userModel;
        $this->pdo = $pdo;
    }

    /**
     * ดำเนินการลงทะเบียนผู้ใช้ใหม่
     * @param array $data ข้อมูลจากฟอร์ม
     * @return array|string trả về array ที่มี otp ถ้าสำเร็จ, trả về string ที่เป็น error message ถ้าล้มเหลว
     */
    public function registerNewUser(array $data)
    {
        if ($this->userModel->checkEmailExists($data['email'])) {
            return "อีเมลนี้ถูกใช้งานในระบบแล้ว";
        }
        // ในไฟล์ RegistrationService.php
        $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
        $otp = strval(random_int(100000, 999999));
        $role_id = 3; // Default member

        // *** การใช้ Transaction เพื่อความปลอดภัยของข้อมูล ***
        // ถ้าการสร้าง user หรือ profile ล้มเหลว ข้อมูลทั้งหมดจะถูกยกเลิก
        try {
            $this->pdo->beginTransaction();

            $userId = $this->userModel->createUser($data['email'], $hashedPassword, $role_id, $otp);
            $this->userModel->createProfile($userId, $data['fname'], $data['lname'], $data['dob'], $data['phone']);

            $this->pdo->commit();
            
            return ['success' => true, 'otp' => $otp];

        } catch (Exception $e) {
            $this->pdo->rollBack();
            error_log("RegistrationService Error: " . $e->getMessage());
            return "เกิดข้อผิดพลาดร้ายแรงในการลงทะเบียน กรุณาลองใหม่อีกครั้ง";
        }
    }
}