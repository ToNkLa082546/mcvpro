<?php class FilesController extends Controller {

    public function upload() {
        // ตรวจสอบว่ามีไฟล์ส่งมาหรือไม่
        if (isset($_FILES['files'])) {
            $fileModel = new FileUpload($this->pdo);
            $uploadDir = ROOT_PATH . 'public/uploads/files/';
            if (!is_dir($uploadDir)) { mkdir($uploadDir, 0777, true); }
            $allowedExtensions = ['pdf', 'zip'];

            foreach ($_FILES['files']['tmp_name'] as $key => $tmp_name) {
                if ($_FILES['files']['error'][$key] === UPLOAD_ERR_OK) {
                    $originalFilename = $_FILES['files']['name'][$key];
                    $storedFilename = uniqid('file_', true) . '.' . pathinfo($originalFilename, PATHINFO_EXTENSION);
                    $destination = $uploadDir . $storedFilename;

                    if (move_uploaded_file($tmp_name, $destination)) {
                        $fileData = [
                            'original_filename' => $originalFilename,
                            'stored_filename' => $storedFilename,
                            'file_path' => '/uploads/files/' . $storedFilename,
                            'uploaded_by' => $_SESSION['user_id']
                            // quotation_id จะเป็น NULL โดยอัตโนมัติ
                        ];
                        $fileModel->create($fileData);
                    }
                }
            }
            // ส่ง JSON response กลับไปว่าสำเร็จ
            header('Content-Type: application/json');
        
                // 2. ส่งข้อมูล JSON กลับไปแล้วจบการทำงานทันที
                echo json_encode(['status' => 'success']);
                exit(); // <<< สำคัญมาก: ป้องกันไม่ให้มีข้อความอื่นปนออกไป

            } else {
                // กรณีไม่มีไฟล์ส่งมา ก็ควรตอบกลับเป็น JSON เช่นกัน
                header('Content-Type: application/json');
                echo json_encode(['status' => 'error', 'message' => 'No files uploaded.']);
                exit();
            
                }
    }
    
    public function getGallery() {
        // ดึงไฟล์ที่ยังไม่ถูกใช้งาน (quotation_id IS NULL)
        $fileModel = new FileUpload($this->pdo);
        $files = $fileModel->getUnassignedFiles($_SESSION['user_id']);
        // ส่งกลับไปเป็น HTML สำหรับแสดงใน Modal
        include ROOT_PATH . 'app/views/files/_gallery.php';
    }
    public function myFiles()
    {
        $this->auth(); // ตรวจสอบว่าล็อกอินอยู่หรือไม่
        $userId = $_SESSION['user_id'];

        $fileModel = new FileUpload($this->pdo);
        $data['files'] = $fileModel->getFilesByUser($userId);

        // โหลดไฟล์ View ย่อยเพื่อส่งกลับไปเป็น HTML
        include ROOT_PATH . 'app/views/activities/_my_files_list.php';
    }

    public function delete($fileId)
    {
        $this->auth(); // ตรวจสอบว่าล็อกอินอยู่
        $userId = $_SESSION['user_id'];
        $fileId = (int)$fileId;

        header('Content-Type: application/json');
        
        $fileModel = new FileUpload($this->pdo);
        $file = $fileModel->getById($fileId);

        // --- การตรวจสอบความปลอดภัย ---
        // 1. ตรวจสอบว่าหาไฟล์เจอหรือไม่
        // 2. ตรวจสอบว่าเป็นเจ้าของไฟล์จริงหรือไม่
        if (!$file || $file['uploaded_by'] != $userId) {
            echo json_encode(['status' => 'error', 'message' => 'File not found or permission denied.']);
            exit();
        }

        // --- เริ่มทำการลบ ---
        try {
            // 1. ลบไฟล์จริงออกจากเซิร์ฟเวอร์
            $filePath = ROOT_PATH . 'public' . $file['file_path'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }
            
            // 2. ลบข้อมูลออกจากฐานข้อมูล
            $fileModel->delete($fileId);

            echo json_encode(['status' => 'success']);
            exit();

        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => 'Could not delete the file.']);
            exit();
        }
    }
}