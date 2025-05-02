<?php
class Customer {
    private $db;
    private $table = 'customers';

    // تصنيفات الزبائن
    const RATING_EXCELLENT = 5;
    const RATING_GOOD = 4;
    const RATING_AVERAGE = 3;
    const RATING_BELOW_AVERAGE = 2;
    const RATING_POOR = 1;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * إضافة زبون جديد أو تحديث بيانات زبون موجود
     */
    public function addOrUpdateCustomer($name, $phone, $phone2 = null, $governorate = null, $area = null, $address = null, $notes = null, $rating = null) {
        try {
            // التحقق مما إذا كان الزبون موجوداً بالفعل بواسطة رقم الهاتف
            $existingCustomer = $this->getCustomerByPhone($phone);
            
            if ($existingCustomer) {
                // تحديث الزبون الموجود
                $stmt = $this->db->prepare("
                    UPDATE {$this->table} SET
                        name = :name,
                        phone2 = :phone2,
                        governorate = :governorate,
                        area = :area,
                        address = :address,
                        notes = CONCAT(IFNULL(notes, ''), :notes_separator, :notes),
                        rating = IFNULL(:rating, rating),
                        updated_at = NOW()
                    WHERE id = :id
                ");
                
                $notesSeparator = $existingCustomer['notes'] ? "\n---\n" : "";
                $stmt->bindParam(':id', $existingCustomer['id'], PDO::PARAM_INT);
                $stmt->bindParam(':notes_separator', $notesSeparator, PDO::PARAM_STR);
                
                // استخدام معرف الزبون الموجود للعودة
                $customerId = $existingCustomer['id'];
                
            } else {
                // إضافة زبون جديد
                $stmt = $this->db->prepare("
                    INSERT INTO {$this->table} (
                        name, phone, phone2, governorate, area, address, notes, rating, created_at, updated_at
                    ) VALUES (
                        :name, :phone, :phone2, :governorate, :area, :address, :notes, :rating, NOW(), NOW()
                    )
                ");
                
                $stmt->bindParam(':phone', $phone, PDO::PARAM_STR);
                
                // سيتم الحصول على معرف الزبون المضاف حديثاً بعد التنفيذ
                $customerId = null;
            }
            
            $stmt->bindParam(':name', $name, PDO::PARAM_STR);
            $stmt->bindParam(':phone2', $phone2, PDO::PARAM_STR);
            $stmt->bindParam(':governorate', $governorate, PDO::PARAM_STR);
            $stmt->bindParam(':area', $area, PDO::PARAM_STR);
            $stmt->bindParam(':address', $address, PDO::PARAM_STR);
            $stmt->bindParam(':notes', $notes, PDO::PARAM_STR);
            $stmt->bindParam(':rating', $rating, PDO::PARAM_INT);
            
            $result = $stmt->execute();
            
            if ($result && !$customerId) {
                $customerId = $this->db->lastInsertId();
            }
            
            return $customerId;
            
        } catch (PDOException $e) {
            error_log("خطأ في إضافة/تحديث الزبون: " . $e->getMessage());
            return false;
        }
    }

    /**
     * الحصول على زبون بواسطة المعرف
     */
    public function getCustomerById($id) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * الحصول على زبون بواسطة رقم الهاتف
     */
    public function getCustomerByPhone($phone) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE phone = :phone");
        $stmt->bindParam(':phone', $phone, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * البحث عن الزبائن
     */
    public function searchCustomers($searchTerm, $limit = 100) {
        $searchTerm = '%' . $searchTerm . '%';
        
        $stmt = $this->db->prepare("
            SELECT * FROM {$this->table}
            WHERE name LIKE :search_term
               OR phone LIKE :search_term
               OR phone2 LIKE :search_term
               OR area LIKE :search_term
            ORDER BY name ASC
            LIMIT :limit
        ");
        
        $stmt->bindParam(':search_term', $searchTerm, PDO::PARAM_STR);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * تحديث تصنيف الزبون
     */
    public function updateCustomerRating($customerId, $rating) {
        $stmt = $this->db->prepare("
            UPDATE {$this->table} SET
                rating = :rating,
                updated_at = NOW()
            WHERE id = :id
        ");
        
        $stmt->bindParam(':id', $customerId, PDO::PARAM_INT);
        $stmt->bindParam(':rating', $rating, PDO::PARAM_INT);
        
        return $stmt->execute();
    }

    /**
     * إضافة ملاحظة إلى الزبون
     */
    public function addCustomerNote($customerId, $note) {
        $customer = $this->getCustomerById($customerId);
        if (!$customer) {
            return false;
        }
        
        $newNotes = $customer['notes'] ? $customer['notes'] . "\n---\n" . $note : $note;
        
        $stmt = $this->db->prepare("
            UPDATE {$this->table} SET
                notes = :notes,
                updated_at = NOW()
            WHERE id = :id
        ");
        
        $stmt->bindParam(':id', $customerId, PDO::PARAM_INT);
        $stmt->bindParam(':notes', $newNotes, PDO::PARAM_STR);
        
        return $stmt->execute();
    }

    /**
     * الحصول على تاريخ طلبات الزبون
     */
    public function getCustomerOrderHistory($customerId) {
        $stmt = $this->db->prepare("
            SELECT i.*, s.name as status_name
            FROM invoices i
            LEFT JOIN order_statuses s ON i.status_id = s.id
            WHERE i.customer_id = :customer_id
            ORDER BY i.created_at DESC
        ");
        
        $stmt->bindParam(':customer_id', $customerId, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * الحصول على إجمالي عدد طلبات الزبون
     */
    public function getCustomerTotalOrders($customerId) {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as total_orders
            FROM invoices
            WHERE customer_id = :customer_id
        ");
        
        $stmt->bindParam(':customer_id', $customerId, PDO::PARAM_INT);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['total_orders'] : 0;
    }

    /**
     * الحصول على إجمالي قيمة طلبات الزبون
     */
    public function getCustomerTotalSpending($customerId) {
        $stmt = $this->db->prepare("
            SELECT SUM(total_price) as total_spending
            FROM invoices
            WHERE customer_id = :customer_id
        ");
        
        $stmt->bindParam(':customer_id', $customerId, PDO::PARAM_INT);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['total_spending'] : 0;
    }

    /**
     * الحصول على الزبائن حسب التصنيف
     */
    public function getCustomersByRating($rating, $limit = 100) {
        $stmt = $this->db->prepare("
            SELECT * FROM {$this->table}
            WHERE rating = :rating
            ORDER BY name ASC
            LIMIT :limit
        ");
        
        $stmt->bindParam(':rating', $rating, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * الحصول على إحصائيات الزبائن
     */
    public function getCustomerStatistics() {
        $stats = [];
        
        // إجمالي عدد الزبائن
        $stmt = $this->db->query("SELECT COUNT(*) as total FROM {$this->table}");
        $stats['total'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // عدد الزبائن حسب التصنيف
        $stmt = $this->db->query("
            SELECT rating, COUNT(*) as count 
            FROM {$this->table} 
            GROUP BY rating
            ORDER BY rating DESC
        ");
        $stats['by_rating'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // توزيع الزبائن حسب المحافظة
        $stmt = $this->db->query("
            SELECT governorate, COUNT(*) as count 
            FROM {$this->table} 
            WHERE governorate IS NOT NULL
            GROUP BY governorate
            ORDER BY count DESC
        ");
        $stats['by_governorate'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // الزبائن الأكثر طلباً
        $stmt = $this->db->query("
            SELECT c.id, c.name, c.phone, c.rating, COUNT(i.id) as order_count
            FROM {$this->table} c
            JOIN invoices i ON c.id = i.customer_id
            GROUP BY c.id, c.name, c.phone, c.rating
            ORDER BY order_count DESC
            LIMIT 10
        ");
        $stats['top_customers'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return $stats;
    }
}
