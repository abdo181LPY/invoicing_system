<?php
/**
 * واجهة برمجة التطبيقات - حساب التكلفة
 * يقوم بحساب تكلفة الطلب وإنشاء رسالة منسقة للزبون
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

// التأكد من أن الطلب بطريقة POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'الطريقة غير مسموح بها']);
    exit;
}

// الحصول على البيانات المرسلة
$data = json_decode(file_get_contents('php://input'), true);

// التحقق من توفر المعلومات المطلوبة
if (!isset($data['page_id']) || 
    !isset($data['basket_price']) || 
    !isset($data['item_count']) || 
    !isset($data['province'])) {
    http_response_code(400);
    echo json_encode(['error' => 'جميع المعلومات (الصفحة، سعر السلة، عدد القطع، المحافظة) مطلوبة']);
    exit;
}

// التحقق من صحة البيانات
$pageId = (int)$data['page_id'];
$basketPrice = (float)$data['basket_price'];
$itemCount = (int)$data['item_count'];
$province = $data['province'];
$instagram = isset($data['instagram']) ? $data['instagram'] : '';

// التحقق من صحة البيانات
if ($basketPrice <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'سعر السلة يجب أن يكون أكبر من صفر']);
    exit;
}

if ($itemCount <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'عدد القطع يجب أن يكون أكبر من صفر']);
    exit;
}

if (!in_array($province, $GLOBALS['PROVINCES'])) {
    http_response_code(400);
    echo json_encode(['error' => 'المحافظة غير صالحة']);
    exit;
}

// التحقق من وجود الصفحة
$page = new Page();
$pageData = $page->getById($pageId);

if (!$pageData) {
    http_response_code(404);
    echo json_encode(['error' => 'الصفحة غير موجودة']);
    exit;
}

// حساب التكلفة
$invoice = new Invoice();
$calculationData = $invoice->calculateCost($basketPrice, $itemCount, $province);

// إنشاء رسالة منسقة للزبون
$message = $invoice->createCustomerMessage($calculationData, $pageData);

// تحضير البيانات للرد
$response = [
    'success' => true,
    'data' => [
        'calculation' => $calculationData,
        'page' => $pageData,
        'message' => $message,
        'form_data' => [
            'page_id' => $pageId,
            'basket_price' => $basketPrice,
            'item_count' => $itemCount,
            'province' => $province,
            'instagram' => $instagram
        ]
    ]
];

// إرجاع البيانات بتنسيق JSON
echo json_encode($response);
exit;
