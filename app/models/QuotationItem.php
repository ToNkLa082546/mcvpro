<?php

class QuotationItem 
{
    protected $pdo;

    public function __construct(PDO $pdo) 
    {
        $this->pdo = $pdo;
    }

    /**
     * ✅ อัปเกรดฟังก์ชันนี้
     * สร้างรายการสินค้า และคืนค่า ID ที่เพิ่งสร้าง
     * @param array $data ข้อมูลที่จะบันทึก
     * @return int|null คืนค่า ID ถ้าสำเร็จ, คืนค่า null ถ้าล้มเหลว
     */
    public function create(array $data): ?int
    {
        // ทำให้รองรับ field ที่ส่งมาได้ทุก field (เช่น description, parent_item_id)
        $keys = array_keys($data);
        $columns = implode(', ', $keys);
        $placeholders = implode(', ', array_fill(0, count($keys), '?'));
        
        $sql = "INSERT INTO quotation_items ($columns) VALUES ($placeholders)";
        
        $stmt = $this->pdo->prepare($sql);
        
        if ($stmt->execute(array_values($data))) {
            // ถ้าสำเร็จ ให้คืนค่า ID ล่าสุดกลับไป
            return (int)$this->pdo->lastInsertId();
        }
        
        // ถ้าล้มเหลว
        return null;
    }

    public function getAllForQuotation(int $quotationId): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM quotation_items WHERE quotation_id = ? ORDER BY item_id ASC");
        $stmt->execute([$quotationId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function createAndGetId(array $data): ?int
        {
            // สร้าง SQL แบบไดนามิกเพื่อรองรับ field ทั้งหมด
            $keys = array_keys($data);
            $columns = implode(', ', $keys);
            $placeholders = implode(', ', array_fill(0, count($keys), '?'));
            
            $sql = "INSERT INTO quotation_items ($columns) VALUES ($placeholders)";
            
            $stmt = $this->pdo->prepare($sql);
            
            // ถ้า execute สำเร็จ
            if ($stmt->execute(array_values($data))) {
                // คืนค่า ID ล่าสุดที่เพิ่งสร้างกลับไป
                return (int)$this->pdo->lastInsertId();
            }
            
            // ถ้าล้มเหลว
            return null;
        }

}