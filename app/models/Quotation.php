<?php
class Quotation {
    protected $pdo;
    public function __construct(PDO $pdo) { $this->pdo = $pdo; }

    /**
     * สร้างใบเสนอราคาหลัก และคืนค่า ID ที่เพิ่งสร้าง
     */
    public function createWithTotals(array $data): ?int
    {
        $sql = "INSERT INTO quotations (quotation_number, project_id, customer_id, created_by, valid_until, notes, sub_total, vat_amount, grand_total) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->pdo->prepare($sql);
        if ($stmt->execute([
            $data['quotation_number'],
            $data['project_id'],
            $data['customer_id'],
            $data['created_by'],
            $data['valid_until'],
            $data['notes'],
            $data['sub_total'],
            $data['vat_amount'],
            $data['grand_total']
        ])) {
            return (int)$this->pdo->lastInsertId();
        }
        return null;
    }

    
    /**
     * อัปเดตยอดรวมของใบเสนอราคา
     */
    /**
 * อัปเดตยอดรวมของใบเสนอราคา
 */
    public function updateTotals(int $quotationId, float $subTotal, float $vatAmount, float $grandTotal): bool
    {
        $sql = "UPDATE quotations 
                SET sub_total = ?, vat_amount = ?, grand_total = ? 
                WHERE quotation_id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$subTotal, $vatAmount, $grandTotal, $quotationId]);
    }

    public function getAll(): array
    {
        $sql = "
            SELECT 
                q.quotation_id,
                q.quotation_number,
                q.grand_total,
                q.status,
                q.created_at,
                c.company_name,
                p.project_name
            FROM quotations AS q
            LEFT JOIN customer AS c ON q.customer_id = c.customer_id
            LEFT JOIN project AS p ON q.project_id = p.project_id
            ORDER BY q.created_at DESC
        ";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById(int $id)
    {
        $sql = "
            SELECT 
                q.*,
                c.company_name,
                p.project_name,
                c.customer_phone,
                c.customer_email,
                c.customer_address
            FROM quotations AS q
            LEFT JOIN customer AS c ON q.customer_id = c.customer_id
            LEFT JOIN project AS p ON q.project_id = p.project_id
            WHERE q.quotation_id = ?
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function updateStatus(int $id, string $status): bool
    {
        $sql = "UPDATE quotations SET status = ? WHERE quotation_id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$status, $id]);
    }

    public function getAllForUser(int $userId): array
    {
        $sql = "
            SELECT 
                q.quotation_id, q.quotation_number, q.grand_total, q.status, q.created_at,
                c.company_name, p.project_name
            FROM quotations AS q
            LEFT JOIN customer AS c ON q.customer_id = c.customer_id
            LEFT JOIN project AS p ON q.project_id = p.project_id
            WHERE q.created_by = ?  -- ✅ ดึงเฉพาะที่ตัวเองสร้าง
            ORDER BY q.created_at DESC
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

public function deleteById(int $id): bool
{
    // ใช้ Transaction เพื่อความปลอดภัย
    // ถ้าขั้นตอนใดขั้นตอนหนึ่งล้มเหลว จะยกเลิกทั้งหมด
    $this->pdo->beginTransaction();
    try {
        // 1. ลบ "รายการย่อย" (Sub-items) และ "รายการหลัก" ทั้งหมดก่อน
        $stmtItems = $this->pdo->prepare("DELETE FROM quotation_items WHERE quotation_id = ?");
        $stmtItems->execute([$id]);

        // 2. ลบ "ใบเสนอราคาหลัก"
        $stmtQuotation = $this->pdo->prepare("DELETE FROM quotations WHERE quotation_id = ?");
        $stmtQuotation->execute([$id]);

        // ถ้าทุกอย่างสำเร็จ
        $this->pdo->commit();
        return true;

    } catch (PDOException $e) {
        // ถ้ายกขั้นตอนใดผิดพลาด ให้ยกเลิกทั้งหมด
        $this->pdo->rollBack();
        // สามารถ log error ไว้ตรวจสอบได้
        // error_log($e->getMessage());
        return false;
    }
}

public function countAll(): int
    {
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM quotations");
        return (int)$stmt->fetchColumn();
    }

    /**
     * ✅ เพิ่มฟังก์ชันนี้: หายอดรวมของใบเสนอราคาที่สถานะเป็น 'Accepted'
     * @return float
     */
    public function sumOfAccepted(): float
    {
        $sql = "SELECT SUM(grand_total) FROM quotations WHERE status = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['Accepted']);
        
        // ถ้าไม่มีรายการที่ Accepted เลยจะคืนค่า NULL เราจึงต้องแปลงเป็น 0
        return (float)($stmt->fetchColumn() ?? 0);
    }

    public function getAllPaginated(int $page = 1, int $perPage = 15): array
    {
        $offset = ($page - 1) * $perPage;
        $sql = "
            SELECT q.*, c.company_name, p.project_name
            FROM quotations AS q
            LEFT JOIN customer AS c ON q.customer_id = c.customer_id
            LEFT JOIN project AS p ON q.project_id = p.project_id
            ORDER BY q.created_at DESC
            LIMIT :limit OFFSET :offset
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * ✅ เพิ่มฟังก์ชันนี้: ดึงข้อมูลสำหรับ User คนเดียวแบบแบ่งหน้า
     */
    public function getAllForUserPaginated(int $userId, int $page = 1, int $perPage = 15): array
    {
        $offset = ($page - 1) * $perPage;
        $sql = "
            SELECT q.*, c.company_name, p.project_name
            FROM quotations AS q
            LEFT JOIN customer AS c ON q.customer_id = c.customer_id
            LEFT JOIN project AS p ON q.project_id = p.project_id
            WHERE q.created_by = :userId
            ORDER BY q.created_at DESC
            LIMIT :limit OFFSET :offset
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':userId', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * ✅ เพิ่มฟังก์ชันนี้: นับจำนวนใบเสนอราคาของ User คนเดียว
     */
    public function countForUser(int $userId): int
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM quotations WHERE created_by = ?");
        $stmt->execute([$userId]);
        return (int)$stmt->fetchColumn();
    }
    
    public function getLatestRevisionFor($baseQuotationNumber)
    {
        // 1. SQL statement with named placeholders
        $sql = "SELECT quotation_number FROM quotations WHERE quotation_number = :base_num OR quotation_number LIKE :rev_pattern";

        try {
            // 2. Prepare the statement
            $stmt = $this->pdo->prepare($sql);

            // 3. Execute the statement with an array of parameters
            $stmt->execute([
                ':base_num'    => $baseQuotationNumber,
                'rev_pattern' => $baseQuotationNumber . '-REV-%'
            ]);

            // 4. Fetch all results as an array of objects
            $results = $stmt->fetchAll(PDO::FETCH_OBJ);

            // ส่วนตรรกะที่เหลือทำงานเหมือนเดิม
            $maxRev = 0;
            if ($results) {
                foreach ($results as $row) {
                    if (preg_match('/-REV-(\d+)$/', $row->quotation_number, $matches)) {
                        if ((int)$matches[1] > $maxRev) {
                            $maxRev = (int)$matches[1];
                        }
                    }
                }
            }
            return $maxRev;

        } catch (PDOException $e) {
            // จัดการ Error กรณีที่ Query ไม่สำเร็จ
            // คุณสามารถ log error หรือโยน exception ต่อไปได้
            // ในที่นี้เราจะคืนค่า 0 ไปก่อนเพื่อไม่ให้ระบบล่ม
            error_log('Database error in getLatestRevisionFor: ' . $e->getMessage());
            return 0; 
        }
    }

    public function countFiltered(array $filters, int $role, int $userId): int
    {
        $params = [];
        $sql = "SELECT COUNT(q.quotation_id) 
                FROM quotations q
                LEFT JOIN customer c ON q.customer_id = c.customer_id
                LEFT JOIN project p ON q.project_id = p.project_id
                WHERE 1=1"; // เริ่มต้น WHERE clause

        // --- สร้างเงื่อนไขแบบไดนามิก ---
        if (!empty($filters['search'])) {
            $searchTerm = "%{$filters['search']}%";
            $sql .= " AND (q.quotation_number LIKE ? OR c.company_name LIKE ? OR p.project_name LIKE ?)";
            array_push($params, $searchTerm, $searchTerm, $searchTerm);
        }
        if (!empty($filters['status'])) {
            $sql .= " AND q.status = ?";
            $params[] = $filters['status'];
        }
        if (!empty($filters['start_date'])) {
            $sql .= " AND DATE(q.created_at) >= ?";
            $params[] = $filters['start_date'];
        }
        if (!empty($filters['end_date'])) {
            $sql .= " AND DATE(q.created_at) <= ?";
            $params[] = $filters['end_date'];
        }

        // จำกัดการค้นหาสำหรับ Member
        if (!in_array($role, [1, 2])) {
            $sql .= " AND q.created_by = ?";
            $params[] = $userId;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    /**
     * ✅ 2. เมธอดสำหรับดึงข้อมูลตามฟิลเตอร์แบบแบ่งหน้า (แทนที่ของเดิม)
     */
    public function getFilteredPaginated(array $filters, int $page, int $perPage, int $role, int $userId): array 
{
    $params = [];
    $offset = ($page - 1) * $perPage;

    // เริ่มต้น SQL Query
    $sql = "SELECT q.*, c.company_name, p.project_name
            FROM quotations q
            LEFT JOIN customer c ON q.customer_id = c.customer_id
            LEFT JOIN project p ON q.project_id = p.project_id
            WHERE 1=1";

    
    if (!empty($filters['customer_id'])) {
        $sql .= " AND q.customer_id = :customer_id";
        $params[':customer_id'] = $filters['customer_id'];
    }
    if (!empty($filters['project_id'])) {
        $sql .= " AND q.project_id = :project_id";
        $params[':project_id'] = $filters['project_id'];
    }
    

    if (!empty($filters['search'])) {
        $searchTerm = "%{$filters['search']}%";
                // เปลี่ยน :search ให้มีชื่อไม่ซ้ำกัน
        $sql .= " AND (q.quotation_number LIKE :search_num OR c.company_name LIKE :search_company OR p.project_name LIKE :search_project)";
                // เพิ่ม parameter ให้ครบทั้ง 3 ตัว
        $params[':search_num'] = $searchTerm;
        $params[':search_company'] = $searchTerm;
        $params[':search_project'] = $searchTerm;
    }
    if (!empty($filters['status'])) {
        $sql .= " AND q.status = :status";
        $params[':status'] = $filters['status'];
    }
    if (!empty($filters['start_date'])) {
        $sql .= " AND DATE(q.created_at) >= :start_date";
        $params[':start_date'] = $filters['start_date'];
    }
    if (!empty($filters['end_date'])) {
        $sql .= " AND DATE(q.created_at) <= :end_date";
        $params[':end_date'] = $filters['end_date'];
    }
    // --- สิ้นสุดส่วนที่ขาดไป ---

    if (!in_array($role, [1, 2])) { // สมมติว่า role 1, 2 คือ Admin
        $sql .= " AND q.created_by = :user_id";
        $params[':user_id'] = $userId;
    }

    $sql .= " ORDER BY q.created_at DESC LIMIT :limit OFFSET :offset";
    $params[':limit'] = $perPage;
    $params[':offset'] = $offset;

    $stmt = $this->pdo->prepare($sql);

    $stmt->execute($params);

 return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
    public function getMemberMonthlySummary(?int $year = null, ?int $month = null): array
    {
        $sql = "SELECT * FROM v_staff_all_members_summary ORDER BY member_name ASC";
        $stmt = $this->pdo->query($sql);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if ($year !== null || $month !== null) {
            $data = array_filter($data, function ($row) {
                return !empty($row['created_at']);
            });

        }

        return array_values($data); // reindex array
    }


public function countAllMembers(): int
{
    // Role 3 คือ Member
    $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 3");
    $stmt->execute();
    return (int)$stmt->fetchColumn();
}
public function sumAllApproved(): float
    {
        $sql = "SELECT COALESCE(SUM(grand_total), 0) FROM quotations WHERE status = 'Approved'";
        return (float)$this->pdo->query($sql)->fetchColumn();
    }
    
public function updateSentTimestamp($id)
{
    try {
        $sql = "UPDATE quotations SET last_sent_at = NOW() WHERE quotation_id = :id"; 
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    } catch (PDOException $e) {
        error_log($e->getMessage());
        return false;
    }
}

public function getQuotationsByMember($memberId)
{
    $stmt = $this->pdo->prepare("
        SELECT quotation_id, status, total, created_at
        FROM quotations
        WHERE created_by = :member_id
        ORDER BY created_at DESC
    ");
    $stmt->execute(['member_id' => $memberId]);
    return $stmt->fetchAll();
}

public function getMemberName($memberId)
{
    $stmt = $this->pdo->prepare("SELECT name FROM users WHERE id = :id");
    $stmt->execute(['id' => $memberId]);
    return $stmt->fetchColumn();
}
public function getSummaryByDateRange($startDate, $endDate) {
        $sql = "
    SELECT
        u.id_user,
        u.email_user,
        COUNT(q.quotation_id) AS total_quotations,
        SUM(CASE WHEN TRIM(UPPER(q.status)) = 'DRAFT' THEN 1 ELSE 0 END) AS status_draft,
        SUM(CASE WHEN TRIM(UPPER(q.status)) = 'PENDING APPROVAL' THEN 1 ELSE 0 END) AS status_pending,
        SUM(CASE WHEN TRIM(UPPER(q.status)) = 'APPROVED' THEN 1 ELSE 0 END) AS status_approval,
        SUM(CASE WHEN TRIM(UPPER(q.status)) = 'REVISED' THEN 1 ELSE 0 END) AS status_revised,
        SUM(CASE WHEN TRIM(UPPER(q.status)) = 'REJECTED' THEN 1 ELSE 0 END) AS status_rejected,
        SUM(CASE WHEN TRIM(UPPER(q.status)) = 'CANCEL' THEN 1 ELSE 0 END) AS status_cancel,
        SUM(CASE WHEN TRIM(UPPER(q.status)) = 'APPROVED' THEN q.grand_total ELSE 0 END) AS total_approved_amount
    FROM
        users AS u
    LEFT JOIN
        quotations AS q ON u.id_user = q.created_by -- <<< ✅✅ นี่คือการเปลี่ยนแปลงที่สำคัญที่สุด ✅✅
                       AND q.created_at BETWEEN :startDate AND :endDate
    WHERE
        u.role_id NOT IN (1, 2) -- กรอง Admin และ Staff (อย่าลืมเช็ค role_id ของ Staff)
    GROUP BY
        u.id_user, u.email_user
    ORDER BY
        u.email_user ASC;
";

try {
    $stmt = $this->pdo->prepare($sql);
    $stmt->execute(['startDate' => $startDate, 'endDate' => $endDate]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log($e->getMessage());
    return [];
}
    }


    public function getMemberDashboardStats($userId, $startDate, $endDate) {
    // แก้ไข SQL Query ให้รับเงื่อนไขวันที่
    $sql = "
        SELECT
            COALESCE(COUNT(q.quotation_id), 0) AS total_quotations,
            COALESCE(SUM(CASE WHEN TRIM(UPPER(q.status)) = 'DRAFT' THEN 1 ELSE 0 END), 0) AS draft_quotations,
            COALESCE(SUM(CASE WHEN TRIM(UPPER(q.status)) = 'APPROVED' THEN 1 ELSE 0 END), 0) AS approved_quotations,
            COALESCE(SUM(CASE WHEN TRIM(UPPER(q.status)) = 'REJECTED' THEN 1 ELSE 0 END), 0) AS rejected_quotations,
            COALESCE(SUM(CASE WHEN TRIM(UPPER(q.status)) = 'CANCEL' THEN 1 ELSE 0 END), 0) AS canceled_quotations,
            COALESCE(SUM(CASE WHEN TRIM(UPPER(q.status)) = 'APPROVED' THEN q.grand_total ELSE 0 END), 0) AS approved_sum_in_range
        FROM
            users AS u
        LEFT JOIN
            quotations AS q ON u.id_user = q.created_by
                           AND q.created_at BETWEEN :startDate AND :endDate -- << เพิ่มเงื่อนไขวันที่ตรงนี้
        WHERE
            u.id_user = :userId;
    ";

    try {
        $stmt = $this->pdo->prepare($sql);
        // เพิ่มการ bind ค่าวันที่
        $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':startDate', $startDate, PDO::PARAM_STR);
        $stmt->bindParam(':endDate', $endDate, PDO::PARAM_STR);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ?: [
            'total_quotations' => 0, 'draft_quotations' => 0, 'approved_quotations' => 0,
            'rejected_quotations' => 0, 'canceled_quotations' => 0, 'approved_sum_in_range' => 0
        ];

    } catch (PDOException $e) {
        error_log("Error in getMemberDashboardStats: " . $e->getMessage());
        return [];
    }
}
}