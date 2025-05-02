<?php
/**
 * نموذج القائمة السوداء
 * يدير الأرقام المحظورة في القائمة السوداء
 */
class Blacklist {
    private $db;
    
    /**
     * إنشاء كائن نموذج القائمة السوداء
     */
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * إضافة رقم هاتف إلى القائمة السوداء
     */
    public function add($phone, $reason = null) {
        // التحقق من عدم وجود الرقم بالفعل
        if ($this->exists($phone)) {
            return ['error' => 'الرقم موجود بالفعل في القائمة السوداء'];
        }
        
        // إضافة الرقم
        $blacklistData = [
            'phone' => $phone,
            'reason' => $reason,
            'added_by' => isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null,
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $id = $this->db->insert('blacklist', $blacklistData);
        
        if ($id) {
            return ['success' => true, 'id' => $id];
        }
        
        return ['error' => 'فشل في إضافة الرقم إلى القائمة السوداء'];
    }
    
    /**
     * حذف رقم هاتف من القائمة السوداء
     */
    public function remove($id) {
        // التحقق من وجود الرقم
        if (!$this->existsById($id)) {
            return ['error' => 'الرقم غير موجود في القائمة السوداء'];
        }
        
        // حذف الرقم
        $deleted = $this->db->delete('blacklist', 'id = :id', ['id' => $id]);
        
        if ($deleted) {
            return ['success' => true];
        }
        
        return ['error' => 'فشل في حذف الرقم من القائمة السوداء'];
    }
    
    /**
     * تحديث سبب الحظر
     */
    public function updateReason($id, $reason) {
        // التحقق من وجود الرقم
        if (!$this->existsById($id)) {
            return ['error' => 'الرقم غير موجود في القائمة السوداء'];
        }
        
        // تحديث السبب
        $updated = $this->db->update('blacklist', 
            ['reason' => $reason, 'updated_at' => date('Y-m-d H:i:s')], 
            'id = :id', 
            ['id' => $id]
        );
        
        if ($updated) {
            return ['success' => true];
        }
        
        return ['error' => 'فشل في تحديث سبب الحظر'];
    }
    
    /**
     * التحقق من وجود رقم هاتف في القائمة السوداء بواسطة الرقم
     */
    public function exists($phone) {
        return $this->db->exists('blacklist', 'phone = :phone', ['phone' => $phone]);
    }
    
    /**
     * التحقق من وجود رقم هاتف في القائمة السوداء بواسطة المعرف
     */
    public function existsById($id) {
        return $this->db->exists('blacklist', 'id = :id', ['id' => $id]);
    }
    
    /**
     * الحصول على رقم هاتف محظور بواسطة المعرف
     */
    public function getById($id) {
        $sql = "SELECT b.*, u.username as added_by_username
                FROM blacklist b
                LEFT JOIN users u ON b.added_by = u.id
                WHERE b.id = :id LIMIT 1";
        return $this->db->fetch($sql, ['id' => $id]);
    }
    
    /**
     * الحصول على رقم هاتف محظور بواسطة الرقم
     */
    public function getByPhone($phone) {
        $sql = "SELECT b.*, u.username as added_by_username
                FROM blacklist b
                LEFT JOIN users u ON b.added_by = u.id
                WHERE b.phone = :phone LIMIT 1";
        return $this->db->fetch($sql, ['phone' => $phone]);
    }
    
    /**
     * الحصول على قائمة الأرقام المحظورة
     */
    public function getAll($limit = null, $offset = 0, $orderBy = 'id', $orderDir = 'DESC') {
        $sql = "SELECT b.*, u.username as added_by_username
                FROM blacklist b
                LEFT JOIN users u ON b.added_by = u.id
                ORDER BY b.{$orderBy} {$orderDir}";
        
        if ($limit !== null) {
            $sql .= " LIMIT {$limit} OFFSET {$offset}";
        }
        
        return $this->db->fetchAll($sql);
    }
    
    /**
     * عدد الأرقام المحظورة
     */
    public function count() {
        $sql = "SELECT COUNT(*) FROM blacklist";
        return $this->db->fetchColumn($sql);
    }
    
    /**
     * البحث عن الأرقام المحظورة
     */
    public function search($keyword, $limit = null, $offset = 0) {
        $keyword = '%' . $keyword . '%';
        
        $sql = "SELECT b.*, u.username as added_by_username
                FROM blacklist b
                LEFT JOIN users u ON b.added_by = u.id
                WHERE b.phone LIKE :keyword OR b.reason LIKE :keyword
                ORDER BY b.id DESC";
        
        if ($limit !== null) {
            $sql .= " LIMIT {$limit} OFFSET {$offset}";
        }
        
        return $this->db->fetchAll($sql, ['keyword' => $keyword]);
    }
    
    /**
     * استيراد مجموعة من الأرقام المحظورة من ملف
     */
    public function importFromFile($filePath, $reason = null) {
        // التحقق من وجود الملف
        if (!file_exists($filePath)) {
            return ['error' => 'الملف غير موجود'];
        }
        
        // قراءة الملف
        $phones = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        // بدء المعاملة
        $this->db->beginTransaction();
        
        try {
            $added = 0;
            $skipped = 0;
            
            foreach ($phones as $phone) {
                // تنظيف الرقم
                $phone = trim($phone);
                
                // التحقق من صحة الرقم
                if (!preg_match('/^07[0-9]{9}$/', $phone)) {
                    $skipped++;
                    continue;
                }
                
                // التحقق من عدم وجود الرقم بالفعل
                if ($this->exists($phone)) {
                    $skipped++;
                    continue;
                }
                
                // إضافة الرقم
                $blacklistData = [
                    'phone' => $phone,
                    'reason' => $reason,
                    'added_by' => isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null,
                    'created_at' => date('Y-m-d H:i:s')
                ];
                
                $this->db->insert('blacklist', $blacklistData);
                $added++;
            }
            
            // تنفيذ المعاملة
            $this->db->commit();
            
            return [
                'success' => true,
                'added' => $added,
                'skipped' => $skipped,
                'total' => count($phones)
            ];
        } catch (Exception $e) {
            // التراجع عن المعاملة في حالة حدوث خطأ
            $this->db->rollback();
            return ['error' => 'فشل في استيراد الأرقام: ' . $e->getMessage()];
        }
    }
    
    /**
     * تصدير قائمة الأرقام المحظورة إلى ملف
     */
    public function exportToFile($filePath) {
        // الحصول على جميع الأرقام المحظورة
        $blacklist = $this->getAll();
        
        // التحقق من وجود أرقام
        if (empty($blacklist)) {
            return ['error' => 'لا توجد أرقام محظورة للتصدير'];
        }
        
        // فتح الملف للكتابة
        $file = fopen($filePath, 'w');
        
        if (!$file) {
            return ['error' => 'فشل في فتح الملف للكتابة'];
        }
        
        // كتابة العناوين
        fputcsv($file, ['الرقم', 'السبب', 'تاريخ الإضافة', 'أضيف بواسطة']);
        
        // كتابة البيانات
        foreach ($blacklist as $item) {
            fputcsv($file, [
                $item['phone'],
                $item['reason'],
                $item['created_at'],
                $item['added_by_username']
            ]);
        }
        
        // إغلاق الملف
        fclose($file);
        
        return [
            'success' => true,
            'count' => count($blacklist),
            'file_path' => $filePath
        ];
    }
}