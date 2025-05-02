<?php
class NotificationController {
    private $db;
    private $user;
    private $log;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->user = new User();
        $this->log = new Log();
    }

    /**
     * الحصول على الإشعارات غير المقروءة للمستخدم الحالي
     */
    public function getUnreadNotificationsAction() {
        // التحقق من تسجيل الدخول
        if (!Auth::isLoggedIn()) {
            $this->jsonResponse(['error' => 'غير مصرح بالوصول']);
            return;
        }

        $userId = Auth::getUserId();
        $notifications = $this->getNotifications($userId, true);

        $this->jsonResponse([
            'success' => true,
            'data' => $notifications,
            'count' => count($notifications)
        ]);
    }

    /**
     * الحصول على جميع الإشعارات للمستخدم الحالي
     */
    public function getAllNotificationsAction() {
        // التحقق من تسجيل الدخول
        if (!Auth::isLoggedIn()) {
            $this->jsonResponse(['error' => 'غير مصرح بالوصول']);
            return;
        }

        $userId = Auth::getUserId();
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
        $offset = ($page - 1) * $limit;

        $notifications = $this->getNotifications($userId, false, $limit, $offset);
        $totalCount = $this->getNotificationsCount($userId);

        $this->jsonResponse([
            'success' => true,
            'data' => $notifications,
            'total' => $totalCount,
            'page' => $page,
            'pages' => ceil($totalCount / $limit)
        ]);
    }

    /**
     * تعليم إشعار كمقروء
     */
    public function markAsReadAction() {
        // التحقق من تسجيل الدخول
        if (!Auth::isLoggedIn()) {
            $this->jsonResponse(['error' => 'غير مصرح بالوصول']);
            return;
        }

        // التحقق من طريقة الطلب
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'طريقة الطلب غير صحيحة']);
            return;
        }

        $notificationId = isset($_POST['notification_id']) ? (int)$_POST['notification_id'] : 0;
        
        if ($notificationId <= 0) {
            $this->jsonResponse(['error' => 'معرف الإشعار غير صالح']);
            return;
        }

        $userId = Auth::getUserId();
        $result = $this->markNotificationAsRead($notificationId, $userId);

        if ($result) {
            $this->jsonResponse([
                'success' => true,
                'message' => 'تم تعليم الإشعار كمقروء'
            ]);
        } else {
            $this->jsonResponse(['error' => 'حدث خطأ أثناء تعليم الإشعار كمقروء']);
        }
    }

    /**
     * تعليم جميع الإشعارات كمقروءة
     */
    public function markAllAsReadAction() {
        // التحقق من تسجيل الدخول
        if (!Auth::isLoggedIn()) {
            $this->jsonResponse(['error' => 'غير مصرح بالوصول']);
            return;
        }

        // التحقق من طريقة الطلب
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'طريقة الطلب غير صحيحة']);
            return;
        }

        $userId = Auth::getUserId();
        $result = $this->markAllNotificationsAsRead($userId);

        if ($result) {
            $this->jsonResponse([
                'success' => true,
                'message' => 'تم تعليم جميع الإشعارات كمقروءة'
            ]);
        } else {
            $this->jsonResponse(['error' => 'حدث خطأ أثناء تعليم الإشعارات كمقروءة']);
        }
    }

    /**
     * إنشاء إشعار جديد
     */
    public function createNotificationAction() {
        // التحقق من تسجيل الدخول والصلاحيات
        if (!Auth::isLoggedIn() || !Auth::hasPermission('admin')) {
            $this->jsonResponse(['error' => 'غير مصرح بالوصول']);
            return;
        }

        // التحقق من طريقة الطلب
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'طريقة الطلب غير صحيحة']);
            return;
        }

        // استلام بيانات الإشعار
        $title = $_POST['title'] ?? '';
        $message = $_POST['message'] ?? '';
        $type = $_POST['type'] ?? 'info';
        $link = $_POST['link'] ?? '';
        $userIds = isset($_POST['user_ids']) ? explode(',', $_POST['user_ids']) : [];
        $roles = isset($_POST['roles']) ? explode(',', $_POST['roles']) : [];

        // التحقق من صحة البيانات
        if (empty($title) || empty($message)) {
            $this->jsonResponse(['error' => 'يرجى ملء جميع الحقول المطلوبة']);
            return;
        }

        // إنشاء الإشعار
        $result = $this->createNotification($title, $message, $type, $link, $userIds, $roles);

        if ($result) {
            $this->jsonResponse([
                'success' => true,
                'message' => 'تم إنشاء الإشعار بنجاح'
            ]);
        } else {
            $this->jsonResponse(['error' => 'حدث خطأ أثناء إنشاء الإشعار']);
        }
    }

    /**
     * إنشاء إشعار لحدث في النظام
     */
    public function createSystemNotification($title, $message, $type = 'info', $link = '', $userIds = [], $roles = []) {
        return $this->createNotification($title, $message, $type, $link, $userIds, $roles);
    }

    /**
     * حذف إشعار
     */
    public function deleteNotificationAction() {
        // التحقق من تسجيل الدخول والصلاحيات
        if (!Auth::isLoggedIn() || !Auth::hasPermission('admin')) {
            $this->jsonResponse(['error' => 'غير مصرح بالوصول']);
            return;
        }

        // التحقق من طريقة الطلب
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'طريقة الطلب غير صحيحة']);
            return;
        }

        $notificationId = isset($_POST['notification_id']) ? (int)$_POST['notification_id'] : 0;
        
        if ($notificationId <= 0) {
            $this->jsonResponse(['error' => 'معرف الإشعار غير صالح']);
            return;
        }

        $result = $this->deleteNotification($notificationId);

        if ($result) {
            $this->jsonResponse([
                'success' => true,
                'message' => 'تم حذف الإشعار بنجاح'
            ]);
        } else {
            $this->jsonResponse(['error' => 'حدث خطأ أثناء حذف الإشعار']);
        }
    }

    /**
     * إعدادات الإشعارات
     */
    public function settingsAction() {
        // التحقق من تسجيل الدخول
        if (!Auth::isLoggedIn()) {
            Session::set('flash_message', 'يجب تسجيل الدخول للوصول إلى إعدادات الإشعارات');
            Session::set('flash_type', 'error');
            Router::redirect('/auth/login');
            return;
        }

        $userId = Auth::getUserId();
        $user = $this->user->getUserById($userId);
        
        if (!$user) {
            Session::set('flash_message', 'حدث خطأ أثناء الوصول إلى بيانات المستخدم');
            Session::set('flash_type', 'error');
            Router::redirect('/dashboard');
            return;
        }

        // الحصول على إعدادات الإشعارات الحالية
        $settings = $this->getNotificationSettings($userId);

        // بيانات الصفحة
        $pageData = [
            'title' => 'إعدادات الإشعارات',
            'settings' => $settings
        ];

        // عرض صفحة الإعدادات
        include(ROOT_PATH . '/views/notifications/settings.php');
    }

    /**
     * حفظ إعدادات الإشعارات
     */
    public function saveSettingsAction() {
        // التحقق من تسجيل الدخول
        if (!Auth::isLoggedIn()) {
            Session::set('flash_message', 'يجب تسجيل الدخول لحفظ إعدادات الإشعارات');
            Session::set('flash_type', 'error');
            Router::redirect('/auth/login');
            return;
        }

        // التحقق من طريقة الطلب
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Session::set('flash_message', 'طريقة الطلب غير صحيحة');
            Session::set('flash_type', 'error');
            Router::redirect('/notifications/settings');
            return;
        }

        $userId = Auth::getUserId();
        
        // استلام الإعدادات
        $settings = [
            'invoice_created' => isset($_POST['invoice_created']),
            'invoice_deleted' => isset($_POST['invoice_deleted']),
            'status_changed' => isset($_POST['status_changed']),
            'user_created' => isset($_POST['user_created']),
            'blacklist_updated' => isset($_POST['blacklist_updated']),
            'email_notifications' => isset($_POST['email_notifications']),
            'browser_notifications' => isset($_POST['browser_notifications'])
        ];
        
        // حفظ الإعدادات
        $result = $this->saveNotificationSettings($userId, $settings);
        
        if ($result) {
            Session::set('flash_message', 'تم حفظ إعدادات الإشعارات بنجاح');
            Session::set('flash_type', 'success');
        } else {
            Session::set('flash_message', 'حدث خطأ أثناء حفظ إعدادات الإشعارات');
            Session::set('flash_type', 'error');
        }
        
        Router::redirect('/notifications/settings');
    }

    /**
     * عرض صفحة الإشعارات
     */
    public function indexAction() {
        // التحقق من تسجيل الدخول
        if (!Auth::isLoggedIn()) {
            Session::set('flash_message', 'يجب تسجيل الدخول للوصول إلى صفحة الإشعارات');
            Session::set('flash_type', 'error');
            Router::redirect('/auth/login');
            return;
        }

        $userId = Auth::getUserId();
        
        // بيانات الصفحة
        $pageData = [
            'title' => 'الإشعارات',
            'unread_count' => $this->getUnreadNotificationsCount($userId)
        ];

        // عرض صفحة الإشعارات
        include(ROOT_PATH . '/views/notifications/index.php');
    }

    /**
     * الحصول على إشعارات المستخدم
     */
    private function getNotifications($userId, $unreadOnly = false, $limit = 20, $offset = 0) {
        $sql = "
            SELECT n.*, un.is_read, un.read_at
            FROM notifications n
            JOIN user_notifications un ON n.id = un.notification_id
            WHERE un.user_id = :user_id
        ";
        
        if ($unreadOnly) {
            $sql .= " AND un.is_read = 0";
        }
        
        $sql .= " ORDER BY n.created_at DESC LIMIT :limit OFFSET :offset";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * الحصول على عدد إشعارات المستخدم
     */
    private function getNotificationsCount($userId) {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count
            FROM user_notifications
            WHERE user_id = :user_id
        ");
        
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['count'] : 0;
    }

    /**
     * الحصول على عدد الإشعارات غير المقروءة للمستخدم
     */
    private function getUnreadNotificationsCount($userId) {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count
            FROM user_notifications
            WHERE user_id = :user_id AND is_read = 0
        ");
        
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['count'] : 0;
    }

    /**
     * تعليم إشعار كمقروء
     */
    private function markNotificationAsRead($notificationId, $userId) {
        $stmt = $this->db->prepare("
            UPDATE user_notifications
            SET is_read = 1, read_at = NOW()
            WHERE notification_id = :notification_id AND user_id = :user_id
        ");
        
        $stmt->bindParam(':notification_id', $notificationId, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        
        return $stmt->execute();
    }

    /**
     * تعليم جميع إشعارات المستخدم كمقروءة
     */
    private function markAllNotificationsAsRead($userId) {
        $stmt = $this->db->prepare("
            UPDATE user_notifications
            SET is_read = 1, read_at = NOW()
            WHERE user_id = :user_id AND is_read = 0
        ");
        
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        
        return $stmt->execute();
    }

    /**
     * إنشاء إشعار جديد
     */
    private function createNotification($title, $message, $type = 'info', $link = '', $userIds = [], $roles = []) {
        try {
            $this->db->beginTransaction();
            
            // إدراج الإشعار
            $stmt = $this->db->prepare("
                INSERT INTO notifications (
                    title, message, type, link, created_by, created_at
                ) VALUES (
                    :title, :message, :type, :link, :created_by, NOW()
                )
            ");
            
            $createdBy = Auth::isLoggedIn() ? Auth::getUserId() : null;
            
            $stmt->bindParam(':title', $title, PDO::PARAM_STR);
            $stmt->bindParam(':message', $message, PDO::PARAM_STR);
            $stmt->bindParam(':type', $type, PDO::PARAM_STR);
            $stmt->bindParam(':link', $link, PDO::PARAM_STR);
            $stmt->bindParam(':created_by', $createdBy, $createdBy ? PDO::PARAM_INT : PDO::PARAM_NULL);
            
            $stmt->execute();
            $notificationId = $this->db->lastInsertId();
            
            // تحديد المستخدمين المستهدفين
            $targetUsers = [];
            
            // إذا تم تحديد مستخدمين محددين
            if (!empty($userIds)) {
                foreach ($userIds as $userId) {
                    $targetUsers[] = (int)$userId;
                }
            }
            
            // إذا تم تحديد أدوار
            if (!empty($roles)) {
                $roleUsers = $this->getUsersByRoles($roles);
                foreach ($roleUsers as $user) {
                    if (!in_array($user['id'], $targetUsers)) {
                        $targetUsers[] = $user['id'];
                    }
                }
            }
            
            // إذا لم يتم تحديد أي مستخدمين أو أدوار، أرسل الإشعار إلى جميع المستخدمين
            if (empty($targetUsers)) {
                $allUsers = $this->user->getAllUsers();
                foreach ($allUsers as $user) {
                    $targetUsers[] = $user['id'];
                }
            }
            
            // إنشاء إشعارات المستخدمين
            if (!empty($targetUsers)) {
                $insertValues = [];
                $insertParams = [];
                
                foreach ($targetUsers as $index => $userId) {
                    $insertValues[] = "(:notification_id, :user_id_{$index}, 0, NULL)";
                    $insertParams[":user_id_{$index}"] = $userId;
                }
                
                $insertSql = "
                    INSERT INTO user_notifications (notification_id, user_id, is_read, read_at)
                    VALUES " . implode(', ', $insertValues);
                
                $stmt = $this->db->prepare($insertSql);
                $stmt->bindParam(':notification_id', $notificationId, PDO::PARAM_INT);
                
                foreach ($insertParams as $param => $value) {
                    $stmt->bindValue($param, $value, PDO::PARAM_INT);
                }
                
                $stmt->execute();
            }
            
            $this->db->commit();
            
            // تسجيل إنشاء الإشعار
            $this->log->addLog(
                Log::TYPE_INFO,
                'إنشاء إشعار',
                "تم إنشاء إشعار جديد: {$title}",
                $createdBy
            );
            
            return true;
            
        } catch (PDOException $e) {
            $this->db->rollBack();
            $this->log->logError("خطأ في إنشاء الإشعار: " . $e->getMessage(), Auth::getUserId());
            return false;
        }
    }

    /**
     * حذف إشعار
     */
    private function deleteNotification($notificationId) {
        try {
            $this->db->beginTransaction();
            
            // حذف إشعارات المستخدمين أولاً
            $stmt = $this->db->prepare("
                DELETE FROM user_notifications
                WHERE notification_id = :notification_id
            ");
            
            $stmt->bindParam(':notification_id', $notificationId, PDO::PARAM_INT);
            $stmt->execute();
            
            // ثم حذف الإشعار نفسه
            $stmt = $this->db->prepare("
                DELETE FROM notifications
                WHERE id = :notification_id
            ");
            
            $stmt->bindParam(':notification_id', $notificationId, PDO::PARAM_INT);
            $stmt->execute();
            
            $this->db->commit();
            
            // تسجيل حذف الإشعار
            $this->log->addLog(
                Log::TYPE_INFO,
                'حذف إشعار',
                "تم حذف الإشعار رقم: {$notificationId}",
                Auth::getUserId()
            );
            
            return true;
            
        } catch (PDOException $e) {
            $this->db->rollBack();
            $this->log->logError("خطأ في حذف الإشعار: " . $e->getMessage(), Auth::getUserId());
            return false;
        }
    }

    /**
     * الحصول على المستخدمين حسب الأدوار
     */
    private function getUsersByRoles($roles) {
        if (empty($roles)) {
            return [];
        }
        
        $placeholders = implode(',', array_fill(0, count($roles), '?'));
        
        $sql = "
            SELECT id, username, email
            FROM users
            WHERE role IN ({$placeholders})
        ";
        
        $stmt = $this->db->prepare($sql);
        
        foreach ($roles as $index => $role) {
            $stmt->bindValue($index + 1, $role, PDO::PARAM_STR);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * الحصول على إعدادات الإشعارات للمستخدم
     */
    private function getNotificationSettings($userId) {
        $stmt = $this->db->prepare("
            SELECT settings
            FROM user_preferences
            WHERE user_id = :user_id AND preference_type = 'notifications'
        ");
        
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result && !empty($result['settings'])) {
            return json_decode($result['settings'], true);
        }
        
        // القيم الافتراضية
        return [
            'invoice_created' => true,
            'invoice_deleted' => true,
            'status_changed' => true,
            'user_created' => true,
            'blacklist_updated' => true,
            'email_notifications' => true,
            'browser_notifications' => true
        ];
    }

    /**
     * حفظ إعدادات الإشعارات للمستخدم
     */
    private function saveNotificationSettings($userId, $settings) {
        $settingsJson = json_encode($settings);
        
        // التحقق من وجود إعدادات سابقة
        $stmt = $this->db->prepare("
            SELECT id
            FROM user_preferences
            WHERE user_id = :user_id AND preference_type = 'notifications'
        ");
        
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        
        $existingSettings = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existingSettings) {
            // تحديث الإعدادات الموجودة
            $stmt = $this->db->prepare("
                UPDATE user_preferences
                SET settings = :settings, updated_at = NOW()
                WHERE id = :id
            ");
            
            $stmt->bindParam(':id', $existingSettings['id'], PDO::PARAM_INT);
            $stmt->bindParam(':settings', $settingsJson, PDO::PARAM_STR);
            
        } else {
            // إنشاء إعدادات جديدة
            $stmt = $this->db->prepare("
                INSERT INTO user_preferences (
                    user_id, preference_type, settings, created_at, updated_at
                ) VALUES (
                    :user_id, 'notifications', :settings, NOW(), NOW()
                )
            ");
            
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':settings', $settingsJson, PDO::PARAM_STR);
        }
        
        $result = $stmt->execute();
        
        if ($result) {
            // تسجيل تحديث الإعدادات
            $this->log->addLog(
                Log::TYPE_INFO,
                'تحديث إعدادات الإشعارات',
                "تم تحديث إعدادات الإشعارات",
                $userId
            );
        }
        
        return $result;
    }

    /**
     * إرجاع استجابة JSON
     */
    private function jsonResponse($data) {
        header('Content-Type: application/json');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
}
