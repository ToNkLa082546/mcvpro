<?php

// สมมติว่า User Model และไฟล์เริ่มต้น (init.php) ถูกเรียกใช้งานแล้ว
// require_once __DIR__ . '/../models/User.php';

class UsersController
{
    private $userModel;
    private $pdo;

    /**
     * Constructor รับ PDO connection และสร้าง instance ของ User Model
     */
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->userModel = new User($this->pdo);
    }

    /**
     * ฟังก์ชันสำหรับตรวจสอบสิทธิ์การเป็น Admin
     * ทำให้ไม่ต้องเขียนโค้ดซ้ำซ้อนในทุก method
     */
    private function checkAdmin()
    {
        // ตรวจสอบว่ามีการล็อกอินและ role เป็น 1 (admin) หรือไม่
        if (empty($_SESSION['user_id']) || (int)$_SESSION['user_role'] !== 1) {
            // ถ้าไม่ใช่ ให้ส่งกลับไปหน้า login
            header("Location: /mcvpro/public/login");
            exit;
        }
    }

    /**
     * [READ] แสดงรายชื่อผู้ใช้ทั้งหมด (ยกเว้นตัวเอง)
     */
    public function index()
    {
        $this->checkAdmin();
        $currentUserId = $_SESSION['user_id'] ?? null;
        
        // ดึงข้อมูลผู้ใช้ทั้งหมดจาก Model
        $users = $this->userModel->getAllUsers($currentUserId);

        // โหลด View
        include ROOT_PATH . 'app/views/layout/sidebar.php';
        include ROOT_PATH . 'app/views/users/index.php';
    }

    /**
     * [READ] แสดงรายละเอียดผู้ใช้รายบุคคล
     */
    /**
 * [READ] แสดงรายละเอียดผู้ใช้รายบุคคล
 */
public function view($hashedId) // 1. เปลี่ยนชื่อตัวแปรเพื่อความชัดเจน (แนะนำ)
{
    $this->checkAdmin();

    $id = decodeId($hashedId); // 2. **เพิ่มบรรทัดนี้เพื่อถอดรหัส ID**
    if ($id === null) {
        // กรณี ID ที่ส่งมาไม่ถูกต้องหรือถูกปลอมแปลง
        die("Invalid ID.");
    }
    
    // 3. ใช้ $id ที่ถอดรหัสแล้วในการค้นหา
    $user = $this->userModel->findById($id); 

    if (!$user) {
        // หากไม่พบผู้ใช้ ให้ตั้งค่า flash message แล้วกลับไปหน้าหลัก
        $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'ไม่พบข้อมูลผู้ใช้'];
        header("Location: /mcvpro/public/admin/users");
        exit;
    }

    // โหลด View
    include ROOT_PATH . 'app/views/layout/sidebar.php';
    include ROOT_PATH . 'app/views/users/view.php';
}

    /**
     * [CREATE] แสดงฟอร์มสำหรับเพิ่มผู้ใช้ใหม่
     */
    public function create()
    {
        $this->checkAdmin();

        // โหลด View ที่มีฟอร์มเพิ่มผู้ใช้
        include ROOT_PATH . 'app/views/layout/sidebar.php';
        include ROOT_PATH . 'app/views/users/create.php';
    }

    /**
     * [CREATE] บันทึกข้อมูลผู้ใช้ใหม่ลงฐานข้อมูล
     */
    public function store()
    {
        $hashedId = $_POST['project_id'];
        $id = decodeId($hashedId);
        if ($id === null) {
            die("Invalid ID.");
        }
        $this->checkAdmin();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // **ควรมีการ Validation ข้อมูลก่อนเสมอ**
            // เช่น ตรวจสอบค่าว่าง, รูปแบบอีเมล, ตรวจสอบว่าอีเมลซ้ำหรือไม่
            if ($this->userModel->checkEmailExists($_POST['email_user'])) {
                $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'อีเมลนี้มีผู้ใช้งานแล้ว'];
                header("Location: /mcvpro/public/admin/users/create");
                exit;
            }

            $email = $_POST['email_user'] ?? '';
            $password = $_POST['password'] ?? '';
            $roleId = $_POST['role_id'] ?? 3;
            $fname = $_POST['fname_personal'] ?? '';
            $lname = $_POST['lname_personal'] ?? '';
            // สมมติว่ามี dob และ phone ส่งมาด้วย
            $dob = $_POST['dob_personal'] ?? null;
            $phone = $_POST['phone_personal'] ?? null;

            // เข้ารหัสผ่าน
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            // สร้างผู้ใช้ในตาราง users (สมมติว่า admin สร้างไม่ต้องใช้ OTP)
            // เราต้องปรับปรุง createUser ให้ยืดหยุ่นกว่านี้ หรือสร้างเมธอดใหม่
            // แต่เพื่อความง่าย จะใช้ createUser ที่มีอยู่ไปก่อน โดยส่ง OTP เป็นค่าว่าง
            $newUserId = $this->userModel->createUser($email, $hashedPassword, $roleId, '');

            if ($newUserId) {
                // ถ้าสร้าง user สำเร็จ ให้สร้าง profile ต่อ
                $this->userModel->createProfile($newUserId, $fname, $lname, $dob, $phone);
                $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'เพิ่มผู้ใช้ใหม่สำเร็จ'];
            } else {
                $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'เกิดข้อผิดพลาดในการเพิ่มผู้ใช้'];
            }
            
            header("Location: /mcvpro/public/admin/users");
            exit;
        }
    }

    /**
     * [UPDATE] แสดงฟอร์มสำหรับแก้ไขข้อมูลผู้ใช้
     */
    public function edit($hashedId)
    {
         $id = decodeId($hashedId);
        if ($id === null) {
            die("Invalid ID.");
        }
        $this->checkAdmin();
        
        $user = $this->userModel->findById($id);

        if (!$user) {
            $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'ไม่พบข้อมูลผู้ใช้'];
            header("Location: /mcvpro/public/admin/users");
            exit;
        }

        // โหลด View ที่มีฟอร์มแก้ไขข้อมูล
        include ROOT_PATH . 'app/views/layout/sidebar.php';
        include ROOT_PATH . 'app/views/users/edit.php';
    }

    /**
     * [UPDATE] อัปเดตข้อมูลผู้ใช้ลงฐานข้อมูล
     */
    // UsersController.php

    public function update($hashedId)
    {
        $id = decodeId($hashedId);
        if ($id === null) {
            die("Invalid ID.");
        }
        $this->checkAdmin();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'fname' => $_POST['fname_personal'] ?? '',
                'lname' => $_POST['lname_personal'] ?? '',
                'email' => $_POST['email_user'] ?? '',
                'role_id' => $_POST['role_id'] ?? 3
            ];

            // ถ้ามีการกรอกรหัสผ่านใหม่ ให้เข้ารหัสและเพิ่ม vào $data
            if (!empty($_POST['password'])) {
                $data['password'] = password_hash($_POST['password'], PASSWORD_DEFAULT);
            }

            // เรียกใช้เมธอดใหม่ที่เราสร้างขึ้น
            if ($this->userModel->updateUserAndProfile($id, $data)) {
                $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'อัปเดตข้อมูลผู้ใช้สำเร็จ'];
            } else {
                $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'เกิดข้อผิดพลาดในการอัปเดต'];
            }
            
            header("Location: /mcvpro/public/admin/users");
            exit;
        }
    }

    /**
     * [DELETE] ลบผู้ใช้ออกจากระบบ
     */
    // User.php

public function delete(int $hashedId): bool
{
    $id = decodeId($hashedId);
        if ($id === null) {
            die("Invalid ID.");
        }
    // ใช้ Transaction เพื่อความปลอดภัยในการลบข้อมูลจาก 2 ตาราง
    $this->pdo->beginTransaction();
    try {
        // ลบข้อมูลจาก personal ก่อน (เพราะมี foreign key)
        $stmt1 = $this->pdo->prepare("DELETE FROM personal WHERE user_id = ?");
        $stmt1->execute([$id]);

        // จากนั้นลบข้อมูลจาก users
        $stmt2 = $this->pdo->prepare("DELETE FROM users WHERE id_user = ?");
        $stmt2->execute([$id]);

        $this->pdo->commit();
        return true;

    } catch (PDOException $e) {
        $this->pdo->rollBack();
        return false;
    }
    
   
}
}