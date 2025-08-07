<?php
// file: app/models/Notification.php
class Notification{
    protected $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

     /**
     * สร้างการแจ้งเตือนใหม่ในฐานข้อมูล
     */
    public function create(int $userId, string $message, ?string $link = null): bool
    {
        $sql = "INSERT INTO notifications (Id_user, message, link) VALUES (:user_id, :message, :link)";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            'user_id' => $userId,
            'message' => $message,
            'link'    => $link
        ]);
    }

    /**
     * ดึงการแจ้งเตือนที่ยังไม่อ่านทั้งหมดสำหรับผู้ใช้คนหนึ่ง
     */
    public function getUnreadForUser(int $userId, int $limit = 5): array
    {
        $sql = "SELECT * FROM notifications WHERE Id_user = :user_id AND is_read = 0 ORDER BY created_at DESC LIMIT :limit";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * นับจำนวนการแจ้งเตือนที่ยังไม่อ่าน (สำหรับแสดงบน Badge)
     */
    public function countUnreadForUser(int $userId): int
    {
        $sql = "SELECT COUNT(*) FROM notifications WHERE Id_user = :user_id AND is_read = 0";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['user_id' => $userId]);
        return (int)$stmt->fetchColumn();
    }

    /**
     * อัปเดตสถานะการแจ้งเตือนเป็น "อ่านแล้ว"
     */
    public function markAsRead(int $notificationId, int $userId): bool
    {
        // เช็ค userId เพื่อความปลอดภัย ป้องกันไม่ให้ user คนอื่นมาแก้สถานะของคนอื่น
        $sql = "UPDATE notifications SET is_read = 1 WHERE notification_id = :notification_id AND Id_user = :user_id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute(['notification_id' => $notificationId, 'user_id' => $userId]);
    }

    public function deleteByLink($link) 
    {
        try {
            $sql = "DELETE FROM notifications WHERE link = :link";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':link', $link, PDO::PARAM_STR);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log($e->getMessage());
            return false;
        }
    }
    public function clearUnreadByUser($userId)
    {
        $sql = "UPDATE notifications SET is_read = 1 WHERE Id_user = :user_id AND is_read = 0";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        return $stmt->execute();
    }
}