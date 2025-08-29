<?php

class Activity
{
    protected $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * สร้าง Activity ใหม่ในฐานข้อมูล
     * @param array $data ข้อมูลสำหรับสร้าง Activity
     * @return string|false ID ของแถวที่เพิ่งเพิ่มเข้าไป หรือ false ถ้าล้มเหลว
     */
    public function create($data)
    {
        $columns = implode(", ", array_keys($data));
        $placeholders = ":" . implode(", :", array_keys($data));
        $sql = "INSERT INTO activities ($columns) VALUES ($placeholders)";
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($data);
            return $this->pdo->lastInsertId();
        } catch (PDOException $e) {
            error_log("Error creating activity: " . $e->getMessage());
            return false;
        }
    }

    /**
     * ดึง Activity ทั้งหมดแบบแบ่งหน้า (สำหรับ Admin/Staff)
     * @return array รายการ Activities
     */
    public function getAllPaginated($currentPage, $perPage)
    {
        $offset = ($currentPage - 1) * $perPage;
        $sql = "SELECT act.*, c.company_name, p.project_name
                FROM activities act
                JOIN customer c ON c.customer_id = act.customer_id
                JOIN project p ON p.project_id = act.project_id
                ORDER BY act.created_at DESC
                LIMIT :perPage OFFSET :offset";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':perPage', $perPage, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * นับจำนวน Activity ทั้งหมด
     * @return int จำนวน Activity
     */
    public function countAll()
    {
        $sql = "SELECT COUNT(*) FROM activities";
        return $this->pdo->query($sql)->fetchColumn();
    }

    /**
     * ดึง Activity ตาม ID
     * @return array|false ข้อมูล Activity หรือ false ถ้าไม่พบ
     */
    public function getById($id)
    {
        // --- แก้ไขสมบูรณ์แล้ว ---
        // เปลี่ยนไป JOIN กับตาราง 'personal' และดึงข้อมูลชื่อ-นามสกุล
        $sql = "SELECT 
                    act.*, 
                    c.company_name AS customer_name, 
                    p.project_name,
                    CONCAT(per.fname_personal, ' ', per.lname_personal) AS creator_name
                FROM 
                    activities act
                LEFT JOIN 
                    customer c ON act.customer_id = c.customer_id
                LEFT JOIN 
                    project p ON act.project_id = p.project_id
                LEFT JOIN 
                    personal per ON act.created_by = per.user_id
                WHERE 
                    act.activity_id = ?";
        
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$result) {
                return false;
            }
            return $result;

        } catch (PDOException $e) {
            // ถ้าเกิด Error ให้แสดงออกมาเลย
            die("DATABASE ERROR in Activity Model: " . $e->getMessage());
        }
    }

    /**
     * ดึงรายการ Quotation ที่เกี่ยวข้องกับ Activity
     * @return array รายการ Quotation
     */
    public function getQuotationsForActivity($activityId, $status = 'all')
    {
        try {
            // First, get the project_id from the activity
            $projectSql = "SELECT project_id FROM activities WHERE activity_id = ?";
            $projectStmt = $this->pdo->prepare($projectSql);
            $projectStmt->execute([$activityId]);
            $activity = $projectStmt->fetch(PDO::FETCH_ASSOC);

            if (!$activity || empty($activity['project_id'])) {
                return [];
            }
            $projectId = $activity['project_id'];

            // Now, fetch quotations based on the project_id and status
            $baseSql = "SELECT quotation_id, quotation_number, status FROM quotations WHERE project_id = ?";
            $params = [$projectId];

            // Add status filter if it's not 'all'
            if ($status !== 'all') {
                $baseSql .= " AND status = ?";
                $params[] = $status;
            }

            $quotationStmt = $this->pdo->prepare($baseSql);
            $quotationStmt->execute($params);
            return $quotationStmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * นับจำนวน Activity สำหรับ User ที่ Login อยู่
     * @return int จำนวน Activity ของ User
     */
    public function countAllForUser($userId)
    {
        $sql = "SELECT COUNT(*) FROM activities WHERE created_by = :user_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchColumn();
    }
    
    /**
     * ดึง Activity สำหรับ User ที่ Login อยู่แบบแบ่งหน้า
     * @return array รายการ Activity
     */
    public function getAllForUserPaginated($userId, $currentPage, $perPage)
    {
        $offset = ($currentPage - 1) * $perPage;
        $sql = "SELECT act.*, c.company_name, p.project_name
                FROM activities act
                JOIN customer c ON c.customer_id = act.customer_id
                JOIN project p ON p.project_id = act.project_id
                WHERE act.created_by = :user_id
                ORDER BY act.created_at DESC
                LIMIT :perPage OFFSET :offset";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':perPage', $perPage, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * นับจำนวน Activity ตามเงื่อนไข Filter
     * @return int จำนวน Activity
     */
   public function countFiltered($filters, $userId, $role)
{
    // SQL เริ่มต้นพร้อม JOIN ตารางที่จำเป็นสำหรับการค้นหา
    $sql = "SELECT COUNT(act.activity_id) 
            FROM activities act
            JOIN customer c ON c.customer_id = act.customer_id
            JOIN project p ON p.project_id = act.project_id
            WHERE 1=1";

    $params = [];
    // เรียกใช้ Helper Function เพื่อสร้างเงื่อนไข WHERE แบบไดนามิก
    list($sql, $params) = $this->buildFilterQuery($sql, $params, $filters, $userId, $role);
    $stmt = $this->pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchColumn();
}

/**
 * ดึง Activity ตามเงื่อนไข Filter แบบแบ่งหน้า
 * @return array รายการ Activity
 */
public function getFilteredPaginated($filters, $currentPage, $perPage, $userId, $role)
{
    $offset = ($currentPage - 1) * $perPage;
    
    // SQL เริ่มต้นพร้อม JOIN ตารางที่จำเป็นสำหรับการแสดงผลและค้นหา
    $sql = "SELECT act.*, c.company_name, p.project_name
            FROM activities act
            JOIN customer c ON c.customer_id = act.customer_id
            JOIN project p ON p.project_id = act.project_id
            WHERE 1=1";
            
    $params = [];
    // เรียกใช้ Helper Function เพื่อสร้างเงื่อนไข WHERE แบบไดนามิก
    list($sql, $params) = $this->buildFilterQuery($sql, $params, $filters, $userId, $role);
    
    // เพิ่มส่วนของการเรียงลำดับและแบ่งหน้า
    $sql .= " ORDER BY act.created_at DESC LIMIT :perPage OFFSET :offset";
    
    $stmt = $this->pdo->prepare($sql);
    
    // Bind ค่าจาก filter ทั้งหมด
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    // Bind ค่าสำหรับการแบ่งหน้า
    $stmt->bindParam(':perPage', $perPage, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Helper Function สำหรับสร้าง Dynamic SQL Query จาก Filter
 * ฟังก์ชันนี้จะถูกใช้ทั้งใน countFiltered และ getFilteredPaginated
 */
private function buildFilterQuery($sql, $params, $filters, $userId, $role)
{
    // --- ส่วนที่ 1: ตัวกรองตามสิทธิ์ผู้ใช้ ---
    if (in_array($role, [3])) { // Filter for regular users
        $sql .= " AND act.created_by = :user_id";
        $params[':user_id'] = $userId;
    }

    // --- ส่วนที่ 2: ตัวกรองจากฟอร์ม ---
    if (!empty($filters['search'])) {
        $sql .= " AND (c.company_name LIKE :search_customer OR p.project_name LIKE :search_project)";
        // เพิ่ม parameter 2 ตัว แต่ใช้ค่าเดียวกัน
        $params[':search_customer'] = '%' . $filters['search'] . '%';
        $params[':search_project'] = '%' . $filters['search'] . '%';
    }

    // สำหรับช่องค้นหาขั้นสูง (Specific Customer Name)
    if (!empty($filters['customer_name'])) {
        $sql .= " AND c.company_name LIKE :customer_name";
        $params[':customer_name'] = '%' . $filters['customer_name'] . '%';
    }

    // สำหรับช่องค้นหาขั้นสูง (Specific Project Name)
    if (!empty($filters['project_name'])) {
        // Correct: Both the SQL and the parameter are added in the same block.
        $sql .= " AND p.project_name LIKE :project_name";
        $params[':project_name'] = '%' . $filters['project_name'] . '%';
    }

    // สำหรับช่องค้นหาขั้นสูง (Date Range)
    if (!empty($filters['start_date'])) {
        $sql .= " AND DATE(act.created_at) >= :start_date";
        $params[':start_date'] = $filters['start_date'];
    }
    if (!empty($filters['end_date'])) {
        $sql .= " AND DATE(act.created_at) <= :end_date";
        $params[':end_date'] = $filters['end_date'];
    }

    return [$sql, $params];
}

    public function updateDescription($activityId, $description)
    {
        // เตรียมคำสั่ง SQL สำหรับการอัปเดต
        $sql = "UPDATE activities SET description = :description WHERE activity_id = :activity_id";
        
        try {
            $stmt = $this->pdo->prepare($sql);
            
            // ส่งค่าพารามิเตอร์และ execute คำสั่ง
            // เมธอด execute() จะคืนค่า true หากสำเร็จ
            return $stmt->execute([
                ':description' => $description,
                ':activity_id' => $activityId
            ]);
        } catch (PDOException $e) {
            // กรณีเกิดข้อผิดพลาดในการเชื่อมต่อหรือคำสั่ง SQL
            // คุณอาจจะต้องการบันทึก error ไว้: error_log($e->getMessage());
            return false;
        }
    }

public function delete($activityId)
{
    try {
        $sql = "DELETE FROM activities WHERE activity_id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$activityId]);
    } catch (PDOException $e) {
        // Optional: Log the error
        // error_log($e->getMessage());
        return false;
    }
}
    
    
    
}