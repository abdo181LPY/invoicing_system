<?php
/**
 * نقطة دخول واجهة برمجة التطبيقات (API)
 * تستقبل طلبات API وتوجهها إلى المتحكمات المناسبة
 */

// تحميل ملف الإعدادات
require_once '../config.php';

// إعداد رأس HTTP للاستجابة
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// معالجة طلبات OPTIONS (CORS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// الحصول على مسار API
$request_uri = $_SERVER['REQUEST_URI'];
$base_path = '/api';
$api_path = str_replace($base_path, '', parse_url($request_uri, PHP_URL_PATH));

// طريقة الطلب
$method = $_SERVER['REQUEST_METHOD'];

// الاستجابة الافتراضية
$response = [
    'success' => false,
    'message' => 'طلب غير صالح',
    'data' => null
];

// دالة للاستجابة بخطأ
function respondWithError($code, $message) {
    http_response_code($code);
    echo json_encode([
        'success' => false,
        'message' => $message,
        'data' => null
    ]);
    exit;
}

// دالة للاستجابة بنجاح
function respondWithSuccess($data = null, $message = 'تمت العملية بنجاح') {
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

// دالة للتحقق من المصادقة
function authenticate() {
    // التحقق من وجود رمز المصادقة
    if (!isset($_SERVER['HTTP_AUTHORIZATION']) && !isset($_SERVER['HTTP_X_API_KEY'])) {
        respondWithError(401, 'المصادقة مطلوبة');
    }
    
    // استخراج رمز المصادقة
    $api_key = null;
    
    if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        if (preg_match('/Bearer\s(\S+)/', $_SERVER['HTTP_AUTHORIZATION'], $matches)) {
            $api_key = $matches[1];
        }
    } elseif (isset($_SERVER['HTTP_X_API_KEY'])) {
        $api_key = $_SERVER['HTTP_X_API_KEY'];
    }
    
    // التحقق من صحة رمز المصادقة
    if (!$api_key || !verifyApiKey($api_key)) {
        respondWithError(401, 'رمز المصادقة غير صالح');
    }
    
    // إرجاع معرف المستخدم
    return getUserIdFromApiKey($api_key);
}

// دالة للتحقق من صحة رمز المصادقة
function verifyApiKey($api_key) {
    $db = Database::getInstance();
    
    // البحث عن رمز المصادقة في قاعدة البيانات
    $sql = "SELECT user_id FROM api_keys WHERE api_key = :api_key AND active = 1 AND (expiry_date IS NULL OR expiry_date > NOW())";
    $result = $db->fetch($sql, ['api_key' => $api_key]);
    
    return !empty($result);
}

// دالة للحصول على معرف المستخدم من رمز المصادقة
function getUserIdFromApiKey($api_key) {
    $db = Database::getInstance();
    
    // البحث عن معرف المستخدم
    $sql = "SELECT user_id FROM api_keys WHERE api_key = :api_key AND active = 1";
    $result = $db->fetch($sql, ['api_key' => $api_key]);
    
    if ($result) {
        return $result['user_id'];
    }
    
    return null;
}

// استيراد ملف التوجيهات
require_once 'routes.php';

// معالجة الطلب
try {
    $found = false;
    
    // البحث عن المسار المناسب في مصفوفة التوجيهات
    foreach ($routes as $route) {
        $route_path_regex = str_replace('/', '\/', $route['path']);
        $route_path_regex = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '([^\/]+)', $route_path_regex);
        $route_path_regex = '/^' . $route_path_regex . '$/';
        
        if (preg_match($route_path_regex, $api_path, $matches) && $route['method'] === $method) {
            $found = true;
            
            // استخراج المعلمات من المسار
            array_shift($matches); // إزالة المطابقة الكاملة
            $params = [];
            
            if (preg_match_all('/\{([a-zA-Z0-9_]+)\}/', $route['path'], $param_names)) {
                $param_names = $param_names[1];
                foreach ($param_names as $index => $name) {
                    if (isset($matches[$index])) {
                        $params[$name] = $matches[$index];
                    }
                }
            }
            
            // التحقق من المصادقة إذا كانت مطلوبة
            $user_id = null;
            if ($route['auth']) {
                $user_id = authenticate();
            }
            
            // الحصول على بيانات الطلب
            $data = [];
            
            if ($method === 'GET') {
                $data = $_GET;
            } else {
                $input = file_get_contents('php://input');
                
                if (!empty($input)) {
                    $json_data = json_decode($input, true);
                    
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $data = $json_data;
                    } else {
                        parse_str($input, $data);
                    }
                }
                
                if ($method === 'POST' && empty($data)) {
                    $data = $_POST;
                }
            }
            
            // تنفيذ الإجراء
            $controller = $route['controller'];
            $action = $route['action'];
            
            // التحقق من وجود المتحكم
            if (!file_exists("../controllers/{$controller}.php")) {
                respondWithError(500, "المتحكم غير موجود: {$controller}");
            }
            
            // تحميل المتحكم
            require_once "../controllers/{$controller}.php";
            
            // إنشاء كائن المتحكم
            $controller_instance = new $controller();
            
            // التحقق من وجود الإجراء
            if (!method_exists($controller_instance, $action)) {
                respondWithError(500, "الإجراء غير موجود: {$action}");
            }
            
            // تنفيذ الإجراء
            $result = $controller_instance->$action($params, $data, $user_id);
            
            // الاستجابة بالنتيجة
            if (isset($result['success']) && $result['success'] === false) {
                // حالة خطأ
                $code = isset($result['code']) ? $result['code'] : 400;
                $message = isset($result['message']) ? $result['message'] : 'حدث خطأ أثناء معالجة الطلب';
                respondWithError($code, $message);
            } else {
                // حالة نجاح
                $message = isset($result['message']) ? $result['message'] : 'تمت العملية بنجاح';
                $data = isset($result['data']) ? $result['data'] : $result;
                respondWithSuccess($data, $message);
            }
            
            break;
        }
    }
    
    // إذا لم يتم العثور على مسار مطابق
    if (!$found) {
        respondWithError(404, 'المسار غير موجود');
    }
} catch (Exception $e) {
    // خطأ في معالجة الطلب
    respondWithError(500, 'خطأ في الخادم: ' . $e->getMessage());
}
