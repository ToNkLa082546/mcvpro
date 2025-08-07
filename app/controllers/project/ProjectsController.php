<?php

class ProjectsController extends Controller
{
    private $projectModel;
    private $customerModel;
    private $notificationModel;
    public function __construct($pdo)
    {
        parent::__construct($pdo);
        $this->auth(); // ทุกหน้าในนี้ต้อง Login ก่อน
        $this->projectModel = new Project($this->pdo);
        $this->customerModel = new Customers($this->pdo);
        $this->notificationModel = new Notification($pdo);
    }

    /**
     * แสดงรายการโปรเจกต์ทั้งหมด
     */
    public function index()
{
    $page_title = "Project List";
    $data = [];
    $role = $_SESSION['user_role'] ?? 0;
    $userId = $_SESSION['user_id'] ?? 0;
    
    // เตรียมข้อมูล Notification สำหรับ Sidebar
    if (isset($_SESSION['user_id'])) {
        $data['unreadCount'] = $this->notificationModel->countUnreadForUser($userId);
        $data['unreadNotifications'] = $this->notificationModel->getUnreadForUser($userId, 5);
    }
    
    // รับค่าฟิลเตอร์และ Pagination
    $filters = [
        'search'     => trim($_GET['search'] ?? ''),
        'status'     => trim($_GET['status'] ?? ''),
        'start_date' => trim($_GET['start_date'] ?? ''),
        'end_date'   => trim($_GET['end_date'] ?? '')
    ];
    $perPage = 15;
    $currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;

    // ตรวจสอบว่ามีการใช้ฟิลเตอร์หรือไม่
    if (!empty(array_filter($filters))) {
        // ถ้ามีฟิลเตอร์, เรียกใช้เมธอดค้นหา
        $totalProjects = $this->projectModel->countFiltered($filters, $role, $userId);
        $projects = $this->projectModel->getFilteredPaginated($filters, $currentPage, $perPage, $role, $userId);
    } else {
        // ถ้าไม่มีฟิลเตอร์, ดึงข้อมูลตาม Role
        if (in_array($role, [1, 2])) {
            $totalProjects = $this->projectModel->countAll();
            $projects = $this->projectModel->getAllPaginated($currentPage, $perPage);
        } else {
            $totalProjects = $this->projectModel->countProjectsForUser($userId);
            $projects = $this->projectModel->getProjectsForUserPaginated($userId, $currentPage, $perPage);
        }
    }

    // ส่งข้อมูลทั้งหมดไปให้ View
    $data['projects'] = $projects;
    $data['total_projects'] = $totalProjects;
    $data['per_page'] = $perPage;
    $data['current_page'] = $currentPage;
    
    // เรียกใช้ View
    include ROOT_PATH . 'app/views/layout/sidebar.php';
    include ROOT_PATH . 'app/views/project/projects.php';
}

    /**
     * แสดงฟอร์มสร้างโปรเจกต์
     */
    public function create($customerId = null)
    {
        include ROOT_PATH . 'app/views/project/create.php';
    }

    /**
     * บันทึกโปรเจกต์ใหม่ลงฐานข้อมูล
     */
    public function store()
    {
        $logger = LoggerService::getLogger('project');
    // 1. ตรวจสอบข้อมูลเบื้องต้น (เอา customer_id ออก)
    if (empty($_POST['project_name'])) {
        $_SESSION['error'] = 'Project Name is required.';
        header('Location: /mcvpro/public/projects/create');
        exit();
    }

    // 2. เตรียมข้อมูลสำหรับส่งให้ Model (เอา customer_id ออก)
    $data = [
        'project_name'  => $_POST['project_name'],
        'description'   => $_POST['description'] ?? '',
        'project_price' => $_POST['project_price'] ?? 0,
        'created_by'    => $_SESSION['user_id']
    ];

    // 3. เรียกใช้ Project Model เพื่อบันทึกข้อมูล
    $projectModel = new Project($this->pdo);
    if ($projectModel->create($data)) {
        $_SESSION['success'] = 'Project created successfully!';
        $logger->info(
            'Project created successfully!', 
            [
                'project_name'  => $_POST['project_name'],
                'action_by_user_id'    => $_SESSION['user_id']
            ]
        );
        header('Location: /mcvpro/public/projects');
        exit();
    } else {
        $_SESSION['error'] = 'Failed to create project.';
        $logger->info(
            'Failed to create project.', 
            [
                'project_name'  => $_POST['project_name'],
                'action_by_user_id'    => $_SESSION['user_id']
            ]
        );
        header('Location: /mcvpro/public/projects/create');
        exit();
    }
    }

    /**
     * แสดงฟอร์มแก้ไขโปรเจกต์
     */
    public function edit($hashedId)
    {
        $id = decodeId($hashedId);
        if ($id === null) {
            die("Invalid ID.");
        }
        
        
        $project = $this->projectModel->getById($id);
        if (!$project) { die("Project not found."); }
        
        $customers = $this->customerModel->getAll();
        include ROOT_PATH . 'app/views/project/edit.php';
    }

    /**
     * อัปเดตข้อมูลโปรเจกต์ในฐานข้อมูล
     */
    public function update()
    {
        $hashedId = $_POST['project_id'];
        $id = decodeId($hashedId);
        if ($id === null) {
            die("Invalid ID.");
        }

        $oldProject = $this->projectModel->getById($id);
        if (!$oldProject) {
            // จัดการกรณีหาโปรเจกต์ไม่เจอ
            $_SESSION['error'] = 'Project not found.';
            header('Location: /mcvpro/public/projects');
            exit();
        }

        $logger = LoggerService::getLogger('project');

        $newData = [
            'project_name'  => $_POST['project_name'],
            'description'   => $_POST['description'],
            'project_price' => $_POST['project_price'],
            'customer_id'   => $_POST['customer_id'],
            'status'        => $_POST['status']
        ];

        $changedData = [];
        foreach ($newData as $key => $value) {
            // ตรวจสอบว่ามี key นี้ในข้อมูลเก่า และค่าไม่ตรงกันหรือไม่
            if (isset($oldProject[$key]) && $oldProject[$key] != $value) {
                $changedData[$key] = [
                    'from' => $oldProject[$key],
                    'to' => $value
                ];
            }
        }

        if ($this->projectModel->update($id, $newData)) {
            $_SESSION['success'] = 'Project updated successfully!';
            if (!empty($changedData)) { // บันทึกก็ต่อเมื่อมีการเปลี่ยนแปลงจริงๆ
            $logger->info(
                'Project updated updated.',
                [
                    'project_id' => $id,
                    'updated_by_user_id' => $_SESSION['user_id'],
                    'changes' => $changedData 
                ]
            );
        }
        } else {
            $_SESSION['error'] = 'Failed to update project.';
            $logger->error('Failed to update project.', ['project_id' => $id, 'user_id' => $_SESSION['user_id']]);
        }
        header('Location: /mcvpro/public/projects');
        exit();
    }

    /**
     * ลบโปรเจกต์
     */
    public function delete($hashedId)
    {
        $id = decodeId($hashedId);
        if ($id === null) {
            die("Invalid ID.");
        }
        $logger = LoggerService::getLogger('project');
        $this->hasRole([2]);
        if ($this->projectModel->delete($id)) {
            $_SESSION['success'] = 'Project deleted successfully.';
            $logger->info(
            'Project deleted successfully.', 
            [
                'project_name'  => $_POST['project_name'],
                'action_by_user_id'    => $_SESSION['user_id']
            ]
            );
        } else {
            $_SESSION['error'] = 'Failed to delete project.';
            $logger->info(
            'Failed to deleted successfully.', 
            [
                'project_name'  => $_POST['project_name'],
                'action_by_user_id'    => $_SESSION['user_id']
            ]
            );
        }
        header('Location: /mcvpro/public/projects');
        exit();
    }
    /**
     * แสดงหน้ารายการโปรเจกต์ที่ยังว่าง (สำหรับลูกค้า)
     */
    public function browse()
    {

        $projects = $this->projectModel->getAvailableProjects();
        include ROOT_PATH . 'app/views/projects/browse.php';
    }

    /**
     * รับคำขอเลือกโปรเจกต์จากลูกค้า
     * @param int $id ID ของโปรเจกต์ที่ถูกเลือก
     */
    public function claim($hashedId)
    {
        $id = decodeId($hashedId);
        if ($id === null) {
            die("Invalid ID.");
        }
        // ดึง Customer ID จาก User ID ที่ Login อยู่
        $customerId = $_SESSION['user_id']; 

        if ($this->projectModel->assignCustomer($id, $customerId)) {
            $_SESSION['success'] = 'You have successfully claimed the project!';
        } else {
            $_SESSION['error'] = 'Failed to claim the project. It might have been taken by someone else.';
        }

        // กลับไปที่หน้ารายการโปรเจกต์
        header('Location: /mcvpro/public/projects/browse');
        exit();
    }
    public function view($hashedId)
    {
        $id = decodeId($hashedId);
        if ($id === null) {
            die("Invalid ID.");
        }
        // 1. ดึงข้อมูลโปรเจกต์จาก Model
        $project = $this->projectModel->getById($id);

        // ถ้าหาโปรเจกต์ไม่เจอ, แสดงหน้า 404
        if (!$project) {
            http_response_code(404);
            die("404 Project Not Found.");
        }

        // 2. ตรรกะการกำหนดสิทธิ์: ใครสามารถแก้ไข/ลบได้บ้าง
        // ในที่นี้คือ Admin(1), Staff(2), หรือเจ้าของโปรเจกต์เท่านั้น
        $isCreator = ($_SESSION['user_id'] == $project['created_by']);
        $isAdminOrStaff = in_array($_SESSION['user_role'], [1, 2]);
        $canEditOrDelete = ($isCreator || $isAdminOrStaff);

        // 3. เรียก View และส่งข้อมูลที่จำเป็นไปด้วย
        // ตัวแปร $project และ $canEditOrDelete จะสามารถใช้ได้ในไฟล์ view.php
        include ROOT_PATH . 'app/views/project/view.php';
    }

    
}