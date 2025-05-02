<?php
/**
 * API لبيانات لوحة التحكم
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
require_once '../models/Invoice.php';
require_once '../models/User.php';
require_once '../models/Customer.php';
require_once '../models/Page.php';
require_once '../models/OrderStatus.php';
require_once '../models/Log.php';

// إنشاء المثيلات
$invoice = new Invoice();
$user = new User();
$customer = new Customer();
$page = new Page();
$orderStatus = new OrderStatus();
$log = new Log();

// تحديد العملية المطلوبة
switch ($endpoint) {
    case 'summary':
        handleDashboardSummary();
        break;
        
    case 'recent_invoices':
        handleRecentInvoices();
        break;
        
    case 'sales_chart':
        handleSalesChart();
        break;
        
    case 'status_chart':
        handleStatusChart();
        break;
        
    case 'user_activity':
        handleUserActivity();
        break;
        
    case 'notifications':
        handleNotifications();
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
 * معالجة طلب ملخص لوحة التحكم
 */
function handleDashboardSummary() {
    global $invoice, $user, $customer, $page, $log;
    
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
    
    // الحصول على البيانات الإحصائية العامة
    $summary = [
        'today_sales' => $invoice->getTodaySales(),
        'yesterday_sales' => $invoice->getYesterdaySales(),
        'week_sales' => $invoice->getWeekSales(),
        'month_sales' => $invoice->getMonthSales(),
        'pending_orders' => $invoice->getPendingOrdersCount(),
        'processing_orders' => $invoice->getProcessingOrdersCount(),
        'completed_orders' => $invoice->getCompletedOrdersCount(),
        'total_customers' => $customer->getTotalCustomersCount(),
        'active_customers' => $customer->getActiveCustomersCount(),
        'user_count' => $user->getUserCount(),
        'total_pages' => $page->getTotalPagesCount()
    ];
    
    // احتساب نسبة النمو
    $summary['sales_growth'] = calculateGrowthPercentage($summary['month_sales'], $invoice->getPreviousMonthSales());
    $summary['orders_growth'] = calculateGrowthPercentage($invoice->getMonthOrdersCount(), $invoice->getPreviousMonthOrdersCount());
    $summary['customers_growth'] = calculateGrowthPercentage($customer->getNewCustomersThisMonth(), $customer->getNewCustomersLastMonth());
    
    // تسجيل العملية
    $log->addLog(
        Log::TYPE_INFO,
        'طلب ملخص لوحة التحكم عبر API',
        "تم طلب ملخص لوحة التحكم من قبل المستخدم رقم: {$userId} عبر API",
        $userId
    );
    
    // إرجاع البيانات
    echo json_encode([
        'success' => true,
        'data' => $summary
    ]);
}

/**
 * معالجة طلب الفواتير الحديثة
 */
function handleRecentInvoices() {
    global $invoice, $user, $log;
    
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
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
    $statusId = isset($_GET['status_id']) ? (int)$_GET['status_id'] : null;
    $userSpecific = isset($_GET['user_specific']) && $_GET['user_specific'] === 'true';
    
    // الحصول على الفواتير الحديثة
    if ($userSpecific) {
        $recentInvoices = $invoice->getRecentInvoicesByUser($userId, $limit, $statusId);
    } else {
        $recentInvoices = $invoice->getRecentInvoices($limit, $statusId);
    }
    
    // تسجيل العملية
    $log->addLog(
        Log::TYPE_INFO,
        'طلب الفواتير الحديثة عبر API',
        "تم طلب الفواتير الحديثة من قبل المستخدم رقم: {$userId} عبر API",
        $userId
    );
    
    // إرجاع البيانات
    echo json_encode([
        'success' => true,
        'data' => $recentInvoices
    ]);
}

/**
 * معالجة طلب بيانات مخطط المبيعات
 */
function handleSalesChart() {
    global $invoice, $user, $log;
    
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
    $period = $_GET['period'] ?? 'week';
    $userSpecific = isset($_GET['user_specific']) && $_GET['user_specific'] === 'true';
    
    // الحصول على بيانات المبيعات
    switch ($period) {
        case 'year':
            if ($userSpecific) {
                $salesData = $invoice->getYearlySalesByUser($userId);
            } else {
                $salesData = $invoice->getYearlySales();
            }
            break;
            
        case 'month':
            if ($userSpecific) {
                $salesData = $invoice->getMonthlySalesByUser($userId);
            } else {
                $salesData = $invoice->getMonthlySales();
            }
            break;
            
        case 'week':
        default:
            if ($userSpecific) {
                $salesData = $invoice->getWeeklySalesByUser($userId);
            } else {
                $salesData = $invoice->getWeeklySales();
            }
            break;
    }
    
    // تحويل البيانات إلى الشكل المناسب للرسم البياني
    $chartData = [
        'labels' => array_column($salesData, 'date'),
        'datasets' => [
            [
                'label' => 'المبيعات',
                'data' => array_column($salesData, 'total')
            ],
            [
                'label' => 'عدد الفواتير',
                'data' => array_column($salesData, 'count')
            ]
        ]
    ];
    
    // تسجيل العملية
    $log->addLog(
        Log::TYPE_INFO,
        'طلب بيانات مخطط المبيعات عبر API',
        "تم طلب بيانات مخطط المبيعات للفترة: {$period} من قبل المستخدم رقم: {$userId} عبر API",
        $userId
    );
    
    // إرجاع البيانات
    echo json_encode([
        'success' => true,
        'data' => $chartData,
        'meta' => [
            'period' => $period,
            'user_specific' => $userSpecific
        ]
    ]);
}

/**
 * معالجة طلب بيانات مخطط حالات الطلبات
 */
function handleStatusChart() {
    global $invoice, $orderStatus, $user, $log;
    
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
    
    // الحصول على إحصائيات حالات الطلبات
    $statusStats = $orderStatus->getInvoiceCountByStatus();
    
    // تحويل البيانات إلى الشكل المناسب للرسم البياني
    $chartData = [
        'labels' => array_column($statusStats, 'name'),
        'data' => array_column($statusStats, 'count')
    ];
    
    // تسجيل العملية
    $log->addLog(
        Log::TYPE_INFO,
        'طلب بيانات مخطط حالات الطلبات عبر API',
        "تم طلب بيانات مخطط حالات الطلبات من قبل المستخدم رقم: {$userId} عبر API",
        $userId
    );
    
    // إرجاع البيانات
    echo json_encode([
        'success' => true,
        'data' => $chartData
    ]);
}

/**
 * معالجة طلب بيانات نشاط المستخدمين
 */
function handleUserActivity() {
    global $user, $log;
    
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
            'error' => 'غير مصرح لك بالوصول إلى هذه البيانات'
        ]);
        return;
    }
    
    // الحصول على بيانات نشاط المستخدمين
    $userActivity = $user->getUserActivity();
    
    // تسجيل العملية
    $log->addLog(
        Log::TYPE_INFO,
        'طلب بيانات نشاط المستخدمين عبر API',
        "تم طلب بيانات نشاط المستخدمين من قبل المستخدم رقم: {$userId} عبر API",
        $userId
    );
    
    // إرجاع البيانات
    echo json_encode([
        'success' => true,
        'data' => $userActivity
    ]);
}

/**
 * معالجة طلب الإشعارات للوحة التحكم
 */
function handleNotifications() {
    global $user, $log;
    
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
    
    // الحصول على الإشعارات غير المقروءة للمستخدم
    $db = Database::getInstance();
    $stmt = $db->prepare("
        SELECT n.*, un.is_read, un.read_at
        FROM notifications n
        JOIN user_notifications un ON n.id = un.notification_id
        WHERE un.user_id = :user_id AND un.is_read = 0
        ORDER BY n.created_at DESC
        LIMIT 5
    ");
    
    $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $stmt->execute();
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // الحصول على عدد الإشعارات غير المقروءة
    $stmt = $db->prepare("
        SELECT COUNT(*) as count
        FROM user_notifications
        WHERE user_id = :user_id AND is_read = 0
    ");
    
    $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $stmt->execute();
    $unreadCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // تسجيل العملية
    $log->addLog(
        Log::TYPE_INFO,
        'طلب الإشعارات للوحة التحكم عبر API',
        "تم طلب الإشعارات للوحة التحكم من قبل المستخدم رقم: {$userId} عبر API",
        $userId
    );
    
    // إرجاع البيانات
    echo json_encode([
        'success' => true,
        'data' => [
            'notifications' => $notifications,
            'unread_count' => (int)$unreadCount
        ]
    ]);
}

/**
 * احتساب نسبة النمو
 */
function calculateGrowthPercentage($current, $previous) {
    if ($previous == 0) {
        return $current > 0 ? 100 : 0;
    }
    
    return round((($current - $previous) / $previous) * 100, 2);
}
