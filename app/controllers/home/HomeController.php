<?php

class HomeController extends Controller
{
    private $notificationModel;
    public function __construct($pdo)
    {
        parent::__construct($pdo);
        $this->auth(); // ทุกหน้าในนี้ต้อง Login
        $this->notificationModel = new Notification($pdo);

    }

    /**
     * แสดงหน้า Dashboard ตาม Role ของผู้ใช้
     */
    public function index()
    {
        $role = $_SESSION['user_role'] ?? 0;
        $userId = $_SESSION['user_id'];
        $page_title = "Dashboard";
        $data = [];
        $viewFile = '';

        if (isset($_SESSION['user_id'])) {
            $userId = $_SESSION['user_id'];
            $data['unreadCount'] = $this->notificationModel->countUnreadForUser($userId);
            $data['unreadNotifications'] = $this->notificationModel->getUnreadForUser($userId, 5);
        } else {
            $data['unreadCount'] = 0;
            $data['unreadNotifications'] = [];
        }



        // ใช้ switch-case เพื่อเลือกการทำงานตาม Role
        switch ($role) {
            case 1: // Admin
                $userModel = new User($this->pdo);
                $customerModel = new Customers($this->pdo);
                $projectModel = new Project($this->pdo);
                $quotationModel = new Quotation($this->pdo);

                $data['stats'] = [
                    'total_users' => $userModel->countAll(),
                    'total_customers' => $customerModel->countAll(),
                    'total_projects' => $projectModel->countAll(),
                    'unassigned_projects' => $projectModel->countUnassigned(),
                    'total_quotations' => $quotationModel->countAll(),
                    'accepted_quotation_value' => $quotationModel->sumOfAccepted(),
                    'total_approved_value' => $quotationModel->sumAllApproved()
                ];

                $data['member_summary'] = $quotationModel->getMemberMonthlySummary();

                $viewFile = 'admin_dashboard.php';
                $scripts[] = '/mcvpro/public/js/admin-dashboard.js';
                break;
            
            case 2: // Staff
                $quotationModel = new Quotation($this->pdo);

                // --- ส่วนตรรกะการจัดการวันที่ ---
                // รับค่าวันที่จาก GET parameter ถ้าไม่มี ให้ใช้เดือนปัจจุบันเป็นค่าเริ่มต้น
                $startDateStr = $_GET['start_date'] ?? date('Y-m-01');
                $endDateStr = $_GET['end_date'] ?? date('Y-m-t');

                // จัดรูปแบบวันที่ให้ถูกต้องสำหรับ SQL Query
                $startDate = date('Y-m-d 00:00:00', strtotime($startDateStr));
                $endDate = date('Y-m-d 23:59:59', strtotime($endDateStr));

                // ดึงข้อมูลสรุปทั้งหมดโดยใช้ method ใหม่
                $memberSummary = $quotationModel->getSummaryByDateRange($startDate, $endDate);

                // --- คำนวณค่าสรุปรวมสำหรับแสดงบนการ์ด ---
                $totalQuotations = 0;
                $totalApprovedValue = 0;
                
                foreach ($memberSummary as $summary) {
                    $totalQuotations += $summary['total_quotations'];
                    $totalApprovedValue += $summary['total_approved_amount'];
                }

                // --- ตรวจสอบว่าเป็น AJAX request หรือไม่ ---
                // ถ้าใช่ ให้ส่งข้อมูลกลับเป็น JSON แล้วจบการทำงาน
                if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'memberSummary' => $memberSummary,
                        'totalQuotations' => $totalQuotations,
                        'totalApprovedValue' => $totalApprovedValue,
                        
                        'totalMembers' => count($memberSummary),
                        'dateRange' => [
                            'start' => date('d M Y', strtotime($startDate)),
                            'end' => date('d M Y', strtotime($endDate))
                        ]
                    ]);
                    exit(); // จบการทำงานของ script ทันที
                }

                // --- ถ้าเป็นการโหลดหน้าปกติ ให้ส่งข้อมูลไปยัง View ---
                $data['member_summary'] = $memberSummary;
                $data['total_quotations'] = $totalQuotations;
                $data['total_approved_value'] = $totalApprovedValue;
                // ส่งค่าวันที่ไปด้วย เพื่อแสดงผลบนฟอร์ม
                $data['initial_start_date'] = $startDateStr;
                $data['initial_end_date'] = $endDateStr;

                $viewFile = 'staff_dashboard.php';
                break;

                
            case 3: // Member
                $quotationModel = new Quotation($this->pdo);

                // --- ส่วนตรรกะการจัดการวันที่ ---
                $startDateStr = $_GET['start_date'] ?? date('Y-m-01');
                $endDateStr = $_GET['end_date'] ?? date('Y-m-t');
                $startDate = date('Y-m-d 00:00:00', strtotime($startDateStr));
                $endDate = date('Y-m-d 23:59:59', strtotime($endDateStr));

                // --- ดึงข้อมูลสถิติโดยส่งค่าวันที่ไปด้วย ---
                $stats = $quotationModel->getMemberDashboardStats($userId, $startDate, $endDate);
                
                // ตั้งค่า Sales Goal (อาจจะดึงมาจาก DB ในอนาคต)
                $salesGoal = 50000;

                // --- ตรวจสอบว่าเป็น AJAX request หรือไม่ ---
                if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'stats' => $stats,
                        'sales_goal' => $salesGoal,
                        'dateRange' => [
                            'start' => date('d M Y', strtotime($startDate)),
                            'end' => date('d M Y', strtotime($endDate))
                        ]
                    ]);
                    exit();
                }

                // --- ถ้าเป็นการโหลดหน้าปกติ ---
                $data['stats'] = $stats;
                $data['sales_goal'] = $salesGoal;
                $data['initial_start_date'] = $startDateStr;
                $data['initial_end_date'] = $endDateStr;

                $viewFile = 'member_dashboard.php';
                break;

            default:
                // กรณีอื่นๆ หรือ Role ที่ไม่มี
                echo "Invalid user role.";
                exit();
        }
        

        // เรียก Layout และ View ที่เลือกไว้
        include ROOT_PATH . 'app/views/layout/sidebar.php';
        if (!empty($viewFile)) {
            include ROOT_PATH . 'app/views/home/' . $viewFile;
        }
    }
}