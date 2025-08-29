<?php

class CustomersController extends Controller
{
    /**
     * @var Customers Model
     */
    private $customerModel;
    private $notificationModel;
    
    public function __construct($pdo)
    {
        parent::__construct($pdo);
        $this->auth();
        $this->customerModel = new Customers($this->pdo);
        $this->notificationModel = new Notification($pdo);
    }


   public function index()
    {
        $page_title = "Customer List";
        $data = [];
        $role = $_SESSION['user_role'] ?? 0;
        $userId = $_SESSION['user_id'] ?? 0;

        // 1. Prepare Notification
        if (isset($_SESSION['user_id'])) {
            $data['unreadCount'] = $this->notificationModel->countUnreadForUser($userId);
            $data['unreadNotifications'] = $this->notificationModel->getUnreadForUser($userId, 5);
        }

        // 2. Get filter from URL
        $filters = [
            'search'     => trim($_GET['search'] ?? ''),
            'start_date' => trim($_GET['start_date'] ?? ''),
            'end_date'   => trim($_GET['end_date'] ?? '')
        ];

        // 3. Pagination setup
        $limit = 15;
        $page = isset($_GET['page']) && is_numeric($_GET['page']) ? max((int)$_GET['page'], 1) : 1;
        $offset = ($page - 1) * $limit;

        // 4. Load customer data with or without filters
        if (!empty(array_filter($filters))) {
            $customers = $this->customerModel->getFiltered($filters, $limit, $offset);
            $totalCustomers = $this->customerModel->countFiltered($filters);
        } else {
            if (in_array($role, [1, 2])) {
                $customers = $this->customerModel->getAll($limit, $offset);
                $totalCustomers = $this->customerModel->countAll();
            } else {
                $customers = $this->customerModel->getAllForUser($userId, $limit, $offset);
                $totalCustomers = $this->customerModel->countAllForUser($userId);
            }
        }

        // 5. Set data for View
        $data['customers'] = $customers;
        $data['currentPage'] = $page;
        $data['totalPages'] = ceil($totalCustomers / $limit);

        // 6. Include view
        include ROOT_PATH . 'app/views/layout/sidebar.php';
        include ROOT_PATH . 'app/views/customer/index.php';
    }

    public function view($hashedId)
    {
        $customerId = decodeId($hashedId);
        if ($customerId === null) {
            die("Invalid ID.");
        }
        // ใช้ getById เพื่อดึงข้อมูลพื้นฐานมาก่อน
        $customer = $this->customerModel->getById($customerId);
        $branchModel = new Branch($this->pdo);
        if (!$customer) { 
            http_response_code(404);
            die("404 Customer Not Found."); 
        }
        
        // ตรวจสอบสิทธิ์ด้วย canUserAccess
        $canAccess = $this->customerModel->canUserAccess($customerId, $_SESSION['user_id'], $_SESSION['user_role']);
        if (!$canAccess) {
            http_response_code(403);
            die("<h1>403 Forbidden</h1><p>You do not have permission to view this customer.</p>");
        }
        $contactModel = new Contact($this->pdo);
        // เตรียมข้อมูลทั้งหมดส่งไปให้ View ในตัวแปร $data
        $projectModel = new Project($this->pdo);
        $userModel = new User($this->pdo);
        $loggedInUserId = $_SESSION['user_id'];

        $data = [
            'customer' => $customer,
            'assignedProjects' => $projectModel->getAssignedByCustomerId($customerId),
            'unassignedProjects' => $projectModel->getAllUnassigned(),
            'collaborators' => $this->customerModel->getCollaborators($customerId),
            'allUsers' => $userModel->getAllUsers($loggedInUserId), 
            'branches' => $branchModel->getAllForCustomer($customerId),
            'contacts' => $contactModel->getAllForCustomer($customerId),
            'isOwner' => ($customer['user_id'] == $loggedInUserId),
            'canManage' => $canAccess 
        ];

        $page_title = $customer['company_name'];
        include ROOT_PATH . 'app/views/customer/view.php';
}

/**
 * แสดงหน้าแก้ไข/จัดการข้อมูลลูกค้า 
 */
public function edit($hashedId)
{
    $id = decodeId($hashedId);
        if ($id === null) {
            die("Invalid ID.");
        }

    if (!$this->customerModel->canUserAccess($id, $_SESSION['user_id'], $_SESSION['user_role'])) {
        http_response_code(403);
        die("<h1>403 Forbidden</h1><p>You do not have permission to edit this customer.</p>");
    }

    $customer = $this->customerModel->getById($id);
    if (!$customer) { die("404 Customer Not Found."); }
    
    // ส่งแค่ข้อมูล customer ไปก็พอ
    $page_title = 'Edit: ' . $customer['company_name'];
    include ROOT_PATH . 'app/views/customer/edit.php';
}


/**
 * แสดงหน้าสำหรับจัดการโปรเจกต์และผู้ร่วมแก้ไข
 */
    public function manage($hashedId)
    {
        $customerId = decodeId($hashedId);
        if ($customerId === null) {
            die("Invalid ID.");
        }
        // ตรวจสอบสิทธิ์: เฉพาะเจ้าของเท่านั้น
        $customer = $this->customerModel->getById($customerId);
        if (!$customer || $customer['user_id'] != $_SESSION['user_id']) {
            http_response_code(403);
            die("<h1>403 Forbidden</h1><p>Only the owner can access this management page.</p>");
        }
        
        // ✅✅✅ เพิ่มการดึงข้อมูลที่จำเป็นทั้งหมดสำหรับหน้า Manage ✅✅✅
        $projectModel = new Project($this->pdo);
        $userModel = new User($this->pdo);
        $loggedInUserId = $_SESSION['user_id'];

        $data = [
            'customer'           => $customer,
            'assignedProjects'   => $projectModel->getAssignedByCustomerId($customerId),
            'unassignedProjects' => $projectModel->getAllUnassigned(), // ใช้ฟังก์ชันที่เห็นโปรเจกต์ว่างทั้งหมด
            'collaborators'      => $this->customerModel->getCollaborators($customerId),
            'allUsers'           => $userModel->getAllUsers($loggedInUserId), 
            'isOwner'            => true
        ];

        $page_title = 'Manage: ' . $customer['company_name'];
        
        include ROOT_PATH . 'app/views/customer/manage.php';
    }
    /**
     * แสดงฟอร์มสำหรับสร้างลูกค้าใหม่
     */
    public function create()
    {
        // อนุญาตให้ Admin และ Staff สร้างได้
        include ROOT_PATH . 'app/views/customer/create.php';
    }

    /**
     * รับข้อมูลจากฟอร์มสร้างลูกค้าแล้วบันทึกลงฐานข้อมูล
     */
    // แทนที่ฟังก์ชัน store()
public function store()
{

    $logger = LoggerService::getLogger('customer');
    if (empty($_POST['company_name'])) {
        $_SESSION['error'] = 'Company name is required.';
        header('Location: /mcvpro/public/customers/create');
        exit();
    }

    // ✅ เพิ่มการรับข้อมูลใหม่
    $data = [
        'company_name'     => $_POST['company_name'],
        'created_by'       => $_SESSION['user_id'],
        'customer_phone'   => $_POST['customer_phone'] ?? null,
        'customer_email'   => $_POST['customer_email'] ?? null,
        'customer_address' => $_POST['customer_address'] ?? null
    ];

    if ($this->customerModel->create($data)) {
        $_SESSION['success'] = 'Customer added successfully.';
        $logger->info('Customer added successfully.', ['company_name'=> $_POST['company_name'], 'created_by'=> $_SESSION['user_id']]);
    } else {
        $_SESSION['error'] = 'Failed to add customer.';
        $logger->info('Failed to add customer.', ['company_name'=> $_POST['company_name'], 'created_by'=> $_SESSION['user_id']]);
    }
    header('Location: /mcvpro/public/customers');
    exit();
}


/**
 * รับข้อมูลที่แก้ไขแล้วมาอัปเดตลงฐานข้อมูล (พร้อม Log การเปลี่ยนแปลง)
 */
public function update($id)
{
    
    // --- 1. ตรวจสอบสิทธิ์ ---
    if (!$this->customerModel->canUserAccess($id, $_SESSION['user_id'], $_SESSION['user_role'])) {
        http_response_code(403);
        die("<h1>403 Forbidden</h1><p>You do not have permission to update this customer.</p>");
    }

    // --- 2. ดึงข้อมูลเก่าเพื่อใช้เปรียบเทียบ ---
    $oldCustomer = $this->customerModel->getById($id);
    if (!$oldCustomer) {
        // จัดการกรณีหาลูกค้าไม่เจอ
        $_SESSION['error'] = 'Customer not found.';
        header('Location: /mcvpro/public/customers');
        exit();
    }

    // --- 3. เตรียมข้อมูลใหม่จากฟอร์ม ---
    $newData = [
        'company_name'     => $_POST['company_name'] ?? '',
        'customer_phone'   => $_POST['customer_phone'] ?? null,
        'customer_email'   => $_POST['customer_email'] ?? null,
        'customer_address' => $_POST['customer_address'] ?? null,
    ];

    // --- 4. ตรวจสอบข้อมูล (Validation) ---
    if (empty($newData['company_name'])) {
        $_SESSION['error'] = 'Company name is required.';
        header('Location: /mcvpro/public/customers/edit/' . $id);
        exit();
    }

    // --- 5. เปรียบเทียบหาข้อมูลที่เปลี่ยนแปลง ---
    $changedData = [];
    foreach ($newData as $key => $value) {
        if (isset($oldCustomer[$key]) && $oldCustomer[$key] != $value) {
            $changedData[$key] = [
                'from' => $oldCustomer[$key],
                'to'   => $value
            ];
        }
    }

    // --- 6. อัปเดตและบันทึก Log ---
    $logger = LoggerService::getLogger('customer');

    if ($this->customerModel->updateById($id, $newData)) {
        $_SESSION['success'] = 'Customer updated successfully.';
        
        // บันทึก Log ก็ต่อเมื่อมีการเปลี่ยนแปลงข้อมูล
        if (!empty($changedData)) {
            $logger->info(
                'Customer details updated.',
                [
                    'customer_id' => $id,
                    'updated_by_user_id' => $_SESSION['user_id'],
                    'changes' => $changedData
                ]
            );
        }
    } else {
        $_SESSION['error'] = 'Failed to update customer.';
        // ✅ ใช้ error() สำหรับการทำงานที่ล้มเหลว
        $logger->error('Failed to update customer in database.', ['customer_id' => $id, 'user_id' => $_SESSION['user_id']]);
    }

    header('Location: /mcvpro/public/customers');
    exit();
}

    /**
     * ลบข้อมูลลูกค้า
     */
    public function delete($id)
    {

        $logger = LoggerService::getLogger('customer');
        $this->hasRole([2]); // เฉพาะ Staff ที่ลบได้
        if ($this->customerModel->deleteById($id)) {
            $_SESSION['success'] = 'Customer and related projects deleted successfully.';
            $logger->info('Customer deleted.', ['customer_id' => $id, 'user_id' => $_SESSION['user_id']]);
        } else {
            $_SESSION['error'] = 'Failed to delete customer.';
            $logger->info('Failed to delete customer.', ['customer_id' => $id, 'user_id' => $_SESSION['user_id']]);
        }
        header('Location: /mcvpro/public/customers');
        exit();
    }

    // --- เมธอดสำหรับจัดการผู้ร่วมแก้ไขและโปรเจกต์ ---

    public function addCollaborator($hashedId)
    {
        $customerId = decodeId($hashedId);
        if ($customerId === null) {
            die("Invalid ID.");
        }
        $logger = LoggerService::getLogger('customer');
        $customer = $this->customerModel->getById($customerId);
        if ($customer['user_id'] != $_SESSION['user_id']) {
            $_SESSION['error'] = "Only the owner can add collaborators.";
            header('Location: /mcvpro/public/customers/manage/' . encodeId($customerId));
            exit();
        }

        $userIdToAdd = $_POST['user_id'] ?? 0;
        $roleIdToAdd = 2; // กำหนดให้เป็น Staff (แก้ไขได้) โดยอัตโนมัติ

        if ($userIdToAdd) {
            $this->customerModel->addCollaborator($customerId, $userIdToAdd, $roleIdToAdd);
            $_SESSION['success'] = "Collaborator added successfully.";
            $logger->info(
            'Collaborator added customer.', 
            [
                'customer_id' => $customerId,
                'add_user_id' => $userIdToAdd,
                'action_by_user_id' => $_SESSION['user_id'] 
            ]
        );
        } else {
            $_SESSION['error'] = "Please select a user.";
        }
        header('Location: /mcvpro/public/customers/manage/' . encodeId($customerId));
        exit();
    }

    public function removeCollaborator($hashedId, $userIdToRemove)
    {
        $customerId = decodeId($hashedId);
        if ($customerId === null) {
            die("Invalid ID.");
        }
        $logger = LoggerService::getLogger('customer');
        $customer = $this->customerModel->getById($customerId);
        if ($customer['user_id'] != $_SESSION['user_id']) {
            $_SESSION['error'] = "Only the owner can remove collaborators.";
            header('Location: /mcvpro/public/customers/manage/' . encodeId($customerId));
            exit();
        }
        
        $this->customerModel->removeCollaborator($customerId, $userIdToRemove);
        $_SESSION['success'] = "Collaborator removed successfully.";
        $logger->info(
            'Collaborator removed from customer.', 
            [
                'customer_id' => $customerId,
                'removed_user_id' => $userIdToRemove,
                'action_by_user_id' => $_SESSION['user_id'] 
            ]
        );
        header('Location: /mcvpro/public/customers/manage/' . encodeId($customerId));
        exit();
    }

    public function assignProject($hashedId)
    {
        $customerId = decodeId($hashedId);
        if ($customerId === null) {
            die("Invalid ID.");
        }
        $logger = LoggerService::getLogger('customer');
        $loggedInUserId = $_SESSION['user_id'];
        $projectId = $_POST['project_id'] ?? 0;

        if (!$this->customerModel->getByIdAndOwner($customerId, $loggedInUserId)) {
            $_SESSION['error'] = 'Access Denied.';
            header('Location: /mcvpro/public/customers');
            exit();
        }
        
        $projectModel = new Project($this->pdo);
        if ($projectModel->assignToCustomer($projectId, $customerId, $loggedInUserId)) {
            $_SESSION['success'] = 'Project assigned successfully!';
            $logger->info(
            'Project assigned successfully!', 
            [
                'customer_id' => $customerId,
                'project_id' => $projectId,
                'action_by_user_id' => $_SESSION['user_id'] 
            ]
        );
        } else {
            $_SESSION['error'] = 'Failed to assign project. It might be already assigned or you are not the owner.';
            $logger->info(
            'Failed to assign project. It might be already assigned or you are not the owner.', 
            [
                'customer_id' => $customerId,
                'project_id' => $projectId,
                'action_by_user_id' => $_SESSION['user_id'] 
            ]
        );
        }

        header('Location: /mcvpro/public/customers/manage/' . encodeId($customerId));
        exit();
    }
    
   public function unassignProject($hashedId, $projectId)
{
    $customerId = decodeId($hashedId);
        if ($customerId === null) {
            die("Invalid ID.");
        }
    $logger = LoggerService::getLogger('customer');
    $loggedInUserId = $_SESSION['user_id'];

    // ✅ ใช้ $customerId ที่ได้รับจาก URL โดยตรง
    if (!$this->customerModel->getByIdAndOwner($customerId, $loggedInUserId)) {
        $_SESSION['error'] = 'Access Denied.';
        header('Location: /mcvpro/public/customers');
        exit();
    }

    $projectModel = new Project($this->pdo);
    if ($projectModel->unassignFromCustomer($projectId, $loggedInUserId)) {
        $_SESSION['success'] = 'Project has been unassigned successfully.';
        $logger->info('Project unassigned.', ['project_id' => $projectId, 'customer_id' => $customerId, 'user_id' => $loggedInUserId]);
    } else {
        $_SESSION['error'] = 'Failed to unassign project.';
        $logger->error('Failed to unassign project.', ['project_id' => $projectId, 'customer_id' => $customerId, 'user_id' => $loggedInUserId]);
    }

    header('Location: /mcvpro/public/customers/manage/' . encodeId($customerId));
    exit();
}
}