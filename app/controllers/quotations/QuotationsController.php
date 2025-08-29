<?php

use NumberToWords\NumberToWords;
class QuotationsController extends Controller
{
    private $projectModel;
    private $quotationModel;
    private $itemModel;
    private $notificationModel;
    

    public function __construct($pdo)
    {
        parent::__construct($pdo);
        $this->auth();
        $this->projectModel = new Project($this->pdo);
        $this->quotationModel = new Quotation($this->pdo);
        $this->itemModel = new QuotationItem($this->pdo);
        $this->notificationModel = new Notification($pdo);
    }

    
   public function index()
{
    $role = $_SESSION['user_role'] ?? 0;
    $userId = $_SESSION['user_id'] ?? 0;
    $page_title = "Quotations";
    $data = []; 

    // ... (ส่วนของ Notification เหมือนเดิม) ...
    if (isset($_SESSION['user_id'])) {
        $data['unreadCount'] = $this->notificationModel->countUnreadForUser($userId);
        $data['unreadNotifications'] = $this->notificationModel->getUnreadForUser($userId, 5);
    }

    // --- รับค่าฟิลเตอร์ทั้งหมดจาก URL ---
    $filters = [
        'search'     => trim($_GET['search'] ?? ''),
        'status'     => trim($_GET['status'] ?? ''),
        'start_date' => trim($_GET['start_date'] ?? ''),
        'end_date'   => trim($_GET['end_date'] ?? '')
    ];

    $perPage = 15;
    $currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    if ($currentPage < 1) $currentPage = 1;

    // --- ตรวจสอบว่ามีการใช้ฟิลเตอร์หรือไม่ ---
    if (!empty(array_filter($filters))) {
        // ถ้ามีฟิลเตอร์
        $totalQuotations = $this->quotationModel->countFiltered($filters, $role, $userId);
        $quotations = $this->quotationModel->getFilteredPaginated($filters, $currentPage, $perPage, $role, $userId);
    } else {
        // ถ้าไม่มีฟิลเตอร์ (โค้ดเดิมของคุณถูกต้องแล้ว)
        if (in_array($role, [1, 2])) {
            $totalQuotations = $this->quotationModel->countAll();
            $quotations = $this->quotationModel->getAllPaginated($currentPage, $perPage);
        } else {
            $totalQuotations = $this->quotationModel->countForUser($userId);
            $quotations = $this->quotationModel->getAllForUserPaginated($userId, $currentPage, $perPage);
        }
    }

    // --- ส่งข้อมูลทั้งหมดไปให้ View ---
    $data['quotations'] = $quotations;
    $data['total_quotations'] = $totalQuotations;
    $data['per_page'] = $perPage;
    $data['current_page'] = $currentPage;
    $data['filters'] = $filters; // <<< เพิ่มส่วนนี้ เพื่อส่งค่าฟิลเตอร์กลับไปให้ View

    include ROOT_PATH . 'app/views/layout/sidebar.php';
    include ROOT_PATH . 'app/views/quotations/index.php';
}

    public function create($hashedProjectId = null)
{
    $this->isAllowed([1, 2, 3]);

    if (isset($_SESSION['duplicate_data'])) {
        
        // 1. ดึงข้อมูลที่ถูกส่งมาเก็บไว้ในตัวแปร
        $duplicateData = $_SESSION['duplicate_data'];
        

        // 3. เตรียมข้อมูลทั้งหมดเพื่อส่งไปให้ View
       $data = [
            'from_project' => true,
            'customer'     => $duplicateData['quotation'],
            'project'      => $duplicateData['quotation'], 
            'page_title'   => "Create New Quotation (from " . htmlspecialchars($duplicateData['quotation']['quotation_number']) . ")",
            'prefill_data' => $duplicateData 
        ];


        // ตอนนี้ View จะมองเห็นตัวแปร $scripts แล้ว
        include ROOT_PATH . 'app/views/quotations/create.php';
        return; 
    }
    $projectId = null;
    // 1. Decode ID ที่เข้ามาจาก URL เพียงครั้งเดียว
    if ($hashedProjectId) {
        $projectId = decodeId($hashedProjectId);
        if ($projectId === null) {
            die("Invalid Project ID.");
        }
    }

    $customerModel = new Customers($this->pdo);
    $projectModel = new Project($this->pdo);

    // 2. กำหนดค่าเริ่มต้นให้ $data
    $data = [
        'customers' => [],
        'projects' => [],
        'from_project' => false,
        'customer' => null,
        'project' => null,
        'page_title' => "Create New Quotation (Step 1 of 2)" // Title เริ่มต้น
    ];

    // จัดการข้อมูลที่มาจากการ Duplicate (ถ้ามี)
    if (isset($_SESSION['duplicate_data'])) {
        $data['prefill_data'] = $_SESSION['duplicate_data'];
        unset($_SESSION['duplicate_data']);
    }

    // 3. ตรวจสอบว่าเป็นเคสที่สร้างจากโปรเจกต์ (Step 2) หรือสร้างใหม่ (Step 1)
    if ($projectId !== null) {
        // --- ส่วนของการสร้างจากโปรเจกต์ (Step 2) ---
        $project = $projectModel->getById($projectId);
        if (!$project) die("Project not found.");

        $customer = $customerModel->getById($project['customer_id']);
        if (!$customer) die("Customer not found for this project.");

        $data['project'] = $project;
        $data['customer'] = $customer;
        $data['from_project'] = true; // ตั้งค่าให้ View แสดงผลแบบ Step 2
        $data['page_title'] = "New Quotation for " . htmlspecialchars($project['project_name']);

    } else {
        // --- ส่วนของการสร้างใหม่ (Step 1) ---
        $userId = $_SESSION['user_id'] ?? 0;
        $data['customers'] = $customerModel->getAllForUser($userId);

        // 👇 --- จุดแก้ไขหลัก --- 👇
        // ดึงโปรเจกต์ทั้งหมด แล้วแปลง ID เป็น Hashid ก่อนส่งไปให้ View
        $allProjects = $projectModel->getAll();
        $projectsWithHashedId = [];
        foreach ($allProjects as $proj) {
            $proj['hashed_id'] = encodeId($proj['project_id']);
            $projectsWithHashedId[] = $proj;
        }
        $data['projects'] = $projectsWithHashedId;
    }

    $scripts = ['/mcvpro/public/js/quotation.js'];

    include ROOT_PATH . 'app/views/quotations/create.php';
}




    /**
     * บันทึกใบเสนอราคาใหม่
     */
    public function store()
{
    if (empty($_POST['items']) || !is_array($_POST['items']['item_name'])) {
        $_SESSION['error'] = "Please add at least one item before saving.";
                // ส่งผู้ใช้กลับไปที่หน้าเดิมที่พวกเขากรอกฟอร์ม
        header('Location: ' . $_SERVER['HTTP_REFERER']);
        exit();
        }
    
    if (
        empty($_POST['project_id']) || 
        empty($_POST['items']) || 
        !is_array($_POST['items']['item_name']) || 
        empty($_POST['items']['item_name'][0])
    ) {
        $_SESSION['error'] = "Invalid data submitted. Please add at least one item.";
        header('Location: /mcvpro/public/quotations/create');
        exit();
    }

    

    $this->pdo->beginTransaction();
    try {
        $inputItems = $_POST['items'];
        $itemCount = count($inputItems['item_name']);
        $subTotal = 0;
        $vatRate = 7;
        
        $mainItems = [];
        $subItems = [];

        for ($i = 0; $i < $itemCount; $i++) {
            $cost = (float) $inputItems['cost'][$i];
            $margin = (float) $inputItems['margin'][$i];
            $quantity = (int) $inputItems['quantity'][$i];
            $itemTotal = $cost * (1 + ($margin / 100)) * $quantity;
            $subTotal += $itemTotal;

            $itemData = [
                'item_name'   => $inputItems['item_name'][$i],
                'description' => $inputItems['description'][$i] ?? '',
                'cost'        => $cost,
                'margin'      => $margin,
                'quantity'    => $quantity,
                'total'       => $itemTotal,
                'type'        => $inputItems['type'][$i] ?? 'show'
            ];
            
            // แยก Main กับ Sub ออกจากกัน
            if ($inputItems['is_subitem'][$i] == "1") {
                $itemData['parent_temp_id'] = $inputItems['parent_temp_id'][$i];
                $subItems[] = $itemData;
            } else {
                $itemData['temp_id'] = $inputItems['temp_id'][$i];
                $mainItems[] = $itemData;
            }
        }

        $vatAmount = $subTotal * ($vatRate / 100);
        $grandTotal = $subTotal + $vatAmount;
        
        // 2. สร้างใบเสนอราคาหลัก
        $quotationData = [
            'quotation_number' => 'QT-' . time(),
            'project_id'     => $_POST['project_id'],
            'customer_id'    => $_POST['customer_id'],
            'created_by'     => $_SESSION['user_id'],
            'valid_until'    => !empty($_POST['valid_until']) ? $_POST['valid_until'] : null,
            'notes'          => $_POST['notes'] ?? '',
            'sub_total'      => $subTotal,
            'vat_amount'     => $vatAmount,
            'grand_total'    => $grandTotal
        ];
        $quotationId = $this->quotationModel->createWithTotals($quotationData);
        if (!$quotationId) throw new Exception("Failed to create quotation header.");

        $tempIdToDbIdMap = [];

        // 3. รอบที่ 1: บันทึก Main Items
        foreach ($mainItems as $mainItem) {
            $mainItem['quotation_id'] = $quotationId;
            $tempId = $mainItem['temp_id'];
            unset($mainItem['temp_id']);
            
            $dbId = $this->itemModel->createAndGetId($mainItem);
            if (!$dbId) throw new Exception("Failed to create main item: " . $mainItem['item_name']);
            
            $tempIdToDbIdMap[$tempId] = $dbId;
        }

        // 4. รอบที่ 2: บันทึก Sub-items
        foreach ($subItems as $subItem) {
            $subItem['quotation_id'] = $quotationId;
            $parentTempId = $subItem['parent_temp_id'];

            if (isset($tempIdToDbIdMap[$parentTempId])) {
                $subItem['parent_item_id'] = $tempIdToDbIdMap[$parentTempId];
            } else {
                // กรณีหา parent ไม่เจอ อาจจะข้ามไปหรือโยน error
                continue; 
            }
            
            unset($subItem['parent_temp_id']);
            if (!$this->itemModel->create($subItem)) { // ใช้ create() ธรรมดาได้
                throw new Exception("Failed to create sub item: " . $subItem['item_name']);
            }
        }
        
        $this->pdo->commit();
        $_SESSION['success'] = "Quotation created successfully!";
        header('Location: /mcvpro/public/quotations/view/' . encodeId($quotationId));

    } catch (Exception $e) {
        $this->pdo->rollBack();
        $_SESSION['error'] = "Error: " . $e->getMessage();
        $redirectUrl = $_POST['project_id'] ? '/mcvpro/public/quotations/create/' . $_POST['project_id'] : '/mcvpro/public/quotations/create';
        header('Location: ' . $redirectUrl);
    }
    exit();
}



    public function view($hashedId): void
    {
        $id = decodeId($hashedId);
        if ($id === null) {
            die("Invalid ID.");
        }
        $quotation = $this->quotationModel->getById($id);
        $data = [];
        if (isset($_SESSION['user_id'])) {
            $userId = $_SESSION['user_id'];
            // ต้องมั่นใจว่า $this->notificationModel ถูกสร้างใน __construct() แล้ว
            $data['unreadCount'] = $this->notificationModel->countUnreadForUser($userId);
            $data['unreadNotifications'] = $this->notificationModel->getUnreadForUser($userId, 5);
        } else {
            $data['unreadCount'] = 0;
            $data['unreadNotifications'] = [];
        }
        if (!$quotation) {
            die("Quotation not found.");
        }

        $userRole = $_SESSION['user_role'] ?? null;
        $userId = $_SESSION['user_id'] ?? null;
        $project = $this->projectModel->getById($quotation['project_id']);

        $isAllowed =
            in_array($userRole, [1, 2]) ||
            $quotation['created_by'] == $userId ||
            ($project && $project['created_by'] == $userId);

        if (!$isAllowed) {
            http_response_code(403);
            die("<h1>403 Forbidden</h1><p>You do not have permission to view this quotation.</p>");
        }

        $items = $this->itemModel->getAllForQuotation($id);

        $groupedItems = [];
        $itemMap = [];
        foreach ($items as $item) {
            $itemMap[$item['item_id']] = $item;
        }
        foreach ($itemMap as $itemId => &$item) {
            if (isset($item['parent_item_id']) && isset($itemMap[$item['parent_item_id']])) {
                $itemMap[$item['parent_item_id']]['children'][] = &$item;
            } else {
                $groupedItems[] = &$item;
            }
        }
        unset($item);

        foreach ($groupedItems as &$mainItem) {
            if (!empty($mainItem['children'])) {
                foreach ($mainItem['children'] as $subItem) {
                    $mainItem['total'] += $subItem['total'];
                }
            }
        }
        unset($mainItem);

        $grandTotalInWords = '';
        if (class_exists('NumberToWords\NumberToWords')) {
            $numberToWords = new NumberToWords();
            $thaiTransformer = $numberToWords->getNumberTransformer('en');
            $grandTotalInWords = $thaiTransformer->toWords($quotation['grand_total']);
        }
        
        //... ดึงข้อมูล notification ใส่ $data แล้ว ...
        $data['quotation'] = $quotation;
        $data['items'] = $groupedItems;
        $data['grand_total_words'] = $grandTotalInWords;

        $page_title = "Quotation: " . $quotation['quotation_number'];
        include ROOT_PATH . 'app/views/quotations/view.php';
    }

     /**
     * แสดงฟอร์มแก้ไขใบเสนอราคา
     */
    public function edit($hashedId)
    {

        $id = decodeId($hashedId);
        if ($id === null) {
            die("Invalid ID.");
        }
        $this->isAllowed([1, 2]);

        $data['quotation'] = $this->quotationModel->getById($id);
        if (!$data['quotation']) { die("Quotation not found."); }

         if ($data['quotation']['status'] === 'Approved') {
        // ตั้งค่าข้อความแจ้งเตือน
        $_SESSION['error'] = 'This quotation has been approved and cannot be edited.';
        
        // ส่งผู้ใช้กลับไปที่หน้า view
        header('Location: /mcvpro/public/quotations/view/' . encodeId($id));
        exit(); // หยุดการทำงานของสคริปต์ทันที
        }

        // ดึงและจัดกลุ่ม Items ให้เป็นลำดับชั้น
        $items = $this->itemModel->getAllForQuotation($id);
        $groupedItems = [];
        $itemMap = [];
        foreach ($items as $item) {
            $itemMap[$item['item_id']] = $item;
        }
        foreach ($itemMap as $itemId => &$item) {
            if (isset($item['parent_item_id']) && isset($itemMap[$item['parent_item_id']])) {
                $itemMap[$item['parent_item_id']]['children'][] = &$itemMap[$item['item_id']];
            } else {
                $groupedItems[] = &$itemMap[$item['item_id']];
            }
        }
        unset($item);

        // ส่งข้อมูลที่จัดกลุ่มแล้วไปให้ View
        $data['items'] = $groupedItems; 

        $page_title = "Edit Quotation: " . $data['quotation']['quotation_number'];
        
        // ✅ ส่งข้อมูลทั้งหมดไปให้ View เพื่อให้ JS นำไปใช้ต่อ
        $data_for_js = json_encode($data);

        // เรียกใช้ View

        include ROOT_PATH . 'app/views/quotations/edit.php';
    }

    /**
     * สร้างใบเสนอราคาฉบับใหม่จากการแก้ไข
     */
    public function revise($hashedOriginalId)
    {
    $originalQuotationId = decodeId($hashedOriginalId);
    if ($originalQuotationId === null) {
        die("Invalid Original ID.");
    }

        $this->isAllowed([1, 2]);

        if (empty($_POST['project_id']) || empty($_POST['items']['item_name'])) {
            $_SESSION['error'] = "Invalid data submitted.";
            header('Location: /mcvpro/public/quotations/edit/' . encodeId($originalQuotationId));
            exit();
        }

        $this->pdo->beginTransaction();
        try {
            // ส่วนประมวลผล Items เหมือนเดิม ไม่ต้องแก้ไข
            $inputItems = $_POST['items'];
            $itemCount = count($inputItems['item_name']);
            $subTotal = 0;
            $vatRate = 7;
            $mainItems = [];
            $subItems = [];

            for ($i = 0; $i < $itemCount; $i++) {
                $cost = (float)($inputItems['cost'][$i] ?? 0);
                $margin = (float)($inputItems['margin'][$i] ?? 0);
                $quantity = (int)($inputItems['quantity'][$i] ?? 1);
                $itemTotal = $cost * (1 + ($margin / 100)) * $quantity;
                $subTotal += $itemTotal;
                $itemData = [
                    'item_name'   => $inputItems['item_name'][$i],
                    'description' => $inputItems['description'][$i] ?? '',
                    'cost'        => $cost,
                    'margin'      => $margin,
                    'quantity'    => $quantity,
                    'total'       => $itemTotal,
                    'type'        => $inputItems['type'][$i] ?? 'show' // อย่าลืมบันทึก Type ด้วย
                ];
                if (($inputItems['is_subitem'][$i] ?? '0') == "1") {
                    $itemData['parent_temp_id'] = $inputItems['parent_temp_id'][$i];
                    $subItems[] = $itemData;
                } else {
                    $itemData['temp_id'] = $inputItems['temp_id'][$i];
                    $mainItems[] = $itemData;
                }
            }
            $vatAmount = $subTotal * ($vatRate / 100);
            $grandTotal = $subTotal + $vatAmount;


            // 2. สร้างเลขที่ใบเสนอราคาใหม่ (New Revision Number Logic)
            $old_quotation_number = $_POST['quotation_number'];
            // 2.1 ตัดส่วน -REV-n หรือ -REV เดิมออก เพื่อหาเลขที่ตั้งต้น
            $base_number = preg_replace('/-REV(-\d+)?$/', '', $old_quotation_number);
            
            // 2.2 ไปถาม Model ว่าเลขที่นี้มี revision ล่าสุดเป็นเลขอะไร
            $latest_rev_num = $this->quotationModel->getLatestRevisionFor($base_number);
            
            // 2.3 สร้างเลข revision ใหม่ (+1)
            $new_rev_num = $latest_rev_num + 1;
            $new_quotation_number = $base_number . '-REV-' . $new_rev_num;




            // 3. สร้างใบเสนอราคา "ใบใหม่"
            $quotationData = [
                'quotation_number' => $new_quotation_number, // <-- ใช้เลขที่ใหม่ที่สร้างขึ้น
                'project_id'       => $_POST['project_id'],
                'customer_id'      => $_POST['customer_id'],
                'created_by'       => $_SESSION['user_id'],
                'valid_until'      => !empty($_POST['valid_until']) ? $_POST['valid_until'] : null,
                'notes'            => $_POST['notes'] ?? '',
                'status'           => $_POST['status'] ?? 'Draft',
                'sub_total'        => $subTotal,
                'vat_amount'       => $vatAmount,
                'grand_total'      => $grandTotal
            ];
            $newQuotationId = $this->quotationModel->createWithTotals($quotationData);
            if (!$newQuotationId) throw new Exception("Failed to create new revision.");

            // ส่วนบันทึก items และ commit/rollback เหมือนเดิม ไม่ต้องแก้ไข
            $tempIdToDbIdMap = [];
            foreach ($mainItems as $mainItem) {
                $mainItem['quotation_id'] = $newQuotationId;
                $tempId = $mainItem['temp_id'];
                unset($mainItem['temp_id']);
                $dbId = $this->itemModel->createAndGetId($mainItem);
                if (!$dbId) throw new Exception("Failed to create main item: " . $mainItem['item_name']);
                $tempIdToDbIdMap[$tempId] = $dbId;
            }
            foreach ($subItems as $subItem) {
                $subItem['quotation_id'] = $newQuotationId;
                $parentTempId = $subItem['parent_temp_id'];
                if (isset($tempIdToDbIdMap[$parentTempId])) {
                    $subItem['parent_item_id'] = $tempIdToDbIdMap[$parentTempId];
                }
                unset($subItem['parent_temp_id']);
                $this->itemModel->create($subItem);
            }

            $this->quotationModel->updateStatus($originalQuotationId, 'Revised');
            $this->pdo->commit();
            $_SESSION['success'] = "New revision created successfully!";
            header('Location: /mcvpro/public/quotations/view/' . encodeId( $newQuotationId));

        } catch (Exception $e) {
            $this->pdo->rollBack();
            $_SESSION['error'] = "Error creating revision: " . $e->getMessage();
            header('Location: /mcvpro/public/quotations/edit/' . encodeId($originalQuotationId));
        }
        exit();
    }


    public function downloadPdf($hashedId)
    {
        $id = decodeId($hashedId);
        if ($id === null) die("Invalid ID.");
        // 1. ดึงข้อมูลที่จำเป็น
        $quotation = $this->quotationModel->getById((int)$id);
        if (!$quotation) {
            die("Quotation not found.");
        }
        
        // คุณสามารถเพิ่มการตรวจสอบสิทธิ์ได้ที่นี่
        // $this->isAllowed([...]);

        $itemsData = $this->itemModel->getAllForQuotation((int)$id);

        // 2. จัดการข้อมูล (เหมือนในไฟล์ pdf เดิม)
        // จัดกลุ่ม, คำนวณ, แปลงเป็นตัวอักษร ฯลฯ
        $groupedItems = [];
        $itemMap = [];
        foreach ($itemsData as $item) {
            $itemMap[$item['item_id']] = $item;
        }
        foreach ($itemMap as &$item) {
            if (isset($item['parent_item_id']) && isset($itemMap[$item['parent_item_id']])) {
                $itemMap[$item['parent_item_id']]['children'][] = &$item;
            } else {
                $groupedItems[] = &$item;
            }
        }
        unset($item);

        $numberToWords = new NumberToWords();
        $grandTotalInWords = $numberToWords->getNumberTransformer('en')->toWords($quotation['grand_total']);
        
        $data = [
            'quotation' => $quotation, 
            'items' => $groupedItems,
            'grand_total_words' => $grandTotalInWords
        ];

        // 3. สร้าง PDF ด้วย Mpdf
        try {
            ob_start();
            include ROOT_PATH . 'app/views/quotations/pdf_template.php'; 
            $html = ob_get_clean();

            $mpdf = new \Mpdf\Mpdf([
                'default_font' => 'thsarabun'
            ]);
            $mpdf->WriteHTML($html);
            $mpdf->Output("quotation-{$quotation['quotation_number']}.pdf", \Mpdf\Output\Destination::INLINE);
            exit;

        } catch (\Mpdf\MpdfException $e) {
            die("Mpdf error: " . $e->getMessage());
        }
    }

public function delete($hashedId)
{
     $id = decodeId($hashedId);
        if ($id === null) die("Invalid ID.");
    // 1. ตรวจสอบสิทธิ์ (เฉพาะ role 1 และ 2 ที่ลบได้)
    $this->isAllowed([1, 2]);

    try {
        // 2. สั่งให้ Model ทำการลบข้อมูล
        $success = $this->quotationModel->deleteById((int)$id);

        if ($success) {
            // 3. ถ้าสำเร็จ ให้ตั้งค่า session แล้วส่งกลับไปหน้าหลัก
            $_SESSION['success'] = "Quotation deleted successfully.";
        } else {
            $_SESSION['error'] = "Failed to delete the quotation.";
        }
    } catch (Exception $e) {
        // จัดการ Error ที่อาจเกิดขึ้น
        $_SESSION['error'] = "An error occurred: " . $e->getMessage();
    }

    // 4. ส่งผู้ใช้กลับไปที่หน้ารายการใบเสนอราคา
    header('Location: /mcvpro/public/quotations');
    exit();
}


public function requestApproval($hashedId)
{
     $id = decodeId($hashedId);
        if ($id === null) die("Invalid ID.");
    // 1. ดึงข้อมูลและตรวจสอบสิทธิ์/Cooldown (เหมือนเดิม)
    $quotation = $this->quotationModel->getById($id);
    if (!$quotation) {
        $_SESSION['error'] = 'Quotation not found.';
        header('Location: /mcvpro/public/quotations');
        exit();
    }
    if ($quotation['created_by'] != $_SESSION['user_id'] && !in_array($_SESSION['user_role'], [1])) {
        $_SESSION['error'] = "You do not have permission to perform this action.";
        header('Location: /mcvpro/public/quotations');
        exit();
    }
    if (!empty($quotation['last_sent_at'])) {
        $lastSentTime = new DateTime($quotation['last_sent_at']);
        $now = new DateTime();
        $secondsPassed = $now->getTimestamp() - $lastSentTime->getTimestamp();
        $cooldownSeconds = 180; 

        if ($secondsPassed < $cooldownSeconds) {
            $minutesRemaining = ceil(($cooldownSeconds - $secondsPassed) / 60);
            $_SESSION['error'] = "Please wait {$minutesRemaining} more minute(s) before sending again.";
            header('Location: /mcvpro/public/quotations/view/' . encodeId($id));
            exit();
        }
    }

    // 2. ถ้าผ่านหมดทุกอย่าง ให้อัปเดตสถานะ
    if ($this->quotationModel->updateStatus($id, 'Pending Approval')) {
        
        // บันทึกเวลาที่กดส่ง
        $this->quotationModel->updateSentTimestamp($id);

        try {
            $userModel = new User($this->pdo);
            $staffUsers = $userModel->getUsersByRole([1, 2]); // Role 1=Admin, 2=Staff

            if (!empty($staffUsers)) {
                $notificationModel = new Notification($this->pdo);
                $creator = $userModel->findById($_SESSION['user_id']);
                $creatorName = $creator['fullname'] ?? 'A member';
                
                $message = "{$creatorName} sent quote #{$quotation['quotation_number']} for approval.";
                $link = "/quotations/view/" . $id;

                foreach ($staffUsers as $staff) {
                    $notificationModel->create($staff['Id_user'], $message, $link);
                }
            }
        } catch (Exception $e) {
            error_log("Notification failed to send: " . $e->getMessage());
        }
        // --- จบส่วนแจ้งเตือน ---

        $_SESSION['success'] = "Quotation sent for approval successfully.";
    } else {
        $_SESSION['error'] = "Failed to request approval.";
    }

    header('Location: /mcvpro/public/quotations/view/' . encodeId($id));
    exit();
}

public function cancelRequest($hashedId)
    {
         $id = decodeId($hashedId);
        if ($id === null) die("Invalid ID.");
        // 1. ดึงข้อมูลใบเสนอราคาเพื่อตรวจสอบ
        $quotation = $this->quotationModel->getById($id);
        if (!$quotation) {
            $_SESSION['error'] = 'Quotation not found.';
            header('Location: /mcvpro/public/quotations');
            exit();
        }

        // 2. ตรวจสอบสิทธิ์ (ต้องเป็นเจ้าของเอกสารเท่านั้น)
        $userId = $_SESSION['user_id'];
        if ($quotation['created_by'] != $userId) {
            $_SESSION['error'] = 'You do not have permission to perform this action.';
            header('Location: /mcvpro/public/quotations/view/' . encodeId($id));
            exit();
        }

        // 3. ตรวจสอบสถานะ (ต้องเป็น 'Pending Approval' เท่านั้น)
        if ($quotation['status'] !== 'Pending Approval') {
            $_SESSION['error'] = 'This request cannot be canceled because it is not pending approval.';
            header('Location: /mcvpro/public/quotations/view/' . encodeId($id));
            exit();
        }

        // 4. อัปเดตสถานะกลับเป็น 'Revised'
        if ($this->quotationModel->updateStatus($id, 'Revised')) {
            
            // 5. (แนะนำ) ลบ Notification ที่เคยส่งหา Admin/Staff
            $this->quotationModel->updateSentTimestamp($id);
            try {
                // สร้างลิงก์ที่ตรงกับ Notification ที่ต้องการลบ
                $approvalLink = "/quotations/view/" . $id; 
                $this->notificationModel->deleteByLink($approvalLink);
            } catch (Exception $e) {
                error_log("Failed to delete approval notification on cancellation: " . $e->getMessage());
            }

            $_SESSION['success'] = 'Approval request has been canceled.';
        } else {
            $_SESSION['error'] = 'Failed to cancel the request.';
        }

        header('Location: /mcvpro/public/quotations/view/' . encodeId($id));
        exit();
    }


public function approve($hashedId)
    {
         $id = decodeId($hashedId);
        if ($id === null) die("Invalid ID.");
        $this->isAllowed([1, 2]); // อนุญาตเฉพาะ Admin และ Staff

        if ($this->quotationModel->updateStatus($id, 'Approved')) {

            try {
                $quotation = $this->quotationModel->getById($id);
                if ($quotation) {
                    $recipientId = $quotation['created_by'];
                    $approverId = $_SESSION['user_id'];

                    // ไม่สร้างการแจ้งเตือนถ้าคนกดยืนยันคือคนเดียวกับคนสร้าง
                    if ($recipientId != $approverId) {
                        $userModel = new User($this->pdo);
                        $approver = $userModel->findById($approverId);
                        $approverName = $approver ? $approver['fullname'] : 'Staff';

                        $message = "✅ Your quote #{$quotation['quotation_number']} has been approved.";
                        $link = "/quotations/view/" . $id;

                        $this->notificationModel->create($recipientId, $message, $link);
                    }
                }
            } catch (Exception $e) {
                error_log("Return notification failed to send on approval: " . $e->getMessage());
            }
            // ==========================================================
            
            $_SESSION['success'] = "Quotation approved successfully.";

        } else {
            $_SESSION['error'] = "Failed to approve quotation.";
        }

        header('Location: /mcvpro/public/quotations/view/' . encodeId($id));
        exit();
    }


public function reject($hashedId)
    {
         $id = decodeId($hashedId);
        if ($id === null) die("Invalid ID.");
        $this->isAllowed([1, 2]);

        // ตรวจสอบ Input จาก Form ก่อน
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['rejection_reason'])) {
            $_SESSION['error'] = 'A reason for rejection is required.';
            header('Location: /mcvpro/public/quotations/view/' . encodeId($id));
            exit();
        }

        $reason = trim($_POST['rejection_reason']);

        if ($this->quotationModel->updateStatus($id, 'Rejected')) {
            
            $_SESSION['success'] = "Quotation has been rejected.";

            
            try {
                // ดึงข้อมูล quotation มาเพื่อใช้สร้างข้อความแจ้งเตือน
                $quotation = $this->quotationModel->getById($id);
                if ($quotation) {
                    $recipientId = $quotation['created_by'];
                    $rejectorId = $_SESSION['user_id'];

                    if ($recipientId != $rejectorId) {
                        $message = "❌ Your quote #{$quotation['quotation_number']} was rejected. Reason: \"{$reason}\"";
                        $link = "/quotations/edit/" . $id;
                        $this->notificationModel->create($recipientId, $message, $link);
                    }
                }
            } catch (Exception $e) {
                error_log("Return notification failed to send on rejection: " . $e->getMessage());
            }

        } else {
            $_SESSION['error'] = "Failed to reject quotation.";
        }

        header('Location: /mcvpro/public/quotations/view/' . encodeId($id));
        exit();
    }
    
public function cancel($hashedId)
    {
        $id = decodeId($hashedId);
        if ($id === null) { die("Invalid ID."); }

        $quotation = $this->quotationModel->getById($id);
        if (!$quotation) {
            // Handle not found
            exit();
        }

        // ตรวจสอบสิทธิ์อีกครั้งที่ฝั่ง Server
        $isOwner = ($_SESSION['user_id'] == $quotation['created_by']);
        $isAdminOrStaff = in_array($_SESSION['user_role'], [1, 2]);
        if (!$isOwner && !$isAdminOrStaff) {
            die("Access Denied.");
        }

        // ตรวจสอบสถานะอีกครั้งที่ฝั่ง Server
        if (!in_array($quotation['status'], ['Draft', 'Pending Approval'])) {
            $_SESSION['error'] = 'This quotation cannot be canceled.';
            header('Location: /mcvpro/public/quotations/view/' . $hashedId);
            exit();
        }

        // อัปเดตสถานะเป็น 'Cancel'
        if ($this->quotationModel->updateStatus($id, 'Cancel')) {
            $_SESSION['success'] = 'Quotation has been canceled.';
        } else {
            $_SESSION['error'] = 'Failed to cancel the quotation.';
        }

        header('Location: /mcvpro/public/quotations/view/' . $hashedId);
        exit();
    }


    public function duplicate($hashedId)
{
    $id = decodeId($hashedId);
    if ($id === null) { die("Invalid ID."); }

    $quotation = $this->quotationModel->getById($id);
    $items = $this->itemModel->getAllForQuotation($id);

    if (!$quotation) { die("Quotation to duplicate not found."); }

    // ไปเรียกฟังก์ชัน getFormForDuplicate() แทน
    $this->getFormForDuplicate($hashedId);
}

    public function getFormForDuplicate($hashedId)
{
    $id = decodeId($hashedId);
    if ($id === null) { die("Invalid ID."); }

    $quotation = $this->quotationModel->getById($id);
    $items = $this->itemModel->getAllForQuotation($id);
    
    if (!$quotation) {
        echo "<div class='alert alert-danger'>Quotation not found.</div>";
        return;
    }

    // ใส่ข้อมูลแบบ array เพื่อให้ view ใช้ได้
    $data = [
        'quotation' => $quotation,
        'items' => $items,
        'page_title' => "Create New Quotation (from " . $quotation['quotation_number'] . ")"
    ];

    extract($data); // ทำให้ใช้ตัวแปร $quotation, $items, $page_title ใน view ได้

    include ROOT_PATH . 'app/views/quotations/_form_partial.php';
}

public function member($memberId)
{
    $quotationModel = new Quotation($this->pdo);
    $quotations = $quotationModel->getQuotationsByMember($memberId);
    $member = $quotationModel->getMemberName($memberId);

    $data = [
        'quotations' => $quotations,
        'member' => $member
    ];

    require __DIR__ . 'app/views/home/member_detail.php';
}

}