<?php

class ContactsController extends Controller
{
    private $customerModel;
    private $contactModel;

    public function __construct($pdo)
    {
        parent::__construct($pdo);
        $this->auth();
        $this->customerModel = new Customers($this->pdo);
        $this->contactModel = new Contact($this->pdo);
    }

    /**
     * แสดงฟอร์มสำหรับสร้างผู้ติดต่อใหม่
     */
    public function create($hashedId)
    {
        
        $customerId = decodeId($hashedId);
        if ($customerId === null) {
            die("Invalid Customer ID.");
        }

        if (!$this->customerModel->canUserAccess($customerId, $_SESSION['user_id'], $_SESSION['user_role'])) {
            die("Access Denied.");
        }

        // ✅ ส่ง HASHED ID ไปให้ View โดยใช้ชื่อ 'customer_id'
        $data['customer'] = $this->customerModel->getById($customerId);
        $data['customer_id'] = $hashedId; 
        $page_title = "Add New Contact";

        include ROOT_PATH . 'app/views/layout/sidebar.php';
        include ROOT_PATH . 'app/views/contacts/create.php';
    }

    /**
     * บันทึกข้อมูลผู้ติดต่อใหม่
     */
    public function store()
    {
        // ✅ รับค่า Hashed ID จากฟอร์มด้วยชื่อ 'customer_id'
        $hashedCustomerId = $_POST['customer_id'];
        
        $customerId = decodeId($hashedCustomerId);
        if ($customerId === null) {
            die("Invalid Customer ID.");
        }
        
        if (!$this->customerModel->canUserAccess($customerId, $_SESSION['user_id'], $_SESSION['user_role'])) {
            die("Access Denied.");
        }
        
        if (empty($_POST['contact_name'])) {
            $_SESSION['error'] = "Contact name is required.";
            header('Location: /mcvpro/public/contacts/create/' . $hashedCustomerId);
            exit();
        }

        $data = [
            'customer_id'      => $customerId, // <-- ใช้ ID ที่ถอดรหัสแล้ว
            'contact_name'     => $_POST['contact_name'],
            'contact_email'    => $_POST['contact_email'],
            'contact_phone'    => $_POST['contact_phone'],
            'contact_position' => $_POST['contact_position'],
        ];

        if ($this->contactModel->create($data)) {
            $_SESSION['success'] = 'Contact added successfully.';
        } else {
            $_SESSION['error'] = 'Failed to add contact.';
        }
        
        header('Location: /mcvpro/public/customers/view/' . encodeId($customerId));
        exit();
    }
}