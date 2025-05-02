<?php
class Log {
    private $db;
    private $table = 'system_logs';

    // أنواع السجلات
    const TYPE_LOGIN = 'login';
    const TYPE_LOGOUT = 'logout';
    const TYPE_CREATE = 'create';
    const TYPE_UPDATE = 'update';
    const TYPE_DELETE = 'delete';
    const TYPE_RESTORE = 'restore';
    const TYPE_ERROR = 'error';
    const TYPE_WARNING = 'warning';
    const TYPE_INFO = 'info';

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * إضافة سجل جديد
     * 
     * @param string $type نوع السجل
     * @param string $action العملية
     * @param string $details تفاصيل إضافية
     * @param int $userId معرف المستخدم (إذا كان مسجل دخول)
     * @param string $ipAddress عنوان IP (اختياري)
     * @return bool نتيجة العملية
     */
    public function addLog($type, $action, $details, $userId = null, $ipAddress = null) {
        if (!$ipAddress) {
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
        }

        try {
            $stmt = $this->db->prepare("
                INSERT INTO {$this->table} (
                    type, action, details, user_id, ip_address, created_at
                ) VALUES (
                    :type, :action, :details, :user_id, :ip_address, NOW()
                )
            ");
            
            $stmt->bindParam(':type', $type, PDO::PARAM_STR);
            $stmt->bindParam(':action', $action, PDO::PARAM_STR);
            $stmt->bindParam(':details', $details, PDO::PARAM_STR);
            $stmt->bindParam(':user_id', $userId, $userId ? PDO::PARAM_INT : PDO::PARAM_NULL);
            $stmt->bindParam(':ip_address', $ipAddress, PDO::PARAM_STR);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("خطأ في إضافة سجل: " . $e->getMessage());
            return false;
        }
    }

    /**
     * تسجيل عملية تسجيل الدخول
     */
    public function logLogin($userId, $username, $success, $ipAddress = null) {
        $status = $success ? 'نجاح' : 'فشل';
        $details = "محاولة تسجيل دخول {$status} للمستخدم: {$username}";
        return $this->addLog(self::TYPE_LOGIN, "تسجيل دخول", $details, $userId, $ipAddress);
    }

    /**
     * تسجيل عملية تسجيل الخروج
     */
    public function logLogout($userId, $username) {
        $details = "تسجيل خروج للمستخدم: {$username}";
        return $this->addLog(self::TYPE_LOGOUT, "تسجيل خروج", $details, $userId);
    }

    /**
     * تسجيل عملية إنشاء فاتورة
     */
    public function logInvoiceCreated($userId, $invoiceId, $customerName) {
        $details = "تم إنشاء فاتورة جديدة برقم: {$invoiceId} للزبون: {$customerName}";
        return $this->addLog(self::TYPE_CREATE, "إنشاء فاتورة", $details, $userId);
    }

    /**
     * تسجيل عملية حذف فاتورة
     */
    public function logInvoiceDeleted($userId, $invoiceId, $customerName) {
        $details = "تم حذف الفاتورة رقم: {$invoiceId} للزبون: {$customerName}";
        return $this->addLog(self::TYPE_DELETE, "حذف فاتورة", $details, $userId);
    }

    /**
     * تسجيل عملية استعادة فاتورة
     */
    public function logInvoiceRestored($userId, $invoiceId, $customerName) {
        $details = "تم استعادة الفاتورة رقم: {$invoiceId} للزبون: {$customerName}";
        return $this->addLog(self::TYPE_RESTORE, "استعادة فاتورة", $details, $userId);
    }

    /**
     * تسجيل عملية تغيير حالة فاتورة
     */
    public function logInvoiceStatusChange($userId, $invoiceId, $oldStatus, $newStatus) {
        $details = "تم تغيير حالة الفاتورة رقم: {$invoiceId} من: {$oldStatus} إلى: {$newStatus}";
        return $this->addLog(self::TYPE_UPDATE, "تحديث حالة فاتورة", $details, $userId);
    }

    /**
     * تسجيل عملية إضافة مستخدم
     */
    public function logUserCreated($userId, $createdUsername) {
        $details = "تم إنشاء مستخدم جديد: {$createdUsername}";
        return $this->addLog(self::TYPE_CREATE, "إنشاء مستخدم", $details, $userId);
    }

    /**
     * تسجيل عملية تغيير صلاحيات مستخدم
     */
    public function logUserPermissionChange($userId, $targetUsername, $oldPermission, $newPermission) {
        $details = "تم تغيير صلاحيات المستخدم: {$targetUsername} من: {$oldPermission} إلى: {$newPermission}";
        return $this->addLog(self::TYPE_UPDATE, "تحديث صلاحيات", $details, $userId);
    }

    /**
     * تسجيل خطأ في النظام
     */
    public function logError($error, $userId = null) {
        return $this->addLog(self::TYPE_ERROR, "خطأ في النظام", $error, $userId);
    }

    /**
     * الحصول على سجلات النظام
     */
    public function getLogs($limit = 100, $offset = 0, $type = null) {
        $sql = "
            SELECT l.*, u.username 
            FROM {$this->table} l
            LEFT JOIN users u ON l.user_id = u.id
        ";
        
        $params = [];
        
        if ($type) {
            $sql .= " WHERE l.type = :type";
            $params[':type'] = $type;
        }
        
        $sql .= " ORDER BY l.created_at DESC LIMIT :limit OFFSET :offset";
        $params[':limit'] = $limit;
        $params[':offset'] = $offset;
        
        $stmt = $this->db->prepare($sql);
        
        foreach ($params as $key => $value) {
            if ($key == ':limit' || $key == ':offset') {
                $stmt->bindValue($key, $value, PDO::PARAM_INT);
            } else {
                $stmt->bindValue($key, $value, PDO::PARAM_STR);
            }
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * البحث في سجلات النظام
     */
    public function searchLogs($searchTerm, $limit = 100) {
        $searchTerm = '%' . $searchTerm . '%';
        
        $stmt = $this->db->prepare("
            SELECT l.*, u.username 
            FROM {$this->table} l
            LEFT JOIN users u ON l.user_id = u.id
            WHERE l.action LIKE :search_term 
               OR l.details LIKE :search_term 
               OR u.username LIKE :search_term 
               OR l.ip_address LIKE :search_term
            ORDER BY l.created_at DESC
            LIMIT :limit
        ");
        
        $stmt->bindParam(':search_term', $searchTerm, PDO::PARAM_STR);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * الحصول على إحصائيات السجلات
     */
    public function getLogStatistics() {
        $stats = [];
        
        // إحصائيات حسب النوع
        $stmt = $this->db->query("
            SELECT type, COUNT(*) as count 
            FROM {$this->table} 
            GROUP BY type
            ORDER BY count DESC
        ");
        $stats['by_type'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // إحصائيات حسب المستخدم (أعلى 10)
        $stmt = $this->db->query("
            SELECT u.username, COUNT(l.id) as count 
            FROM {$this->table} l
            JOIN users u ON l.user_id = u.id
            GROUP BY l.user_id
            ORDER BY count DESC
            LIMIT 10
        ");
        $stats['by_user'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // إحصائيات حسب اليوم (آخر 7 أيام)
        $stmt = $this->db->query("
            SELECT DATE(created_at) as date, COUNT(*) as count 
            FROM {$this->table}
            WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
            GROUP BY DATE(created_at)
            ORDER BY date DESC
        ");
        $stats['by_date'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // إحصائيات حسب عنوان IP (أعلى 10)
        $stmt = $this->db->query("
            SELECT ip_address, COUNT(*) as count 
            FROM {$this->table}
            GROUP BY ip_address
            ORDER BY count DESC
            LIMIT 10
        ");
        $stats['by_ip'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return $stats;
    }
}
