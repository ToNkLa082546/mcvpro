<?php
// app/models/Customer.php

class Customers
{
    protected $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * ดึงข้อมูลลูกค้าทั้งหมดพร้อมชื่อผู้สร้าง
     * @return array
     */
    public function getAll(int $limit = 15, int $offset = 0): array
{
    $sql = "
        SELECT 
            c.customer_id, 
            c.company_name, 
            c.created_at,
            COALESCE(p.fname_personal, u.email_user) AS created_by_display
        FROM customer c
        LEFT JOIN users u ON c.user_id = u.id_user
        LEFT JOIN personal p ON u.id_user = p.user_id
        ORDER BY c.created_at DESC
        LIMIT ? OFFSET ?
    ";
    $stmt = $this->pdo->prepare($sql);
    $stmt->bindValue(1, $limit, PDO::PARAM_INT);
    $stmt->bindValue(2, $offset, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


    public function deleteById(int $id): bool
{
    // เพื่อความปลอดภัยของข้อมูล เราควรใช้ Transaction
    // ถ้าการลบส่วนใดส่วนหนึ่งล้มเหลว มันจะยกเลิกทั้งหมด
    try {
        $this->pdo->beginTransaction();

        // 1. ลบโปรเจกต์ทั้งหมดของลูกค้านี้ก่อน (เพราะมี Foreign Key)
        $stmt1 = $this->pdo->prepare("DELETE FROM project WHERE customer_id = ?");
        $stmt1->execute([$id]);

        // 2. ลบตัวลูกค้าเอง
        $stmt2 = $this->pdo->prepare("DELETE FROM customer WHERE customer_id = ?");
        $stmt2->execute([$id]);

        $this->pdo->commit();
        return true;

    } catch (Exception $e) {
        $this->pdo->rollBack();
        error_log("Delete Customer Failed: " . $e->getMessage());
        return false;
    }
}


// ในคลาส Customers

public function getById(int $id)
{
    $stmt = $this->pdo->prepare("SELECT * FROM customer WHERE customer_id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

public function create(array $data): bool
{
    // ✅ เพิ่มคอลัมน์ใหม่
    $sql = "INSERT INTO customer (company_name, user_id, created_at, customer_phone, customer_email, customer_address) 
            VALUES (:company_name, :created_by, NOW(), :customer_phone, :customer_email, :customer_address)";
    $stmt = $this->pdo->prepare($sql);

    return $stmt->execute([
        ':company_name'     => $data['company_name'],
        ':created_by'       => $data['created_by'],
        ':customer_phone'   => $data['customer_phone'],
        ':customer_email'   => $data['customer_email'],
        ':customer_address' => $data['customer_address']
    ]);
}

/**
 * อัปเดตข้อมูลลูกค้า
 */
public function updateById(int $id, array $data): bool
{
    // ✅ เพิ่มคอลัมน์ใหม่
    $sql = "UPDATE customer SET 
                company_name = :company_name,
                customer_phone = :customer_phone,
                customer_email = :customer_email,
                customer_address = :customer_address
            WHERE customer_id = :customer_id";
    $stmt = $this->pdo->prepare($sql);
    
    return $stmt->execute([
        ':company_name'     => $data['company_name'],
        ':customer_phone'   => $data['customer_phone'],
        ':customer_email'   => $data['customer_email'],
        ':customer_address' => $data['customer_address'],
        ':customer_id'      => $id
    ]);
}

public function getByIdAndOwner(int $customerId, int $ownerId)
{
    // SQL นี้จะดึงข้อมูลลูกค้าได้ก็ต่อเมื่อ user_id ตรงกับคนที่ login อยู่
    $stmt = $this->pdo->prepare("SELECT * FROM customer WHERE customer_id = ? AND user_id = ?");
    $stmt->execute([$customerId, $ownerId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}


/**
 * ดึงรายชื่อผู้ร่วมแก้ไขทั้งหมดของลูกค้ารายนี้
 */
public function getCollaborators(int $customerId): array
{
    $sql = "SELECT u.id_user, p.fname_personal, r.role_name
            FROM customer_collaborators cc
            JOIN users u ON cc.user_id = u.id_user
            JOIN personal p ON u.id_user = p.user_id
            JOIN role r ON cc.role_id = r.role_id
            WHERE cc.customer_id = ?";
    $stmt = $this->pdo->prepare($sql);
    $stmt->execute([$customerId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * เพิ่มผู้ร่วมแก้ไขใหม่
 */
public function addCollaborator(int $customerId, int $userId, int $roleId): bool
{
    // ใช้ INSERT IGNORE เพื่อป้องกันการเพิ่มข้อมูลซ้ำ (กรณีมี Unique Key อยู่แล้ว)
    $sql = "INSERT IGNORE INTO customer_collaborators (customer_id, user_id, role_id) VALUES (?, ?, ?)";
    $stmt = $this->pdo->prepare($sql);
    return $stmt->execute([$customerId, $userId, $roleId]);
}

/**
 * ลบผู้ร่วมแก้ไขออก
 */
public function removeCollaborator(int $customerId, int $userId): bool
{
    $sql = "DELETE FROM customer_collaborators WHERE customer_id = ? AND user_id = ?";
    $stmt = $this->pdo->prepare($sql);
    return $stmt->execute([$customerId, $userId]);
}


public function canUserAccess(int $customerId, int $userId, int $userRole): bool
{
    // 1. ถ้าเป็น Admin (role_id=1) ให้ผ่านได้เลยเสมอ
    if ($userRole == 1) {
        return true;
    }

    // 2. ตรวจสอบว่าเป็นเจ้าของ หรือเป็นผู้ร่วมแก้ไข
    // ✅ แก้ไข SQL ให้ใช้เครื่องหมาย ? แทนชื่อ
    $sql = "
        SELECT 1 
        FROM customer c
        LEFT JOIN customer_collaborators cc ON c.customer_id = cc.customer_id
        WHERE 
            c.customer_id = ? 
            AND (c.user_id = ? OR cc.user_id = ?)
        LIMIT 1
    ";
    
    $stmt = $this->pdo->prepare($sql);

    // ✅ ส่งค่าเป็น Array ตามลำดับของเครื่องหมาย ?
    $stmt->execute([$customerId, $userId, $userId]);
    
    // ถ้าเจอข้อมูลอย่างน้อย 1 แถว แสดงว่ามีสิทธิ์
    return $stmt->fetchColumn() !== false;
}

public function getAllForUser(int $userId, int $limit = 15, int $offset = 0): array
{
    $sql = "
        SELECT DISTINCT
            c.customer_id, 
            c.company_name, 
            c.created_at,
            COALESCE(p.fname_personal, u.email_user) AS created_by_display
        FROM 
            customer AS c
        LEFT JOIN 
            users AS u ON c.user_id = u.id_user
        LEFT JOIN 
            personal AS p ON c.user_id = p.user_id 
        LEFT JOIN 
            customer_collaborators AS cc ON c.customer_id = cc.customer_id
        WHERE 
            c.user_id = ? 
            OR cc.user_id = ?
        ORDER BY 
            c.created_at DESC
        LIMIT ? OFFSET ?
    ";
    $stmt = $this->pdo->prepare($sql);
    $stmt->bindValue(1, $userId, PDO::PARAM_INT);
    $stmt->bindValue(2, $userId, PDO::PARAM_INT);
    $stmt->bindValue(3, $limit, PDO::PARAM_INT);
    $stmt->bindValue(4, $offset, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}



public function countAll(): int
{
    return (int)$this->pdo->query("SELECT COUNT(customer_id) FROM customer")->fetchColumn();
}

public function getSimpleById(int $id)
{
    $stmt = $this->pdo->prepare("SELECT customer_id AS id, customer_name AS name FROM customer WHERE customer_id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}


public function getFiltered(array $filters, int $limit = 15, int $offset = 0): array
{
    $params = [];
    $sql = "SELECT c.*, p.fname_personal AS created_by_display
            FROM customer c
            LEFT JOIN users u ON c.user_id = u.Id_user
            LEFT JOIN personal p ON u.Id_user = p.user_id
            WHERE 1=1";

    // --- เงื่อนไขแบบไดนามิก ---
    if (!empty($filters['search'])) {
        $sql .= " AND c.company_name LIKE ?";
        $params[] = "%{$filters['search']}%";
    }
    if (!empty($filters['start_date'])) {
        $sql .= " AND DATE(c.created_at) >= ?";
        $params[] = $filters['start_date'];
    }
    if (!empty($filters['end_date'])) {
        $sql .= " AND DATE(c.created_at) <= ?";
        $params[] = $filters['end_date'];
    }

    $sql .= " ORDER BY c.created_at DESC LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;

    $stmt = $this->pdo->prepare($sql);

    // bindParam ใช้ไม่ได้กับ array indexed แบบนี้ ต้อง bind แบบลูป
    foreach ($params as $index => $value) {
        $paramType = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
        $stmt->bindValue($index + 1, $value, $paramType); // +1 เพราะ PDO เริ่มที่ index 1
    }

    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}



public function countFiltered($filters)
{
    $sql = "SELECT COUNT(*) FROM customer WHERE 1=1";
    $params = [];

    if (!empty($filters['search'])) {
        $sql .= " AND company_name LIKE :search";
        $params[':search'] = '%' . $filters['search'] . '%';
    }
    if (!empty($filters['start_date'])) {
        $sql .= " AND created_at >= :start";
        $params[':start'] = $filters['start_date'];
    }
    if (!empty($filters['end_date'])) {
        $sql .= " AND created_at <= :end";
        $params[':end'] = $filters['end_date'];
    }

    $stmt = $this->pdo->prepare($sql);
    foreach ($params as $key => $val) {
        $stmt->bindValue($key, $val);
    }
    $stmt->execute();
    return $stmt->fetchColumn();
}


public function countAllForUser(int $userId): int
{
    // The SQL needs one placeholder (?) for the user ID
    $sql = "SELECT COUNT(*) FROM customer WHERE user_id = ?";
    
    $stmt = $this->pdo->prepare($sql);

    // execute() needs one value in an array to match the placeholder
    $stmt->execute([$userId]);
    
    return (int)$stmt->fetchColumn();
}
}