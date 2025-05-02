<?php
class OrderStatus {
    private $db;
    private $table = 'order_statuses';

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * الحصول على جميع حالات الطلبات
     */
    public function getAllStatuses() {
        $stmt = $this->db->query("SELECT * FROM {$this->table} ORDER BY id ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * الحصول على حالة طلب بواسطة المعرف
     */
    public function getStatusById($id) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * الحصول على اسم الحالة بواسطة المعرف
     */
    public function getStatusNameById($id) {
        $stmt = $this->db->prepare("SELECT name FROM {$this->table} WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['name'] : 'غير معروف';
    }

    /**
     * إضافة حالة طلب جديدة
     */
    public function addStatus($name, $description = null) {
        $stmt = $this->db->prepare("INSERT INTO {$this->table} (name, description) VALUES (:name, :description)");
        $stmt->bindParam(':name', $name, PDO::PARAM_STR);
        $stmt->bindParam(':description', $description, PDO::PARAM_STR);
        return $stmt->execute();
    }

    /**
     * تحديث حالة طلب
     */
    public function updateStatus($id, $name, $description = null) {
        $stmt = $this->db->prepare("UPDATE {$this->table} SET name = :name, description = :description WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':name', $name, PDO::PARAM_STR);
        $stmt->bindParam(':description', $description, PDO::PARAM_STR);
        return $stmt->execute();
    }

    /**
     * حذف حالة طلب
     */
    public function deleteStatus($id) {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }
    
    /**
     * الحصول على عدد الفواتير لكل حالة
     */
    public function getInvoiceCountByStatus() {
        $sql = "SELECT s.id, s.name, COUNT(i.id) as count 
                FROM {$this->table} s 
                LEFT JOIN invoices i ON s.id = i.status_id 
                GROUP BY s.id, s.name 
                ORDER BY s.id ASC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * تحديث حالة الطلب للفاتورة
     */
    public function updateInvoiceStatus($invoiceId, $statusId) {
        $stmt = $this->db->prepare("UPDATE invoices SET status_id = :status_id WHERE id = :invoice_id");
        $stmt->bindParam(':invoice_id', $invoiceId, PDO::PARAM_INT);
        $stmt->bindParam(':status_id', $statusId, PDO::PARAM_INT);
        return $stmt->execute();
    }
}
