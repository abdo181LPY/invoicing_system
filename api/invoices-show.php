<?php
/**
 * واجهة برمجة التطبيقات - عرض فاتورة
 * يعرض تفاصيل فاتورة محددة
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
require_once 'models/User.php';
require_once 'models/Page.php';

// التحقق من وجود معرف الفاتورة
if (!isset($_GET['id']) || empty($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'معرف الفاتورة مطلوب']);
    exit;
}

$invoiceId = (int)$_GET['id'];

// إنشاء نموذج الفاتورة
$invoice = new Invoice();

// الحصول على الفاتورة
$invoiceData = $invoice->getById($invoiceId);

// التحقق من وجود الفاتورة
if (!$invoiceData) {
    http_response_code(404);
    echo json_encode(['error' => 'الفاتورة غير موجودة']);
    exit;
}

// التحقق من الصلاحية للوصول إلى الفاتورة
$permissionLevel = $authUser['permission_level'];

// إذا لم يكن المستخدم مديرًا ولم ينشئ الفاتورة، يتم منع الوصول
if ($permissionLevel < 3 && $invoiceData['created_by'] != $authUser['id']) {
    http_response_code(403);
    echo json_encode(['error' => 'ليس لديك صلاحية للوصول إلى هذه الفاتورة']);
    exit;
}

// الحصول على صور الفاتورة
$images = $invoice->getInvoiceImages($invoiceId);

// تصنيف الصور حسب النوع
$repeatImages = [];
$customImages = [];
$depositImage = null;

foreach ($images as $image) {
    if ($image['type'] === 'repeat') {
        $repeatImages[] = $image;
    } elseif ($image['type'] === 'custom') {
        $customImages[] = $image;
    } elseif ($image['type'] === 'deposit') {
        $depositImage = $image;
    }
}

// الحصول على معلومات المستخدم الذي أنشأ الفاتورة
$createdBy = null;
if ($invoiceData['created_by']) {
    $user = new User();
    $createdBy = $user->getById($invoiceData['created_by']);
    
    // حذف بيانات حساسة
    if ($createdBy) {
        unset($createdBy['password']);
    }
}

// الحصول على معلومات الصفحة
$pageData = null;
if ($invoiceData['page_id']) {
    $page = new Page();
    $pageData = $page->getById($invoiceData['page_id']);
}

// إضافة معلومات أخرى
$auth = Auth::getInstance();
$canDelete = $auth->canDeleteInvoice($invoiceId);
$canChangeStatus = $permissionLevel >= 2;

// تحضير المسارات لصور الفاتورة
$processedImages = [
    'repeat' => [],
    'custom' => [],
    'deposit' => null
];

// معالجة صور التكرارات
foreach ($repeatImages as $image) {
    $processedImages['repeat'][] = [
        'id' => $image['id'],
        'url' => str_replace(ROOT_PATH, SITE_URL, $image['image_path'])
    ];
}

// معالجة صور القطع المخصصة
foreach ($customImages as $image) {
    $processedImages['custom'][] = [
        'id' => $image['id'],
        'url' => str_replace(ROOT_PATH, SITE_URL, $image['image_path'])
    ];
}

// معالجة صورة العربون
if ($depositImage) {
    $processedImages['deposit'] = [
        'id' => $depositImage['id'],
        'url' => str_replace(ROOT_PATH, SITE_URL, $depositImage['image_path'])
    ];
}

// تحضير البيانات للرد
$response = [
    'success' => true,
    'data' => [
        'invoice' => $invoiceData,
        'images' => $processedImages,
        'created_by' => $createdBy,
        'page' => $pageData,
        'permissions' => [
            'can_delete' => $canDelete,
            'can_change_status' => $canChangeStatus
        ]
    ]
];

// إضافة قائمة حالات الطلبات
if (isset($_GET['include_order_statuses']) && $_GET['include_order_statuses'] == 1) {
    $response['data']['order_statuses'] = $GLOBALS['ORDER_STATUSES'];
}

// إرجاع البيانات بتنسيق JSON
echo json_encode($response);
exit;
