<?php
// app/controllers/BranchesController.php
class BranchesController extends Controller {
    
    // หน้านี้คือหน้าที่ผู้ใช้กรอกฟอร์ม
    // หน้าที่แสดงฟอร์ม
    public function create($hashedId) {
        // ถอดรหัสเพื่อเช็คความถูกต้อง
        $customerId = decodeId($hashedId);
        if ($customerId === null) {
            die("Invalid Customer ID.");
        }
        
        // ✅ ส่ง HASHED ID ไปให้ฟอร์ม โดยใช้ชื่อเดิมคือ 'customer_id'
        $data['customer_id'] = $hashedId; 

        $page_title = "Add New Branch";
        include ROOT_PATH . 'app/views/layout/sidebar.php';
        include ROOT_PATH . 'app/views/branches/create.php';
    }

    // หน้าที่รับข้อมูลจากฟอร์ม
    public function store() {
        $this->auth();
        
        // 1. รับ HASHED ID จากฟอร์ม (จาก input ที่ชื่อ 'customer_id')
        $hashedCustomerId = $_POST['customer_id']; 

        // 2. ถอดรหัสกลับเป็นตัวเลข
        $customerId = decodeId($hashedCustomerId);
        if ($customerId === null) {
            die("Invalid Customer ID provided.");
        }
        
        // 3. ใช้ ID ที่เป็นตัวเลขสำหรับทุกอย่าง
        $customerModel = new Customers($this->pdo);
        if (!$customerModel->canUserAccess($customerId, $_SESSION['user_id'], $_SESSION['user_role'])) {
            die("Access Denied.");
        }

        $data = [
            'customer_id'    => $customerId, // <-- ใช้ ID ที่เป็นตัวเลข
            'branch_name'    => $_POST['branch_name'],
            'branch_address' => $_POST['branch_address'],
            'branch_phone'   => $_POST['branch_phone'],
        ];

        $branchModel = new Branch($this->pdo);
        if ($branchModel->create($data)) {
            $_SESSION['success'] = 'Branch added successfully.';
        } else {
            $_SESSION['error'] = 'Failed to add branch.';
        }
        
        // 4. ตอนย้ายหน้า ให้เข้ารหัสกลับเป็น hash
        header('Location: /mcvpro/public/customers/view/' . encodeId($customerId));
        exit();
    }
}