<?php
/**
 * واجهة برمجة التطبيقات - المصادقة
 * تدير عمليات المصادقة وإنشاء الرموز JWT
 */

// منع الوصول المباشر
if (!defined('API_ACCESS')) {
    header('HTTP/1.0 403 Forbidden');
    echo json_encode(['error' => 'غير مصرح بالوصول المباشر']);
    exit;
}

// تحديد رأس CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json; charset=UTF-8');

// التعامل مع طلبات OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

// التأكد من أن الطلب بطريقة POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'الطريقة غير مسموح بها']);
    exit;
}

// الحصول على البيانات المرسلة
$data = json_decode(file_get_contents('php://input'), true);

// التحقق من توفر المعلومات المطلوبة
if (!isset($data['username']) || !isset($data['password'])) {
    http_response_code(400);
    echo json_encode(['error' => 'اسم المستخدم وكلمة المرور مطلوبان']);
    exit;
}

// تحميل الفئات اللازمة
require_once '../config.php';
require_once '../Database.php';
require_once '../Auth.php';

// تسجيل الدخول
$auth = Auth::getInstance();
$result = $auth->login($data['username'], $data['password'], $_SERVER['REMOTE_ADDR']);

if (is_array($result) && isset($result['error'])) {
    // فشل تسجيل الدخول
    http_response_code(401);
    echo json_encode(['error' => $result['error']]);
    exit;
} elseif ($result) {
    // نجاح تسجيل الدخول
    $user = $auth->getCurrentUser();
    
    // إنشاء رمز JWT
    $token = generateJWT($user);
    
    // إرجاع البيانات المطلوبة
    echo json_encode([
        'success' => true,
        'token' => $token,
        'user' => [
            'id' => $user['id'],
            'username' => $user['username'],
            'permission_level' => $user['permission_level']
        ]
    ]);
    exit;
} else {
    // فشل تسجيل الدخول - بيانات غير صحيحة
    http_response_code(401);
    echo json_encode(['error' => 'اسم المستخدم أو كلمة المرور غير صحيحة']);
    exit;
}

/**
 * دالة إنشاء رمز JWT
 * 
 * @param array $user بيانات المستخدم
 * @return string رمز JWT
 */
function generateJWT($user) {
    // المفتاح السري لتوقيع الرمز (يجب تغييره في الإنتاج)
    $secret_key = "YOUR_SECRET_KEY_CHANGE_THIS_IN_PRODUCTION";
    
    // إنشاء الرأس
    $header = [
        'alg' => 'HS256',
        'typ' => 'JWT'
    ];
    
    // إنشاء البيانات الأساسية
    $payload = [
        'sub' => $user['id'],
        'username' => $user['username'],
        'permission_level' => $user['permission_level'],
        'iat' => time(), // وقت الإصدار
        'exp' => time() + (60 * 60) // وقت الانتهاء (بعد ساعة)
    ];
    
    // تشفير الرأس والبيانات بترميز base64url
    $base64UrlHeader = base64UrlEncode(json_encode($header));
    $base64UrlPayload = base64UrlEncode(json_encode($payload));
    
    // إنشاء التوقيع
    $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, $secret_key, true);
    $base64UrlSignature = base64UrlEncode($signature);
    
    // إرجاع الرمز كاملاً
    return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
}

/**
 * دالة تشفير base64url
 * 
 * @param string $data البيانات المراد تشفيرها
 * @return string البيانات المشفرة
 */
function base64UrlEncode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}
