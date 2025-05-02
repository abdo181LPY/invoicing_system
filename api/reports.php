<?php
/**
 * API للتقارير والإحصائيات
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
    case 'sales':
        handleSalesReport();
        break;
        
    case 'users':
        handleUserReport();
        break;
        
    case 'customers':
        handleCustomerReport();
        break;
        
    case 'pages':
        handlePageReport();
        break;
        
    case 'statuses':
        handleStatusReport();
        break;
        
    case 'dashboard':
        handleDashboardStats();
        break;
        
    default:
        // إذا لم يتم تحديد عملية صالحة
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => 'Endpoint not found'
        ]);
        break;
}

/**
 * معالجة طلب تقرير المبيعات
 */
function handleSalesReport() {
    global $invoice, $user, $log;
    
    // استلام المعايير
    $dateFrom = $_GET['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
    $dateTo = $_GET['date_to'] ?? date('Y-m-d');
    $userId = isset($_GET['user_id']) && $_GET['user_id'] ? $_GET['user_id'] : null;
    $pageId = isset($_GET['page_id']) && $_GET['page_id'] ? $_GET['page_id'] : null;
    $governorate = isset($_GET['governorate']) && $_GET['governorate'] ? $_GET['governorate'] : null;
    
    // الحصول على بيانات المبيعات
    $salesData = $invoice->getSalesReport($dateFrom, $dateTo, $userId, $pageId, $governorate);
    
    // لتسجيل عملية طلب التقرير
    $userId = $user->getUserIdByApiToken($_SERVER['HTTP_API_TOKEN']);
    $log->addLog(
        Log::TYPE_INFO,
        'طلب تقرير مبيعات عبر API',
        "تم طلب تقرير المبيعات للفترة من {$dateFrom} إلى {$dateTo} عبر API",
        $userId
    );
    
    // إرجاع البيانات
    echo json_encode([
        'success' => true,
        'data' => $salesData,
        'meta' => [
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'user_id' => $userId,
            'page_id' => $pageId,
            'governorate' => $governorate
        ]
    ]);
}

/**
 * معالجة طلب تقرير المستخدمين
 */
function handleUserReport() {
    global $user, $log;
    
    // استلام المعايير
    $dateFrom = $_GET['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
    $dateTo = $_GET['date_to'] ?? date('Y-m-d');
    $userId = isset($_GET['user_id']) && $_GET['user_id'] ? $_GET['user_id'] : null;
    
    // الحصول على بيانات المستخدمين
    $userData = $user->getUserPerformanceReport($dateFrom, $dateTo, $userId);
    
    // لتسجيل عملية طلب التقرير
    $currentUserId = $user->getUserIdByApiToken($_SERVER['HTTP_API_TOKEN']);
    $log->addLog(
        Log::TYPE_INFO,
        'طلب تقرير المستخدمين عبر API',
        "تم طلب تقرير المستخدمين للفترة من {$dateFrom} إلى {$dateTo} عبر API",
        $currentUserId
    );
    
    // إرجاع البيانات
    echo json_encode([
        'success' => true,
        'data' => $userData,
        'meta' => [
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'user_id' => $userId
        ]
    ]);
}

/**
 * معالجة طلب تقرير الزبائن
 */
function handleCustomerReport() {
    global $customer, $user, $log;
    
    // استلام المعايير
    $dateFrom = $_GET['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
    $dateTo = $_GET['date_to'] ?? date('Y-m-d');
    $governorate = isset($_GET['governorate']) && $_GET['governorate'] ? $_GET['governorate'] : null;
    $minOrders = isset($_GET['min_orders']) && $_GET['min_orders'] ? (int)$_GET['min_orders'] : 1;
    
    // الحصول على بيانات الزبائن
    $customerData = $customer->getCustomerReport($dateFrom, $dateTo, $governorate, $minOrders);
    
    // لتسجيل عملية طلب التقرير
    $userId = $user->getUserIdByApiToken($_SERVER['HTTP_API_TOKEN']);
    $log->addLog(
        Log::TYPE_INFO,
        'طلب تقرير الزبائن عبر API',
        "تم طلب تقرير الزبائن للفترة من {$dateFrom} إلى {$dateTo} عبر API",
        $userId
    );
    
    // إرجاع البيانات
    echo json_encode([
        'success' => true,
        'data' => $customerData,
        'meta' => [
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'governorate' => $governorate,
            'min_orders' => $minOrders
        ]
    ]);
}

/**
 * معالجة طلب تقرير الصفحات
 */
function handlePageReport() {
    global $page, $user, $log;
    
    // استلام المعايير
    $dateFrom = $_GET['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
    $dateTo = $_GET['date_to'] ?? date('Y-m-d');
    $pageId = isset($_GET['page_id']) && $_GET['page_id'] ? $_GET['page_id'] : null;
    
    // الحصول على بيانات الصفحات
    $pageData = $page->getPageReport($dateFrom, $dateTo, $pageId);
    
    // لتسجيل عملية طلب التقرير
    $userId = $user->getUserIdByApiToken($_SERVER['HTTP_API_TOKEN']);
    $log->addLog(
        Log::TYPE_INFO,
        'طلب تقرير الصفحات عبر API',
        "تم طلب تقرير الصفحات للفترة من {$dateFrom} إلى {$dateTo} عبر API",
        $userId
    );
    
    // إرجاع البيانات
    echo json_encode([
        'success' => true,
        'data' => $pageData,
        'meta' => [
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'page_id' => $pageId
        ]
    ]);
}

/**
 * معالجة طلب تقرير حالات الطلبات
 */
function handleStatusReport() {
    global $invoice, $user, $log;
    
    // استلام المعايير
    $dateFrom = $_GET['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
    $dateTo = $_GET['date_to'] ?? date('Y-m-d');
    $pageId = isset($_GET['page_id']) && $_GET['page_id'] ? $_GET['page_id'] : null;
    
    // الحصول على بيانات حالات الطلبات
    $statusData = $invoice->getOrderStatusReport($dateFrom, $dateTo, $pageId);
    
    // لتسجيل عملية طلب التقرير
    $userId = $user->getUserIdByApiToken($_SERVER['HTTP_API_TOKEN']);
    $log->addLog(
        Log::TYPE_INFO,
        'طلب تقرير حالات الطلبات عبر API',
        "تم طلب تقرير حالات الطلبات للفترة من {$dateFrom} إلى {$dateTo} عبر API",
        $userId
    );
    
    // إرجاع البيانات
    echo json_encode([
        'success' => true,
        'data' => $statusData,
        'meta' => [
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'page_id' => $pageId
        ]
    ]);
}

/**
 * معالجة طلب إحصائيات لوحة التحكم
 */
function handleDashboardStats() {
    global $invoice, $user, $customer, $page, $log;
    
    // الحصول على إحصائيات لوحة التحكم
    $todaySales = $invoice->getTodaySales();
    $monthSales = $invoice->getMonthSales();
    $activeCustomers = $customer->getActiveCustomersCount();
    $pendingOrders = $invoice->getOrdersCountByStatus('pending');
    $processingOrders = $invoice->getOrdersCountByStatus('processing');
    $completedOrders = $invoice->getOrdersCountByStatus('completed');
    $topPages = $page->getTopPerformingPages(5);
    $topCustomers = $customer->getTopCustomers(5);
    $recentInvoices = $invoice->getRecentInvoices(5);
    
    // لوحة المتابعة الرئيسية
    $dashboardStats = [
        'summary' => [
            'today_sales' => $todaySales,
            'month_sales' => $monthSales,
            'active_customers' => $activeCustomers,
            'pending_orders' => $pendingOrders,
            'processing_orders' => $processingOrders,
            'completed_orders' => $completedOrders
        ],
        'top_pages' => $topPages,
        'top_customers' => $topCustomers,
        'recent_invoices' => $recentInvoices
    ];
    
    // لتسجيل عملية طلب الإحصائيات
    $userId = $user->getUserIdByApiToken($_SERVER['HTTP_API_TOKEN']);
    $log->addLog(
        Log::TYPE_INFO,
        'طلب إحصائيات لوحة التحكم عبر API',
        "تم طلب إحصائيات لوحة التحكم عبر API",
        $userId
    );
    
    // إرجاع البيانات
    echo json_encode([
        'success' => true,
        'data' => $dashboardStats
    ]);
}
