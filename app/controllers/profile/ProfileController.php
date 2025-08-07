<?php
class ProfileController extends Controller
{
    private $userModel;
    private $notificationModel;
    public function __construct($pdo)
    {
        parent::__construct($pdo);
        $this->auth(); // ทุกหน้าใน Controller นี้ต้อง Login ก่อน
        $this->userModel = new User($this->pdo);
        $this->notificationModel = new Notification($pdo);
    }

    /**
     * แสดงหน้าโปรไฟล์ (หน้าสำหรับดูเฉยๆ)
     */
    public function index()
{
    if (isset($_SESSION['user_email'])) {
        $email = $_SESSION['user_email'];
        
        $user = $this->userModel->getUserByEmail($email);
        if (isset($_SESSION['user_id'])) {
            $userId = $_SESSION['user_id'];
            $data['unreadCount'] = $this->notificationModel->countUnreadForUser($userId);
            $data['unreadNotifications'] = $this->notificationModel->getUnreadForUser($userId, 5);
        } else {
            $data['unreadCount'] = 0;
            $data['unreadNotifications'] = [];
        }

        include ROOT_PATH . 'app/views/profile/profile.php';
        include ROOT_PATH . 'app/views/layout/sidebar.php';

    } else {

        echo "Please log in to view this page.";
        exit(); 
    }
}

    /**
     * ✅ เมธอดใหม่: แสดงหน้าฟอร์มแก้ไขโปรไฟล์
     */
    public function edit()
    {
        $user = $this->userModel->getUserByEmail($_SESSION['user_email']);
        include ROOT_PATH . 'app/views/profile/edit.php';
    }

    /**
     * ✅ เมธอดใหม่: รับข้อมูลจากฟอร์มแก้ไข แล้วบันทึก
     */
    public function update()
    {
        $data = [
            'fname' => $_POST['fname'] ?? '',
            'lname' => $_POST['lname'] ?? '',
            'nname' => $_POST['nname'] ?? '',
            'dob' => $_POST['dob'] ?? null,
            'phone' => $_POST['phone'] ?? '',
            'address' => $_POST['address'] ?? '',
            'description' => $_POST['description'] ?? ''
        ];

        if ($this->userModel->updateProfile($_SESSION['user_id'], $data)) {
            $_SESSION['success'] = 'Profile updated successfully!';
            // อัปเดตชื่อใน session ด้วย
            $_SESSION['user_fname'] = $data['fname'];
        } else {
            $_SESSION['error'] = 'Failed to update profile.';
        }
        
        header('Location: /mcvpro/public/profile/edit');
        exit();
    }

    /**
     * รับไฟล์ภาพที่อัปโหลด, ตรวจสอบ, และบันทึก
     */
    public function upload()
    {
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = ROOT_PATH . 'public/uploads/profiles/';
            if (!is_dir($uploadDir)) { mkdir($uploadDir, 0755, true); }
            $file = $_FILES['profile_image'];
            $maxSize = 2 * 1024 * 1024; // 2MB
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
            if ($file['size'] > $maxSize) {
                $_SESSION['error'] = 'File is too large. Max 2MB.';
            } elseif (!in_array($file['type'], $allowedTypes)) {
                $_SESSION['error'] = 'Invalid file type. Only JPG, PNG, GIF are allowed.';
            } else {
                $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                $newFilename = 'user_' . $_SESSION['user_id'] . '_' . time() . '.' . $extension;
                $targetPath = $uploadDir . $newFilename;
                if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                    $this->userModel->updateProfileImage($_SESSION['user_id'], $newFilename);
                    $_SESSION['success'] = 'Profile picture updated successfully!';
                } else {
                    $_SESSION['error'] = 'Failed to move uploaded file.';
                }
            }
        } else {
            $_SESSION['error'] = 'No file uploaded or an error occurred.';
        }
        header('Location: /mcvpro/public/profile/edit');
        exit();
    }

    public function security()
    {
        $userId = $_SESSION['user_id'];
        $userEmail = $_SESSION['user_email']; 

  
        $otp = random_int(100000, 999999);
        $this->userModel->updateOtpById($userId, $otp); 

        $mailService = new MailService();
        $mailService->sendPasswordResetOtpEmail($userEmail, $otp);

        header('Location: /mcvpro/public/profile/change-password-form');
        exit();
    }

    /**
     * ✅ 2. แสดงฟอร์มสำหรับกรอกรหัสผ่านใหม่
     */
    public function changePasswordForm()
    {
        $page_title = "Change Password";
        // เรียก View ที่มีฟอร์ม (เราจะสร้างในขั้นตอนถัดไป)
        include ROOT_PATH . 'app/views/profile/change_password_form.php';
    }

    /**
     * ✅ 3. อัปเดตรหัสผ่านใหม่ลงฐานข้อมูล
     */
    public function updatePassword()
    {
        // รับค่าจากฟอร์ม
        $userId = $_SESSION['user_id'];
        $otp = $_POST['otp'];
        $newPassword = $_POST['password'];
        $confirmPassword = $_POST['confirm_password'];

        // --- ตรวจสอบข้อมูล ---
        if ($newPassword !== $confirmPassword) {
            $_SESSION['error'] = "รหัสผ่านใหม่ไม่ตรงกัน";
            header('Location: /mcvpro/public/profile/change-password-form');
            exit();
        }

        // ตรวจสอบ OTP กับในฐานข้อมูล
        $isOtpValid = $this->userModel->verifyOtp($userId, $otp); // คุณต้องสร้างฟังก์ชันนี้ใน Model

        if ($isOtpValid) {
            // ถ้า OTP ถูกต้อง -> อัปเดตรหัสผ่านใหม่
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $this->userModel->updatePasswordById($userId, $hashedPassword); // คุณต้องสร้างฟังก์ชันนี้ใน Model

            $_SESSION['success'] = "เปลี่ยนรหัสผ่านสำเร็จแล้ว";
            header('Location: /mcvpro/public/profile'); // กลับไปหน้าโปรไฟล์
            exit();
        } else {
            // ถ้า OTP ผิด
            $_SESSION['error'] = "รหัส OTP ไม่ถูกต้องหรือหมดอายุ";
            header('Location: /mcvpro/public/profile/change-password-form');
            exit();
        }
    }
    
}