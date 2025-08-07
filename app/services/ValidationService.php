<?php
// app/services/ValidationService.php

class ValidationService
{
    private $errors = [];
    private $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function validateRegistration(): self
    {
        // 1. ตรวจสอบช่องที่จำเป็น
        $requiredFields = ['fname', 'lname', 'email', 'password', 'confirm'];
        foreach ($requiredFields as $field) {
            if (empty(trim($this->data[$field] ?? ''))) {
                $this->errors[] = 'กรุณากรอกข้อมูลที่จำเป็นให้ครบถ้วน';
                break; // เจอข้อผิดพลาดแรกก็หยุดเลย
            }
        }

        // 2. ตรวจสอบรูปแบบอีเมล
        if (!filter_var($this->data['email'], FILTER_VALIDATE_EMAIL)) {
            $this->errors[] = 'รูปแบบอีเมลไม่ถูกต้อง';
        }

        // 3. ตรวจสอบความซับซ้อนของรหัสผ่าน (Server-side)
        $password = $this->data['password'];
        if (strlen($password) < 8 || !preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/[0-9]/', $password)) {
            $this->errors[] = 'รหัสผ่านต้องมี 8-20 ตัวอักษร, พิมพ์ใหญ่, พิมพ์เล็ก, และตัวเลขอย่างน้อย 1 ตัว';
        }

        // 4. ตรวจสอบว่ารหัสผ่านตรงกันหรือไม่
        if ($password !== $this->data['confirm']) {
            $this->errors[] = 'รหัสผ่านและยืนยันรหัสผ่านไม่ตรงกัน';
        }
        
        return $this;
    }
}