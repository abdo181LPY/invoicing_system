<?php
/**
 * نموذج المستخدم
 * يدير عمليات المستخدمين وصلاحياتهم
 */
class User {
    private $db;
    
    /**
     * إنشاء كائن نموذج المستخدم
     */
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * إنشاء مستخدم جديد
     */
    public function create($userData) {
        // التحقق من عدم وجود اسم المستخدم بالفعل
        if ($this->exists(['username' => $userData['username']])) {
            return ['error' => 'اسم المستخدم موجود بالفعل'];
        }
        
        // التحقق من عدم وجود البريد الإلكتروني بالفعل
        if (isset($userData['email']) && !empty($userData['email']) && $this->exists(['email' => $userData['email']])) {
            return ['error' => 'البريد الإلكتروني موجود بالفعل'];
        }
        
        // تشفير كلمة المرور
        $userData['password'] = password_hash($userData['password'], PASSWORD_DEFAULT);
        
        // إضافة حقول إضافية
        $userData['created_at'] = date('Y-m-d H:i:s');
        $userData['last_login'] = null;
        
        // إدراج المستخدم
        $userId = $this->db->insert('users', $userData);
        
        if ($userId) {
            return ['success' => true, 'user_id' => $userId];
        }
        
        return ['error' => 'فشل في إنشاء المستخدم'];
    }
    
    /**
     * تحديث معلومات المستخدم
     */
    public function update($id, $userData) {
        // التحقق من وجود المستخدم
        if (!$this->exists(['id' => $id])) {
            return ['error' => 'المستخدم غير موجود'];
        }
        
        // التحقق من عدم وجود اسم المستخدم لمستخدم آخر
        if (isset($userData['username']) && !empty($userData['username'])) {
            $exists = $this->db->exists('users', 'username = :username AND id != :id', [
                'username' => $userData['username'],
                'id' => $id
            ]);
            
            if ($exists) {
                return ['error' => 'اسم المستخدم موجود بالفعل'];
            }
        }
        
        // التحقق من عدم وجود البريد الإلكتروني لمستخدم آخر
        if (isset($userData['email']) && !empty($userData['email'])) {
            $exists = $this->db->exists('users', 'email = :email AND id != :id', [
                'email' => $userData['email'],
                'id' => $id
            ]);
            
            if ($exists) {
                return ['error' => 'البريد الإلكتروني موجود بالفعل'];
            }
        }
        
        // تشفير كلمة المرور إذا تم تغييرها
        if (isset($userData['password']) && !empty($userData['password'])) {
            $userData['password'] = password_hash($userData['password'], PASSWORD_DEFAULT);
        } else {
            // حذف كلمة المرور من التحديث إذا كانت فارغة
            unset($userData['password']);
        }
        
        // إضافة حقل التحديث
        $userData['updated_at'] = date('Y-m-d H:i:s');
        
        // تحديث المستخدم
        $updated = $this->db->update('users', $userData, 'id = :id', ['id' => $id]);
        
        if ($updated) {
            return ['success' => true];
        }
        
        return ['error' => 'لم يتم تحديث المستخدم'];
    }
    
    /**
     * حذف مستخدم
     */
    public function delete($id) {
        // التحقق من وجود المستخدم
        if (!$this->exists(['id' => $id])) {
            return ['error' => 'المستخدم غير موجود'];
        }
        
        // نقل المستخدم إلى جدول المستخدمين المحذوفين
        $user = $this->getById($id);
        $user['deleted_at'] = date('Y-m-d H:i:s');
        $user['deleted_by'] = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
        
        // إدراج في جدول المستخدمين المحذوفين
        $this->db->insert('deleted_users', $user);
        
        // حذف المستخدم
        $deleted = $this->db->delete('users', 'id = :id', ['id' => $id]);
        
        if ($deleted) {
            return ['success' => true];
        }
        
        return ['error' => 'فشل في حذف المستخدم'];
    }
    
    /**
     * التحقق من وجود مستخدم
     */
    public function exists($conditions) {
        $where = [];
        $params = [];
        
        foreach ($conditions as $field => $value) {
            $where[] = "{$field} = :{$field}";
            $params[$field] = $value;
        }
        
        return $this->db->exists('users', implode(' AND ', $where), $params);
    }
    
    /**
     * الحصول على مستخدم بواسطة المعرف
     */
    public function getById($id) {
        $sql = "SELECT * FROM users WHERE id = :id LIMIT 1";
        return $this->db->fetch($sql, ['id' => $id]);
    }
    
    /**
     * الحصول على مستخدم بواسطة اسم المستخدم
     */
    public function getByUsername($username) {
        $sql = "SELECT * FROM users WHERE username = :username LIMIT 1";
        return $this->db->fetch($sql, ['username' => $username]);
    }
    
    /**
     * الحصول على قائمة المستخدمين
     */
    public function getAll($limit = null, $offset = 0, $orderBy = 'id', $orderDir = 'ASC') {
        $sql = "SELECT * FROM users ORDER BY {$orderBy} {$orderDir}";
        
        if ($limit !== null) {
            $sql .= " LIMIT {$limit} OFFSET {$offset}";
        }
        
        return $this->db->fetchAll($sql);
    }
    
    /**
     * عدد المستخدمين
     */
    public function count() {
        $sql = "SELECT COUNT(*) FROM users";
        return $this->db->fetchColumn($sql);
    }
    
    /**
     * البحث عن المستخدمين
     */
    public function search($keyword, $limit = null, $offset = 0) {
        $keyword = '%' . $keyword . '%';
        
        $sql = "SELECT * FROM users 
                WHERE username LIKE :keyword 
                OR full_name LIKE :keyword 
                OR email LIKE :keyword 
                OR phone LIKE :keyword 
                ORDER BY id DESC";
        
        if ($limit !== null) {
            $sql .= " LIMIT {$limit} OFFSET {$offset}";
        }
        
        return $this->db->fetchAll($sql, ['keyword' => $keyword]);
    }
    
    /**
     * تسجيل دخول المستخدم
     */
    public function login($username, $password, $ipAddress) {
        $auth = Auth::getInstance();
        return $auth->login($username, $password, $ipAddress);
    }
    
    /**
     * تحديث آخر تسجيل دخول
     */
    public function updateLastLogin($id) {
        $this->db->update('users', 
            ['last_login' => date('Y-m-d H:i:s')], 
            'id = :id', 
            ['id' => $id]
        );
    }
    
    /**
     * تغيير كلمة المرور
     */
    public function changePassword($id, $newPassword) {
        $auth = Auth::getInstance();
        return $auth->changePassword($id, $newPassword);
    }
    
    /**
     * تحديث حالة المستخدم (نشط/غير نشط)
     */
    public function updateStatus($id, $isActive) {
        return $this->db->update('users', 
            ['is_active' => $isActive ? 1 : 0], 
            'id = :id', 
            ['id' => $id]
        );
    }
    
    /**
     * تحديث صلاحيات المستخدم
     */
    public function updatePermission($id, $permissionLevel) {
        return $this->db->update('users', 
            ['permission_level' => $permissionLevel], 
            'id = :id', 
            ['id' => $id]
        );
    }
    
    /**
     * الحصول على قائمة طلبات التسجيل
     */
    public function getRegistrationRequests($status = 'pending', $limit = null, $offset = 0) {
        $sql = "SELECT * FROM registration_requests WHERE status = :status ORDER BY registration_date DESC";
        
        if ($limit !== null) {
            $sql .= " LIMIT {$limit} OFFSET {$offset}";
        }
        
        return $this->db->fetchAll($sql, ['status' => $status]);
    }
    
    /**
     * الموافقة على طلب تسجيل
     */
    public function approveRegistration($requestId) {
        $auth = Auth::getInstance();
        return $auth->updateRegistrationStatus($requestId, 'approved');
    }
    
    /**
     * رفض طلب تسجيل
     */
    public function rejectRegistration($requestId, $reason = null) {
        $auth = Auth::getInstance();
        return $auth->updateRegistrationStatus($requestId, 'rejected', $reason);
    }
    
    /**
     * إحصائيات المستخدمين
     */
    public function getStatistics() {
        $stats = [
            'total' => $this->count(),
            'active' => 0,
            'inactive' => 0,
            'admins' => 0,
            'newest' => null,
            'most_active' => null
        ];
        
        // عدد المستخدمين النشطين وغير النشطين
        $sql = "SELECT is_active, COUNT(*) as count FROM users GROUP BY is_active";
        $result = $this->db->fetchAll($sql);
        
        foreach ($result as $row) {
            if ($row['is_active'] == 1) {
                $stats['active'] = $row['count'];
            } else {
                $stats['inactive'] = $row['count'];
            }
        }
        
        // عدد المسؤولين
        $sql = "SELECT COUNT(*) FROM users WHERE permission_level >= 3";
        $stats['admins'] = $this->db->fetchColumn($sql);
        
        // أحدث مستخدم
        $sql = "SELECT * FROM users ORDER BY created_at DESC LIMIT 1";
        $stats['newest'] = $this->db->fetch($sql);
        
        // المستخدم الأكثر نشاطاً (أكثر عدد فواتير)
        $sql = "SELECT u.*, COUNT(i.id) as invoice_count 
                FROM users u 
                LEFT JOIN invoices i ON u.id = i.created_by 
                GROUP BY u.id 
                ORDER BY invoice_count DESC 
                LIMIT 1";
        $stats['most_active'] = $this->db->fetch($sql);
        
        return $stats;
    }
    
    /**
     * الحصول على قائمة المستخدمين حسب مستوى الصلاحيات
     */
    public function getByPermissionLevel($level, $limit = null, $offset = 0) {
        $sql = "SELECT * FROM users WHERE permission_level = :level ORDER BY id DESC";
        
        if ($limit !== null) {
            $sql .= " LIMIT {$limit} OFFSET {$offset}";
        }
        
        return $this->db->fetchAll($sql, ['level' => $level]);
    }
    
    /**
     * الحصول على سجل نشاط المستخدم
     */
    public function getActivityLog($userId, $limit = 50, $offset = 0) {
        $sql = "SELECT * FROM activity_logs WHERE user_id = :user_id ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
        
        return $this->db->fetchAll($sql, [
            'user_id' => $userId,
            'limit' => $limit,
            'offset' => $offset
        ]);
    }
}