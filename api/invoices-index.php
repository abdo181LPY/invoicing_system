<?php
/**
 * واجهة برمجة التطبيقات - قائمة الفواتير
 * يعرض قائمة الفواتير مع دعم التصفح والترتيب
 */

// منع الوصول المباشر
if (!defined('API_ACCESS')) {
    header('HTTP/1.0 403 Forbidden');
    echo json_encode(['error' => 'غير مصرح بالوصول المباشر']);
    exit;
}

// التحقق من المصادقة
require_once 'api/auth_check.php';

// تحميل الفئات اللازمة
require_once 'models/Invoice.php';
require_once 'models/Page.php';

// إنشاء نموذج الفاتورة
$invoice = new Invoice();

// تهيئة المعلمات
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
$offset = ($page - 1) * $limit;
$orderBy = isset($_GET['order_by']) ? $_GET['order_by'] : 'id';
$orderDir = isset($_GET['order_dir']) ? strtoupper($_GET['order_dir']) : 'DESC';

// التحقق من معلمات الترتيب
$allowedOrderFields = ['id', 'customer_name', 'phone', 'province', 'status', 'created_at', 'total_price'];
if (!in_array($orderBy, $allowedOrderFields)) {
    $orderBy = 'id';
}

$allowedOrderDirs = ['ASC', 'DESC'];
if (!in_array($orderDir, $allowedOrderDirs)) {
    $orderDir = 'DESC';
}

// تحديد الشروط
$conditions = [];

// التحقق من نوع الصلاحية
$permissionLevel = $authUser['permission_level'];

// إذا كان المستخدم ليس مديرًا، عرض فواتيره فقط
if ($permissionLevel < 3) {
    $conditions['created_by'] = $authUser['id'];
}

// فلترة حسب حالة الطلب (إن وجدت)
if (isset($_GET['status']) && !empty($_GET['status'])) {
    $conditions['status'] = $_GET['status'];
}

// فلترة حسب الصفحة (إن وجدت)
if (isset($_GET['page_id']) && !empty($_GET['page_id'])) {
    $conditions['page_id'] = (int)$_GET['page_id'];
}

// فلترة حسب المحافظة (إن وجدت)
if (isset($_GET['province']) && !empty($_GET['province'])) {
    $conditions['province'] = $_GET['province'];
}

// فلترة حسب التاريخ (إن وجد)
if (isset($_GET['date_from']) && !empty($_GET['date_from'])) {
    $dateFrom = date('Y-m-d 00:00:00', strtotime($_GET['date_from']));
    $conditions['created_at >='] = $dateFrom;
}

if (isset($_GET['date_to']) && !empty($_GET['date_to'])) {
    $dateTo = date('Y-m-d 23:59:59', strtotime($_GET['date_to']));
    $conditions['created_at <='] = $dateTo;
}

// الحصول على عدد الفواتير الكلي
$totalInvoices = $invoice->count($conditions);

// حساب عدد الصفحات
$totalPages = ceil($totalInvoices / $limit);

// البحث عن الفواتير
$invoices = [];
if ($totalInvoices > 0) {
    $sql = "SELECT i.*, 
                   p.name as page_name,
                   u.username as created_by_username 
            FROM invoices i
            LEFT JOIN pages p ON i.page_id = p.id
            LEFT JOIN users u ON i.created_by = u.id";
    
    // إضافة شروط البحث
    if (!empty($conditions)) {
        $whereClauses = [];
        $params = [];
        
        foreach ($conditions as $key => $value) {
            // التعامل مع الشروط الخاصة مثل >= و <=
            if (strpos($key, ' >=') !== false || strpos($key, ' <=') !== false) {
                $field = str_replace([' >=', ' <='], '', $key);
                $operator = strpos($key, ' >=') !== false ? '>=' : '<=';
                $whereClauses[] = "i.{$field} {$operator} :{$field}";
                $params[$field] = $value;
            } else {
                $whereClauses[] = "i.{$key} = :{$key}";
                $params[$key] = $value;
            }
        }
        
        $sql .= " WHERE " . implode(' AND ', $whereClauses);
        $sql .= " ORDER BY i.{$orderBy} {$orderDir} LIMIT {$limit} OFFSET {$offset}";
        
        $invoices = $invoice->db->fetchAll($sql, $params);
    } else {
        $sql .= " ORDER BY i.{$orderBy} {$orderDir} LIMIT {$limit} OFFSET {$offset}";
        $invoices = $invoice->db->fetchAll($sql);
    }
}

// تحضير البيانات للرد
$response = [
    'success' => true,
    'data' => [
        'invoices' => $invoices,
        'pagination' => [
            'total' => $totalInvoices,
            'per_page' => $limit,
            'current_page' => $page,
            'last_page' => $totalPages,
            'from' => $offset + 1,
            'to' => min($offset + $limit, $totalInvoices)
        ]
    ]
];

// إضافة معلومات إضافية
if (isset($_GET['include_status_counts']) && $_GET['include_status_counts'] == 1) {
    // الحصول على عدد الفواتير حسب الحالة
    $statusCounts = [];
    
    foreach ($GLOBALS['ORDER_STATUSES'] as $status) {
        $statusConditions = $conditions;
        $statusConditions['status'] = $status;
        $statusCounts[$status] = $invoice->count($statusConditions);
    }
    
    $response['data']['status_counts'] = $statusCounts;
}

// إضافة قائمة المحافظات
if (isset($_GET['include_provinces']) && $_GET['include_provinces'] == 1) {
    $response['data']['provinces'] = $GLOBALS['PROVINCES'];
}

// إضافة قائمة الصفحات
if (isset($_GET['include_pages']) && $_GET['include_pages'] == 1) {
    $page = new Page();
    $response['data']['pages'] = $page->getDropdownList();
}

// إرجاع البيانات بتنسيق JSON
echo json_encode($response);
exit;
