<?php

class ActivitiesController extends Controller
{
    private $activityModel;
    private $customerModel;
    private $projectModel;
    private $notificationModel;
    private $activityFileModel;

    public function __construct($pdo)
    {
        parent::__construct($pdo);
        $this->auth(); // All pages in this controller require login
        $this->activityModel = new Activity($this->pdo);
        $this->customerModel = new Customers($this->pdo);
        $this->projectModel = new Project($this->pdo);
        $this->notificationModel = new Notification($pdo);
        $this->activityFileModel = new ActivityFile($pdo);
    }

    /**
     * Display a list of all activities.
     */
    public function index()
{
    $page_title = "activities list";
    $data = [];
    $userId = $_SESSION['user_id'] ?? 0;
    $role = $_SESSION['user_role'] ?? 0;
    
    // เตรียมข้อมูลการแจ้งเตือนสำหรับแถบด้านข้าง
    if (isset($_SESSION['user_id'])) {
        $data['unreadCount'] = $this->notificationModel->countUnreadForUser($userId);
        $data['unreadNotifications'] = $this->notificationModel->getUnreadForUser($userId, 5);
    }

    $filters = [
        'search' => trim($_GET['search'] ?? ''),
        'customer_id' => trim($_GET['customer_id'] ?? ''),
        'project_id' => trim($_GET['project_id'] ?? ''),
        'start_date' => trim($_GET['start_date'] ?? ''),
        'end_date' => trim($_GET['end_date'] ?? '')
    ];
    $perPage = 15;
    $currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;

    if (!empty(array_filter($filters))) {
        $totalActivities = $this->activityModel->countFiltered($filters, $userId, $role);
        $activities = $this->activityModel->getFilteredPaginated($filters, $currentPage, $perPage, $userId, $role);
    } else {
        $totalActivities = $this->activityModel->countAllForUser($userId);
        $activities = $this->activityModel->getAllForUserPaginated($userId, $currentPage, $perPage);
    }
    
    $data['activities'] = $activities;
    $data['total_activities'] = $totalActivities;
    $data['per_page'] = $perPage;
    $data['current_page'] = $currentPage;

    include ROOT_PATH . 'app/views/layout/sidebar.php';
    include ROOT_PATH . 'app/views/activities/index.php';
}

    /**
     * Display the form to create a new activity.
     */
   public function create($fromProjectHashedId = null)
    {
        $page_title = "Create New Activity";
        $data = [
            'fromProject' => false,
            'project' => null,
            'customer' => null,
            'customers' => []
        ];
        
        $userId = $_SESSION['user_id'] ?? 0;
        $userRole = $_SESSION['role_id'] ?? null;
        
        if ($fromProjectHashedId) {
            $projectId = decodeId($fromProjectHashedId);
            if ($projectId) {
                $project = $this->projectModel->getById($projectId);
                if ($project) {
                    $customer = $this->customerModel->getById($project['customer_id']);
                    if ($customer) {
                        $data['fromProject'] = true;
                        $data['project'] = $project;
                        $data['customer'] = $customer;
                    }
                }
            }
        }
        
        if (!$data['fromProject']) {
            if (in_array($userRole, [1, 2])) {
                $data['customers'] = $this->customerModel->getAll();
            } else {
                $data['customers'] = $this->customerModel->getAllForUser($userId);
            }
        }

        if (isset($_SESSION['user_id'])) {
            $data['unreadCount'] = $this->notificationModel->countUnreadForUser($userId);
            $data['unreadNotifications'] = $this->notificationModel->getUnreadForUser($userId, 5);
        }
        
        $viewPath = ROOT_PATH . 'app/views/activities/create.php';
        if (!file_exists($viewPath)) {
            die("Error: View file not found at " . htmlspecialchars($viewPath));
        }
        include $viewPath;
    }

    /**
     * API endpoint to fetch projects for a given customer.
     * Includes enhanced debugging information.
     */
    public function getProjectsByCustomerJson($customerId)
    {
        header('Content-Type: application/json');

        // This is a more direct way to get the SQL and params for debugging
        list($sql, $params) = $this->projectModel->getProjectsByCustomerSql($customerId);

        $debugResponse = [
            'debug_info' => [
                'status' => 'API function was reached.',
                'customerId_received' => $customerId,
                'sql_query' => $sql,
                'sql_params' => $params
            ],
            'data' => null,
            'error' => null
        ];

        try {
            $projects = $this->projectModel->getAllByCustomerId($customerId);
            
            if (is_array($projects)) {
                $debugResponse['data'] = $projects;
                $debugResponse['debug_info']['model_status'] = 'Successfully fetched ' . count($projects) . ' projects.';
            } else {
                $debugResponse['error'] = 'Model did not return a valid array.';
            }

        } catch (Exception $e) {
            $debugResponse['error'] = 'An exception occurred: ' . $e->getMessage();
        }

        echo json_encode($debugResponse);
        exit();
    }
    /**
     * Store a new activity in the database.
     */
    public function store()
    {
        $logger = LoggerService::getLogger('activity');

        // Basic validation
        if (empty($_POST['customer_id']) || empty($_POST['project_id'])) {
            $_SESSION['error'] = 'Customer and Project are required.';
            header('Location: /mcvpro/public/activities/create');
            exit();
        }

        // Prepare data for the model
        $data = [
            'customer_id'   => $_POST['customer_id'],
            'project_id'    => $_POST['project_id'],
            'description'   => $_POST['description'] ?? '',
            'created_by'    => $_SESSION['user_id']
        ];
        
        $newActivityId = $this->activityModel->create($data);

        if ($newActivityId) {
            $_SESSION['success'] = 'Activity created successfully!';
            $logger->info('Activity created successfully!', ['activity_id' => $newActivityId, 'action_by_user_id' => $_SESSION['user_id']]);
            header('Location: /mcvpro/public/activities/view/' . encodeId($newActivityId));
            exit();
        } else {
            $_SESSION['error'] = 'Failed to create activity.';
            $logger->error('Failed to create activity.', ['data' => $data, 'user_id' => $_SESSION['user_id']]);
            header('Location: /mcvpro/public/activities/create');
            exit();
        }
    }

    /**
     * Display a specific activity.
     */
    public function view($hashedId)
    {
        $id = decodeId($hashedId);
        if ($id === null) {
            die("Invalid ID.");
        }

        // 1. Get the activity details and related data
        $activity = $this->activityModel->getById($id);

        if (!$activity) {
            http_response_code(404);
            die("404 Activity Not Found.");
        }
        
        $quotations = $this->activityModel->getQuotationsForActivity($id); // You will need to create this method in your model
        $files = $this->activityFileModel->getFilesByActivityId($id);
        // 2. Logic to determine who can edit/delete
        $isCreator = ($_SESSION['user_id'] == $activity['created_by']);
        $isAdminOrStaff = in_array($_SESSION['user_role'], [1, 2]);
        $canEditOrDelete = ($isCreator || $isAdminOrStaff);
        
        // 3. Pass all necessary data to the view
        $data = [
            'activity' => $activity,
            'quotations' => $quotations,
            'files' => $files,
            'canEditOrDelete' => $canEditOrDelete
        ];
        
        $page_title = 'View Activity';
        include ROOT_PATH . 'app/views/activities/view.php';
    }

    public function updateDescription()
    {
        header('Content-Type: application/json');

        // ตรวจสอบว่าเป็น POST request หรือไม่
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
            exit();
        }

        // รับข้อมูลจาก POST body
        $activityId = $_POST['activity_id'] ?? null;
        $description = $_POST['description'] ?? '';

        if (empty($activityId)) {
            echo json_encode(['success' => false, 'message' => 'Activity ID is missing.']);
            exit();
        }

        // เรียกใช้ Model เพื่ออัปเดตข้อมูล
        $success = $this->activityModel->updateDescription((int)$activityId, $description);

        if ($success) {
            echo json_encode(['success' => true, 'message' => 'Description updated successfully.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update description.']);
        }
        exit();
    }
    public function getFilteredQuotations($activityId, $status = 'all')
    {
        header('Content-Type: application/json');

        $quotations = $this->activityModel->getQuotationsForActivity((int)$activityId, $status);

        // Encode IDs before sending to the client
        $encodedQuotations = array_map(function($q) {
            $q['encoded_id'] = encodeId($q['quotation_id']);
            return $q;
        }, $quotations);

        echo json_encode($encodedQuotations);
        exit();
    }
    public function uploadFile()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['activityFile'])) {
            $activityId = $_POST['activity_id'] ?? 0;
            $file = $_FILES['activityFile'];

            if ($file['error'] !== UPLOAD_ERR_OK || $activityId == 0) {
                // Handle error
                header('Location: /mcvpro/public/activities/view/' . encodeId($activityId));
                exit();
            }

            $allowedMimeType = 'application/pdf';
            // ใช้ mime_content_type() เพื่อการตรวจสอบที่แม่นยำและปลอดภัย
            $fileMimeType = mime_content_type($file['tmp_name']); 

            if ($fileMimeType !== $allowedMimeType) {
                $_SESSION['error'] = 'Only PDF files are allowed. Please upload a valid PDF document.';
                header('Location: /mcvpro/public/activities/view/' . encodeId($activityId));
                exit();
            }

            $uploadDir = 'uploads/activity_files/';
            if (!is_dir(ROOT_PATH . 'public/' . $uploadDir)) {
                mkdir(ROOT_PATH . 'public/' . $uploadDir, 0777, true);
            }

            $originalFilename = basename($file['name']);
            $storedFilename = uniqid() . '-' . $originalFilename;
            $filePath = $uploadDir . $storedFilename;
            $destination = ROOT_PATH . 'public/' . $filePath;

            if (move_uploaded_file($file['tmp_name'], $destination)) {
                $data = [
                    'activity_id' => $activityId,
                    'original_filename' => $originalFilename,
                    'stored_filename' => $storedFilename,
                    'file_path' => $filePath,
                    'uploaded_by' => $_SESSION['user_id']
                ];
                $this->activityFileModel->create($data);
            }

            header('Location: /mcvpro/public/activities/view/' . encodeId($activityId));
            exit();
        }
    }

    /**
     * Deletes a file.
     */
    public function deleteFile($hashedFileId)
    {
        $fileId = decodeId($hashedFileId);
        $file = $this->activityFileModel->getById($fileId);
        if ($file) {
            $filePath = ROOT_PATH . 'public/' . $file['file_path'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }
            $this->activityFileModel->delete($fileId);
        }
        
        header('Location: /mcvpro/public/activities/view/' . encodeId($file['activity_id']));
        exit();
    }
    public function delete($hashedId)
    {
        // 1. Decode the ID
        $id = decodeId($hashedId);
        
        if ($id === null) {
            // If the ID is invalid, redirect back with an error
            $_SESSION['error'] = 'Invalid Activity ID.';
            header('Location: /mcvpro/public/activities');
            exit();
        }

        // 2. (สำคัญ) ตรวจสอบสิทธิ์ก่อนทำการลบ
        // ดึงข้อมูล Activity เพื่อดูว่าใครเป็นเจ้าของ
        $activity = $this->activityModel->getById($id);
        
        if (!$activity) {
            $_SESSION['error'] = 'Activity not found.';
            header('Location: /mcvpro/public/activities');
            exit();
        }

        $isCreator = ($_SESSION['user_id'] == $activity['created_by']);
        $isAdminOrStaff = in_array($_SESSION['role_id'], [1, 2]); // ตรวจสอบว่าใช้ 'role_id' ถูกต้อง

        // ถ้าไม่ใช่เจ้าของ และไม่ใช่ Admin/Staff, จะไม่มีสิทธิ์ลบ
        if (!$isCreator && !$isAdminOrStaff) {
            $_SESSION['error'] = 'You do not have permission to delete this activity.';
            header('Location: /mcvpro/public/activities');
            exit();
        }

        // 3. เรียกใช้ Model เพื่อลบข้อมูล
        // หมายเหตุ: คุณจะต้องสร้างฟังก์ชัน delete() ใน ActivityModel ของคุณด้วย
        $success = $this->activityModel->delete($id);

        // 4. ตั้งค่าข้อความแจ้งเตือนและ Redirect กลับไป
        if ($success) {
            $_SESSION['success'] = 'Activity deleted successfully!';
        } else {
            $_SESSION['error'] = 'Failed to delete activity.';
        }

        header('Location: /mcvpro/public/activities');
        exit();
    }
}