<?php


// เริ่มต้น session หากยังไม่ได้เริ่มต้น
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// เรียกใช้ User model 
require_once __DIR__ . '/../../models/User.php';
// เรียกใช้ไฟล์เชื่อมต่อฐานข้อมูล





class LoginController {
    private $userModel;
    private $pdo; 

    public function __construct() {
        global $pdo; 
        if (!$pdo) {
            die("ไม่สามารถเชื่อมต่อฐานข้อมูลได้");
        }
        $this->pdo = $pdo;
        // สร้าง instance ของ User model โดยส่ง $pdo เข้าไป
        $this->userModel = new User($this->pdo);
    }

    public function index() {
        // แสดงหน้าฟอร์ม Login
        require_once __DIR__ . '/../../views/auth/login.php';
    }

    public function authenticate() {
    $logger = LoggerService::getLogger('login');
    // ตรวจสอบว่าเป็น POST request
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input_email = $_POST['email'] ?? '';
        $input_password = $_POST['password'] ?? '';

        if (empty($input_email) || empty($input_password)) {
            $_SESSION['login_error'] = "กรุณากรอกข้อมูลให้ครบถ้วน";
            header("Location: /mcvpro/public/login");
            exit;
        }

        $userModel = new User($this->pdo); // ใช้ $this->pdo ที่ Controller มีอยู่แล้ว
        $user = $userModel->getUserByEmail($input_email);

        if ($user) {
            // ตรวจสอบรหัสผ่าน
            if (password_verify($input_password, $user['password_user'])) {
                // ตรวจสอบการยืนยันอีเมล
                if ($user['is_verified'] != 1) {
                    $_SESSION['login_error'] = "❌ กรุณายืนยันอีเมลก่อนเข้าสู่ระบบ";
                    // ✅ บันทึก Log กรณีพยายาม Login แต่ยังไม่ยืนยันตัวตน
                    $logger->warning('Login attempt with unverified email', ['email' => $input_email]);
                    header("Location: /mcvpro/public/auth/login");
                    exit;
                }

                // --- Login สำเร็จ ---
                
                // 4. บันทึก Log ก่อน แล้วค่อย Redirect
                $logger->info('User logged in successfully', ['user_id' => $user['id_user'], 'email' => $user['email_user']]);

                // ตั้งค่า session
                $_SESSION['user_id'] = $user['id_user'];
                $_SESSION['user_email'] = $user['email_user'];
                $_SESSION['user_role'] = (int)$user['role_id'];
                $_SESSION['user_fname'] = $user['fname_personal'];
                $_SESSION['user_email'] = $user['email_user'];
                $_SESSION['last_activity'] = time();
                session_regenerate_id(true);

                // Redirect ตาม Role
                if ($_SESSION['user_role'] === 1) { 
                    header("Location: /mcvpro/public/home");
                } else {
                    header("Location: /mcvpro/public/home");
                }
                exit;

            } else {
                // รหัสผ่านไม่ถูกต้อง
                $_SESSION['login_error'] = "❌ รหัสผ่านไม่ถูกต้อง";
                // ✅ บันทึก Log กรณีใส่รหัสผ่านผิด
                $logger->warning('Failed login attempt (Incorrect Password)', ['email' => $input_email]);
                header("Location: /mcvpro/public/login");
                exit;
            }
        } else {
            // ไม่พบบัญชีผู้ใช้
            $_SESSION['login_error'] = "❌ ไม่พบบัญชีนี้ในระบบ";
            // ✅ บันทึก Log กรณีไม่พบอีเมลในระบบ
            $logger->warning('Failed login attempt (User not found)', ['email' => $input_email]);
            header("Location: /mcvpro/public/login");
            exit;
        }
    } else {
        // หากไม่ใช่ POST request
        header("Location: /mcvpro/public/auth/login");
        exit;
    }
    }



    public function home() {
        if (!isset($_SESSION['user_id'])) {
            header("Location: /mcvpro/public/login");
            exit;
        }
        include_once __DIR__ . '/../../views/home.php';
    }


    // เมธอดสำหรับ Logout
    public function logout() {
        $logger = LoggerService::getLogger('logout'); // สมมติว่ามี LoggerService

        // ตรวจสอบว่ามี session และ log สาเหตุ
        if (isset($_SESSION['user_id'])) {
            $isTimeout = isset($_GET['reason']) && $_GET['reason'] === 'timeout';
            if ($isTimeout) {
                $logger->info('User session timed out and was logged out automatically.', ['user_id' => $_SESSION['user_id']]);
            } else {
                $logger->info('User logged out manually.', ['user_id' => $_SESSION['user_id']]);
            }
        }
        session_unset();   // ลบตัวแปร session ทั้งหมด
        session_destroy(); // ทำลาย session
        $redirectUrl = "/mcvpro/public/login";
        if (isset($isTimeout) && $isTimeout) {
            $redirectUrl .= "?status=timeout";
        }

        header("Location: " . $redirectUrl);
        exit;
    }
}