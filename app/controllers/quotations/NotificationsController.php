<?php

class NotificationsController extends Controller
{
    private $notificationModel;

    public function __construct($pdo)
    {
        parent::__construct($pdo);
        $this->auth(); // ตรวจสอบว่าล็อกอินหรือยัง
        $this->notificationModel = new Notification($this->pdo);
    }

    /**
     * API Endpoint สำหรับอัปเดตสถานะเป็น "อ่านแล้ว"
     */
    public function markAsRead($notificationId)
    {
        header('Content-Type: application/json'); // บอก Browser ว่าจะส่งข้อมูล JSON กลับไป

        $userId = $_SESSION['user_id'] ?? 0;
        if ($userId === 0) {
            echo json_encode(['success' => false, 'message' => 'Not authenticated']);
            exit();
        }

        $success = $this->notificationModel->markAsRead((int)$notificationId, $userId);

        if ($success) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update status']);
        }
        exit();
    }


   public function clear()
    {
        $userId = $_SESSION['user_id'] ?? 0;

        if ($userId) {
            $this->notificationModel->clearUnreadByUser($userId);
            echo json_encode(['success' => true]);
        } else {
            http_response_code(403);
            echo json_encode(['error' => 'Unauthorized']);
        }
    }
}