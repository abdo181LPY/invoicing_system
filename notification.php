<?php
/**
 * فئة الإشعارات والتنبيهات
 * تدير إنشاء وإرسال وعرض الإشعارات للمستخدمين
 */
class Notification {
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
     * الحصول على كائن وحيد من الإشعارات
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * إنشاء إشعار جديد
     * 
     * @param int $userId معرف المستخدم المستلم (null للإشعار لجميع المستخدمين)
     * @param string $type نوع الإشعار (info, success, warning, error)
     * @param string $title عنوان الإشعار
     * @param string $message نص الإشعار
     * @param string $link رابط متعلق بالإشعار (اختياري)
     * @param array $data بيانات إضافية للإشعار (اختياري)
     * @return int معرف الإشعار الجديد
     */
    public function create($userId, $type, $title, $message, $link = null, $data = null) {
        $notificationData = [
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'link' => $link,
            'data' => $data ? json_encode($data) : null,
            'is_read' => 0,
            'created_at' => date('Y-m-d H:i:s'),
            'created_by' => isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null
        ];
        
        return $this->db->insert('notifications', $notificationData);
    }
    
    /**
     * إنشاء إشعار نجاح
     */
    public function success($userId, $title, $message, $link = null, $data = null) {
        return $this->create($userId, 'success', $title, $message, $link, $data);
    }
    
    /**
     * إنشاء إشعار معلومات
     */
    public function info($userId, $title, $message, $link = null, $data = null) {
        return $this->create($userId, 'info', $title, $message, $link, $data);
    }
    
    /**
     * إنشاء إشعار تحذير
     */
    public function warning($userId, $title, $message, $link = null, $data = null) {
        return $this->create($userId, 'warning', $title, $message, $link, $data);
    }
    
    /**
     * إنشاء إشعار خطأ
     */
    public function error($userId, $title, $message, $link = null, $data = null) {
        return $this->create($userId, 'error', $title, $message, $link, $data);
    }
    
    /**
     * إنشاء إشعار لجميع المستخدمين
     */
    public function createForAll($type, $title, $message, $link = null, $data = null) {
        return $this->create(null, $type, $title, $message, $link, $data);
    }
    
    /**
     * إنشاء إشعار لمجموعة من المستخدمين
     */
    public function createForGroup($userIds, $type, $title, $message, $link = null, $data = null) {
        $ids = [];
        foreach ($userIds as $userId) {
            $ids[] = $this->create($userId, $type, $title, $message, $link, $data);
        }
        return $ids;
    }
    
    /**
     * إنشاء إشعار متعلق بفاتورة
     */
    public function createInvoiceNotification($invoiceId, $userId, $type, $title, $message) {
        $data = ['invoice_id' => $invoiceId];
        $link = 'invoice/view.php?id=' . $invoiceId;
        
        return $this->create($userId, $type, $title, $message, $link, $data);
    }
    
    /**
     * إنشاء إشعار متعلق بتثبيت فاتورة جديدة
     */
    public function notifyNewInvoice($invoiceId, $invoice) {
        // إشعار للمسؤولين
        $sql = "SELECT id FROM users WHERE permission_level >= 2";
        $admins = $this->db->fetchAll($sql);
        
        $title = 'فاتورة جديدة';
        $message = 'تم تثبيت فاتورة جديدة برقم #' . $invoiceId . ' للزبون ' . $invoice['customer_name'];
        
        foreach ($admins as $admin) {
            $this->createInvoiceNotification($invoiceId, $admin['id'], 'info', $title, $message);
        }
        
        // إشعار للمستخدم الذي قام بتثبيت الفاتورة
        if (isset($_SESSION['user_id'])) {
            $title = 'تم تثبيت الفاتورة بنجاح';
            $message = 'تم تثبيت الفاتورة رقم #' . $invoiceId . ' بنجاح';
            $this->createInvoiceNotification($invoiceId, $_SESSION['user_id'], 'success', $title, $message);
        }
    }
    
    /**
     * إنشاء إشعار متعلق بحذف فاتورة
     */
    public function notifyDeletedInvoice($invoiceId, $invoice) {
        // إشعار للمسؤولين
        $sql = "SELECT id FROM users WHERE permission_level >= 3";
        $admins = $this->db->fetchAll($sql);
        
        $title = 'تم حذف فاتورة';
        $message = 'تم حذف الفاتورة رقم #' . $invoiceId . ' للزبون ' . $invoice['customer_name'] . ' بواسطة ' . $_SESSION['username'];
        
        foreach ($admins as $admin) {
            $this->create($admin['id'], 'warning', $title, $message, null, ['invoice_id' => $invoiceId]);
        }
    }
    
    /**
     * إنشاء إشعار متعلق بتغيير حالة الطلب
     */
    public function notifyStatusChange($invoiceId, $invoice, $oldStatus, $newStatus) {
        // إشعار للمستخدم الذي قام بإنشاء الفاتورة
        $title = 'تغيير حالة الطلب';
        $message = 'تم تغيير حالة الطلب رقم #' . $invoiceId . ' من "' . $oldStatus . '" إلى "' . $newStatus . '"';
        
        $this->createInvoiceNotification($invoiceId, $invoice['created_by'], 'info', $title, $message);
    }
    
    /**
     * إنشاء إشعار بطلب تسجيل جديد
     */
    public function notifyNewRegistration($requestId, $username) {
        // إشعار للمسؤولين
        $sql = "SELECT id FROM users WHERE permission_level >= 3";
        $admins = $this->db->fetchAll($sql);
        
        $title = 'طلب تسجيل جديد';
        $message = 'تم استلام طلب تسجيل جديد من المستخدم ' . $username;
        $link = 'admin/registration_requests.php';
        
        foreach ($admins as $admin) {
            $this->create($admin['id'], 'info', $title, $message, $link, ['request_id' => $requestId]);
        }
    }
    
    /**
     * الحصول على إشعارات المستخدم
     */
    public function getUserNotifications($userId, $limit = 10, $offset = 0, $unreadOnly = false) {
        $sql = "SELECT * FROM notifications WHERE (user_id = :user_id OR user_id IS NULL)";
        
        if ($unreadOnly) {
            $sql .= " AND is_read = 0";
        }
        
        $sql .= " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
        
        return $this->db->fetchAll($sql, [
            'user_id' => $userId,
            'limit' => $limit,
            'offset' => $offset
        ]);
    }
    
    /**
     * عدد الإشعارات غير المقروءة للمستخدم
     */
    public function countUnreadNotifications($userId) {
        $sql = "SELECT COUNT(*) FROM notifications WHERE (user_id = :user_id OR user_id IS NULL) AND is_read = 0";
        return $this->db->fetchColumn($sql, ['user_id' => $userId]);
    }
    
    /**
     * تعيين الإشعار كمقروء
     */
    public function markAsRead($notificationId, $userId) {
        $sql = "UPDATE notifications SET is_read = 1 
                WHERE id = :id AND (user_id = :user_id OR user_id IS NULL)";
        
        return $this->db->query($sql, [
            'id' => $notificationId,
            'user_id' => $userId
        ]);
    }
    
    /**
     * تعيين جميع إشعارات المستخدم كمقروءة
     */
    public function markAllAsRead($userId) {
        $sql = "UPDATE notifications SET is_read = 1 
                WHERE (user_id = :user_id OR user_id IS NULL) AND is_read = 0";
        
        return $this->db->query($sql, ['user_id' => $userId]);
    }
    
    /**
     * حذف إشعار
     */
    public function delete($notificationId, $userId) {
        $sql = "DELETE FROM notifications 
                WHERE id = :id AND (user_id = :user_id OR user_id IS NULL)";
        
        return $this->db->query($sql, [
            'id' => $notificationId,
            'user_id' => $userId
        ]);
    }
    
    /**
     * حذف جميع إشعارات المستخدم المقروءة
     */
    public function deleteAllRead($userId) {
        $sql = "DELETE FROM notifications 
                WHERE (user_id = :user_id OR user_id IS NULL) AND is_read = 1";
        
        return $this->db->query($sql, ['user_id' => $userId]);
    }
    
    /**
     * الحصول على آخر الإشعارات للعرض في الواجهة
     */
    public function getLatestNotifications($userId, $limit = 5) {
        return $this->getUserNotifications($userId, $limit, 0);
    }
    
    /**
     * تحويل الإشعارات إلى بيانات JSON للعرض في الواجهة
     */
    public function getNotificationsAsJson($userId, $limit = 10) {
        $notifications = $this->getLatestNotifications($userId, $limit);
        $unreadCount = $this->countUnreadNotifications($userId);
        
        return json_encode([
            'notifications' => $notifications,
            'unread_count' => $unreadCount
        ]);
    }
    
    /**
     * عرض الإشعارات في الواجهة
     */
    public function renderNotifications($userId, $limit = 5) {
        $notifications = $this->getLatestNotifications($userId, $limit);
        $unreadCount = $this->countUnreadNotifications($userId);
        
        $html = '<div class="notifications-dropdown">';
        $html .= '<div class="notifications-header">';
        $html .= '<span>الإشعارات</span>';
        if ($unreadCount > 0) {
            $html .= '<span class="badge badge-danger">' . $unreadCount . '</span>';
        }
        $html .= '</div>';
        
        $html .= '<div class="notifications-list">';
        
        if (empty($notifications)) {
            $html .= '<div class="notification-item empty">لا توجد إشعارات جديدة</div>';
        } else {
            foreach ($notifications as $notification) {
                $html .= '<div class="notification-item ' . ($notification['is_read'] ? 'read' : 'unread') . '">';
                $html .= '<div class="notification-icon ' . $notification['type'] . '">';
                
                // أيقونة حسب نوع الإشعار
                switch ($notification['type']) {
                    case 'success':
                        $html .= '<i class="fas fa-check-circle"></i>';
                        break;
                    case 'info':
                        $html .= '<i class="fas fa-info-circle"></i>';
                        break;
                    case 'warning':
                        $html .= '<i class="fas fa-exclamation-triangle"></i>';
                        break;
                    case 'error':
                        $html .= '<i class="fas fa-times-circle"></i>';
                        break;
                    default:
                        $html .= '<i class="fas fa-bell"></i>';
                }
                
                $html .= '</div>';
                $html .= '<div class="notification-content">';
                
                if ($notification['link']) {
                    $html .= '<a href="' . $notification['link'] . '" class="notification-title">' . $notification['title'] . '</a>';
                } else {
                    $html .= '<div class="notification-title">' . $notification['title'] . '</div>';
                }
                
                $html .= '<div class="notification-message">' . $notification['message'] . '</div>';
                $html .= '<div class="notification-time">' . $this->timeAgo($notification['created_at']) . '</div>';
                $html .= '</div>';
                
                // زر تعيين كمقروء
                if (!$notification['is_read']) {
                    $html .= '<div class="notification-actions">';
                    $html .= '<button class="btn btn-sm btn-light mark-as-read" data-id="' . $notification['id'] . '">';
                    $html .= '<i class="fas fa-check"></i>';
                    $html .= '</button>';
                    $html .= '</div>';
                }
                
                $html .= '</div>';
            }
        }
        
        $html .= '</div>';
        
        $html .= '<div class="notifications-footer">';
        $html .= '<a href="notifications.php">عرض كل الإشعارات</a>';
        if ($unreadCount > 0) {
            $html .= '<button class="btn btn-sm btn-primary mark-all-read">تعيين الكل كمقروء</button>';
        }
        $html .= '</div>';
        
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * تحويل التاريخ إلى وقت مضى بالعربية
     */
    private function timeAgo($datetime) {
        $time = strtotime($datetime);
        $now = time();
        $diff = $now - $time;
        
        if ($diff < 60) {
            return 'منذ لحظات';
        } elseif ($diff < 3600) {
            $minutes = round($diff / 60);
            return 'منذ ' . $minutes . ($minutes > 1 ? ' دقائق' : ' دقيقة');
        } elseif ($diff < 86400) {
            $hours = round($diff / 3600);
            return 'منذ ' . $hours . ($hours > 1 ? ' ساعات' : ' ساعة');
        } elseif ($diff < 604800) {
            $days = round($diff / 86400);
            return 'منذ ' . $days . ($days > 1 ? ' أيام' : ' يوم');
        } elseif ($diff < 2592000) {
            $weeks = round($diff / 604800);
            return 'منذ ' . $weeks . ($weeks > 1 ? ' أسابيع' : ' أسبوع');
        } elseif ($diff < 31536000) {
            $months = round($diff / 2592000);
            return 'منذ ' . $months . ($months > 1 ? ' أشهر' : ' شهر');
        } else {
            $years = round($diff / 31536000);
            return 'منذ ' . $years . ($years > 1 ? ' سنوات' : ' سنة');
        }
    }
}