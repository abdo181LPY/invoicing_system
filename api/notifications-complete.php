<?php
/**
 * API لنظام الإشعارات
 */

// التحقق من وجود رمز API صالح
require_once 'api-verify-token.php';

// الاستجابة ستكون بتنسيق JSON
header('Content-Type: application/json');

// التحقق من طريقة الطلب
$method = $_SERVER['REQUEST_METHOD'];
$requestUri = $_SERVER['REQUEST_URI'];
$endpoint = explode('?', basename($requestUri))[0];

// استيراد الفئات المطلوبة
require_once '../Database.php';
require_once '../models/User.php';
require_once '../models/Log.php';

// إنشاء المثيلات
$db = Database::getInstance();
$user = new User();
$log = new Log();

// تحديد العملية المطلوبة
switch ($endpoint) {
    case 'unread':
        handleUnreadNotifications();
        break;
        
    case 'all':
        handleAllNotifications();
        break;
        
    case 'markAsRead':
        handleMarkAsRead();
        break;
        
    case 'markAllAsRead':
        handleMarkAllAsRead();
        break;
        
    case 'create':
        handleCreateNotification();
        break;
        
    default:
        // إذا لم يتم تحديد عملية صالحة
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => 'الواجهة البرمجية غير موجودة'
        ]);
        break;
}

/**
 * معالجة طلب الإشعارات غير المقروءة
 */
function handleUnreadNotifications() {
    global $db, $user, $log;
    
    // الحصول على معرف المستخدم من رمز API
    $userId = $user->getUserIdByApiToken($_SERVER['HTTP_API_TOKEN']);
    
    if (!$userId) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'error' => 'غير مصرح بالوصول'
        ]);
        return;
    }
    
    // جلب الإشعارات غير المقروءة
    $notifications = getNotifications($userId, true);
    
    // تسجيل العملية
    $log->addLog(
        Log::TYPE_INFO,
        'جلب الإشعارات غير المقروءة عبر API',
        "تم جلب الإشعارات غير المقروءة للمستخدم رقم: {$userId} عبر API",
        $userId
    );
    
    // إرجاع البيانات
    echo json_encode([
        'success' => true,
        'data' => $notifications,
        'count' => count($notifications)
    ]);
}

/**
 * معالجة طلب جميع الإشعارات
 */
function handleAllNotifications() {
    global $db, $user, $log;
    
    // الحصول على معرف المستخدم من رمز API
    $userId = $user->getUserIdByApiToken($_SERVER['HTTP_API_TOKEN']);
    
    if (!$userId) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'error' => 'غير مصرح بالوصول'
        ]);
        return;
    }
    
    // معايير التصفية
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
    $offset = ($page - 1) * $limit;
    
    // جلب جميع الإشعارات
    $notifications = getNotifications($userId, false, $limit, $offset);
    $totalCount = getNotificationsCount($userId);
    
    // تسجيل العملية
    $log->addLog(
        Log::TYPE_INFO,
        'جلب جميع الإشعارات عبر API',
        "تم جلب جميع الإشعارات للمستخدم رقم: {$userId} عبر API",
        $userId
    );
    
    // إرجاع البيانات
    echo json_encode([
        'success' => true,
        'data' => $notifications,
        'total' => $totalCount,
        'page' => $page,
        'pages' => ceil($totalCount / $limit)
    ]);
}

/**
 * معالجة طلب تعليم إشعار كمقروء
 */
function handleMarkAsRead() {
    global $db, $user, $log;
    
    // التحقق من طريقة الطلب
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode([
            'success' => false,
            'error' => 'طريقة الطلب غير مسموح بها'
        ]);
        return;
    }
    
    // الحصول على معرف المستخدم من رمز API
    $userId = $user->getUserIdByApiToken($_SERVER['HTTP_API_TOKEN']);
    
    if (!$userId) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'error' => 'غير مصرح بالوصول'
        ]);
        return;
    }
    
    // الحصول على معرف الإشعار من البيانات
    $inputData = json_decode(file_get_contents('php://input'), true);
    $notificationId = $inputData['notification_id'] ?? 0;
    
    if ($notificationId <= 0) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'معرف الإشعار غير صالح'
        ]);
        return;
    }
    
    // تعليم الإشعار كمقروء
    $result = markNotificationAsRead($notificationId, $userId);
    
    if ($result) {
        // تسجيل العملية
        $log->addLog(
            Log::TYPE_INFO,
            'تعليم إشعار كمقروء عبر API',
            "تم تعليم الإشعار رقم: {$notificationId} كمقروء للمستخدم رقم: {$userId} عبر API",
            $userId
        );
        
        echo json_encode([
            'success' => true,
            'message' => 'تم تعليم الإشعار كمقروء'
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'فشل تعليم الإشعار كمقروء'
        ]);
    }
}

/**
 * معالجة طلب تعليم جميع الإشعارات كمقروءة
 */
function handleMarkAllAsRead() {
    global $db, $user, $log;
    
    // التحقق من طريقة الطلب
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode([
            'success' => false,
            'error' => 'طريقة الطلب غير مسموح بها'
        ]);
        return;
    }
    
    // الحصول على معرف المستخدم من رمز API
    $userId = $user->getUserIdByApiToken($_SERVER['HTTP_API_TOKEN']);
    
    if (!$userId) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'error' => 'غير مصرح بالوصول'
        ]);
        return;
    }
    
    // تعليم جميع الإشعارات كمقروءة
    $result = markAllNotificationsAsRead($userId);
    
    if ($result) {
        // تسجيل العملية
        $log->addLog(
            Log::TYPE_INFO,
            'تعليم جميع الإشعارات كمقروءة عبر API',
            "تم تعليم جميع الإشعارات كمقروءة للمستخدم رقم: {$userId} عبر API",
            $userId
        );
        
        echo json_encode([
            'success' => true,
            'message' => 'تم تعليم جميع الإشعارات كمقروءة'
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'فشل تعليم جميع الإشعارات كمقروءة'
        ]);
    }
}

/**
 * معالجة طلب إنشاء إشعار جديد
 */
function handleCreateNotification() {
    global $db, $user, $log;
    
    // التحقق من طريقة الطلب
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode([
            'success' => false,
            'error' => 'طريقة الطلب غير مسموح بها'
        ]);
        return;
    }
    
    // الحصول على معرف المستخدم من رمز API
    $userId = $user->getUserIdByApiToken($_SERVER['HTTP_API_TOKEN']);
    
    if (!$userId) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'error' => 'غير مصرح بالوصول'
        ]);
        return;
    }
    
    // التحقق من صلاحيات المستخدم (يجب أن يكون مدير)
    if (!$user->hasPermission($userId, 'admin')) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'error' => 'غير مصرح لك بإنشاء إشعارات'
        ]);
        return;
    }
    
    // الحصول على بيانات الإشعار
    $inputData = json_decode(file_get_contents('php://input'), true);
    
    // التحقق من صحة البيانات
    if (!isset($inputData['title']) || !isset($inputData['message'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'يرجى توفير العنوان والرسالة على الأقل'
        ]);
        return;
    }
    
    $title = $inputData['title'];
    $message = $inputData['message'];
    $type = $inputData['type'] ?? 'info';
    $link = $inputData['link'] ?? '';
    $userIds = $inputData['user_ids'] ?? [];
    $roles = $inputData['roles'] ?? [];
    
    // إنشاء الإشعار
    $result = createNotification($title, $message, $type, $link, $userIds, $roles, $userId);
    
    if ($result) {
        // تسجيل العملية
        $log->addLog(
            Log::TYPE_INFO,
            'إنشاء إشعار عبر API',
            "تم إنشاء إشعار جديد بعنوان: {$title} عبر API",
            $userId
        );
        
        echo json_encode([
            'success' => true,
            'message' => 'تم إنشاء الإشعار بنجاح',
            'notification_id' => $result
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'فشل إنشاء الإشعار'
        ]);
    }
}

/**
 * الحصول على إشعارات المستخدم
 */
function getNotifications($userId, $unreadOnly = false, $limit = 20, $offset = 0) {
    global $db;
    
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
    
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * الحصول على عدد إشعارات المستخدم
 */
function getNotificationsCount($userId) {
    global $db;
    
    $stmt = $db->prepare("
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
 * تعليم إشعار كمقروء
 */
function markNotificationAsRead($notificationId, $userId) {
    global $db;
    
    $stmt = $db->prepare("
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
function markAllNotificationsAsRead($userId) {
    global $db;
    
    $stmt = $db->prepare("
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
function createNotification($title, $message, $type = 'info', $link = '', $userIds = [], $roles = [], $createdBy = null) {
    global $db, $user;
    
    try {
        $db->beginTransaction();
        
        // إدراج الإشعار
        $stmt = $db->prepare("
            INSERT INTO notifications (
                title, message, type, link, created_by, created_at
            ) VALUES (
                :title, :message, :type, :link, :created_by, NOW()
            )
        ");
        
        $stmt->bindParam(':title', $title, PDO::PARAM_STR);
        $stmt->bindParam(':message', $message, PDO::PARAM_STR);
        $stmt->bindParam(':type', $type, PDO::PARAM_STR);
        $stmt->bindParam(':link', $link, PDO::PARAM_STR);
        $stmt->bindParam(':created_by', $createdBy, $createdBy ? PDO::PARAM_INT : PDO::PARAM_NULL);
        
        $stmt->execute();
        $notificationId = $db->lastInsertId();
        
        // تحديد المستخدمين المستهدفين
        $targetUsers = [];
        
        // إذا تم تحديد مستخدمين محددين
        if (!empty($userIds)) {
            foreach ($userIds as $targetUserId) {
                $targetUsers[] = (int)$targetUserId;
            }
        }
        
        // إذا تم تحديد أدوار
        if (!empty($roles)) {
            $roleUsers = getUsersByRoles($roles);
            foreach ($roleUsers as $roleUser) {
                if (!in_array($roleUser['id'], $targetUsers)) {
                    $targetUsers[] = $roleUser['id'];
                }
            }
        }
        
        // إذا لم يتم تحديد أي مستخدمين أو أدوار، أرسل الإشعار إلى جميع المستخدمين
        if (empty($targetUsers)) {
            $allUsers = $user->getAllUsers();
            foreach ($allUsers as $allUser) {
                $targetUsers[] = $allUser['id'];
            }
        }
        
        // إنشاء إشعارات المستخدمين
        if (!empty($targetUsers)) {
            $insertValues = [];
            $insertParams = [];
            
            foreach ($targetUsers as $index => $targetUserId) {
                $insertValues[] = "(:notification_id, :user_id_{$index}, 0, NULL)";
                $insertParams[":user_id_{$index}"] = $targetUserId;
            }
            
            $insertSql = "
                INSERT INTO user_notifications (notification_id, user_id, is_read, read_at)
                VALUES " . implode(', ', $insertValues);
            
            $stmt = $db->prepare($insertSql);
            $stmt->bindParam(':notification_id', $notificationId, PDO::PARAM_INT);
            
            foreach ($insertParams as $param => $value) {
                $stmt->bindValue($param, $value, PDO::PARAM_INT);
            }
            
            $stmt->execute();
        }
        
        $db->commit();
        return $notificationId;
        
    } catch (PDOException $e) {
        $db->rollBack();
        error_log("خطأ في إنشاء الإشعار: " . $e->getMessage());
        return false;
    }
}

/**
 * الحصول على المستخدمين حسب الأدوار
 */
function getUsersByRoles($roles) {
    global $db;
    
    if (empty($roles)) {
        return [];
    }
    
    $placeholders = implode(',', array_fill(0, count($roles), '?'));
    
    $sql = "
        SELECT id, username, email
        FROM users
        WHERE role IN ({$placeholders})
    ";
    
    $stmt = $db->prepare($sql);
    
    foreach ($roles as $index => $role) {
        $stmt->bindValue($index + 1, $role, PDO::PARAM_STR);
    }
    
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
