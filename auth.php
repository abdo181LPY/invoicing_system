<?php
/**
 * فئة المصادقة وإدارة المستخدمين
 * تدير عمليات تسجيل الدخول والخروج والتحقق من الصلاحيات
 */
class Auth {
    private $db;
    private static $instance = null;
    
    /**
     * منع إنشاء كائن مباشرة (نمط Singleton)
     */
    private function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * منع نسخ الكائن
     */
    private function __clone() {}
    
    /**
     * الحصول على كائن وحيد من المصادقة
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * تسجيل دخول المستخدم
     */
    public function login($username, $password, $ipAddress) {
        // التحقق من وجود المستخدم
        $sql = "SELECT * FROM users WHERE username = :username LIMIT 1";
        $user = $this->db->fetch($sql, ['username' => $username]);
        
        // التحقق من صحة كلمة المرور
        if ($user && password_verify($password, $user['password'])) {
            // التحقق من عنوان IP
            if ($user['ip_address'] === null || $user['ip_address'] === $ipAddress) {
                // تحديث عنوان IP إذا كان فارغاً
                if ($user['ip_address'] === null) {
                    $this->db->update('users', 
                        ['ip_address' => $ipAddress], 
                        'id = :id', 
                        ['id' => $user['id']]
                    );
                }
                
                // تسجيل بيانات المستخدم في الجلسة
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['permission_level'] = $user['permission_level'];
                $_SESSION['last_activity'] = time();
                
                // تسجيل تسجيل الدخول
                $this->logActivity($user['id'], 'تسجيل دخول', 'تم تسجيل الدخول بنجاح');
                
                return true;
            } else {
                // عنوان IP غير مطابق
                return ['error' => 'عنوان IP غير مطابق للعنوان المسجل. يرجى التواصل مع الإدارة.'];
            }
        }
        
        return false;
    }
    
    /**
     * تسجيل مستخدم جديد
     */
    public function register($userData) {
        // التحقق من عدم وجود اسم المستخدم بالفعل
        $exists = $this->db->exists('users', 'username = :username', ['username' => $userData['username']]);
        if ($exists) {
            return ['error' => 'اسم المستخدم موجود بالفعل'];
        }
        
        // تشفير كلمة المرور
        $userData['password'] = password_hash($userData['password'], PASSWORD_DEFAULT);
        
        // إدراج المستخدم في جدول طلبات التسجيل
        $userData['registration_date'] = date('Y-m-d H:i:s');
        $userData['status'] = 'pending'; // قيد الانتظار
        
        $registrationId = $this->db->insert('registration_requests', $userData);
        
        if ($registrationId) {
            return ['success' => 'تم إرسال طلب التسجيل بنجاح. سيتم مراجعته من قبل الإدارة.'];
        }
        
        return ['error' => 'حدث خطأ أثناء التسجيل'];
    }
    
    /**
     * تسجيل خروج المستخدم
     */
    public function logout() {
        // تسجيل تسجيل الخروج إذا كان مسجل دخول
        if ($this->isLoggedIn()) {
            $this->logActivity($_SESSION['user_id'], 'تسجيل خروج', 'تم تسجيل الخروج بنجاح');
        }
        
        // تدمير الجلسة
        session_unset();
        session_destroy();
        
        return true;
    }
    
    /**
     * التحقق من تسجيل دخول المستخدم
     */
    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
    
    /**
     * الحصول على بيانات المستخدم الحالي
     */
    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }
        
        $sql = "SELECT * FROM users WHERE id = :id LIMIT 1";
        return $this->db->fetch($sql, ['id' => $_SESSION['user_id']]);
    }
    
    /**
     * الحصول على معرف المستخدم الحالي
     */
    public function getCurrentUserId() {
        return $this->isLoggedIn() ? $_SESSION['user_id'] : null;
    }
    
    /**
     * التحقق من صلاحيات المستخدم
     */
    public function hasPermission($level) {
        if (!$this->isLoggedIn()) {
            return false;
        }
        
        return $_SESSION['permission_level'] >= $level;
    }
    
    /**
     * التحقق من صلاحية حذف الفاتورة
     */
    public function canDeleteInvoice($invoiceId) {
        if (!$this->isLoggedIn()) {
            return false;
        }
        
        $userId = $_SESSION['user_id'];
        $permissionLevel = $_SESSION['permission_level'];
        
        // المستوى الثالث: يمكن حذف أي فاتورة
        if ($permissionLevel >= 3) {
            return true;
        }
        
        // المستوى الثاني: يمكن حذف أي فاتورة ما عدا التي تحتوي على رقم سلة
        if ($permissionLevel == 2) {
            $sql = "SELECT 1 FROM invoices WHERE id = :id AND cart_number IS NULL LIMIT 1";
            return $this->db->exists($sql, ['id' => $invoiceId]);
        }
        
        // المستوى الأول: يمكن حذف الفواتير التي أنشأها فقط
        if ($permissionLevel == 1) {
            $sql = "SELECT 1 FROM invoices WHERE id = :id AND created_by = :user_id LIMIT 1";
            return $this->db->exists($sql, ['id' => $invoiceId, 'user_id' => $userId]);
        }
        
        return false;
    }
    
    /**
     * تحديث حالة طلب التسجيل
     */
    public function updateRegistrationStatus($requestId, $status, $notes = null) {
        // الحصول على بيانات الطلب
        $sql = "SELECT * FROM registration_requests WHERE id = :id LIMIT 1";
        $request = $this->db->fetch($sql, ['id' => $requestId]);
        
        if (!$request) {
            return ['error' => 'طلب التسجيل غير موجود'];
        }
        
        if ($status === 'approved') {
            // نقل بيانات المستخدم إلى جدول المستخدمين
            $userData = [
                'username' => $request['username'],
                'password' => $request['password'], // كلمة المرور مشفرة بالفعل
                'email' => $request['email'],
                'phone' => $request['phone'],
                'ip_address' => $request['ip_address'],
                'permission_level' => 1, // مستوى الصلاحية الافتراضي
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            $userId = $this->db->insert('users', $userData);
            
            if (!$userId) {
                return ['error' => 'فشل في إنشاء حساب المستخدم'];
            }
        }
        
        // تحديث حالة الطلب
        $updateData = [
            'status' => $status,
            'processed_at' => date('Y-m-d H:i:s'),
            'processed_by' => $this->getCurrentUserId(),
            'notes' => $notes
        ];
        
        $this->db->update('registration_requests', $updateData, 'id = :id', ['id' => $requestId]);
        
        return ['success' => 'تم تحديث حالة طلب التسجيل بنجاح'];
    }
    
    /**
     * تغيير كلمة مرور المستخدم
     */
    public function changePassword($userId, $newPassword) {
        // تشفير كلمة المرور الجديدة
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        
        // تحديث كلمة المرور
        $updated = $this->db->update('users', 
            ['password' => $hashedPassword], 
            'id = :id', 
            ['id' => $userId]
        );
        
        if ($updated) {
            $this->logActivity($userId, 'تغيير كلمة المرور', 'تم تغيير كلمة المرور بنجاح');
            return true;
        }
        
        return false;
    }
    
    /**
     * تسجيل نشاط المستخدم
     */
    private function logActivity($userId, $action, $details = null) {
        $logData = [
            'user_id' => $userId,
            'action' => $action,
            'details' => $details,
            'ip_address' => $_SERVER['REMOTE_ADDR'],
            'user_agent' => $_SERVER['HTTP_USER_AGENT'],
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $this->db->insert('activity_logs', $logData);
    }
}
