<?php
// app/models/Project.php

class Project
{
    protected $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * ดึงข้อมูลโปรเจกต์ทั้งหมด พร้อมชื่อลูกค้าและผู้สร้าง
     * @return array
     */
    public function getAll(): array
    {
        $sql = "
            SELECT 
                p.project_id,
                p.project_name,
                p.customer_id,
                p.status,
                p.project_price,
                c.company_name,
                creator.fname_personal AS created_by_name
            FROM project AS p
            LEFT JOIN customer AS c ON p.customer_id = c.customer_id
            LEFT JOIN personal AS creator ON p.created_by = creator.user_id
            ORDER BY p.created_at DESC
        ";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * ดึงข้อมูลโปรเจกต์เดียวด้วย ID
     * @param int $id
     * @return array|false
     */
    public function getById(int $id)
    {
        $sql = "
            SELECT 
                p.*, 
                c.company_name, 
                creator.fname_personal AS created_by_name
            FROM project p
            LEFT JOIN customer c ON p.customer_id = c.customer_id
            LEFT JOIN personal creator ON p.created_by = creator.user_id
            WHERE p.project_id = ?
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * สร้างโปรเจกต์ใหม่
     * @param array $data
     * @return bool
     */
    public function create(array $data): bool
    {
    $sql = "
        INSERT INTO project (
            project_name, 
            description, 
            project_price, 
            created_by, 
            status
        ) 
        VALUES (:project_name, :description, :project_price, :created_by, 'Pending')
    ";
    $stmt = $this->pdo->prepare($sql);
    
    return $stmt->execute([
        ':project_name'  => $data['project_name'],
        ':description'   => $data['description'],
        ':project_price' => $data['project_price'],
        ':created_by'    => $data['created_by']
    ]);
    }
    
    /**
     * อัปเดตข้อมูลโปรเจกต์
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update(int $id, array $data): bool
    {
        $sql = "
            UPDATE project SET
                project_name = :project_name,
                description = :description,
                project_price = :project_price,
                customer_id = :customer_id,
                status = :status,
                updated_at = NOW()
            WHERE project_id = :project_id
        ";
        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([
            ':project_name'  => $data['project_name'],
            ':description'   => $data['description'],
            ':project_price' => $data['project_price'],
            ':customer_id'   => $data['customer_id'],
            ':status'        => $data['status'],
            ':project_id'    => $id
        ]);
    }

    /**
     * ลบโปรเจกต์
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM project WHERE project_id = ?");
        return $stmt->execute([$id]);
    }

    public function getAvailableProjects(): array
{
    $sql = "
        SELECT project_id, project_name, description, project_price, created_at
        FROM project
        WHERE customer_id IS NULL AND status = 'Pending'
        ORDER BY created_at DESC
    ";
    $stmt = $this->pdo->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * กำหนดลูกค้าให้กับโปรเจกต์
 * @param int $projectId ID ของโปรเจกต์
 * @param int $customerId ID ของลูกค้า
 * @return bool
 */
public function assignCustomer(int $projectId, int $customerId): bool
{
    // อัปเดต customer_id และเปลี่ยนสถานะเป็น In Progress
    $sql = "UPDATE project SET customer_id = ?, status = 'In Progress' WHERE project_id = ? AND customer_id IS NULL";
    $stmt = $this->pdo->prepare($sql);
    $stmt->execute([$customerId, $projectId]);

    // ตรวจสอบว่ามีการอัปเดตเกิดขึ้นจริงหรือไม่ (ป้องกันการเคลมซ้ำซ้อน)
    return $stmt->rowCount() > 0;
}


public function getAssignedByCustomerId(int $customerId): array
{
    $stmt = $this->pdo->prepare("SELECT * FROM project WHERE customer_id = ? ORDER BY created_at DESC");
    $stmt->execute([$customerId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * ดึงโปรเจกต์ที่ยังว่าง และสร้างโดยผู้ใช้คนปัจจุบัน
 */
public function getAllUnassigned(): array
{
    $stmt = $this->pdo->query("SELECT * FROM project WHERE customer_id IS NULL ORDER BY project_name ASC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * ผูกโปรเจกต์เข้ากับลูกค้า
 */
public function assignToCustomer(int $projectId, int $customerId, int $ownerId): bool
{
    // อัปเดตเฉพาะโปรเจกต์ที่ยังว่าง และเป็นของคนที่กดเท่านั้น
    $sql = "UPDATE project SET customer_id = ? WHERE project_id = ? AND created_by = ? AND customer_id IS NULL";
    $stmt = $this->pdo->prepare($sql);
    $stmt->execute([$customerId, $projectId, $ownerId]);
    return $stmt->rowCount() > 0;
}

public function unassignFromCustomer(int $projectId, int $ownerId): bool
{
    // อัปเดต customer_id ให้เป็น NULL และเปลี่ยนสถานะกลับเป็น Pending
    // โดยจะทำได้เฉพาะโปรเจกต์ที่เราเป็นคนสร้างเท่านั้น (created_by)
    $sql = "UPDATE project SET customer_id = NULL, status = 'Pending' WHERE project_id = ? AND created_by = ?";
    $stmt = $this->pdo->prepare($sql);
    
    return $stmt->execute([$projectId, $ownerId]);
}


public function countAll(): int
{
    return (int)$this->pdo->query("SELECT COUNT(project_id) FROM project")->fetchColumn();
}

public function countUnassigned(): int
{
    return (int)$this->pdo->query("SELECT COUNT(project_id) FROM project WHERE customer_id IS NULL")->fetchColumn();
}

public function getProjectCountByStatus(): array
{
    $sql = "SELECT status, COUNT(project_id) AS project_count 
            FROM project 
            GROUP BY status";
    $stmt = $this->pdo->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

public function getProjectsForUser(int $userId): array
    {
        $sql = "
            SELECT p.*, c.company_name FROM project p
            JOIN customer c ON p.customer_id = c.customer_id
            LEFT JOIN customer_collaborators cc ON p.customer_id = cc.customer_id
            WHERE c.user_id = ? OR cc.user_id = ?
            GROUP BY p.project_id
            ORDER BY p.created_at DESC
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$userId, $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }



    public function getProjectsForMember(int $userId): array
{
    $sql = "
        SELECT DISTINCT p.*, c.company_name
        FROM project p
        JOIN customer c ON p.customer_id = c.customer_id
        JOIN customer_collaborators cc ON c.customer_id = cc.customer_id
        WHERE cc.user_id = :userId
        ORDER BY p.created_at DESC
    ";

    $stmt = $this->pdo->prepare($sql);
    $stmt->execute(['userId' => $userId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


    public function getAllPaginated(int $page, int $perPage): array {
        $offset = ($page - 1) * $perPage;
        $sql = "SELECT p.*, c.company_name, ps.fname_personal AS created_by_name
                FROM project p
                LEFT JOIN customer c ON p.customer_id = c.customer_id
                LEFT JOIN users u ON p.created_by = u.Id_user
                LEFT JOIN personal ps ON u.Id_user = ps.user_id
                ORDER BY p.created_at DESC LIMIT :limit OFFSET :offset";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // --- เมธอดสำหรับดึงข้อมูลเฉพาะ User (ไม่มีฟิลเตอร์) ---
    public function countProjectsForUser(int $userId): int {
        // ✅ แก้ไข 'projects' เป็น 'project'
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM project WHERE created_by = ?");
        $stmt->execute([$userId]);
        return (int)$stmt->fetchColumn();
    }
    
    public function getProjectsForUserPaginated(int $userId, int $page, int $perPage): array {
        $offset = ($page - 1) * $perPage;
        $sql = "SELECT p.*, c.company_name, ps.fname_personal AS created_by_name
                FROM project p
                LEFT JOIN customer c ON p.customer_id = c.customer_id
                LEFT JOIN users u ON p.created_by = u.Id_user
                LEFT JOIN personal ps ON u.Id_user = ps.user_id
                WHERE p.created_by = :userId
                ORDER BY p.created_at DESC LIMIT :limit OFFSET :offset";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':userId', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // --- เมธอดสำหรับดึงข้อมูลตามฟิลเตอร์ ---
    public function countFiltered(array $filters, int $role, int $userId): int
    {
        $params = [];
        $sql = "SELECT COUNT(p.project_id)
                FROM project p
                LEFT JOIN customer c ON p.customer_id = c.customer_id
                WHERE 1=1";

        if (!empty($filters['search'])) {
            $searchTerm = "%{$filters['search']}%";
            $sql .= " AND (p.project_name LIKE ? OR c.company_name LIKE ?)";
            array_push($params, $searchTerm, $searchTerm);
        }
        if (!empty($filters['status'])) {
            $sql .= " AND p.status = ?";
            $params[] = $filters['status'];
        }
        if (!empty($filters['start_date'])) {
            $sql .= " AND DATE(p.created_at) >= ?";
            $params[] = $filters['start_date'];
        }
        if (!empty($filters['end_date'])) {
            $sql .= " AND DATE(p.created_at) <= ?";
            $params[] = $filters['end_date'];
        }
        if (!in_array($role, [1, 2])) {
            $sql .= " AND p.created_by = ?";
            $params[] = $userId;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }
    
    public function getFilteredPaginated(array $filters, int $page, int $perPage, int $role, int $userId): array {
        $params = [];
        $offset = ($page - 1) * $perPage;

        $sql = "SELECT p.*, c.company_name, ps.fname_personal AS created_by_name
                FROM project p
                LEFT JOIN customer c ON p.customer_id = c.customer_id
                LEFT JOIN users u ON p.created_by = u.Id_user
                LEFT JOIN personal ps ON u.Id_user = ps.user_id
                WHERE 1=1";

        // ✅ เติมเงื่อนไขฟิลเตอร์ที่ขาดไป
        if (!empty($filters['search'])) {
            $searchTerm = "%{$filters['search']}%";
            $sql .= " AND (p.project_name LIKE ? OR c.company_name LIKE ?)";
            array_push($params, $searchTerm, $searchTerm);
        }
        if (!empty($filters['status'])) {
            $sql .= " AND p.status = ?";
            $params[] = $filters['status'];
        }
        if (!empty($filters['start_date'])) {
            $sql .= " AND DATE(p.created_at) >= ?";
            $params[] = $filters['start_date'];
        }
        if (!empty($filters['end_date'])) {
            $sql .= " AND DATE(p.created_at) <= ?";
            $params[] = $filters['end_date'];
        }
        if (!in_array($role, [1, 2])) {
            $sql .= " AND p.created_by = ?";
            $params[] = $userId;
        }

        $sql .= " ORDER BY p.created_at DESC LIMIT ? OFFSET ?";
        array_push($params, $perPage, $offset);
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


public function getAllForUser(int $userId): array
    {
        // ใช้ SQL คล้ายกับฟังก์ชัน getAll() แต่เพิ่มเงื่อนไข WHERE p.created_by
        $sql = "
            SELECT 
                p.project_id,
                p.project_name,
                p.customer_id,
                p.status,
                c.company_name
            FROM project AS p
            LEFT JOIN customer AS c ON p.customer_id = c.customer_id
            WHERE p.created_by = :user_id
            ORDER BY p.project_name ASC
        ";
        
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            // กรณีเกิด Error ให้คืนค่าเป็น Array ว่าง
            // ในระบบจริงควรมีการบันทึก Log Error ไว้ด้วย
            // error_log("Error in ProjectModel::getAllForUser - " . $e->getMessage());
            return [];
        }
    }

    public function getAllByCustomerId($customerId)
    {
        // Convert to integer for security and type consistency
        $safeCustomerId = (int) $customerId;

        list($sql, $params) = $this->getProjectsByCustomerSql($safeCustomerId);
        
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            // This now returns the results to the controller
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            // In case of a database error, return an empty array
            // You might want to log the error here: error_log($e->getMessage());
            return [];
        }
    }

    /**
     * Returns the SQL query and parameters for fetching projects by customer.
     * @param int $customerId The ID of the customer.
     * @return array An array containing the SQL string and the parameters array.
     */
    public function getProjectsByCustomerSql($customerId)
    {
        $safeCustomerId = (int) $customerId;

        // The SQL query is now correct based on your database structure
        $sql = "SELECT project_id, project_name FROM project WHERE customer_id = ? ORDER BY project_name ASC";
        
        $params = [$safeCustomerId];
        return [$sql, $params];
    }

}