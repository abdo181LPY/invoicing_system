<?php
class DeletedInvoice {
    private $db;
    private $table = 'deleted_invoices';

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * حفظ فاتورة محذوفة
     */
    public function saveDeletedInvoice($invoiceData, $deletedBy) {
        try {
            $this->db->beginTransaction();
            
            // تحويل البيانات إلى تنسيق JSON للتخزين
            $jsonData = json_encode($invoiceData, JSON_UNESCAPED_UNICODE);
            
            $stmt = $this->db->prepare("INSERT INTO {$this->table} (
                original_id, invoice_data, deleted_by, deleted_at
            ) VALUES (
                :original_id, :invoice_data, :deleted_by, NOW()
            )");
            
            $stmt->bindParam(':original_id', $invoiceData['id'], PDO::PARAM_INT);
            $stmt->bindParam(':invoice_data', $jsonData, PDO::PARAM_STR);
            $stmt->bindParam(':deleted_by', $deletedBy, PDO::PARAM_INT);
            
            $result = $stmt->execute();
            $this->db->commit();
            return $result;
            
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("خطأ في حفظ الفاتورة المحذوفة: " . $e->getMessage());
            return false;
        }
    }

    /**
     * الحصول على جميع الفواتير المحذوفة
     */
    public function getAllDeletedInvoices($limit = 100, $offset = 0) {
        $stmt = $this->db->prepare("
            SELECT d.*, u.username as deleted_by_name
            FROM {$this->table} d
            LEFT JOIN users u ON d.deleted_by = u.id
            ORDER BY d.deleted_at DESC
            LIMIT :limit OFFSET :offset
        ");
        
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * الحصول على فاتورة محذوفة بواسطة المعرف
     */
    public function getDeletedInvoiceById($id) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result) {
            $result['invoice_data'] = json_decode($result['invoice_data'], true);
        }
        
        return $result;
    }

    /**
     * الحصول على فاتورة محذوفة بواسطة معرف الفاتورة الأصلي
     */
    public function getDeletedInvoiceByOriginalId($originalId) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE original_id = :original_id ORDER BY deleted_at DESC LIMIT 1");
        $stmt->bindParam(':original_id', $originalId, PDO::PARAM_INT);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result) {
            $result['invoice_data'] = json_decode($result['invoice_data'], true);
        }
        
        return $result;
    }

    /**
     * البحث في الفواتير المحذوفة
     */
    public function searchDeletedInvoices($searchTerm) {
        $searchTerm = '%' . $searchTerm . '%';
        
        $stmt = $this->db->prepare("
            SELECT d.*, u.username as deleted_by_name
            FROM {$this->table} d
            LEFT JOIN users u ON d.deleted_by = u.id
            WHERE d.invoice_data LIKE :search_term
            ORDER BY d.deleted_at DESC
        ");
        
        $stmt->bindParam(':search_term', $searchTerm, PDO::PARAM_STR);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * استعادة فاتورة محذوفة
     */
    public function restoreInvoice($id) {
        try {
            $this->db->beginTransaction();
            
            // الحصول على بيانات الفاتورة المحذوفة
            $deletedInvoice = $this->getDeletedInvoiceById($id);
            if (!$deletedInvoice) {
                return false;
            }
            
            $invoiceData = $deletedInvoice['invoice_data'];
            
            // إعادة إدراج الفاتورة في جدول الفواتير
            $invoice = new Invoice();
            $result = $invoice->restoreInvoice($invoiceData);
            
            if ($result) {
                // حذف الفاتورة من جدول الفواتير المحذوفة
                $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE id = :id");
                $stmt->bindParam(':id', $id, PDO::PARAM_INT);
                $stmt->execute();
            }
            
            $this->db->commit();
            return $result;
            
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("خطأ في استعادة الفاتورة المحذوفة: " . $e->getMessage());
            return false;
        }
    }

    /**
     * الحصول على إحصائيات الحذف
     */
    public function getDeletionStatistics() {
        $stats = [];
        
        // إجمالي عدد الفواتير المحذوفة
        $stmt = $this->db->query("SELECT COUNT(*) as total FROM {$this->table}");
        $stats['total'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // عدد الفواتير المحذوفة حسب المستخدم
        $stmt = $this->db->query("
            SELECT u.username, COUNT(d.id) as count 
            FROM {$this->table} d
            JOIN users u ON d.deleted_by = u.id
            GROUP BY d.deleted_by
            ORDER BY count DESC
        ");
        $stats['by_user'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // عدد الفواتير المحذوفة حسب اليوم (آخر 30 يوم)
        $stmt = $this->db->query("
            SELECT DATE(deleted_at) as date, COUNT(*) as count 
            FROM {$this->table}
            WHERE deleted_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            GROUP BY DATE(deleted_at)
            ORDER BY date DESC
        ");
        $stats['by_date'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return $stats;
    }
}
