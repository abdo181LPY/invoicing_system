<?php
/**
 * واجهة برمجة التطبيقات - التحقق من المصادقة
 * يتحقق من صحة الرمز ومستوى الصلاحية
 */

// منع الوصول المباشر
if (!defined('API_ACCESS')) {
    header('HTTP/1.0 403 Forbidden');
    echo json_encode(['error' => 'غير مصرح بالوصول المباشر']);
    exit;
}

// تحميل مسارات API
require_once 'api/routes.php';

// الحصول على المسار والطريقة والإجراء الحالي
$path = isset($_GET['path']) ? $_GET['path'] : '';
$path = trim($path, '/');
$segments = explode('/', $path);

$endpoint = isset($segments[0]) && !empty($segments[0]) ? $segments[0] : 'default';
$action = isset($segments[1]) && !empty($segments[1]) ? $segments[1] : 'index';
$method = $_SERVER['REQUEST_METHOD'];

// الحصول على معلومات المسار
$routeInfo = getRouteInfo($endpoint, $method, $action);

// التحقق من وجود المسار
if (!$routeInfo) {
    http_response_code(404);
    echo json_encode(['error' => 'المسار غير موجود']);
    exit;
}

// التحقق من وجوب المصادقة
if (!isset($routeInfo['auth']) || $routeInfo['auth'] === false) {
    // المسار لا يتطلب مصادقة
    return;
}

// الحصول على الرمز من رأس Authorization
$headers = getallheaders();
$token = null;

if (isset($headers['Authorization'])) {
    $authHeader = $headers['Authorization'];
    
    // التحقق من بداية الرأس بكلمة Bearer
    if (strpos($authHeader, 'Bearer ') === 0) {
        $token = substr($authHeader, 7);
    }
}

// التحقق من وجود الرمز
if (!$token) {
    http_response_code(401);
    echo json_encode(['error' => 'الرمز غير موجود أو غير صالح']);
    exit;
}

// المفتاح السري للتحقق (يجب أن يكون نفس المفتاح المستخدم في التوقيع)
$secret_key = "YOUR_SECRET_KEY_CHANGE_THIS_IN_PRODUCTION";

// تقسيم الرمز إلى أجزائه الثلاثة
$tokenParts = explode('.', $token);
if (count($tokenParts) != 3) {
    http_response_code(401);
    echo json_encode(['error' => 'صيغة الرمز غير صالحة']);
    exit;
}

// فك تشفير الرأس والبيانات
$header = json_decode(base64UrlDecode($tokenParts[0]), true);
$payload = json_decode(base64UrlDecode($tokenParts[1]), true);
$signature = base64UrlDecode($tokenParts[2]);

// التحقق من صحة الرأس
if (!$header || !isset($header['alg']) || $header['alg'] !== 'HS256' || !isset($header['typ']) || $header['typ'] !== 'JWT') {
    http_response_code(401);
    echo json_encode(['error' => 'رأس الرمز غير صالح']);
    exit;
}

// التحقق من صحة البيانات
if (!$payload || !isset($payload['sub']) || !isset($payload['exp'])) {
    http_response_code(401);
    echo json_encode(['error' => 'بيانات الرمز غير صالحة']);
    exit;
}

// التحقق من تاريخ انتهاء الصلاحية
if ($payload['exp'] < time()) {
    http_response_code(401);
    echo json_encode(['error' => 'انتهت صلاحية الرمز']);
    exit;
}

// التحقق من التوقيع
$base64UrlHeader = $tokenParts[0];
$base64UrlPayload = $tokenParts[1];
$expectedSignature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, $secret_key, true);

if (!hash_equals($signature, $expectedSignature)) {
    http_response_code(401);
    echo json_encode(['error' => 'توقيع الرمز غير صالح']);
    exit;
}

// تحميل الفئات اللازمة
require_once 'Database.php';

// التحقق من وجود المستخدم في قاعدة البيانات
$db = Database::getInstance();
$userExists = $db->exists('users', 'id = :id AND is_active = 1', ['id' => $payload['sub']]);

if (!$userExists) {
    http_response_code(401);
    echo json_encode(['error' => 'المستخدم غير موجود أو غير نشط']);
    exit;
}

// التحقق من مستوى الصلاحية
if (isset($routeInfo['permissions']) && is_array($routeInfo['permissions'])) {
    if (!in_array($payload['permission_level'], $routeInfo['permissions'])) {
        http_response_code(403);
        echo json_encode(['error' => 'ليس لديك صلاحية للوصول إلى هذا المسار']);
        exit;
    }
}

// حفظ بيانات المستخدم للاستخدام في API
$authUser = [
    'id' => $payload['sub'],
    'username' => $payload['username'],
    'permission_level' => $payload['permission_level']
];

/**
 * دالة فك تشفير base64url
 * 
 * @param string $data البيانات المشفرة
 * @return string البيانات الأصلية
 */
function base64UrlDecode($data) {
    return base64_decode(strtr($data, '-_', '+/') . str_repeat('=', 3 - (3 + strlen($data)) % 4));
}
