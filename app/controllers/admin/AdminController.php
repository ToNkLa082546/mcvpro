<?php
require_once ROOT_PATH . 'core/Controller.php';

class AdminController extends Controller
{
    public function __construct($pdo)
    {
        parent::__construct($pdo);
        $this->auth();
        $this->isAllowed([1]); // อนุญาตเฉพาะ Admin
        
    }

    /**
     * แสดงหน้า Dashboard หลัก (เรียก Layout และ View)
     */
    public function index()
    {
        $page_title = "Admin Dashboard";
        // ✅ บอกให้ Layout โหลดไฟล์ JS สำหรับหน้านี้โดยเฉพาะ
        $scripts = ['/mcvpro/public/js/admin-dashboard.js']; 

        include ROOT_PATH . 'app/views/layout/sidebar.php';
        include ROOT_PATH . 'app/views/home/admin_dashboard.php';
    }

    /**
     * ✅ API Endpoint สำหรับส่งข้อมูล Dashboard เป็น JSON
     */
    public function stats()
    {
        header('Content-Type: application/json');

        try {
            $userModel = new User($this->pdo);
            $customerModel = new Customers($this->pdo);
            $projectModel = new Project($this->pdo);

            // ดึงข้อมูลสำหรับ Stat cards
            $stats = [
                'total_users' => $userModel->countAll(),
                'total_customers' => $customerModel->countAll(),
                'total_projects' => $projectModel->countAll(),
                'unassigned_projects' => $projectModel->countUnassigned()
            ];

            // ดึงข้อมูลสำหรับกราฟ
            $projectStatusData = $projectModel->getProjectCountByStatus();
            $labels = [];
            $counts = [];
            foreach ($projectStatusData as $row) {
                $labels[] = $row['status'];
                $counts[] = $row['project_count'];
            }
            
            // รวมข้อมูลทั้งหมดแล้วส่งกลับ
            echo json_encode([
                'success' => true, 
                'stats' => $stats,
                'chart' => [
                    'labels' => $labels,
                    'counts' => $counts
                ]
            ]);

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit();
    }
}