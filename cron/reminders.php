<?php
/**
 * نظام التذكيرات التلقائية
 * يتم تشغيل هذا الملف عن طريق جدولة Cron
 * مثال: 0 9 * * * php /path/to/reminders.php
 * (يتم تنفيذه يومياً في الساعة 9 صباحاً)
 */

// تحميل ملفات النظام الرئيسية
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../Database.php';
require_once __DIR__ . '/../models/Log.php';
require_once __DIR__ . '/../models/Invoice.php';
require_once __DIR__ . '/../models/User.php';

// إنشاء الكائنات المطلوبة
$db = Database::getInstance();
$log = new Log();
$invoice = new Invoice();
$user = new User();

// تنفيذ التذكيرات
try {
    // إرسال تذكيرات بالطلبات المعلقة
    sendPendingOrdersReminders();
    
    // إرسال تذكيرات بطلبات لم تتم متابعتها منذ فترة
    sendUnattendedOrdersReminders();
    
    // إرسال تذكير للإدارة بالطلبات المرتجعة
    sendReturnedOrdersReminder();
    
    // إرسال تقرير يومي للإدارة
    sendDailyReportToAdmins();
    
    // تسجيل نجاح العملية
    $log->addLog(Log::TYPE_INFO, 'نظام التذكيرات التلقائي', 'تم إرسال التذكيرات اليومية بنجاح');
    
} catch (Exception $e) {
    // تسجيل الخطأ
    $log->logError("خطأ في نظام التذكيرات التلقائي: " . $e->getMessage());
    error_log("[REMINDERS ERROR] " . $e->getMessage());
}

/**
 * إرسال تذكيرات بالطلبات المعلقة
 */
function sendPendingOrdersReminders() {
    global $db, $invoice, $log;
    
    // الحصول على الطلبات المعلقة (التي لم تتغير حالتها منذ 24 ساعة)
    $pendingOrders = $invoice->getPendingOrdersOlderThan(24);
    
    if (empty($pendingOrders)) {
        return;
    }
    
    // تجميع الطلبات حسب المستخدم المسؤول
    $userOrders = [];
    foreach ($pendingOrders as $order) {
        $userId = $order['created_by'];
        if (!isset($userOrders[$userId])) {
            $userOrders[$userId] = [];
        }
        $userOrders[$userId][] = $order;
    }
    
    // إرسال تذكير لكل مستخدم بطلباته المعلقة
    foreach ($userOrders as $userId => $orders) {
        // تحضير رسالة الإشعار
        $title = 'تذكير بالطلبات المعلقة';
        $message = 'لديك ' . count($orders) . ' طلب/طلبات معلق/معلقة لم تتم متابعتها منذ 24 ساعة على الأقل. يرجى التحقق منها.';
        
        // إنشاء إشعار
        createNotification($title, $message, 'warning', '/invoice/search?status=pending', [$userId]);
        
        // تسجيل العملية
        $log->addLog(
            Log::TYPE_INFO,
            'تذكير بالطلبات المعلقة',
            "تم إرسال تذكير للمستخدم رقم: {$userId} بـ " . count($orders) . " طلب/طلبات معلق/معلقة"
        );
    }
}

/**
 * إرسال تذكيرات بطلبات لم تتم متابعتها منذ فترة
 */
function sendUnattendedOrdersReminders() {
    global $db, $invoice, $log;
    
    // الحصول على الطلبات التي لم تتم متابعتها منذ 3 أيام
    $unattendedOrders = $invoice->getUnattendedOrdersOlderThan(72);
    
    if (empty($unattendedOrders)) {
        return;
    }
    
    // تجميع الطلبات حسب المستخدم المسؤول
    $userOrders = [];
    foreach ($unattendedOrders as $order) {
        $userId = $order['created_by'];
        if (!isset($userOrders[$userId])) {
            $userOrders[$userId] = [];
        }
        $userOrders[$userId][] = $order;
    }
    
    // إرسال تذكير لكل مستخدم بطلباته التي لم تتم متابعتها
    foreach ($userOrders as $userId => $orders) {
        // تحضير رسالة الإشعار
        $title = 'طلبات تحتاج للمتابعة';
        $message = 'لديك ' . count($orders) . ' طلب/طلبات لم تتم متابعتها منذ 3 أيام على الأقل. يرجى التحقق منها وتحديث حالتها.';
        
        // إنشاء إشعار
        createNotification($title, $message, 'warning', '/invoice/search?status=unattended', [$userId]);
        
        // تسجيل العملية
        $log->addLog(
            Log::TYPE_INFO,
            'تذكير بالطلبات غير المتابعة',
            "تم إرسال تذكير للمستخدم رقم: {$userId} بـ " . count($orders) . " طلب/طلبات غير متابعة"
        );
    }
    
    // إرسال تذكير للإدارة أيضاً
    $adminIds = getAdminUserIds();
    if (!empty($adminIds)) {
        $title = 'طلبات غير متابعة منذ فترة طويلة';
        $message = 'يوجد ' . count($unattendedOrders) . ' طلب/طلبات لم تتم متابعتها منذ 3 أيام على الأقل. يرجى متابعة الموظفين المسؤولين.';
        
        // إنشاء إشعار
        createNotification($title, $message, 'warning', '/invoice/search?status=unattended', $adminIds);
    }
}

/**
 * إرسال تذكير للإدارة بالطلبات المرتجعة
 */
function sendReturnedOrdersReminder() {
    global $db, $invoice, $log;
    
    // الحصول على الطلبات المرتجعة خلال اليومين الماضيين
    $returnedOrders = $invoice->getReturnedOrdersInLastDays(2);
    
    if (empty($returnedOrders)) {
        return;
    }
    
    // الحصول على قائمة المدراء
    $adminIds = getAdminUserIds();
    
    if (empty($adminIds)) {
        return;
    }
    
    // تحضير رسالة الإشعار
    $title = 'الطلبات المرتجعة';
    $message = 'تم إرجاع ' . count($returnedOrders) . ' طلب/طلبات خلال اليومين الماضيين. يرجى مراجعتها ومتابعتها.';
    
    // إنشاء إشعار
    createNotification($title, $message, 'danger', '/invoice/search?status=returned', $adminIds);
    
    // تسجيل العملية
    $log->addLog(
        Log::TYPE_INFO,
        'تذكير بالطلبات المرتجعة',
        "تم إرسال تذكير للإدارة بـ " . count($returnedOrders) . " طلب/طلبات مرتجعة"
    );
}

/**
 * إرسال تقرير يومي للإدارة
 */
function sendDailyReportToAdmins() {
    global $db, $invoice, $user, $log;
    
    // الحصول على إحصائيات اليوم
    $todaySales = $invoice->getTodaySales();
    $todayInvoicesCount = $invoice->getTodayInvoicesCount();
    $newCustomersCount = $invoice->getNewCustomersToday();
    $completedOrdersCount = $invoice->getCompletedOrdersToday();
    $returnedOrdersCount = $invoice->getReturnedOrdersToday();
    
    // الحصول على إحصائيات الموظفين لليوم
    $employeeStats = $user->getEmployeeStatsForToday();
    
    // تحضير رسالة الإشعار
    $title = 'التقرير اليومي - ' . date('Y-m-d');
    
    $message = "إحصائيات اليوم:\n";
    $message .= "------------------------\n";
    $message .= "إجمالي المبيعات: " . number_format($todaySales) . " دينار\n";
    $message .= "عدد الفواتير: " . $todayInvoicesCount . "\n";
    $message .= "العملاء الجدد: " . $newCustomersCount . "\n";
    $message .= "الطلبات المكتملة: " . $completedOrdersCount . "\n";
    $message .= "الطلبات المرتجعة: " . $returnedOrdersCount . "\n";
    
    // إضافة رابط للتقرير المفصل
    $link = '/reports/daily?date=' . date('Y-m-d');
    
    // الحصول على قائمة المدراء
    $adminIds = getAdminUserIds();
    
    if (empty($adminIds)) {
        return;
    }
    
    // إنشاء إشعار
    createNotification($title, $message, 'info', $link, $adminIds);
    
    // تسجيل العملية
    $log->addLog(
        Log::TYPE_INFO,
        'إرسال التقرير اليومي',
        "تم إرسال التقرير اليومي للإدارة"
    );
}

/**
 * الحصول على قائمة معرفات المستخدمين المدراء
 */
function getAdminUserIds() {
    global $db;
    
    $stmt = $db->query("SELECT id FROM users WHERE role = 'admin'");
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

/**
 * إنشاء إشعار للمستخدمين
 */
function createNotification($title, $message, $type = 'info', $link = '', $userIds = []) {
    global $db;
    
    if (empty($userIds)) {
        return false;
    }
    
    try {
        $db->beginTransaction();
        
        // إدراج الإشعار
        $stmt = $db->prepare("
            INSERT INTO notifications (
                title, message, type, link, created_at
            ) VALUES (
                :title, :message, :type, :link, NOW()
            )
        ");
        
        $stmt->bindParam(':title', $title, PDO::PARAM_STR);
        $stmt->bindParam(':message', $message, PDO::PARAM_STR);
        $stmt->bindParam(':type', $type, PDO::PARAM_STR);
        $stmt->bindParam(':link', $link, PDO::PARAM_STR);
        
        $stmt->execute();
        $notificationId = $db->lastInsertId();
        
        // إنشاء إشعارات المستخدمين
        $insertValues = [];
        $insertParams = [];
        
        foreach ($userIds as $index => $userId) {
            $insertValues[] = "(:notification_id, :user_id_{$index}, 0, NULL)";
            $insertParams[":user_id_{$index}"] = $userId;
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
        
        $db->commit();
        return $notificationId;
        
    } catch (PDOException $e) {
        $db->rollBack();
        error_log("[NOTIFICATION ERROR] " . $e->getMessage());
        return false;
    }
}
