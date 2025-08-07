<?php
// app/models/Contact.php
class Contact
{
    protected $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function getAllForCustomer(int $customerId): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM customer_contacts WHERE customer_id = ? ORDER BY contact_name ASC");
        $stmt->execute([$customerId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create(array $data): bool
    {
        $sql = "INSERT INTO customer_contacts (customer_id, contact_name, contact_email, contact_phone, contact_position) VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            $data['customer_id'], 
            $data['contact_name'], 
            $data['contact_email'], 
            $data['contact_phone'],
            $data['contact_position']
        ]);
    }

    // เราสามารถเพิ่มฟังก์ชัน getById, update, delete ที่นี่ในอนาคต
}