<?php
// app/models/Branch.php
class Branch {
    protected $pdo;
    public function __construct(PDO $pdo) { $this->pdo = $pdo; }

    public function getAllForCustomer(int $customerId) {
        $stmt = $this->pdo->prepare("SELECT * FROM customer_branches WHERE customer_id = ? ORDER BY branch_name ASC");
        $stmt->execute([$customerId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create(array $data): bool {
        $sql = "INSERT INTO customer_branches (customer_id, branch_name, branch_address, branch_phone) VALUES (?, ?, ?, ?)";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$data['customer_id'], $data['branch_name'], $data['branch_address'], $data['branch_phone']]);
    }

}