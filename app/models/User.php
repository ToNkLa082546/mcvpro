<?php
// app/models/User.php

class User
{
    /**
     * @var PDO การเชื่อมต่อฐานข้อมูลที่ถูกส่งเข้ามา
     */
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function getUserByEmail(string $email)
    {
        // คำสั่ง SQL ที่จะดึงข้อมูลจาก 3 ตารางพร้อมกัน
        $sql = "
            SELECT 
                u.id_user,
                u.email_user,
                u.password_user,
                u.is_verified,
                u.role_id,
                r.role_name,
                p.fname_personal,
                p.lname_personal,
                p.nname_personal,
                p.dob_personal,
                p.phone_personal,
                p.address_personal,
                p.description_personal,
                p.profile_image
            FROM 
                users AS u
            LEFT JOIN 
                personal AS p ON u.id_user = p.user_id
            LEFT JOIN 
                role AS r ON u.role_id = r.role_id
            WHERE 
                u.email_user = ?
        ";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function checkEmailExists(string $email): bool
    {
        $stmt = $this->pdo->prepare("SELECT 1 FROM users WHERE email_user = ?");
        $stmt->execute([$email]);
        return $stmt->fetchColumn() !== false;
    }

    public function createUser(string $email, string $hashedPassword, int $roleId, string $otp)
    {
        // กำหนดให้ OTP มีอายุ 10 นาที
        $sql = "
            INSERT INTO users (email_user, password_user, role_id, otp_code, otp_expire, is_verified)
            VALUES (?, ?, ?, ?, NOW() + INTERVAL 10 MINUTE, 0)
        ";
        $stmt = $this->pdo->prepare($sql);

        if ($stmt->execute([$email, $hashedPassword, $roleId, $otp])) {
            return (int)$this->pdo->lastInsertId();
        }
        return false;
    }

    public function createProfile(int $userId, string $fname, string $lname, string $dob, string $phone): bool
    {
        $sql = "
            INSERT INTO personal (user_id, fname_personal, lname_personal, dob_personal, phone_personal)
            VALUES (?, ?, ?, ?, ?)
        ";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$userId, $fname, $lname, $dob, $phone]);
    }

    // --- 3. ฟังก์ชันสำหรับยืนยัน OTP ---

    /**
     * ดึงข้อมูล OTP และวันหมดอายุเพื่อใช้ตรวจสอบ
     * @param string $email
     * @return array|false ข้อมูล OTP หรือ false ถ้าไม่พบ
     */
    public function getOtpDataByEmail(string $email)
    {
        $stmt = $this->pdo->prepare("SELECT otp_code, otp_expire FROM users WHERE email_user = ?");
        $stmt->execute([$email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * อัปเดตสถานะผู้ใช้เป็น `is_verified = 1`
     * @param string $email
     * @return bool
     */
    public function verifyUser(string $email): bool
    {
        // เมื่อยืนยันสำเร็จ ให้ล้างค่า OTP ทิ้งเพื่อความปลอดภัย
        $sql = "
            UPDATE users 
            SET is_verified = 1, otp_code = NULL, otp_expire = NULL 
            WHERE email_user = ?
        ";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$email]);
    }
public function updateOtp(string $email, string $newOtp): bool
{
    // กำหนดให้ OTP ใหม่มีอายุอีก 10 นาที
    $sql = "UPDATE users SET otp_code = ?, otp_expire = NOW() + INTERVAL 10 MINUTE WHERE email_user = ?";
    $stmt = $this->pdo->prepare($sql);
    return $stmt->execute([$newOtp, $email]);
}
public function updatePasswordByEmail(string $email, string $hashedPassword): bool
{
    // เมื่อเปลี่ยนรหัสผ่านสำเร็จ ควรล้างค่า otp และ token รีเซ็ตทั้งหมด
    $sql = "
        UPDATE users SET 
            password_user = ?, 
            otp_code = NULL, 
            otp_expire = NULL,
            password_reset_token = NULL,
            password_reset_expire = NULL
        WHERE email_user = ?
    ";
    $stmt = $this->pdo->prepare($sql);
    return $stmt->execute([$hashedPassword, $email]);
}

public function updateProfile(int $userId, array $data): bool
{
    $sql = "
        UPDATE personal SET 
            fname_personal = :fname, 
            lname_personal = :lname,
            nname_personal = :nname, 
            dob_personal = :dob, 
            phone_personal = :phone, 
            address_personal = :address, 
            description_personal = :description
        WHERE user_id = :user_id
    ";
    $stmt = $this->pdo->prepare($sql);
    
    return $stmt->execute([
        ':fname' => $data['fname'],
        ':lname' => $data['lname'],
        ':nname' => $data['nname'],
        ':dob' => $data['dob'],
        ':phone' => $data['phone'],
        ':address' => $data['address'],
        ':description' => $data['description'],
        ':user_id' => $userId
    ]);
}
public function updateProfileImage(int $userId, string $imageFilename): bool
{
    $sql = "UPDATE personal SET profile_image = ? WHERE user_id = ?";
    $stmt = $this->pdo->prepare($sql);
    return $stmt->execute([$imageFilename, $userId]);
}

public function getAllUsers(int $excludeUserId): array
{
    
    $sql = "SELECT 
                u.id_user, 
                u.email_user,
                p.fname_personal,
                p.lname_personal,
                r.role_name
            FROM 
                users u 
            JOIN 
                personal p ON u.id_user = p.user_id 
            LEFT JOIN
                role r ON u.role_id = r.role_id
            WHERE 
                u.id_user != ? 
            ORDER BY 
                p.fname_personal ASC";
            
    $stmt = $this->pdo->prepare($sql);
    $stmt->execute([$excludeUserId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

public function countAll(): int
{
    return (int)$this->pdo->query("SELECT COUNT(id_user) FROM users")->fetchColumn();
}

public function updateOtpById(int $userId, string $otp): bool
    {
        $otp_expiry = date('Y-m-d H:i:s', strtotime('+15 minutes'));
        $sql = "UPDATE users SET otp_code = ?, otp_expire = ? WHERE id_user = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$otp, $otp_expiry, $userId]);
    }

    /**
     * ✅ 2. ตรวจสอบ OTP ว่าถูกต้องและยังไม่หมดอายุหรือไม่
     */
    public function verifyOtp(int $userId, string $submittedOtp): bool
    {
        $stmt = $this->pdo->prepare("SELECT otp_code, otp_expire FROM users WHERE id_user = ?");
        $stmt->execute([$userId]);
        $otpData = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$otpData) {
            return false; // ไม่พบผู้ใช้
        }

        // ตรวจสอบว่า OTP ตรงกัน และยังไม่หมดอายุ
        if ($submittedOtp === $otpData['otp_code'] && time() < strtotime($otpData['otp_expire'])) {
            return true;
        }

        return false;
    }

    /**
     * ✅ 3. อัปเดตรหัสผ่านใหม่ และล้างค่า OTP
     */
    public function updatePasswordById(int $userId, string $hashedPassword): bool
    {
        $sql = "UPDATE users SET password_user = ?, otp_code = NULL, otp_expire = NULL WHERE id_user = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$hashedPassword, $userId]);
    }

      public function getUsersByRole(array $roles)
    {
        // สร้าง placeholder '?,?' สำหรับ SQL IN clause
        $inQuery = implode(',', array_fill(0, count($roles), '?'));
        
        $sql = "SELECT u.Id_user, u.email_user, p.fname_personal AS fullname
        FROM users u
        LEFT JOIN personal p ON u.Id_user = p.user_id
        WHERE u.role_id IN ({$inQuery})";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($roles);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    /**
     * ค้นหาผู้ใช้ด้วย ID
     */
    public function findById($id)
    {
        $sql = "SELECT u.*, p.fname_personal AS fullname
        FROM users u
        LEFT JOIN personal p ON u.Id_user = p.user_id
        WHERE u.Id_user = ?";
        $stmt = $this->pdo->prepare("
                SELECT 
                    u.id_user, 
                    u.email_user, 
                    u.created_at, 
                    u.is_verified,
                    u.role_id,
                    p.fname_personal, 
                    p.lname_personal, 
                    CONCAT(p.fname_personal, ' ', p.lname_personal) AS fullname,
                    r.role_name
                FROM users u
                JOIN personal p ON u.id_user = p.user_id
                JOIN role r ON u.role_id = r.role_id
                WHERE u.id_user = ?
            ");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

// User.php

public function updateUserAndProfile(int $id, array $data): bool
{
    // เริ่มต้น Transaction
    $this->pdo->beginTransaction();

    try {
        // 1. อัปเดตตาราง users
        $userSql = "UPDATE users SET email_user = :email, role_id = :role_id WHERE id_user = :id";
        $userStmt = $this->pdo->prepare($userSql);
        $userStmt->execute([
            ':email' => $data['email'],
            ':role_id' => $data['role_id'],
            ':id' => $id
        ]);
        
        // ถ้ามีการส่งรหัสผ่านใหม่มาด้วย ให้อัปเดต
        if (!empty($data['password'])) {
            $this->updatePasswordById($id, $data['password']);
        }

        // 2. อัปเดตตาราง personal
        // เราสามารถใช้เมธอด updateProfile ที่มีอยู่แล้วได้เลย
        // แต่ต้องแน่ใจว่า key ใน $data ตรงกัน
        $profileData = [
            'fname' => $data['fname'],
            'lname' => $data['lname'],
            // เพิ่ม field อื่นๆ ตามฟอร์ม
            'nname' => $data['nname'] ?? null,
            'dob' => $data['dob'] ?? null,
            'phone' => $data['phone'] ?? null,
            'address' => $data['address'] ?? null,
            'description' => $data['description'] ?? null,
        ];
        $this->updateProfile($id, $profileData);

        // ถ้าทุกอย่างสำเร็จ ให้ commit
        $this->pdo->commit();
        return true;

    } catch (PDOException $e) {
        // ถ้าเกิดข้อผิดพลาด ให้ rollback
        $this->pdo->rollBack();
        // สามารถ log error ไว้ดูได้
        // error_log($e->getMessage());
        return false;
    }
}

}