<?php
/**
 * نموذج الصفحة
 * يدير الصفحات التي يتم التعامل معها
 */
class Page {
    private $db;
    
    /**
     * إنشاء كائن نموذج الصفحة
     */
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * إنشاء صفحة جديدة
     */
    public function create($pageData) {
        // التحقق من عدم وجود الصفحة بالفعل
        if ($this->exists(['name' => $pageData['name']])) {
            return ['error' => 'اسم الصفحة موجود بالفعل'];
        }
        
        // إضافة حقول إضافية
        $pageData['created_at'] = date('Y-m-d H:i:s');
        $pageData['created_by'] = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
        
        // إدراج الصفحة
        $pageId = $this->db->insert('pages', $pageData);
        
        if ($pageId) {
            return ['success' => true, 'page_id' => $pageId];
        }
        
        return ['error' => 'فشل في إنشاء الصفحة'];
    }
    
    /**
     * تحديث معلومات الصفحة
     */
    public function update($id, $pageData) {
        // التحقق من وجود الصفحة
        if (!$this->exists(['id' => $id])) {
            return ['error' => 'الصفحة غير موجودة'];
        }
        
        // التحقق من عدم وجود الاسم لصفحة أخرى
        if (isset($pageData['name']) && !empty($pageData['name'])) {
            $exists = $this->db->exists('pages', 'name = :name AND id != :id', [
                'name' => $pageData['name'],
                'id' => $id
            ]);
            
            if ($exists) {
                return ['error' => 'اسم الصفحة موجود بالفعل'];
            }
        }
        
        // إضافة حقل التحديث
        $pageData['updated_at'] = date('Y-m-d H:i:s');
        $pageData['updated_by'] = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
        
        // تحديث الصفحة
        $updated = $this->db->update('pages', $pageData, 'id = :id', ['id' => $id]);
        
        if ($updated) {
            return ['success' => true];
        }
        
        return ['error' => 'لم يتم تحديث الصفحة'];
    }
    
    /**
     * حذف صفحة
     */
    public function delete($id) {
        // التحقق من وجود الصفحة
        if (!$this->exists(['id' => $id])) {
            return ['error' => 'الصفحة غير موجودة'];
        }
        
        // التحقق من عدم وجود فواتير مرتبطة بالصفحة
        $invoiceExists = $this->db->exists('invoices', 'page_id = :page_id', ['page_id' => $id]);
        
        if ($invoiceExists) {
            return ['error' => 'لا يمكن حذف الصفحة لوجود فواتير مرتبطة بها'];
        }
        
        // حذف الصفحة
        $deleted = $this->db->delete('pages', 'id = :id', ['id' => $id]);
        
        if ($deleted) {
            return ['success' => true];
        }
        
        return ['error' => 'فشل في حذف الصفحة'];
    }
    
    /**
     * التحقق من وجود صفحة
     */
    public function exists($conditions) {
        $where = [];
        $params = [];
        
        foreach ($conditions as $field => $value) {
            $where[] = "{$field} = :{$field}";
            $params[$field] = $value;
        }
        
        return $this->db->exists('pages', implode(' AND ', $where), $params);
    }
    
    /**
     * الحصول على صفحة بواسطة المعرف
     */
    public function getById($id) {
        $sql = "SELECT p.*, u.username as created_by_username
                FROM pages p
                LEFT JOIN users u ON p.created_by = u.id
                WHERE p.id = :id LIMIT 1";
        return $this->db->fetch($sql, ['id' => $id]);
    }
    
    /**
     * الحصول على صفحة بواسطة الاسم
     */
    public function getByName($name) {
        $sql = "SELECT p.*, u.username as created_by_username
                FROM pages p
                LEFT JOIN users u ON p.created_by = u.id
                WHERE p.name = :name LIMIT 1";
        return $this->db->fetch($sql, ['name' => $name]);
    }
    
    /**
     * الحصول على قائمة الصفحات
     */
    public function getAll($limit = null, $offset = 0, $orderBy = 'name', $orderDir = 'ASC') {
        $sql = "SELECT p.*, u.username as created_by_username, 
                (SELECT COUNT(*) FROM invoices WHERE page_id = p.id) as invoice_count
                FROM pages p
                LEFT JOIN users u ON p.created_by = u.id
                ORDER BY p.{$orderBy} {$orderDir}";
        
        if ($limit !== null) {
            $sql .= " LIMIT {$limit} OFFSET {$offset}";
        }
        
        return $this->db->fetchAll($sql);
    }
    
    /**
     * عدد الصفحات
     */
    public function count() {
        $sql = "SELECT COUNT(*) FROM pages";
        return $this->db->fetchColumn($sql);
    }
    
    /**
     * البحث عن الصفحات
     */
    public function search($keyword, $limit = null, $offset = 0) {
        $keyword = '%' . $keyword . '%';
        
        $sql = "SELECT p.*, u.username as created_by_username, 
                (SELECT COUNT(*) FROM invoices WHERE page_id = p.id) as invoice_count
                FROM pages p
                LEFT JOIN users u ON p.created_by = u.id
                WHERE p.name LIKE :keyword 
                OR p.instagram LIKE :keyword 
                OR p.facebook LIKE :keyword 
                OR p.phone LIKE :keyword
                ORDER BY p.name ASC";
        
        if ($limit !== null) {
            $sql .= " LIMIT {$limit} OFFSET {$offset}";
        }
        
        return $this->db->fetchAll($sql, ['keyword' => $keyword]);
    }
    
    /**
     * الحصول على قائمة الصفحات للقوائم المنسدلة
     */
    public function getDropdownList() {
        $sql = "SELECT id, name FROM pages ORDER BY name ASC";
        return $this->db->fetchAll($sql);
    }
    
    /**
     * الحصول على إحصائيات الصفحات
     */
    public function getStatistics() {
        $stats = [
            'total' => $this->count(),
            'top_pages' => [],
            'recent_pages' => []
        ];
        
        // الصفحات الأكثر مبيعاً
        $sql = "SELECT p.id, p.name, COUNT(i.id) as invoice_count, SUM(i.total_price) as total_sales
                FROM pages p
                LEFT JOIN invoices i ON p.id = i.page_id
                GROUP BY p.id, p.name
                ORDER BY total_sales DESC
                LIMIT 5";
        $stats['top_pages'] = $this->db->fetchAll($sql);
        
        // أحدث الصفحات
        $sql = "SELECT p.*, u.username as created_by_username
                FROM pages p
                LEFT JOIN users u ON p.created_by = u.id
                ORDER BY p.created_at DESC
                LIMIT 5";
        $stats['recent_pages'] = $this->db->fetchAll($sql);
        
        return $stats;
    }
}